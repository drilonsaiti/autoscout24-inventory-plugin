<?php
/**
 * WP-Cron scheduling of synchronizations.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps exactly one synchronization event scheduled, either as a recurring
 * interval or as a daily single event that re-arms itself.
 */
final class Scheduler {

	public const SCHEDULE = 'dinv_interval';

	/**
	 * Register the custom interval.
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public static function cron_schedules( array $schedules ): array {
		$minutes = self::interval_minutes();

		$schedules[ self::SCHEDULE ] = array(
			'interval' => $minutes * MINUTE_IN_SECONDS,
			/* translators: %d: number of minutes. */
			'display'  => sprintf( _n( 'Dealer Inventory: every %d minute', 'Dealer Inventory: every %d minutes', $minutes, 'dealer-inventory-for-autoscout24' ), $minutes ),
		);

		return $schedules;
	}

	/**
	 * Cron callback.
	 */
	public static function run(): void {
		// Re-arm the daily event first so a failing run cannot stop future runs.
		if ( 'daily' === Settings::get( 'sync_mode', 'interval' ) ) {
			self::schedule_daily( false );
		}
		Sync::run( false );
	}

	/**
	 * Make sure an event exists (cheap: reads the autoloaded cron option).
	 */
	public static function ensure(): void {
		if ( ! wp_next_scheduled( Sync::HOOK ) ) {
			self::schedule();
		}
	}

	/**
	 * Replace the scheduled event after settings changed.
	 */
	public static function reschedule(): void {
		wp_clear_scheduled_hook( Sync::HOOK );
		self::schedule();
	}

	/**
	 * Remove all events.
	 */
	public static function clear(): void {
		wp_clear_scheduled_hook( Sync::HOOK );
	}

	/**
	 * Human readable schedule.
	 */
	public static function summary(): string {
		if ( 'daily' === Settings::get( 'sync_mode', 'interval' ) ) {
			/* translators: %s: time of day, e.g. 03:00. */
			return sprintf( __( 'Daily at %s', 'dealer-inventory-for-autoscout24' ), (string) Settings::get( 'sync_time', '03:00' ) );
		}
		$options = Schema::field( 'sync_interval' )['options'] ?? array();
		return (string) ( $options[ self::interval_minutes() ] ?? '' );
	}

	/**
	 * Schedule according to the current mode.
	 */
	private static function schedule(): void {
		if ( 'daily' === Settings::get( 'sync_mode', 'interval' ) ) {
			self::schedule_daily();
			return;
		}

		$result = wp_schedule_event( time() + MINUTE_IN_SECONDS, self::SCHEDULE, Sync::HOOK, array(), true );
		if ( is_wp_error( $result ) ) {
			Logger::log( 'error', 'cron_schedule_failed', 'Could not schedule synchronization.', array( 'reason' => $result->get_error_message() ) );
		}
	}

	/**
	 * Schedule the next daily run.
	 *
	 * @param bool $only_if_missing Skip when an event exists.
	 */
	private static function schedule_daily( bool $only_if_missing = true ): void {
		if ( $only_if_missing && wp_next_scheduled( Sync::HOOK ) ) {
			return;
		}

		$result = wp_schedule_single_event( self::next_daily_timestamp(), Sync::HOOK, array(), true );
		if ( is_wp_error( $result ) ) {
			Logger::log( 'error', 'cron_schedule_failed', 'Could not schedule the daily synchronization.', array( 'reason' => $result->get_error_message() ) );
		}
	}

	/**
	 * Next occurrence of the configured time in the site time zone.
	 */
	private static function next_daily_timestamp(): int {
		$time = (string) Settings::get( 'sync_time', '03:00' );
		if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
			$time = '03:00';
		}

		list( $hour, $minute ) = array_map( 'intval', explode( ':', $time ) );

		$now  = new \DateTimeImmutable( 'now', wp_timezone() );
		$next = $now->setTime( $hour, $minute );
		if ( $next <= $now ) {
			$next = $next->modify( '+1 day' )->setTime( $hour, $minute );
		}

		return $next->getTimestamp();
	}

	/**
	 * Configured interval in minutes.
	 */
	private static function interval_minutes(): int {
		$minutes = (int) Settings::get( 'sync_interval', 60 );
		return in_array( $minutes, array( 15, 30, 60, 120, 240, 360, 720 ), true ) ? $minutes : 60;
	}
}
