# NetworkStats OOP Aggregator

This folder contains an OOP refactor of the network-wide Koko Analytics aggregation script.

## Entry point

- `aggregate-network.php` – CLI script that wires up classes, acquires a distributed lock, and optionally notifies Slack.

## Usage

Run from your WP install root or anywhere with PHP and access to this path:

```sh
php <PLUGIN_PATH>/bin/sync/NetworkStats/aggregate-network.php \
  --mode=both \
  --batch-size=200 \
  --retries=3 \
  --lock-timeout=5
```

Options:
- `--mode`            visits | referrers | both (default: both)
- `--batch-size`      number of blogs per loop (default: 200)
- `--start-id`        blog_id to start from (default: 0)
- `--max-sites`       stop after this many sites (default: unlimited)
- `--since-failures`  process previously failed sites only
- `--lock-timeout`    seconds to wait for GET_LOCK (default: 5)
- `--retries`         per-site max retries (default: 3)

## Notes
- Uses MySQL GET_LOCK to avoid concurrent runs (lock key is per-network).
- Tracks failures in `{base_prefix}network_aggregator_failures` with capped exponential backoff.
- If `/srv/www/scripts/send_slack_notification.sh` exists, a summary notification is sent.
