<?php

namespace Pressbooks_CLI\I18n;

use Gettext\Extractors\PhpCode;
use Gettext\Translations;
use Jenssegers\Blade\Blade;
use WP_CLI;
use WP_CLI\I18n\PhpCodeExtractor;
use WP_CLI\I18n\PhpFunctionsScanner;

final class BladeCodeExtractor extends PhpCode {
	use WP_CLI\I18n\IterableCodeExtractor;

	/**
	 * {@inheritdoc}
	 */
	public static function fromString( $string, Translations $translations, array $options = [] ) {

		// Use the same options as \WP_CLI\I18n\PhpCodeExtractor
		static::$options = PhpCodeExtractor::$options;
		$options += static::$options;

		// Pull in the BladeCompiler class, compile into regular PHP and then provide it to the regular parser.
		/** @var \Illuminate\View\Compilers\BladeCompiler $compiler */
		$blade = new Blade('views', 'cache');
		$compiler = $blade->compiler();
		$string = $compiler->compileString( $string );
		$functions = new PhpFunctionsScanner( $string );

		$functions->enableCommentsExtraction( $options['extractComments'] );
		$functions->saveGettextFunctions( $translations, $options );
	}
}
