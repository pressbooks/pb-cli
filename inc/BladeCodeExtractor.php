<?php

namespace Pressbooks_CLI;

use Gettext\Extractors\PhpCode;
use Gettext\Translations;
use WP_CLI;
use WP_CLI\I18n\PhpCodeExtractor;

final class BladeCodeExtractor extends PhpCode {
	use WP_CLI\I18n\IterableCodeExtractor;

	/**
	 * {@inheritdoc}
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {

		static::$options = PhpCodeExtractor::$options;
		$options += static::$options;

		/** @var \Illuminate\View\Compilers\BladeCompiler $compiler */
		$compiler = \Pressbooks\Container::get( 'Blade' )->compiler();
		$string = $compiler->compileString( $string );
		$functions = new \WP_CLI\I18n\PhpFunctionsScanner( $string );

		$functions->enableCommentsExtraction( $options['extractComments'] );
		$functions->saveGettextFunctions( $translations, $options );
	}
}
