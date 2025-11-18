<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class CliLogger {

	public function info( string $msg ): void {
		$this->write( 'INFO', $msg );
	}

	public function error( string $msg ): void {
		$this->write( 'ERROR', $msg );
	}

	private function write( string $level, string $msg ): void {
		echo '[' . date( 'Y-m-d H:i:s' ) . "] {$level}: {$msg}\n";
	}
}
