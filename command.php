<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

// If invoking the network-aggregate command, predefine WP_CLI_SKIP_PLUGINS so Pressbooks (which ships a conflicting Mustache) won't load.
if ( isset( $GLOBALS['argv'] ) ) {
	$cmdline = implode( ' ', $GLOBALS['argv'] );
	if ( preg_match( '/\bpb\b.*\bnetwork-aggregate\b/', $cmdline ) ) {
		if ( ! defined( 'WP_CLI_SKIP_PLUGINS' ) ) {
			define( 'WP_CLI_SKIP_PLUGINS', [
				'pressbooks/pressbooks.php',
				'pressbooks-network-analytics/pressbooks-network-analytics.php',
				'pressbooks-lti/pressbooks-lti.php',
				'pressbooks-content-checker/pressbooks-content-checker.php',
			] );
		}
	}
}

if ( ! class_exists( '\Pressbooks_CLI\PB_CLI_Command' ) ) {
	WP_CLI::add_command( 'pb', '\Pressbooks_CLI\PB_CLI_Command' );
}

// For the network-aggregate command only, skip loading plugins known to cause Mustache conflicts
// by setting WP-CLI runner config before WordPress boots.
// Legacy hook approach retained for completeness; with WP_CLI_SKIP_PLUGINS set early, this is a no-op.
// (Safe to leave; will not reintroduce conflicts.)
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_hook( 'before_wp_load', function () {
		if ( defined( 'WP_CLI_SKIP_PLUGINS' ) ) {
			return;
		}
		$argv = isset( $GLOBALS['argv'] ) ? $GLOBALS['argv'] : [];
		$cmd  = implode( ' ', $argv );
		if ( preg_match( '/\bpb\b.*\bnetwork-aggregate\b/', $cmd ) ) {
			define( 'WP_CLI_SKIP_PLUGINS', [ 'pressbooks/pressbooks.php' ] );
		}
	} );
}

WP_CLI::add_command( 'scaffold book-theme', [ 'Pressbooks_CLI\ScaffoldBookThemeCommand', 'scaffold_book_theme' ] );
WP_CLI::add_command( 'pb issue-template', [ 'Pressbooks_CLI\IssueTemplateCommand', 'generate_issue_template' ] );
WP_CLI::add_command( 'pb theme lock', [ 'Pressbooks_CLI\ThemeLockCommand', 'lock' ] );
WP_CLI::add_command( 'pb theme unlock', [ 'Pressbooks_CLI\ThemeLockCommand', 'unlock' ] );
WP_CLI::add_command( 'pb clone', [ 'Pressbooks_CLI\CloneCommand', 'clone' ] );
WP_CLI::add_command('pb populate-books-admins', ['Pressbooks_CLI\PopulateBooksAdminsCommand', 'populate']);
WP_CLI::add_command( 'pb network-aggregate', [ 'Pressbooks_CLI\NetworkAggregateCommand', 'run' ] );

