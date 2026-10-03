<?php
/**
 * Shortcode parsing, request parameters and rendering.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Inventory;
use DealerInventory\Schema;
use DealerInventory\Shortcode;

/**
 * @covers \DealerInventory\Shortcode
 * @covers \DealerInventory\Inventory
 */
class ShortcodeTest extends Test_Case {

	public function test_request_filters_are_normalized(): void {
		$filters = Shortcode::filters_from_request(
			array(
				'dinv_make'     => 'BMW',
				'dinv_price_to' => '50000',
				'dinv_year_from' => '-5',
				'dinv_warranty' => 'yes',
				'dinv_version'  => '<b>xDrive</b>',
				'dinv_fuel'     => array( 'x' ),
				'other'         => 'ignored',
			)
		);

		$this->assertSame(
			array(
				'make'         => 'bmw',
				'version'      => 'xDrive',
				'has_warranty' => 1,
				'price_to'     => 50000.0,
			),
			$filters
		);
	}

	public function test_named_instances_use_their_own_parameters(): void {
		$params = array(
			'dinv_make'      => 'audi',
			'dinv_home_make' => 'bmw',
		);

		$this->assertSame( array( 'make' => 'bmw' ), Shortcode::filters_from_request( $params, 'home' ) );
		$this->assertSame( 'dinv_home_page', Shortcode::request_key( 'page', 'home' ) );
		$this->assertSame( 'dinv_page', Shortcode::request_key( 'page' ) );
	}

	public function test_preset_filters_from_attributes_and_query(): void {
		$config  = Schema::resolve(
			array(
				'make'  => 'bmw',
				'query' => 'body=suv&price_to=60000&make=audi&unknown=1',
			)
		);
		$filters = Shortcode::filters_from_config( $config );

		$this->assertSame( 'audi', $filters['make'], 'The compact query wins over single attributes.' );
		$this->assertSame( 'suv', $filters['body_type'] );
		$this->assertSame( 60000.0, $filters['price_to'] );
		$this->assertArrayNotHasKey( 'unknown', $filters );
	}

	public function test_presets_cannot_be_widened_by_visitors(): void {
		$merged = Inventory::merge_filters(
			array(
				'make'     => 'bmw',
				'price_to' => 50000.0,
			),
			array(
				'make'     => 'audi',
				'price_to' => 90000.0,
				'fuel'     => 'diesel',
			)
		);

		$this->assertSame( 'bmw', $merged['make'] );
		$this->assertSame( 50000.0, $merged['price_to'] );
		$this->assertSame( 'diesel', $merged['fuel'] );

		$narrower = Inventory::merge_filters( array( 'price_to' => 50000.0 ), array( 'price_to' => 30000.0 ) );
		$this->assertSame( 30000.0, $narrower['price_to'] );
	}

	public function test_base_url_accepts_only_same_site_paths(): void {
		$this->assertSame( '/cars/?lang=fr', Shortcode::sanitize_base_url( '/cars/?lang=fr' ) );
		$this->assertSame( '', Shortcode::sanitize_base_url( '//evil.example/' ) );
		$this->assertSame( '', Shortcode::sanitize_base_url( 'https://evil.example/' ) );
		$this->assertSame( '', Shortcode::sanitize_base_url( '/\\evil' ) );
		$this->assertStringNotContainsString( '<', Shortcode::sanitize_base_url( '/a"><script>' ) );
	}

	public function test_url_params_skip_the_default_sort(): void {
		$this->assertSame(
			array( 'dinv_x_make' => 'bmw' ),
			Shortcode::url_params( array( 'make' => 'bmw' ), 'newest', 'newest', 'x' )
		);
		$this->assertSame(
			array( 'dinv_sort' => 'price_asc' ),
			Shortcode::url_params( array(), 'price_asc', 'newest', '' )
		);
	}

	public function test_per_page_choices_and_layout_switch(): void {
		$config = Schema::resolve( array( 'per_page' => '10', 'per_page_options' => '24,12,99,12' ) );

		$this->assertSame( array( 10, 12, 24 ), Inventory::per_page_choices( $config ), 'Sorted, unique, max. 48, with the default.' );
		$this->assertSame( '12,24,48', Schema::resolve( array( 'per_page_options' => '12,abc' ) )['per_page_options'], 'Invalid lists fall back.' );
		$this->assertSame( 'list', Inventory::effective_layout( 'card', 'list' ) );
		$this->assertSame( 'grid', Inventory::effective_layout( 'grid', 'grid' ) );
		$this->assertSame( 'card', Inventory::effective_layout( 'table', 'grid' ) );
		$this->assertSame( 'table', Inventory::effective_layout( 'table', '' ) );
	}

	public function test_visitor_choices_are_validated(): void {
		$config    = Schema::resolve(
			array(
				'per_page_selector' => 'yes',
				'view_switcher'     => 'yes',
				'sort_options'      => 'price_asc,newest',
			)
		);
		$inventory = new Inventory(
			$config,
			array(
				'dinv_per_page' => '24',
				'dinv_view'     => 'list',
				'dinv_sort'     => 'power_desc',
			),
			'',
			'',
			true
		);

		$this->assertSame( 24, $inventory->config['per_page'] );
		$this->assertSame( 'list', $inventory->config['layout'] );
		$this->assertSame( 'newest', $inventory->sort, 'A sort that is not offered is ignored.' );
	}

	public function test_shortcode_renders_vehicles_and_filters(): void {
		$this->fixture();
		$_SERVER['REQUEST_URI'] = '/cars/?lang=fr';
		$_GET['lang']           = 'fr';

		$html = do_shortcode( '[dealer_inventory per_page="4" layout="list"]' );

		$this->assertSame( 4, substr_count( $html, 'class="dinv-vehicle dinv-row"' ) );
		$this->assertStringContainsString( 'data-dinv-form', $html );
		$this->assertStringContainsString( 'data-dinv-make-mode="separate"', $html );
		$this->assertStringContainsString( 'aria-live="polite"', $html );
		$this->assertStringContainsString( 'href="/cars/?lang=fr&#038;dinv_page=2"', $html, 'Pagination uses crawlable links.' );
		$this->assertStringContainsString( '<input type="hidden" name="lang" value="fr">', $html, 'Other query arguments survive a filter submit.' );

		unset( $_GET['lang'] );
		$_SERVER['REQUEST_URI'] = '/';
	}

	public function test_static_shortcode_has_no_form_controls(): void {
		$this->fixture();

		$html = do_shortcode( '[dealer_inventory per_page="3" show_filters="no" show_sort="no" show_pagination="no" show_count="no" show_header="no"]' );

		$this->assertSame( 3, substr_count( $html, 'class="dinv-vehicle dinv-card"' ) );
		$this->assertStringContainsString( 'data-interactive="0"', $html );
		$this->assertStringNotContainsString( '<select', $html );
	}

	public function test_preset_shortcode_shows_only_matching_vehicles(): void {
		$this->fixture();

		$html = do_shortcode( '[dealer_inventory make="audi" show_filters="no"]' );

		$this->assertSame( 2, substr_count( $html, 'data-vehicle-id=' ) );
		$this->assertStringNotContainsString( 'data-vehicle-id="1"', $html );
	}

	public function test_attribute_values_are_escaped(): void {
		$this->fixture();

		$html = do_shortcode( '[dealer_inventory header_title="<script>alert(1)</script>" button_text="\"><img src=x onerror=alert(1)>"]' );

		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
	}
}
