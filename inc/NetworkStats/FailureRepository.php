<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class FailureRepository {

	private string $table;

	public function __construct(
		private \wpdb $db,
		private BackoffStrategy $backoff,
		private CliLogger $log = new CliLogger()
	) {
		$this->table = $this->db->base_prefix . 'network_aggregator_failures';
		$this->ensureTable();
	}

	private function ensureTable(): void {
		$charset = 'DEFAULT CHARSET=utf8mb4';
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            blog_id BIGINT UNSIGNED NOT NULL,
            last_error TEXT NOT NULL,
            attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            last_attempt_at DATETIME NOT NULL,
            next_attempt_at DATETIME DEFAULT NULL,
            mode VARCHAR(20) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY blog_mode (blog_id, mode),
            INDEX idx_next_attempt (next_attempt_at)
        ) {$charset};";
		$this->db->query( $sql );
	}

	public function record( int $blogId, string $mode, string $error, int $attempts ): void {
		$now = gmdate( 'Y-m-d H:i:s' );
		$next = $this->backoff->nextAttemptAtUTC( $attempts );
		$id = $this->db->get_var(
			$this->db->prepare( "SELECT id FROM {$this->table} WHERE blog_id = %d AND mode = %s", $blogId, $mode )
		);
		if ( $id ) {
			$this->db->update(
				$this->table,
				[
					'last_error' => $error,
					'attempts' => $attempts,
					'last_attempt_at' => $now,
					'next_attempt_at' => $next,
				],
				[ 'id' => $id ],
				[ '%s', '%d', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			$this->db->insert(
				$this->table,
				[
					'blog_id' => $blogId,
					'last_error' => $error,
					'attempts' => $attempts,
					'last_attempt_at' => $now,
					'next_attempt_at' => $next,
					'mode' => $mode,
				],
				[ '%d', '%s', '%d', '%s', '%s', '%s' ]
			);
		}
	}

	public function clear( int $blogId, string $mode ): void {
		$this->db->delete($this->table, [
			'blog_id' => $blogId,
			'mode' => $mode,
		], [ '%d', '%s' ]);
	}

	/**
	 * @return array<int, array{blog_id:int, mode:string, attempts:int}>
	 */
	public function dueFailures( int $limit ): array {
		$now = gmdate( 'Y-m-d H:i:s' );
		$sql = $this->db->prepare(
			"SELECT blog_id, mode, attempts FROM {$this->table}
             WHERE next_attempt_at IS NULL OR next_attempt_at <= %s
             ORDER BY next_attempt_at ASC
             LIMIT %d",
			$now,
			$limit
		);
		$rows = $this->db->get_results( $sql, ARRAY_A ) ?: [];
		return array_map(static function( array $r ): array {
			return [
				'blog_id' => (int) $r['blog_id'],
				'mode' => (string) $r['mode'],
				'attempts' => (int) $r['attempts'],
			];
		}, $rows);
	}
}
