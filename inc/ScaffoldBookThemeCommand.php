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
	 * - .gitignore, .editorconfig, package.json, composer.json, composer.lock, yarn.lock
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
	 * [--theme_name=<title>]
	 * : What to put in the 'Theme Name:' header in 'style.css'. Defaults to <slug>.
	 *
	 * [--description=<description>]
	 * : Human-readable description for the theme.
	 *
	 * [--uri=<uri>]
	 * : What to put in the 'Theme URI:' header in 'style.css'.
	 *
	 * [--author=<author>]
	 * : What to put in the 'Author:' header in 'style.css'.
	 *
	 * [--author_uri=<author_uri>]
	 * : What to put in the 'Author URI:' header in 'style.css'.
	 *
	 * [--license=<license>]
	 * : What to put in the 'License:' header in 'style.css'.
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
	 * [--activate]
	 * : Activate the newly created book theme.
	 *
	 * [--enable-network]
	 * : Enable the newly created book theme for the entire network.
	 *
	 * [--force]
	 * : Overwrite files that already exist.
	 *
	 * @when after_wp_load
	 */
	public function scaffold_book_theme( $args, $assoc_args ) {
		$theme_slug = $args[0];
		$assoc_args = array_merge( [
			'description' => '',
			'uri' 				=> '',
			'author'    	=> '',
			'author_uri'  => '',
			'license'			=> 'GPL-2.0+',
			'version'			=> '1.0',
			'dir'         => '',
		], $assoc_args );

		$assoc_args['slug'] = $theme_slug;
		$assoc_args['theme_function_safe'] = str_replace( '-', '_', $assoc_args['slug'] );

		if ( empty( $assoc_args['theme_name'] ) ) {
			$assoc_args['theme_name'] = ucfirst($theme_slug);
		}

		if ( empty( $assoc_args['textdomain'] ) ) {
			$assoc_args['textdomain'] = $theme_slug;
		}

		if ( ! empty( $assoc_args['dir'] ) ) {
			$theme_dir = $assoc_args['dir'];
		} else {
			$theme_dir = WP_CONTENT_DIR . '/themes/' . $assoc_args['slug'];
		}

		foreach ( $assoc_args as $key => $value ) {
			$assoc_args[ 'has_' . $key ] = ! empty( $value );
		}

		$activate = Utils\get_flag_value( $assoc_args, 'activate' );
		$enable_network = Utils\get_flag_value( $assoc_args, 'enable-network' );
		$force = Utils\get_flag_value( $assoc_args, 'force' );
		$package_root = dirname( dirname( __FILE__ ) );
		$template_path = $package_root . '/templates/scaffold-book-theme';
		$files_written = $this->create_files( array(
			"{$theme_dir}/.github/ISSUE_TEMPLATE.md"  => Utils\mustache_render( "{$template_path}/.github/ISSUE_TEMPLATE.mustache", $assoc_args ),
			"{$theme_dir}/functions.php"  => Utils\mustache_render( "{$template_path}/functions.mustache", $assoc_args ),
			"{$theme_dir}/style.css"			=> Utils\mustache_render( "{$template_path}/style.mustache", $assoc_args ),
			"{$theme_dir}/assets/styles/epub/style.scss"		=> Utils\mustache_render( "{$template_path}/assets/styles/epub/style.mustache", $assoc_args ),
			"{$theme_dir}/assets/styles/prince/style.scss"  => Utils\mustache_render( "{$template_path}/assets/styles/prince/style.mustache", $assoc_args ),
			"{$theme_dir}/assets/styles/web/style.scss"			=> Utils\mustache_render( "{$template_path}/assets/styles/web/style.mustache", $assoc_args ),
			"{$theme_dir}/composer.json"										=> Utils\mustache_render( "{$template_path}/composer.mustache", $assoc_args ),
			"{$theme_dir}/package.json"											=> Utils\mustache_render( "{$template_path}/package.mustache", $assoc_args ),
			"{$theme_dir}/composer.lock"																=> file_get_contents( "{$template_path}/composer.lock" ),
			"{$theme_dir}/.gitignore"																		=> file_get_contents( "{$template_path}/.gitignore" ),
			"{$theme_dir}/.editorconfig"																=> file_get_contents( "{$template_path}/.editorconfig" ),
			"{$theme_dir}/yarn.lock"																		=> file_get_contents( "{$template_path}/yarn.lock" ),
			"{$theme_dir}/assets/styles/components/_colors.scss"				=> file_get_contents( "{$template_path}/assets/styles/components/_colors.scss" ),
			"{$theme_dir}/assets/styles/components/_elements.scss"			=> file_get_contents( "{$template_path}/assets/styles/components/_elements.scss" ),
			"{$theme_dir}/assets/styles/components/_accessibility.scss"	=> file_get_contents( "{$template_path}/assets/styles/components/_accessibility.scss" ),
			"{$theme_dir}/assets/styles/components/_colors.scss"				=> file_get_contents( "{$template_path}/assets/styles/components/_colors.scss" ),
			"{$theme_dir}/assets/styles/components/_elements.scss"			=> file_get_contents( "{$template_path}/assets/styles/components/_elements.scss" ),
			"{$theme_dir}/assets/styles/components/_media.scss"					=> file_get_contents( "{$template_path}/assets/styles/components/_media.scss" ),
			"{$theme_dir}/assets/styles/components/_pages.scss"					=> file_get_contents( "{$template_path}/assets/styles/components/_pages.scss" ),
			"{$theme_dir}/assets/styles/components/_section-titles.scss"				=> file_get_contents( "{$template_path}/assets/styles/components/_section-titles.scss" ),
			"{$theme_dir}/assets/styles/components/_specials.scss"			=> file_get_contents( "{$template_path}/assets/styles/components/_specials.scss" ),
			"{$theme_dir}/assets/styles/components/_structure.scss"			=> file_get_contents( "{$template_path}/assets/styles/components/_structure.scss" ),
			"{$theme_dir}/assets/styles/components/_toc.scss"						=> file_get_contents( "{$template_path}/assets/styles/components/_toc.scss" ),
			"{$theme_dir}/assets/styles/epub/_fonts.scss"								=> file_get_contents( "{$template_path}/assets/styles/epub/_fonts.scss" ),
			"{$theme_dir}/assets/styles/prince/_fonts.scss"							=> file_get_contents( "{$template_path}/assets/styles/prince/_fonts.scss" ),
			"{$theme_dir}/assets/styles/web/_fonts.scss"								=> file_get_contents( "{$template_path}/assets/styles/web/_fonts.scss" ),
		), $force );

		if ( empty( $files_written ) ) {
			WP_CLI::log( 'Theme files were skipped.' );
		} else {
			WP_CLI::success( 'Created theme ' . $assoc_args['theme_name'] . '.' );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'activate' ) ) {
			WP_CLI::run_command( array( 'theme', 'activate', $theme_slug ) );
		} else if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'enable-network' ) ) {
			WP_CLI::run_command( array( 'theme', 'enable', $theme_slug ), array( 'network' => true ) );
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
