<?php
/**
 * Language resolution.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides which language visitors see and which language synchronized
 * content is requested in.
 *
 * Multilingual plugins are supported through adapters (TranslatePress, WPML,
 * Polylang). Anything else can hook "dinv_current_language".
 */
final class I18n {

	/**
	 * Two-letter language of the current visitor request.
	 */
	public static function current_language(): string {
		/**
		 * Short-circuit the detected visitor language (two-letter code).
		 *
		 * @param string|null $language Language or null to auto-detect.
		 */
		$filtered = apply_filters( 'dinv_current_language', null );
		if ( is_string( $filtered ) && '' !== $filtered ) {
			return self::two_letter( $filtered );
		}

		global $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- TranslatePress global.
		if ( is_string( $TRP_LANGUAGE ) && '' !== $TRP_LANGUAGE ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return self::two_letter( $TRP_LANGUAGE ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		}

		$wpml = apply_filters( 'wpml_current_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		if ( is_string( $wpml ) && '' !== $wpml && 'all' !== $wpml ) {
			return self::two_letter( $wpml );
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$polylang = pll_current_language( 'slug' );
			if ( is_string( $polylang ) && '' !== $polylang ) {
				return self::two_letter( $polylang );
			}
		}

		return self::two_letter( determine_locale() );
	}

	/**
	 * Language used to request synchronized texts (teasers) from the API.
	 *
	 * @param Providers\Provider $provider Provider.
	 */
	public static function content_language( Providers\Provider $provider ): string {
		$supported = $provider->languages();
		$setting   = sanitize_key( (string) Settings::get( 'content_language', 'auto' ) );

		if ( 'auto' !== $setting && in_array( $setting, $supported, true ) ) {
			return $setting;
		}

		$site = self::two_letter( get_locale() );
		return in_array( $site, $supported, true ) ? $site : $supported[0];
	}

	/**
	 * Language for links to the marketplace website: the visitor's language
	 * when the marketplace offers it, otherwise the content language.
	 *
	 * @param Providers\Provider $provider Provider.
	 */
	public static function listing_language( Providers\Provider $provider ): string {
		$visitor = self::current_language();
		return in_array( $visitor, $provider->languages(), true ) ? $visitor : self::content_language( $provider );
	}

	/**
	 * "de_CH" / "de-CH" / "DE" → "de".
	 *
	 * @param string $locale Locale or language code.
	 */
	public static function two_letter( string $locale ): string {
		return strtolower( substr( str_replace( '-', '_', $locale ), 0, 2 ) );
	}
}
