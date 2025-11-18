<?php

declare(strict_types=1);

namespace Pressbooks_CLI\NetworkStats;

final class BlogRepository {

	public function __construct( private \wpdb $db ) {
	}

	/**
	 * Fetch the next batch of blog IDs using keyset pagination by blog_id.
	 *
	 * @return int[]
	 */
	public function nextBatch( int $startAfterId, int $limit ): array {
		$mainSiteId = (int) get_network()->site_id;
		$sql = $this->db->prepare(
			"SELECT blog_id FROM {$this->db->blogs}
             WHERE archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d AND blog_id > %d
             ORDER BY blog_id ASC
             LIMIT %d",
			$mainSiteId,
			$startAfterId,
			$limit
		);
		$ids = $this->db->get_col( $sql ) ?: [];
		return array_map( 'intval', $ids );
	}
}
