<?php
/**
 * Uninstall: keep or remove data.
 *
 * Runs in its own process because uninstall.php declares a function.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Repository;
use DealerInventory\Settings;

/**
 * @coversNothing
 */
class UninstallTest extends Test_Case {

	/**
	 * Whether the vehicles table exists.
	 */
	private function table_exists(): bool {
		global $wpdb;
		return Repository::vehicles_table() === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', Repository::vehicles_table() ) );
	}

	/**
	 * Run uninstall.php.
	 */
	private function uninstall(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'dealer-inventory-for-autoscout24/dealer-inventory-for-autoscout24.php' );
		}
		if ( function_exists( 'dinv_uninstall_site' ) ) {
			dinv_uninstall_site();
			return;
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	public function test_data_is_kept_by_default_and_removed_on_request(): void {
		$this->fixture();
		Settings::save( array( 'seller_id' => '123' ) );
		set_transient( 'dinv_filter_options', array( 1 ) );

		$this->uninstall();

		$this->assertFalse( get_transient( 'dinv_filter_options' ), 'Caches are always removed.' );
		$this->assertSame( 123, (int) get_option( Settings::OPTION )['seller_id'] );
		$this->assertTrue( $this->table_exists() );

		Settings::save( array( 'uninstall_data' => 'delete' ) );

		// The test framework turns DROP TABLE into DROP TEMPORARY TABLE.
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->uninstall();
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$this->assertFalse( get_option( Settings::OPTION ) );
		$this->assertFalse( $this->table_exists() );

		// Recreate the real tables for the other tests.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		Repository::create_tables();
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
	}
}
