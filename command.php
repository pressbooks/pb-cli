<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

if ( ! class_exists( '\Pressbooks_CLI\PB_CLI_Command' ) ) {
	WP_CLI::add_command( 'pb', '\Pressbooks_CLI\PB_CLI_Command' );
}

WP_CLI::add_command( 'scaffold book-theme', [ 'Pressbooks_CLI\ScaffoldBookThemeCommand', 'scaffold_book_theme' ] );
WP_CLI::add_command( 'pb issue-template', [ 'Pressbooks_CLI\IssueTemplateCommand', 'generate_issue_template' ] );
WP_CLI::add_command( 'pb theme lock', [ 'Pressbooks_CLI\ThemeLockCommand', 'lock' ] );
WP_CLI::add_command( 'pb theme unlock', [ 'Pressbooks_CLI\ThemeLockCommand', 'unlock' ] );
WP_CLI::add_command( 'pb clone', [ 'Pressbooks_CLI\CloneCommand', 'clone' ] );

