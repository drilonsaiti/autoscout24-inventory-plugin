<?php
/**
 * Provider registry.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of marketplace providers.
 *
 * Third-party code can add providers:
 *
 *     add_filter( 'dinv_providers', function ( $providers ) {
 *         $providers['my_market'] = new My_Market_Provider();
 *         return $providers;
 *     } );
 */
final class Providers {

	public const DEFAULT_ID = Providers\AutoScout24_CH::ID;

	/**
	 * Provider instances keyed by id.
	 *
	 * @var array<string, Providers\Provider>|null
	 */
	private static ?array $providers = null;

	/**
	 * All registered providers.
	 *
	 * @return array<string, Providers\Provider>
	 */
	public static function all(): array {
		if ( null === self::$providers ) {
			$providers = array(
				Providers\AutoScout24_CH::ID => new Providers\AutoScout24_CH(),
			);

			/**
			 * Filters the available marketplace providers.
			 *
			 * @param array<string, Providers\Provider> $providers Providers keyed by id.
			 */
			$filtered = apply_filters( 'dinv_providers', $providers );

			self::$providers = array();
			foreach ( (array) $filtered as $id => $provider ) {
				if ( $provider instanceof Providers\Provider && $id === $provider->id() ) {
					self::$providers[ $id ] = $provider;
				}
			}
			if ( ! self::$providers ) {
				self::$providers = $providers;
			}
		}

		return self::$providers;
	}

	/**
	 * Provider by id; falls back to the default provider.
	 *
	 * @param string $id Provider id.
	 */
	public static function get( string $id ): Providers\Provider {
		$all = self::all();
		return $all[ $id ] ?? $all[ self::DEFAULT_ID ] ?? reset( $all );
	}

	/**
	 * Reset the cache (tests).
	 */
	public static function reset(): void {
		self::$providers = null;
	}
}
