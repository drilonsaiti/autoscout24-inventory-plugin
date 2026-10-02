<?php
/**
 * Site-wide settings storage.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the site-wide values of every Schema field in one option.
 *
 * The option is autoloaded and kept complete (all keys present), so normal
 * requests read it without building the translated schema. When the plugin
 * version changes, missing keys are filled from the schema once.
 */
final class Settings {

	public const OPTION = 'dinv_settings';

	/**
	 * Per-request cache.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $runtime_cache = null;

	/**
	 * All site-wide settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( is_array( self::$runtime_cache ) ) {
			return self::$runtime_cache;
		}

		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		if ( DINV_VERSION !== ( $stored['_version'] ?? '' ) ) {
			$stored             = array_merge( Schema::global_defaults(), $stored );
			$stored['_version'] = DINV_VERSION;
			update_option( self::OPTION, $stored, true );
		}

		self::$runtime_cache = $stored;
		return $stored;
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Setting name.
	 * @param mixed  $fallback Value when the key is unknown.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$settings = self::all();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Patch-style save: only keys present in $input change.
	 *
	 * Each admin screen submits its own subset of fields.
	 *
	 * @param array $input Unslashed form input.
	 * @return array<string, mixed> New settings.
	 */
	public static function save( array $input ): array {
		$new = self::all();

		foreach ( $input as $key => $raw ) {
			$field = Schema::field( (string) $key );
			if ( null === $field || Schema::SCOPE_INSTANCE === $field['scope'] || is_array( $raw ) ) {
				continue;
			}

			// Values defined in wp-config.php cannot be changed from the admin.
			if ( ! empty( $field['constant'] ) && defined( $field['constant'] ) ) {
				continue;
			}

			if ( 'secret' === $field['type'] ) {
				$secret = trim( (string) $raw );
				if ( '' === $secret ) {
					continue;
				}
				$encrypted = Crypto::encrypt( $secret );
				if ( '' !== $encrypted ) {
					$new[ $key ] = $encrypted;
				}
				continue;
			}

			$new[ $key ] = Schema::sanitize( (string) $key, $raw );
		}

		return self::replace( $new );
	}

	/**
	 * Reset every design setting to the default preset.
	 *
	 * @return array<string, mixed>
	 */
	public static function reset_design(): array {
		$settings = self::all();
		foreach ( Schema::group( 'design' ) as $key => $field ) {
			$settings[ $key ] = $field['default'];
		}
		return self::replace( $settings );
	}

	/**
	 * Store a complete settings array.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	private static function replace( array $settings ): array {
		$settings['_version'] = DINV_VERSION;
		update_option( self::OPTION, $settings, true );
		self::$runtime_cache = $settings;
		Connection::reset();
		return $settings;
	}

	/**
	 * Forget the per-request cache (tests).
	 */
	public static function reset_cache(): void {
		self::$runtime_cache = null;
		Connection::reset();
	}
}
