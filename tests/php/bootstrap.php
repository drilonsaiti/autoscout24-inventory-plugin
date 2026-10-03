<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library and the plugin.
 *
 * The WordPress test library is found through WP_TESTS_DIR, or through the
 * wp-phpunit/wp-phpunit Composer package (configure its database with the
 * WP_PHPUNIT__TESTS_CONFIG environment variable).
 *
 * @package DealerInventory
 */

$dinv_root = dirname( __DIR__, 2 );

if ( file_exists( $dinv_root . '/vendor/autoload.php' ) ) {
	require_once $dinv_root . '/vendor/autoload.php';
}

$dinv_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $dinv_tests_dir ) {
	$dinv_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}
if ( ! $dinv_tests_dir || ! file_exists( $dinv_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test library not found. Run composer install or set WP_TESTS_DIR.\n" );
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	$dinv_polyfills = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $dinv_polyfills ? $dinv_polyfills : $dinv_root . '/vendor/yoast/phpunit-polyfills' );
}

require_once $dinv_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $dinv_root ): void {
		require $dinv_root . '/dealer-inventory-for-autoscout24.php';
	}
);

require $dinv_tests_dir . '/includes/bootstrap.php';

// Tables are created once; tests run inside transactions.
\DealerInventory\Repository::create_tables();
update_option( \DealerInventory\Migrations::OPTION, \DealerInventory\Migrations::VERSION );

require_once __DIR__ . '/class-test-case.php';
