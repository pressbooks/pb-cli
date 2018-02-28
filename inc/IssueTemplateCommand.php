<?php

namespace Pressbooks_CLI;

use WP_CLI;
use WP_CLI\Process;
use WP_CLI\Utils;

class IssueTemplateCommand extends PB_CLI_Command {

	/**
	 * Generate an issue template for a Pressbooks theme or plugin, placing it in .github/ISSUE_TEMPLATE.md.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Slug for the theme or plugin (e.g. pressbooks, pressbooks-book).
	 *
	 * --type=<type>
	 * : The type of repo for which we're generating an issue template. Must be `theme` or `plugin`.
	 *
	 * --owner=<owner>
	 * : The GitHub username of this repo's owner (e.g. pressbooks).
	 *
	 * [--dir=<dir>]
	 * : Specify a destination directory for the command. Defaults to the theme or plugin's directory.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when after_wp_load
	 */
	public function generate_issue_template( $args, $assoc_args ) {
		$slug = $args[0];
		$assoc_args = array_merge( [], $assoc_args );

		$assoc_args['slug'] = $slug;

		if ( ! empty( $assoc_args['dir'] ) ) {
			$dir = $assoc_args['dir'];
		} else {
			$dir = WP_CONTENT_DIR . '/' . $assoc_args['type'] . 's/' . $assoc_args['slug'];
		}

		foreach ( $assoc_args as $key => $value ) {
			$assoc_args[ 'has_' . $key ] = ! empty( $value );
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );
		$type = $assoc_args['type'];
		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/issue-template';
		$files_written = $this->create_files( array(
			"{$dir}/.github/ISSUE_TEMPLATE.md"  => Utils\mustache_render( "{$template_path}/{$assoc_args['type']}.mustache", $assoc_args ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'No issue template created.' );
		} else {
			WP_CLI::success( 'Created issue template for ' . $assoc_args['owner'] . '/' . $assoc_args['slug'] . '.' );
		}
	}

	private static function rewrap_param_desc( $matches ) {
		$param = $matches[1];
		$desc = self::indent( "\t\t", $matches[2] );
		return "\t$param\n$desc\n\n";
	}

	private static function indent( $whitespace, $text ) {
		$lines = explode( "\n", $text );
		foreach ( $lines as &$line ) {
			$line = $whitespace . $line;
		}
		return implode( $lines, "\n" );
	}

	private function prompt_if_files_will_be_overwritten( $filename, $force ) {
		$should_write_file = true;
		if ( ! file_exists( $filename ) ) {
			return true;
		}
		WP_CLI::warning( 'File already exists!' );
		WP_CLI::log( $filename );
		if ( ! $force ) {
			do {
				$answer = \cli\prompt(
					'Skip this file, or replace it with scaffolding?',
					$default = false,
					$marker = '[s/r]: '
				);
			} while ( ! in_array( $answer, array( 's', 'r' ) ) );
			$should_write_file = 'r' === $answer;
		}
		$outcome = $should_write_file ? 'Replacing.' : 'Skipping.';
		WP_CLI::log( $outcome . PHP_EOL );
		return $should_write_file;
	}

	private function create_files( $files_and_contents, $force ) {
		$wrote_files = array();
		foreach ( $files_and_contents as $filename => $contents ) {
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $filename, $force );
			if ( ! $should_write_file ) {
				continue;
			}
			if ( ! is_dir( dirname( $filename ) ) ) {
				Process::create( Utils\esc_cmd( 'mkdir -p %s', dirname( $filename ) ) )->run();
			}
			if ( ! file_put_contents( $filename, $contents ) ) {
				WP_CLI::error( "Error creating file: $filename" );
			} elseif ( $should_write_file ) {
				$wrote_files[] = $filename;
			}
		}
		return $wrote_files;
	}
}
