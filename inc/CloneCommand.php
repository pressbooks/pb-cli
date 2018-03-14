<?php

namespace Pressbooks_CLI;

use Pressbooks\Book;
use Pressbooks\Cloner;
use WP_CLI;

class CloneCommand extends PB_CLI_Command {

	/**
	 * Clone a book.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : URL
	 *
	 * <destination>
	 * : Book slug on the current network
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function clone( $args, $assoc_args ) {

	}

}
