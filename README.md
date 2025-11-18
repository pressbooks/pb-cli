pressbooks/pb-cli
=================

A suite of wp-cli commands for Pressbooks.

[![Build Status](https://travis-ci.org/pressbooks/pb-cli.svg?branch=production)](https://travis-ci.org/pressbooks/pb-cli)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](docs/contributing.md#contributing) | [Support](docs/contributing.md#support)

## Using

This package implements the following commands:

### wp scaffold book-theme

Generate the files needed for a Pressbooks book theme.

~~~
wp scaffold book-theme <slug> <vendor> [--theme_name=<title>] [--description=<description>] [--uri=<uri>] [--author=<author>] [--author_uri=<author_uri>] [--github_account=<github_account>] [--github_repo=<github_repo>] [--license=<license>] [--textdomain=<textdomain>] [--version=<version>] [--dir=<dir>] [--activate] [--enable-network] [--force]
~~~

Default behavior is to create the following files:
- functions.php
- .gitignore, .editorconfig, package.json, composer.json, composer.lock, yarn.lock
- README.md

Unless specified with `--dir=<dir>`, the theme is placed in the themes
directory.

**OPTIONS**

	<slug>
		Slug for the new theme.

	<vendor>
		Vendor for the new theme. Used in composer.json and package.json.

	[--theme_name=<title>]
		What to put in the 'Theme Name:' header in 'style.css'. Defaults to <slug>.

	[--description=<description>]
		Human-readable description for the theme.

	[--uri=<uri>]
		What to put in the 'Theme URI:' header in 'style.css'.

	[--author=<author>]
		What to put in the 'Author:' header in 'style.css'.

	[--author_uri=<author_uri>]
		What to put in the 'Author URI:' header in 'style.css'.

	[--github_account=<github_account>]
		The GitHub account that owns this project (e.g. 'pressbooks'). Defaults to vendor.

	[--github_repo=<github_repo>]
		The GitHub repo name for this project (e.g. 'pressbooks-book'). Defaults to slug.

	[--license=<license>]
		What to put in the 'License:' header in 'style.css'.
		---
		default: GPL 2.0+
		---

	[--textdomain=<textdomain>]
		Text domain for the theme. Defaults to <slug>.

	[--version=<version>]
		Version for the theme.
		---
		default: 1.0
		---

	[--dir=<dir>]
		Specify a destination directory for the command. Defaults to the themes directory.

	[--activate]
		Activate the newly created book theme.

	[--enable-network]
		Enable the newly created book theme for the entire network.

	[--force]
		Overwrite files that already exist.



### wp pb issue-template

Generate an issue template for a Pressbooks theme or plugin, placing it in .github/ISSUE_TEMPLATE.md.

~~~
wp pb issue-template <slug> --type=<type> --owner=<owner> [--dir=<dir>] [--force]
~~~

**OPTIONS**

	<slug>
		Slug for the theme or plugin (e.g. pressbooks, pressbooks-book).

	--type=<type>
		The type of repo for which we're generating an issue template. Must be `theme` or `plugin`.

	--owner=<owner>
		The GitHub username of this repo's owner (e.g. pressbooks).

	[--dir=<dir>]
		Specify a destination directory for the command. Defaults to the theme or plugin's directory.

	[--force]
		Overwrite files that already exist.



### wp pb theme lock

Lock a book's theme.

~~~
wp pb theme lock --url=<url>
~~~

**OPTIONS**

	--url=<url>
		The URL of the target book.



### wp pb theme unlock

Unlock a book's theme.

~~~
wp pb theme unlock --url=<url>
~~~

**OPTIONS**

	--url=<url>
		The URL of the target book.



### wp pb clone

Clone a book.

~~~
wp pb clone <source> <destination> --user=<user>
~~~

**OPTIONS**

	<source>
		URL

	<destination>
		Book slug on the current network

	--user=<user>
		sets request to a specific WordPress user

### wp pb populate-books-admins
It populates `pb_book_admins` blog metada for each blog with comma-separated book administrator's user IDs.

### wp pb network-aggregate

Aggregate Koko Analytics (visits & referrers) across every site in a Pressbooks multisite using batched processing, retries and failure backoff.

~~~
wp pb network-aggregate [--mode=<visits|referrers|both>] [--batch-size=<n>] [--start-id=<blog_id>] [--max-sites=<n>] [--since-failures] [--lock-timeout=<sec>] [--retries=<n>]
~~~

**OPTIONS**

	--mode=<visits|referrers|both>
		Which data to aggregate. Default: both.

	--batch-size=<n>
		Number of blogs per batch (controls memory). Default: 200.

	--start-id=<blog_id>
		Begin at this numeric blog ID (skip smaller IDs). Default: 0.

	--max-sites=<n>
		Stop after processing this many sites. Default: unlimited.

	--since-failures
		Process only previously failed sites whose backoff has expired.

	--lock-timeout=<sec>
		Seconds to wait for MySQL GET_LOCK before aborting gracefully. Default: 5.

	--retries=<n>
		Max attempts per blog+mode before recording a failure. Default: 3.

**BEHAVIOR**
* Uses a MySQL advisory lock (`GET_LOCK`) to prevent concurrent runs.
* Sequential batches scale to large networks without scanning all blogs each retry cycle.
* Failures stored with exponential backoff; `--since-failures` reprocesses only due rows.
* Exit codes: 0 (no failures), 2 (failures recorded), 1 (invalid mode/parameters).

**EXAMPLES**

Process entire network (both modes):
~~~
wp pb network-aggregate --mode=both --batch-size=200
~~~

Visits only with higher retry budget:
~~~
wp pb network-aggregate --mode=visits --retries=5
~~~

Retry only previously failed blogs:
~~~
wp pb network-aggregate --since-failures --batch-size=300
~~~

Stagger large networks (start at ID 1000, cap at 500 sites):
~~~
wp pb network-aggregate --start-id=1000 --max-sites=500
~~~

**TROUBLESHOOTING**
| Symptom | Cause | Fix |
|---------|-------|-----|
| `Cannot declare interface Mustache_Loader` | Duplicate Mustache autoload (WP-CLI + plugin) | Run with `--skip-plugins=pressbooks,pressbooks-network-analytics` or predefine `WP_CLI_SKIP_PLUGINS` |
| "Another aggregation is running" | Advisory lock held by another process | Retry later or raise `--lock-timeout` |
| Repeated failures for certain blogs | Persistent data/plugin errors | Use `--since-failures`; inspect failure table entries |
| High memory usage | Batch size too large | Lower `--batch-size` (e.g. 100) |

Skip plugins if needed:
~~~
wp --skip-plugins=pressbooks,pressbooks-network-analytics pb network-aggregate
~~~

Failure table columns: blog_id, mode, last_error, attempts, last_attempt_at, next_attempt_at (cleared on success).

Internal logic (Runner, LockManager, BackoffStrategy, etc.) lives in `inc/NetworkStats/`.

## Installing

Installing this package requires WP-CLI v2.5.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:pressbooks/pb-cli.git


## Upgrade Notice


### 2.1.0
* PB-CLI requires Pressbooks >= 5.21.0
