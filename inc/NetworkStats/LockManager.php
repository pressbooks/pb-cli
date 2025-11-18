<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class LockManager {

	public function __construct(
		private \wpdb $db,
		private string $lockName,
		private int $timeoutSec = 5,
		private CliLogger $log = new CliLogger()
	) {
	}

	public function acquire(): bool {
		$got = (bool) $this->db->get_var(
			$this->db->prepare( 'SELECT GET_LOCK(%s, %d)', $this->lockName, $this->timeoutSec )
		);
		if ( $got ) {
			$this->log->info( "Lock acquired '{$this->lockName}'." );
		} else {
			$this->log->info( "Could not acquire lock '{$this->lockName}' after {$this->timeoutSec}s." );
		}
		return $got;
	}

	public function release(): void {
		try {
			$this->db->query( $this->db->prepare( 'SELECT RELEASE_LOCK(%s)', $this->lockName ) );
			$this->log->info( "Lock released '{$this->lockName}'." );
		} catch ( \Throwable $e ) {
			$this->log->error( 'Failed to release lock: ' . $e->getMessage() );
		}
	}
}
