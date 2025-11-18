<?php

namespace Pressbooks_CLI\NetworkStats;

/**
 * DataCollector for visits
 */
class Visits {
	/**
	 * Database collation
	 */
	public const CHARSET = 'latin1';

	/**
	 * This table is the aggregated number of visits for all the sites.
	 * @const string
	 */
	public const VISITS_TABLE = 'network_aggregated_stats';

	/**
	 * This table is the aggregated number of referrers for all the sites.
	 * @const string
	 */
	public const REFERER_TABLE = 'network_aggregated_referrers';

	public static $IS_TESTING = false;

	public $today = '';

	public function __construct() {
		$this->today = gmdate( 'Y-m-d', time() );
	}

	public static function install() {
		global $wpdb;
		$table = $wpdb->base_prefix . self::VISITS_TABLE;

		if ( ! is_multisite() ) {
			return;
		}

		self::createTables();
	}

	/**
	 * This function creates the tables for the aggregated stats.
	 */
	public static function createTables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		$engine = 'ENGINE=InnoDB DEFAULT CHARSET=' . self::CHARSET;
		$visitors_table = $wpdb->base_prefix . self::VISITS_TABLE;
		$referrers_table = $wpdb->base_prefix . self::REFERER_TABLE;

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$visitors_table} (
				id int(10) unsigned NOT NULL AUTO_INCREMENT,
				blog_id int(10) unsigned NOT NULL,
				visitors int(10) unsigned NOT NULL,
				pageviews int(10) unsigned NOT NULL,
				created_at date NOT NULL,
				PRIMARY KEY  (id),
				CONSTRAINT aggregated_stats_unique_index UNIQUE (blog_id, created_at)
			) {$engine};"
		);

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$referrers_table} (
				id int(10) unsigned NOT NULL AUTO_INCREMENT,
				blog_id int(10) unsigned NOT NULL,
				visitors int(10) unsigned NOT NULL,
				pageviews int(10) unsigned NOT NULL,
				url varchar(150) NOT NULL,
				created_at date NOT NULL,
				PRIMARY KEY  (id),
				CONSTRAINT aggregated_referrers_unique_index UNIQUE (blog_id, url, created_at)
			) {$engine};"
		);

		( new self )->populatePreviousData(); // populate previous data only on network activation or if the tables are empty

	}

	/**
	 * This function updates the table with the historical stats.
	 */
	public function populatePreviousData(): void {
		foreach ( $this->getAllBooks() as $blog_id ) {
			$this->aggregateVisits( $blog_id, true );
			$this->aggregateReferrers( $blog_id, true );
		}
	}

	/**
	 * Returns a list of all the blogs in the network.
	 * @return array
	 */
	public function getAllBooks() : array {
		global $wpdb;
		$main_site_id = get_network()->site_id;
		return $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND blog_id != %d ", $main_site_id ) );
	}

	/**
	 * This function needs to be called with the WP Cron job for each site (maybe the cron loop).
	 */
	public function aggregateVisits( int $blog_id, bool $since_beginning = false ): void {
		global $wpdb;
		$source_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_site_stats';
		$aggregated_table = $wpdb->base_prefix . self::VISITS_TABLE;
		$where = '';

		if ( self::$IS_TESTING || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $source_table ) ) === $source_table ) { // check if koko table exists
			if ( $since_beginning ) {
				$this->purgeOldData( $blog_id, $aggregated_table );
			} else {
				$this->purgeOldData( $blog_id, $aggregated_table, $this->today );
				$where = 'WHERE `date` = "' . $this->today . '"';
			}

			$sql = "INSERT IGNORE INTO {$aggregated_table} (blog_id, visitors, pageviews, created_at)
				SELECT {$blog_id}, visitors, pageviews, `date` FROM {$source_table} $where";

			$wpdb->query( $sql );
		}
	}

	/**
	 * This function needs to be called with the WP Cron job for each site (maybe the cron loop).
	 */
	public function aggregateReferrers( int $blog_id, bool $since_beginning = false ): void {
		global $wpdb;
		$source_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_referrer_stats';
		$source_url_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_referrer_urls';
		$aggregated_table = $wpdb->base_prefix . self::REFERER_TABLE;
		$where = '';

		if ( self::$IS_TESTING || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $source_table ) ) === $source_table ) { // check if koko table exists
			if ( $since_beginning ) {
				$this->purgeOldData( $blog_id, $aggregated_table );
			} else {
				$this->purgeOldData( $blog_id, $aggregated_table, $this->today );
				$where = 'WHERE `date` = "' . $this->today . '"';
			}

			$sql = "INSERT IGNORE INTO {$aggregated_table} (blog_id, visitors, pageviews, url, created_at)
				SELECT {$blog_id}, visitors, pageviews, u.url, s.date FROM {$source_table} s JOIN $source_url_table u ON s.id = u.id $where";

			$wpdb->query( $sql );
		}

	}

	/**
	 * This function will purge today's book data from the aggregated tables, useful if the cron runs more than 1 time per day.
	 */
	private function purgeOldData( int $blog_id, string $table, string $created_at = null ): void {
		// delete data if it exists for the given date
		global $wpdb;

		$where = [ 'blog_id' => $blog_id ];
		$where_format = [ '%d' ];

		if ( $created_at ) {
			$where['created_at'] = $created_at;
			$where_format[] = '%s';
		}

		$wpdb->delete( $table, $where, $where_format );
	}

}
