<?php
/**
 * Repository: storage, search, sorting, facets and sync bookkeeping.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Repository;

/**
 * @covers \DealerInventory\Repository
 */
class RepositoryTest extends Test_Case {

	public function test_search_returns_active_vehicles_with_totals(): void {
		$this->fixture();

		$result = Repository::search( array(), 1, 4, 'price_asc' );

		$this->assertSame( 6, $result['total'] );
		$this->assertSame( 2, $result['total_pages'] );
		$this->assertCount( 4, $result['items'] );
		$this->assertSame( array( '4', '1', '6', '2' ), array_column( $result['items'], 'external_id' ) );
	}

	public function test_second_page_and_page_size_limit(): void {
		$this->fixture();

		$page2 = Repository::search( array(), 2, 4, 'price_asc' );
		$this->assertSame( array( '5', '3' ), array_column( $page2['items'], 'external_id' ) );

		$capped = Repository::search( array(), 1, 500, 'newest' );
		$this->assertSame( 48, $capped['per_page'] );
	}

	/**
	 * @dataProvider filter_cases
	 *
	 * @param array    $filters  Filters.
	 * @param string[] $expected Expected ids (price ascending).
	 */
	public function test_filters( array $filters, array $expected ): void {
		$this->fixture();
		$result = Repository::search( $filters, 1, 48, 'price_asc' );
		$this->assertSame( $expected, array_column( $result['items'], 'external_id' ) );
		$this->assertSame( count( $expected ), $result['total'] );
	}

	/**
	 * Filter cases.
	 */
	public function filter_cases(): array {
		return array(
			'make'           => array( array( 'make' => 'audi' ), array( '4', '5' ) ),
			'make and model' => array( array( 'make' => 'bmw', 'model' => 'm3' ), array( '3' ) ),
			'price range'    => array( array( 'price_from' => 30000, 'price_to' => 55000 ), array( '1', '6', '2' ) ),
			'year from'      => array( array( 'year_from' => 2021 ), array( '4', '6', '2', '5', '3' ) ),
			'mileage to'     => array( array( 'mileage_to' => 100 ), array( '3' ) ),
			'fuel'           => array( array( 'fuel' => 'electric' ), array( '6' ) ),
			'body type'      => array( array( 'body_type' => 'estate' ), array( '4' ) ),
			'condition'      => array( array( 'condition' => 'new' ), array( '3' ) ),
			'warranty'       => array( array( 'has_warranty' => 1 ), array( '2', '5' ) ),
			'keyword'        => array( array( 'version' => 'xDrive' ), array( '4', '1', '6', '2', '5', '3' ) ),
			'no match'       => array( array( 'make' => 'fiat' ), array() ),
			'invalid number' => array( array( 'price_to' => 'abc' ), array( '4', '1', '6', '2', '5', '3' ) ),
		);
	}

	public function test_unknown_sort_falls_back_to_newest(): void {
		$this->fixture();
		$result = Repository::search( array(), 1, 10, '1; DROP TABLE x' );
		$this->assertSame( 6, $result['total'] );
	}

	public function test_missing_vehicles_are_deactivated_after_a_sync(): void {
		$this->fixture();
		$this->store( array( $this->vehicle( 1 ), $this->vehicle( 2 ) ), 'next' );

		$this->assertSame( 4, Repository::deactivate_missing( 'default', 'next' ) );
		$this->assertSame( 2, Repository::count_active() );
		$this->assertNull( Repository::get_vehicle( 3 ) );
		$this->assertNotNull( Repository::get_vehicle( 3, false ) );
	}

	public function test_upsert_updates_existing_rows(): void {
		$this->fixture();
		$this->store( array( $this->vehicle( 2, array( 'price' => 1234.0 ) ) ), 'next' );

		$this->assertSame( 1234.0, (float) Repository::get_vehicle( 2 )['price'] );
		$this->assertSame( 6, Repository::count_active() );
	}

	public function test_facets_count_makes_and_models_ignoring_make_filter(): void {
		$this->fixture();

		$facets = Repository::facets( array( 'make' => 'bmw', 'fuel' => 'petrol' ) );

		$this->assertSame( array( 'bmw' => 2 ), $facets['makes'] );
		$this->assertSame( array( '3-series' => 1, 'm3' => 1 ), $facets['models']['bmw'] );

		$all = Repository::facets( array() );
		$this->assertSame( 3, $all['makes']['bmw'] );
		$this->assertSame( 2, $all['makes']['audi'] );
	}

	public function test_make_tree_is_sorted_with_counts(): void {
		$this->fixture();
		$tree = Repository::make_tree();

		$this->assertSame( array( 'Audi', 'BMW', 'VW' ), array_column( $tree, 'label' ) );
		$this->assertSame( 3, $tree[1]['count'] );
		$this->assertSame( array( '3 Series', 'M3', 'X5' ), array_column( $tree[1]['models'], 'label' ) );
	}

	public function test_filter_options_contain_bounds(): void {
		$this->fixture();
		$options = Repository::filter_options();

		$this->assertSame( array( 'min' => 25000, 'max' => 80000 ), $options['bounds']['price'] );
		$this->assertSame( array( 'min' => 2016, 'max' => 2024 ), $options['bounds']['year'] );
		$this->assertContains( 'electric', array_column( $options['fuels'], 'value' ) );
	}

	public function test_details_are_stored_and_listed_for_refresh(): void {
		$this->fixture();

		$this->assertCount( 6, Repository::vehicles_needing_details( 'default', 10 ) );

		Repository::store_details( 'default', 2, array( 'description' => 'Hello' ) );
		$row = Repository::get_vehicle( 2 );

		$this->assertSame( 'Hello', json_decode( $row['detail_json'], true )['description'] );
		$this->assertCount( 5, Repository::vehicles_needing_details( 'default', 10 ) );
	}
}
