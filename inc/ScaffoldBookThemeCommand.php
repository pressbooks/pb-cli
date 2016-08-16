<?php

namespace Pressbooks_CLI;

use WP_CLI;
use WP_CLI\Process;
use WP_CLI\Utils;

class ScaffoldBookThemeCommand {

	/**
	 * Generate the files needed for a Pressbooks book theme.
	 *
	 * Default behavior is to create the following files:
	 * - functions.php
	 * - .gitignore, .editorconfig and .scss-lint.yml
	 * - README.md
	 *
	 * Unless specified with `--dir=<dir>`, the theme is placed in the themes
	 * directory.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Slug for the new theme.
	 *
	 * [--theme_name=<theme_name>]
	 * : Name for the new theme. Defaults to <slug>.
	 *
	 * [--description=<description>]
	 * : Human-readable description for the theme.
	 *
	 * [--uri=<uri>]
	 * : URI for the theme.
	 *
	 * [--author=<author>]
	 * : Author for the theme.
	 *
	 * [--author_uri=<author_uri>]
	 * : Author URI for the theme.
	 *
	 * [--license=<license>]
	 * : License for the theme.
	 * ---
	 * default: GPL 2.0+
	 * ---
	 *
	 * [--textdomain=<textdomain>]
	 * : Text domain for the theme. Defaults to <slug>.
	 *
	 * [--version=<version>]
	 * : Version for the theme.
	 * ---
	 * default: 1.0
	 * ---
	 *
	 * [--dir=<dir>]
	 * : Specify a destination directory for the command. Defaults to the themes directory.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when before_wp_load
	 */
	public function theme( $args, $assoc_args ) {
		$defaults = array(
			'description' => '',
			'uri' 				=> '',
			'author'    	=> '',
			'author_uri'  => '',
			'license'			=> 'GPL-2.0+',
			'version'			=> '1.0',
			'dir'         => '',
		);
		$assoc_args = array_merge( $defaults, $assoc_args );
		$assoc_args['slug'] = $args[0];
		$assoc_args['prefix'] = str_replace('-', '_', $args[0]);
		if ( empty( $assoc_args['theme_name'] ) ) {
			$assoc_args['theme_name'] = $args[0];
		}
		if ( empty( $assoc_args['textdomain'] ) ) {
			$assoc_args['textdomain'] = $args[0];
		}
		if ( ! empty( $assoc_args['dir'] ) ) {
			$theme_dir = $assoc_args['dir'];
		} else {
			$theme_dir = WP_CONTENT_DIR . '/themes/' . $assoc_args['slug'];
		}
		$force = Utils\get_flag_value( $assoc_args, 'force' );
		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/';
		$files_written = $this->create_files( array(
			"{$theme_dir}/functions.php"  => Utils\mustache_render( "{$template_path}/functions.mustache", $assoc_args ),
			"{$theme_dir}/style.css"			=> Utils\mustache_render( "{$template_path}/style.mustache", $assoc_args ),
		), $force );
		if ( empty( $files_written ) ) {
			WP_CLI::log( 'All theme files were skipped.' );
		} else {
			WP_CLI::success( "Created theme files in {$theme_dir}" );
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
		WP_CLI::warning( 'File already exists' );
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
		$outcome = $should_write_file ? 'Replacing' : 'Skipping';
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
