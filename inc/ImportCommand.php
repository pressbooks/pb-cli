<?php

namespace Pressbooks_CLI;

use Peast\Syntax\Exception;
use Pressbooks\Modules\Import\WordPress\Parser;
use WP_CLI;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Modules\Import\Api\Api;
use Pressbooks\Modules\Import\Wordpress\Wxr ;

class ImportCommand extends PB_CLI_Command {

	/**
	 * Import a book.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : XML FILE
	 *
	 * --user=<user>
	 * : sets request to a specific WordPress user
	 *
	 * [--slug=<slug>]
	 * : Book slug on the network
	 *
	 * [--filterslug=<filterslug>]
	 * : Book slug regex filter
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function import( $args, $assoc_args ) {
		global $domain;

		// Validate command parameters
		if ( count( $args ) < 1 ) {
			WP_CLI::error( 'Expects 1 parameters: <source> --user=<user> --slug=<slug> --filterslug=<filterslug>' );
		}
		if ( ! get_current_user_id() ) {
			WP_CLI::error( 'Missing --user parameter (sets request to a specific WordPress user)' );
		}

		// Main logic
		$success = false;
		try {

			// Set xml file path
			$source = $args[0];

			# xml content
			$importer = new \Pressbooks\Modules\Import\WordPress\Wxr();
			$file = $importer->createTmpFile();
			file_put_contents( $file, file_get_contents( $args[0] ) );

			$parser = new Parser();
			$xml = $parser->parse( $file );
			if($assoc_args['slug']) {
				$slug = $assoc_args['slug'];
			} else {
				$slug = substr(parse_url($xml['base_blog_url'])['path'],1);
			}
			$slug = strtolower($slug);
			$slug = preg_replace("/[^a-z0-9]/", "", $slug);
			$slug = preg_replace("/{$assoc_args['filterslug']}/", "", $slug);
			$title = $xml['title'];

			// Validate book name is legal
			$dest = Cloner::validateNewBookName( ($assoc_args['slug'])? $assoc_args['slug'] : $slug );
			if ( is_wp_error( $dest ) ) {
				WP_CLI::error( '<dest> ' . $dest->get_error_message() );
			}

			//get book slug
			WP_CLI::log( "Import {$source} into {$dest}" );

			//create book
			WP_CLI::log( "Creating book" );

			// Disable automatic redirect to new book dashboard
			add_filter('pb_redirect_to_new_book', function () {
				return false;
			});
			// Remove default content so that the book only contains the results of the clone operation
			add_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );
			$blog_id = wpmu_create_blog( $domain, $slug, $title, get_current_user_id() );
			remove_all_filters( 'pb_redirect_to_new_book' );
			remove_filter( 'pb_default_book_content', [ $this, 'removeDefaultBookContent' ] );

			if ( is_wp_error( $blog_id ) ) {
				WP_CLI::log( "error creating blog" );
				return false;
			}

			// Switch to the new book
			switch_to_blog($blog_id);

			// Create temp file

			// Get xml meta options
			$xml_options = $importer->setCurrentImportOption( [ 'file' => $file, 'type' => '' ] );

			// Put a check mark in every box and import
			$options = get_option( 'pressbooks_current_import' );

			$post = [];
			foreach ( $options['chapters'] as $k => $v ) {
				$post[ $k ] = [
					'import' => 1,
					'type' => $options['post_types'][ $k ],
				];
			}
			$_POST['chapters'] = $post;

			// Import book content from xml
			$success = $importer->import( $options );


		} catch ( \Exception $e ) {
			// Do nothing, look at $_SESSION['pb_errors'] instead
//			var_dump($e);
		}

		if ( ! empty( $_SESSION['pb_errors'] ) ) {
			foreach ( $_SESSION['pb_errors'] as $error ) {
				$error = wp_strip_all_tags( $error, true );
				$error = html_entity_decode( $error );
				WP_CLI::warning( $error );
			}
		}
		if ( ! $success ) {
			WP_CLI::error( 'Import failed!' );
		} else {
			WP_CLI::success( 'Import succeeded!' );
		}

		WP_CLI::log("import completed");
	}

	/**
	 * Batch Import a book.
	 *
	 * ## OPTIONS
	 *
	 * <folderpath>
	 * : FOLDER PATH
	 *
	 * --user=<user>
	 * : sets request to a specific WordPress user
	 *
	 * [--filterslug=<filterslug>]
	 * : Book slug regex filter
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function batch_import( $args, $assoc_args ) {

		// Validate command parameters
		if ( count( $args ) < 1 ) {
			WP_CLI::error( 'Expects 1 parameters: <folderpath> --user=<user> --filterslug=<filterslug>' );
		}
		if ( ! get_current_user_id() ) {
			WP_CLI::error( 'Missing --user parameter (sets request to a specific WordPress user)' );
		}

		try {

			$dir = $args[0];
			$files = scandir($dir);

			$progress = \WP_CLI\Utils\make_progress_bar( 'Generating users', $count );

			for($i = 2; $i < count( $files ); $i++) {
				var_dump($i);
				$path = $dir . '/' . $files[$i];

				if ( !file_exists($path) ) {
					continue;
				}

				try {
					WP_CLI::runcommand("pb import {$path} --user=admin --filterslug={$assoc_args['filterslug']}");
				} catch (Exception $e) {
					//log error file
					WP_CLI::error("Error has occured with the file $path");
					WP_CLI::error($e->getMessage());
				}

				$progress->tick();

			}

		} catch ( \Exception $e ) {
			// Do nothing, look at $_SESSION['pb_errors'] instead
		}
		$progress->finish();
		WP_CLI::log("Batch import completed");
	}

	/**
	 * When creating a new book as the target of a clone operation, this function removes
	 * default front matter, parts, chapters and back matter from the book creation routines.
	 *
	 * @since 4.1.0
	 * @see apply_filters( 'pb_default_book_content', ... )
	 *
	 * @param array $contents The default book contents
	 *
	 * @return array The filtered book contents
	 */
	public function removeDefaultBookContent( $contents ) {
		foreach (
			[
				'introduction',
				'main-body',
				'chapter-1',
				'appendix',
			] as $post
		) {
			unset( $contents[ $post ] );
		}
		return $contents;
	}

}

