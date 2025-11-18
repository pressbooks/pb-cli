<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class BackoffStrategy {

	public function __construct( private int $maxExponent = 5 ) {
	}

	public function delaySeconds( int $attempt ): int {
		$attempt = max( 1, $attempt );
		return (int) min( 5, pow( 2, $attempt ) );
	}

	public function nextAttemptAtUTC( int $attempt ): string {
		$delay = (int) ( 60 * pow( 2, min( $this->maxExponent, max( 1, $attempt ) ) ) );
		return gmdate( 'Y-m-d H:i:s', time() + $delay );
	}
}
