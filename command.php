<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

if ( ! class_exists( 'Pressbooks_CLI\IssueTemplateCommand' ) ) {
	require_once __DIR__ . '/inc/PBCliCommand.php';
	require_once __DIR__ . '/inc/IssueTemplateCommand.php';
	require_once __DIR__ . '/inc/ScaffoldBookThemeCommand.php';
	require_once __DIR__ . '/inc/ThemeLockCommand.php';

	WP_CLI::add_command( 'pb issue-template', [ 'Pressbooks_CLI\IssueTemplateCommand', 'generate_issue_template' ] );
	WP_CLI::add_command( 'pb theme lock', [ 'Pressbooks_CLI\ThemeLockCommand', 'lock' ] );
	WP_CLI::add_command( 'pb theme unlock', [ 'Pressbooks_CLI\ThemeLockCommand', 'unlock' ] );
	WP_CLI::add_command( 'scaffold book-theme', [ 'Pressbooks_CLI\ScaffoldBookThemeCommand', 'scaffold_book_theme' ] );
}
