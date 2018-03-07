<?php

namespace Pressbooks_CLI;

use Pressbooks\Book;
use Pressbooks\CustomCss;
use Pressbooks\Theme\Lock;
use WP_CLI;

class ThemeLockCommand extends PB_CLI_Command {

	/**
	 * Lock a book's theme.
	 *
	 * ## OPTIONS
	 *
	 * --url=<url>
	 * : The URL of the target book.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function lock( $args, $assoc_args ) {
		if ( ! Book::isBook() ) {
			WP_CLI::warning( 'Not a book. Did you forget the --url parameter?' );
			return;
		}
		if ( CustomCss::isCustomCss() ) {
			WP_CLI::warning( "Deprecated! Can't lock a theme if it's Custom CSS" );
			return;
		}

		$lock = new Lock();
		if ( $lock->isLocked() ) {
			WP_CLI::warning( 'Theme already locked.' );
		} else {
			$data = $lock->lockTheme();
			if ( $data === false ) {
				WP_CLI::error( 'Theme could not be locked.' );
			} else {
				$this->updateThemeLockOption( true );
				WP_CLI::success( 'Theme was locked: ' . wp_json_encode( $data ) );
			}
		}
	}

	/**
	 * Unlock a book's theme.
	 *
	 * ## OPTIONS
	 *
	 * --url=<url>
	 * : The URL of the target book.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function unlock( $args, $assoc_args ) {
		if ( ! Book::isBook() ) {
			WP_CLI::warning( 'Not a book. Did you forget the --url parameter?' );
			return;
		}
		if ( CustomCss::isCustomCss() ) {
			WP_CLI::warning( "Deprecated! Can't unlock a theme if it's Custom CSS" );
			return;
		}

		$lock = new Lock();
		if ( ! $lock->isLocked() ) {
			WP_CLI::warning( 'Theme already unlocked.' );
		} else {
			$theme = $lock->unlockTheme();
			$this->updateThemeLockOption( false );
			WP_CLI::success( 'Theme was unlocked. Now using ' . $theme->get( 'Name' ) . ', version ' . $theme->get( 'Version' ) );
		}
	}

	/**
	 * @param bool $on
	 */
	private function updateThemeLockOption( $on ) {
		$option = get_option( 'pressbooks_export_options' );
		if ( $on ) {
			$option['theme_lock'] = 1;
		} else {
			unset( $option['theme_lock'] );
		}
		update_option( 'pressbooks_export_options', $option );
	}

}
