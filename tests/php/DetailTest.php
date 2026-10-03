<?php
/**
 * Vehicle detail pages: URLs, view model and structured data.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Detail;
use DealerInventory\Schema;
use DealerInventory\Settings;

/**
 * @covers \DealerInventory\Detail
 */
class DetailTest extends Test_Case {

	public function test_slug_and_pretty_url(): void {
		$this->set_permalink_structure( '/%postname%/' );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_name' => 'cars', 'post_status' => 'publish' ) );
		Settings::save( array( 'detail_page' => (string) $page ) );

		$vehicle = $this->vehicle( 42 );

		$this->assertSame( '42-bmw-x5-xdrive30d', Detail::slug( $vehicle ) );
		$this->assertSame( home_url( '/cars/vehicle/42-bmw-x5-xdrive30d/' ), Detail::url( $vehicle ) );
	}

	public function test_plain_permalinks_use_a_query_argument(): void {
		$this->set_permalink_structure( '' );
		$url = Detail::url( $this->vehicle( 42 ), home_url( '/?page_id=5' ) );

		$this->assertStringContainsString( 'dinv_vehicle=42-bmw-x5-xdrive30d', $url );
	}

	public function test_view_model_and_json_ld(): void {
		$this->store( array( $this->vehicle( 7, array( 'previous_price' => 52000.0, 'detail_json' => null ) ) ) );
		\DealerInventory\Repository::store_details(
			'default',
			7,
			array(
				'description' => '<p>Nice</p><script>x</script>',
				'specs'       => array( 'doors' => 5, 'bodyColor' => 'black' ),
				'equipment'   => array( 'ABS', 'Navigation' ),
				'images'      => array(),
			)
		);
		$row  = \DealerInventory\Repository::get_vehicle( 7 );
		$data = Detail::data( $row, Schema::resolve( array() ) );

		$this->assertSame( 'BMW X5 xDrive30d', $data['title'] );
		$this->assertSame( array( 'ABS', 'Navigation' ), $data['equipment'] );
		$this->assertSame( '5', $data['specs']['doors']['value'] );
		$this->assertArrayHasKey( 'price_reduced', $data['badges'] );

		$json = Detail::json_ld( $data );
		$this->assertSame( 'Car', $json['@type'] );
		$this->assertSame( 'BMW', $json['brand']['name'] );
		$this->assertSame( 20000, $json['mileageFromOdometer']['value'] );
		$this->assertSame( 50000.0, $json['offers']['price'] );
		$this->assertSame( 'https://schema.org/UsedCondition', $json['itemCondition'] );
		$this->assertStringNotContainsString( '<script>', (string) $json['description'] );

		$html = Detail::render( $row, Schema::resolve( array() ) );
		$this->assertStringContainsString( '<h1 class="dinv-detail__title">BMW X5 xDrive30d</h1>', $html );
		$this->assertStringNotContainsString( '<script>x</script>', $html );
	}
}
