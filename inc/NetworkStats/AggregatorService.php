<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class AggregatorService {

	public function __construct( private Visits $collector ) {
	}

	public function aggregate( int $blogId, string $mode ): void {
		if ( $mode === 'visits' ) {
			$this->collector->aggregateVisits( $blogId );
			return;
		}
		if ( $mode === 'referrers' ) {
			$this->collector->aggregateReferrers( $blogId );
			return;
		}
		// both
		$this->collector->aggregateVisits( $blogId );
		$this->collector->aggregateReferrers( $blogId );
	}
}
