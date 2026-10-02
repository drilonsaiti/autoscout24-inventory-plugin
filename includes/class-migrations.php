<?php
/**
 * Versioned, idempotent database migrations.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs schema and data migrations exactly once per version.
 *
 * Each step must be idempotent: running it twice leaves the same result.
 * The stored version is only advanced after a step succeeds, so a failed
 * step is retried on the next request.
 */
final class Migrations {

	public const OPTION = 'dinv_db_version';

	/**
	 * Latest schema version.
	 */
	public const VERSION = 1;

	/**
	 * Run pending migrations. Cheap when up to date (one autoloaded option read).
	 */
	public static function maybe_run(): void {
		$current = (int) get_option( self::OPTION, 0 );
		if ( $current >= self::VERSION ) {
			return;
		}

		// Avoid two concurrent requests running the same step.
		if ( get_transient( 'dinv_migrating' ) ) {
			return;
		}
		set_transient( 'dinv_migrating', 1, 5 * MINUTE_IN_SECONDS );

		foreach ( self::steps() as $version => $step ) {
			if ( $version <= $current ) {
				continue;
			}
			call_user_func( $step );
			update_option( self::OPTION, $version, true );
			$current = $version;
		}

		delete_transient( 'dinv_migrating' );
	}

	/**
	 * Ordered migration steps keyed by the version they produce.
	 *
	 * @return array<int, callable>
	 */
	public static function steps(): array {
		return array(
			1 => array( self::class, 'v1_install' ),
		);
	}

	/**
	 * Version 1: create the vehicles and logs tables.
	 */
	public static function v1_install(): void {
		Repository::create_tables();
	}
}
