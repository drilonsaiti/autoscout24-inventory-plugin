<?php
/**
 * Template loading with theme overrides.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads templates from the theme first, then from the plugin.
 *
 * Copy any file from the plugin's templates/ folder to
 * yourtheme/dealer-inventory/ (same sub-path) to override it.
 */
final class Template {

	public const THEME_DIR = 'dealer-inventory';

	/**
	 * Absolute path of a template.
	 *
	 * @param string $name Relative path, e.g. "loop/card.php".
	 */
	public static function locate( string $name ): string {
		$name = ltrim( str_replace( array( '..', '\\' ), '', $name ), '/' );

		$path = locate_template( self::THEME_DIR . '/' . $name );
		if ( '' === $path ) {
			$path = DINV_PLUGIN_DIR . 'templates/' . $name;
		}

		/**
		 * Filters the template file used for a template name.
		 *
		 * @param string $path Absolute path.
		 * @param string $name Template name, e.g. "loop/card.php".
		 */
		return (string) apply_filters( 'dinv_template', $path, $name );
	}

	/**
	 * Render a template with isolated variables.
	 *
	 * @param string $name Template name.
	 * @param array  $vars Variables available in the template.
	 */
	public static function render( string $name, array $vars = array() ): string {
		$path = self::locate( $name );
		if ( ! is_readable( $path ) ) {
			return '';
		}

		/**
		 * Filters the variables passed to a template.
		 *
		 * @param array  $vars Variables.
		 * @param string $name Template name.
		 */
		$vars = (array) apply_filters( 'dinv_template_args', $vars, $name );

		ob_start();
		( static function ( string $dinv_template_path, array $dinv_template_vars ): void {
			extract( $dinv_template_vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Template scope only.
			include $dinv_template_path;
		} )( $path, $vars );
		return (string) ob_get_clean();
	}
}
