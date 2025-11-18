<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

use Throwable;

final class Runner {

	private const MODES = [ 'visits', 'referrers', 'both' ];

	public function __construct(
		private \wpdb $db,
		private AggregatorService $svc,
		private BlogRepository $blogs,
		private FailureRepository $failures,
		private BackoffStrategy $backoff,
		private CliLogger $log = new CliLogger()
	) {
	}

	/**
	 * @param array<string, mixed> $opts
	 * @return array{processed:int, success:int, fail:int, duration:float, exitCode:int}
	 */
	public function run( array $opts ): array {
		$mode = $opts['mode'] ?? 'both';
		if ( ! in_array( $mode, self::MODES, true ) ) {
			$this->log->error( "Invalid mode '$mode'. Allowed: " . implode( ',', self::MODES ) );
			return [
				'processed' => 0,
				'success' => 0,
				'fail' => 0,
				'duration' => 0.0,
				'exitCode' => 1,
			];
		}
		$batchSize  = (int) ( $opts['batch-size'] ?? 200 );
		$startId    = (int) ( $opts['start-id'] ?? 0 );
		$maxSites   = (int) ( $opts['max-sites'] ?? 0 );
		$onlyFailed = isset( $opts['since-failures'] );
		$retries    = (int) ( $opts['retries'] ?? 3 );

		$start = microtime( true );
		$processed = 0;
		$success = 0;
		$fail = 0;

		try {
			if ( $onlyFailed ) {
				$this->log->info( 'Processing previous failures only.' );
				$rows = $this->failures->dueFailures( $batchSize );
				foreach ( $rows as $row ) {
					$blogId = (int) $row['blog_id'];
					$rowMode = (string) $row['mode'];
					$attempts = (int) $row['attempts'] + 1;
					$this->processOne( $blogId, $rowMode, $retries, $attempts, $success, $fail );
					$processed++;
					if ( $maxSites && $processed >= $maxSites ) {
						break;
					}
				}
			} else {
				$last = $startId;
				$remaining = $maxSites ?: PHP_INT_MAX;
				while ( $remaining > 0 ) {
					$toFetch = (int) min( $batchSize, $remaining );
					$batch = $this->blogs->nextBatch( $last, $toFetch );
					if ( ! $batch ) {
						break;
					}
					foreach ( $batch as $blogId ) {
						$last = (int) $blogId;
						$modes = ( $mode === 'both' ) ? [ 'visits', 'referrers' ] : [ $mode ];
						foreach ( $modes as $m ) {
							$this->processOne( (int) $blogId, $m, $retries, 0, $success, $fail );
						}
						$processed++;
						$remaining--;

						if ( $maxSites && $processed >= $maxSites ) {
							break 2;
						}
					}
				}
			}
		} finally {
			$duration = round( microtime( true ) - $start, 2 );
			$this->log->info( "Processed {$processed} site(s) in {$duration}s. Successes: {$success}. Failures: {$fail}" );
		}

		$duration = round( microtime( true ) - $start, 2 );
		return [
			'processed' => $processed,
			'success' => $success,
			'fail' => $fail,
			'duration' => $duration,
			'exitCode' => $fail === 0 ? 0 : 2,
		];
	}

	private function processOne( int $blogId, string $mode, int $maxRetries, int $startingAttempt, int &$success, int &$fail ): void {
		$attempt = $startingAttempt;
		while ( $attempt < $maxRetries ) {
			$attempt++;
			$t0 = microtime( true );
			try {
				$this->svc->aggregate( $blogId, $mode );
				$this->failures->clear( $blogId, $mode );
				$dt = round( microtime( true ) - $t0, 2 );
				$this->log->info( "✔ blog={$blogId} mode={$mode} in {$dt}s" );
				$success++;
				return;
			} catch ( Throwable $e ) {
				$this->log->error( "✖ blog={$blogId} mode={$mode} attempt={$attempt}: " . $e->getMessage() );
				if ( $attempt >= $maxRetries ) {
					$this->failures->record( $blogId, $mode, $e->getMessage(), $attempt );
					$fail++;
					return;
				}
				sleep( $this->backoff->delaySeconds( $attempt ) );
			}
		}
	}
}
