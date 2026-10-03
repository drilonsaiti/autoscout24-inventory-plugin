<?php
/**
 * Regenerates the HTML fixtures of the JavaScript smoke test from the real
 * templates. Needs a site with synced (or demo) vehicles:
 *
 *   wp eval-file tests/js/build-fixtures.php
 *
 * @package DealerInventory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_fixtures = array(
	'separate'   => array(
		'instance'     => 'main',
		'per_page'     => '4',
		'mobile_drawer' => 'yes',
		'url_state'    => 'yes',
	),
	'searchable' => array(
		'instance'        => 'side',
		'per_page'        => '4',
		'make_model_mode' => 'searchable',
		'filter_position' => 'sidebar',
		'range_style'     => 'slider',
		'pagination'      => 'load_more',
		'view_switcher'   => 'yes',
	),
);

$_SERVER['REQUEST_URI'] = '/cars/';
foreach ( $dinv_fixtures as $dinv_name => $dinv_atts ) {
	$dinv_html = \DealerInventory\Shortcode::render( $dinv_atts );
	file_put_contents( __DIR__ . '/fixtures/' . $dinv_name . '.html', $dinv_html ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Developer tool.
	echo esc_html( $dinv_name ) . ': ' . strlen( $dinv_html ) . " bytes\n";
}
