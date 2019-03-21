<?php

namespace Pressbooks_CLI\I18n;

use WP_CLI;

class MakePotCommand extends WP_CLI\I18n\MakePotCommand {

	/**
	 * {@inheritdoc}
	 */
	protected function extract_strings() {

		$old_exclude = $this->exclude;
		$this->exclude[] = '*.blade.php';
		$translations = parent::extract_strings();

		WP_CLI::log( 'Extracting strings from .blade.php templates...' );

		try {
			BladeCodeExtractor::fromDirectory( $this->source, $translations, [
				// Extract 'Template Name' headers in theme files.
				'wpExtractTemplates' => isset( $this->main_file_data['Theme Name'] ),
				'include'            => [ '*.blade.php' ],
				'exclude'            => $old_exclude,
				'extensions'         => [ 'php' ],
			] );
		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		return $translations;
	}

}