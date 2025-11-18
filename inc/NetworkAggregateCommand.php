<?php

namespace Pressbooks_CLI;

use Pressbooks_CLI\NetworkStats\AggregatorService;
use Pressbooks_CLI\NetworkStats\BackoffStrategy;
use Pressbooks_CLI\NetworkStats\BlogRepository;
use Pressbooks_CLI\NetworkStats\CliLogger;
use Pressbooks_CLI\NetworkStats\FailureRepository;
use Pressbooks_CLI\NetworkStats\LockManager;
use Pressbooks_CLI\NetworkStats\Runner;
use Pressbooks_CLI\NetworkStats\Visits;
use WP_CLI;
use WP_CLI\Utils;

class NetworkAggregateCommand extends PB_CLI_Command
{
    /**
     * Aggregate Koko Analytics for the multisite network.
     *
     * ## OPTIONS
     *
     * [--mode=<mode>]
     * : Mode to run: visits | referrers | both. Default: both.
     *
     * [--batch-size=<n>]
     * : Number of blogs per batch. Default: 200.
     *
     * [--start-id=<blog_id>]
     * : Start from this blog_id (useful for staggering). Default: 0.
     *
     * [--max-sites=<n>]
     * : Stop after processing this many sites. Default: unlimited.
     *
     * [--since-failures]
     * : Process only previously failed sites that are due for retry.
     *
     * [--lock-timeout=<sec>]
     * : Seconds to wait for MySQL GET_LOCK. Default: 5.
     *
     * [--retries=<n>]
     * : Max retries per site per mode. Default: 3.
     *
     * ## EXAMPLES
     *
     *     wp pb network-aggregate --mode=visits --batch-size=200
     *     wp pb network-aggregate --mode=both --since-failures --retries=4
     * 
     * @when after_wp_load
     * 
     * @param array $args
     * @param array $assoc_args
     */
    public function run(array $args, array $assoc_args): void
    {
        $logger = new CliLogger();
        WP_CLI::log('Starting Pressbooks Network Analytics aggregation...');
        $mode        = Utils\get_flag_value($assoc_args, 'mode', 'both');
        $batchSize   = (int) Utils\get_flag_value($assoc_args, 'batch-size', 200);
        $startId     = (int) Utils\get_flag_value($assoc_args, 'start-id', 0);
        $maxSites    = (int) Utils\get_flag_value($assoc_args, 'max-sites', 0);
        $sinceFailed = (bool) Utils\get_flag_value($assoc_args, 'since-failures', false);
        $lockTimeout = (int) Utils\get_flag_value($assoc_args, 'lock-timeout', 5);
        $retries     = (int) Utils\get_flag_value($assoc_args, 'retries', 3);

        $opts = [
            'mode' => $mode,
            'batch-size' => $batchSize,
            'start-id' => $startId,
            'max-sites' => $maxSites,
            'since-failures' => $sinceFailed,
            'lock-timeout' => $lockTimeout,
            'retries' => $retries,
        ];

        // Acquire distributed lock
        $lockName = 'pna_aggregate_lock_' . md5(get_site_url());
        $lock = new LockManager($GLOBALS['wpdb'], $lockName, $lockTimeout, $logger);
        if (!$lock->acquire()) {
            WP_CLI::log("Another aggregation is running (lock: {$lockName}). Exiting.");
            return; // non-error
        }

        try {
            $runner = new Runner(
                $GLOBALS['wpdb'],
                new AggregatorService(new Visits()),
                new BlogRepository($GLOBALS['wpdb']),
                new FailureRepository($GLOBALS['wpdb'], new BackoffStrategy(), $logger),
                new BackoffStrategy(),
                $logger
            );

            $results = $runner->run($opts);

            // Optional Slack summary script
            $slack_script = '/srv/www/scripts/utility/send_slack_notification.sh';
            if (file_exists($slack_script)) {
                if ((int) $results['fail'] === 0) {
                    shell_exec("bash {$slack_script} 'Success' 'Aggregate network analytics' 'Processed {$results['processed']} sites in {$results['duration']}s' '" . date(DATE_RFC3339) . "' '#bots'"); // phpcs:ignore
                } else {
                    $errCount = (int) $results['fail'];
                    shell_exec("bash {$slack_script} 'Fail' 'Aggregate network analytics' '{$errCount} failures' '" . date(DATE_RFC3339) . "' '#bots'"); // phpcs:ignore
                }
            }

            if ((int) $results['fail'] === 0) {
                WP_CLI::success("Processed {$results['processed']} site(s) in {$results['duration']}s");
            } else {
                WP_CLI::warning("Processed {$results['processed']} site(s) in {$results['duration']}s. Failures: {$results['fail']}");
                // Use non-zero exit for failures to integrate with schedulers/monitors.
                WP_CLI::halt((int) $results['exitCode']);
            }
        } finally {
            $lock->release();
        }
    }
}
