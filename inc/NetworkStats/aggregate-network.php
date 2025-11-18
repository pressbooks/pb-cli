<?php

// declare(strict_types=1);

// namespace Pressbooks_CLI\NetworkStats;

// use PressbooksNetworkAnalytics\Collector\Visits;

// // Bootstrap WordPress (must run from WP install root or plugin path)
// require_once dirname( __DIR__, 6 ) . '/wp/wp-load.php';

// if ( ! class_exists( 'PressbooksNetworkAnalytics\Bin\Sync\NetworkStats\CliLogger' ) ) {
// 	\HM\Autoloader\register_class_path( 'PressbooksNetworkAnalytics\Bin\Sync\NetworkStats', __DIR__ );
// }

// // Parse CLI args
// $opts = getopt('', [
// 	'mode::',         // visits | referrers | both
// 	'batch-size::',   // number of blogs per loop
// 	'start-id::',     // blog_id to start from (for manual staggering)
// 	'max-sites::',    // stop after this many sites
// 	'since-failures', // handle only previously failed sites
// 	'lock-timeout::', // seconds to wait for GET_LOCK
// 	'retries::',      // per-site max retries
// ]);

// $logger = new CliLogger();
// $lockName = 'pna_aggregate_lock_' . md5( get_site_url() );
// $lockTimeout = (int) ( $opts['lock-timeout'] ?? 5 );
// $lock = new LockManager( $GLOBALS['wpdb'], $lockName, $lockTimeout, $logger );

// if ( ! $lock->acquire() ) {
// 	// another instance is running; not an error
// 	exit( 0 );
// }

// $start = microtime( true );
// $results = [
// 	'processed' => 0,
// 	'success' => 0,
// 	'fail' => 0,
// 	'duration' => 0.0,
// 	'exitCode' => 1,
// ];

// try {
// 	$runner = new Runner(
// 		$GLOBALS['wpdb'],
// 		new AggregatorService( new Visits() ),
// 		new BlogRepository( $GLOBALS['wpdb'] ),
// 		new FailureRepository( $GLOBALS['wpdb'], new BackoffStrategy(), $logger ),
// 		new BackoffStrategy(),
// 		$logger
// 	);
// 	$results = $runner->run( $opts );
// } finally {
// 	$lock->release();
// }

// // Optional Slack summary script (kept for parity with legacy script)
// $slack_script = '/srv/www/scripts/utility/send_slack_notification.sh';
// if ( file_exists( $slack_script ) ) {
// 	if ( (int) $results['fail'] === 0 ) {
// 		shell_exec( "bash {$slack_script} 'Success' 'Aggregate network analytics' 'Processed {$results['processed']} sites in {$results['duration']}s' '" . date( DATE_RFC3339 ) . "' '#bots'" ); // phpcs:ignore
// 	} else {
// 		$errCount = (int) $results['fail'];
// 		shell_exec( "bash {$slack_script} 'Fail' 'Aggregate network analytics' '{$errCount} failures' '" . date( DATE_RFC3339 ) . "' '#bots'" ); // phpcs:ignore
// 	}
// }

// exit( (int) $results['exitCode'] );
