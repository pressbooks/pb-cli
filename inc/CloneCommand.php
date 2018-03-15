<?php

namespace Pressbooks_CLI;

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
	 * --user=<user>
	 * : sets request to a specific WordPress user
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function clone( $args, $assoc_args ) {
		if ( count( $args ) < 2 ) {
			WP_CLI::error( 'Expects 3 parameters: <source> <dest> --user=<user> ' );
		}
		if ( ! get_current_user_id() ) {
			WP_CLI::error( 'Missing --user parameter (sets request to a specific WordPress user)' );
		}

		$success = false;
		try {
			$source = esc_url( $args[0] );

			$dest = Cloner::validateNewBookName( $args[1] );
			if ( is_wp_error( $dest ) ) {
				WP_CLI::error( '<dest> ' . $dest->get_error_message() );
			}

			WP_CLI::log( "Cloning {$source} into {$dest}" );

			\Pressbooks\Metadata\init_book_data_models();
			\Pressbooks\Api\init_book();
			$cloner = new Cloner( $source, $dest );
			$success = $cloner->cloneBook();

		} catch ( \Exception $e ) {
			// Do nothing, look at $_SESSION['pb_errors'] instead
		}

		if ( ! empty( $_SESSION['pb_errors'] ) ) {
			foreach ( $_SESSION['pb_errors'] as $error ) {
				$error = wp_strip_all_tags( $error, true );
				$error = html_entity_decode( $error );
				WP_CLI::warning( $error );
			}
		}
		if ( ! $success ) {
			WP_CLI::error( 'Cloning failed!' );
		} else {
			WP_CLI::success( 'Cloning succeeded!' );
		}
	}

}
