pressbooks/pb-cli
=================

A suite of wp-cli commands for Pressbooks.

[![Build Status](https://travis-ci.org/pressbooks/pb-cli.svg?branch=master)](https://travis-ci.org/pressbooks/pb-cli)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

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

## Installing

Installing this package requires WP-CLI v1.3.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:pressbooks/pb-cli.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/pressbooks/pb-cli/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/pressbooks/pb-cli/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/pressbooks/pb-cli/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
