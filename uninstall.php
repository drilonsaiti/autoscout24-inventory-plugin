<?php
/**
 * Uninstall.
 *
 * By default the settings, vehicles and logs are kept, so a reinstall works
 * without entering the credentials again. With "When the plugin is deleted:
 * Remove everything" (Synchronization screen) all plugin data is removed.
 * Scheduled events and temporary caches are always removed.
 *
 * @package DealerInventory
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up the current site.
 */
function dinv_uninstall_site(): void {
	global $wpdb;

	wp_clear_scheduled_hook( 'dinv_sync_event' );

	// Known caches first (also when a persistent object cache holds transients).
	foreach ( array( 'dinv_filter_options', 'dinv_make_model_rows', 'dinv_migrating' ) as $transient ) {
		delete_transient( $transient );
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables and caches.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_dinv_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_dinv_' ) . '%'
		)
	);
	delete_option( 'dinv_public_cache' );
	delete_option( 'dinv_sync_lock' );
	delete_option( 'dinv_flush_rewrite' );
	flush_rewrite_rules( false );

	$settings = get_option( 'dinv_settings', array() );
	if ( 'delete' !== ( is_array( $settings ) ? ( $settings['uninstall_data'] ?? 'keep' ) : 'keep' ) ) {
		return;
	}

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
		'dinv_flush_rewrite',
	) as $option ) {
		delete_option( $option );
	}
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
