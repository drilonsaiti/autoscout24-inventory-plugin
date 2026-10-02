<?php
/**
 * Diagnostics log.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small bounded log of synchronization and API events.
 *
 * Never stores credentials, tokens or personal data: sensitive keys are
 * redacted and long values truncated.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 */
final class Logger {

	private const KEEP_ROWS  = 500;
	private const TRIM_EVERY = 50;

	/**
	 * Log table name.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'dinv_logs';
	}

	/**
	 * Write one entry.
	 *
	 * @param string $level   info|warning|error.
	 * @param string $event   Machine event name.
	 * @param string $message Message (plain text).
	 * @param array  $context Extra data; sensitive keys are redacted.
	 */
	public static function log( string $level, string $event, string $message, array $context = array() ): void {
		global $wpdb;

		$wpdb->insert(
			self::table(),
			array(
				'level'      => substr( sanitize_key( $level ), 0, 20 ),
				'event'      => substr( sanitize_key( $event ), 0, 80 ),
				'message'    => mb_substr( wp_strip_all_tags( $message ), 0, 1000 ),
				'context'    => wp_json_encode( self::sanitize_context( $context ) ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		// Trim occasionally instead of after every insert.
		if ( $wpdb->insert_id && 0 === ( (int) $wpdb->insert_id % self::TRIM_EVERY ) ) {
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i WHERE id <= %d',
					self::table(),
					(int) $wpdb->insert_id - self::KEEP_ROWS
				)
			);
		}
	}

	/**
	 * Most recent entries.
	 *
	 * @param int $limit Number of rows (1-200).
	 * @return array[]
	 */
	public static function recent( int $limit = 50 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', self::table(), max( 1, min( 200, $limit ) ) ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Redact secrets and truncate long values.
	 *
	 * @param array $context Context.
	 * @return array
	 */
	private static function sanitize_context( array $context ): array {
		$blocked = array( 'client_secret', 'access_token', 'authorization', 'token', 'password', 'email', 'phone', 'message' );

		array_walk_recursive(
			$context,
			static function ( &$value, $key ) use ( $blocked ): void {
				if ( in_array( strtolower( (string) $key ), $blocked, true ) ) {
					$value = '[redacted]';
				} elseif ( is_string( $value ) && strlen( $value ) > 500 ) {
					$value = substr( $value, 0, 500 ) . '…';
				}
			}
		);

		return $context;
	}
}
