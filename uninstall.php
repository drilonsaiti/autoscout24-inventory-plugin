<?php
/**
 * Uninstall: remove all plugin data.
 *
 * @package DealerInventory
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove the plugin's tables, options, transients and cron events for the
 * current site.
 */
function dinv_uninstall_site(): void {
	global $wpdb;

	wp_clear_scheduled_hook( 'dinv_sync_event' );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $wpdb->prefix . 'dinv_vehicles' ) );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $wpdb->prefix . 'dinv_logs' ) );

	foreach ( array(
		'dinv_settings',
		'dinv_db_version',
		'dinv_plugin_version',
		'dinv_sync_stats',
		'dinv_last_sync_attempt',
		'dinv_connection_status',
		'dinv_seller_profile',
		'dinv_sync_lock',
	) as $option ) {
		delete_option( $option );
	}

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_dinv_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_dinv_' ) . '%'
		)
	);
	// phpcs:enable
}

if ( ! is_multisite() ) {
	dinv_uninstall_site();
	return;
}

foreach ( get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
) as $dinv_site_id ) {
	switch_to_blog( (int) $dinv_site_id );
	dinv_uninstall_site();
	restore_current_blog();
}
