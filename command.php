<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

require_once __DIR__ . '/inc/ScaffoldBookThemeCommand.php';

WP_CLI::add_command( 'scaffold book-theme', array( 'Pressbooks_CLI\ScaffoldBookThemeCommand', 'theme' ) );
