<?php
/**
 * Shared helpers for the plugin tests.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Repository;
use DealerInventory\Schema;
use DealerInventory\Settings;

/**
 * Base test case with a small fixture inventory.
 */
abstract class Test_Case extends \WP_UnitTestCase {

	/**
	 * Reset plugin state before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( Settings::OPTION );
		Settings::reset_cache();
		Schema::reset();
		Repository::invalidate_public_cache();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down(): void {
		Settings::reset_cache();
		Repository::invalidate_public_cache();
		parent::tear_down();
	}

	/**
	 * A normalized vehicle row.
	 *
	 * @param int   $id        External id.
	 * @param array $overrides Column values.
	 * @return array<string, mixed>
	 */
	protected function vehicle( int $id, array $overrides = array() ): array {
		return array_merge(
			array(
				'connection_id'           => 'default',
				'external_id'             => $id,
				'seller_id'               => 1,
				'seller_vehicle_id'       => 'REF' . $id,
				'vehicle_category'        => 'car',
				'make_key'                => 'bmw',
				'make_name'               => 'BMW',
				'model_key'               => 'x5',
				'model_name'              => 'X5',
				'version_full_name'       => 'X5 xDrive30d',
				'teaser'                  => 'One owner',
				'price'                   => 50000.0,
				'previous_price'          => null,
				'list_price'              => null,
				'mileage'                 => 20000,
				'first_registration_date' => '2021-05-01',
				'first_registration_year' => 2021,
				'fuel_type'               => 'diesel',
				'transmission_type'       => 'automatic',
				'transmission_group'      => 'automatic',
				'body_type'               => 'suv',
				'condition_type'          => 'used',
				'drive_type'              => 'all',
				'horse_power'             => 286,
				'kilo_watts'              => 210,
				'consumption_combined'    => 6.5,
				'co2_emission'            => 170,
				'range_km'                => null,
				'image_url'               => 'https://images.autoscout24.ch/photo/' . $id . '.jpg',
				'images_json'             => null,
				'quali_logo_image_url'    => '',
				'leasing_monthly_rate'    => null,
				'has_warranty'            => 0,
				'source_order'            => $id,
			),
			$overrides
		);
	}

	/**
	 * Store vehicles as one sync batch.
	 *
	 * @param array[] $rows  Rows.
	 * @param string  $batch Batch id.
	 */
	protected function store( array $rows, string $batch = 'test' ): void {
		Repository::upsert_rows( $rows, $batch );

		// Warranty flags come from a separate API pass during a sync.
		$warranty = array();
		foreach ( $rows as $row ) {
			if ( ! empty( $row['has_warranty'] ) ) {
				$warranty[] = (int) $row['external_id'];
			}
		}
		if ( $warranty ) {
			Repository::set_warranty_flags( 'default', $warranty );
		}
		Repository::invalidate_public_cache();
	}

	/**
	 * Store the default fixture: 3 BMW, 2 Audi, 1 VW with different values.
	 */
	protected function fixture(): void {
		$this->store(
			array(
				$this->vehicle( 1, array( 'price' => 30000.0, 'mileage' => 90000, 'first_registration_year' => 2016, 'model_key' => '3-series', 'model_name' => '3 Series', 'fuel_type' => 'petrol' ) ),
				$this->vehicle( 2, array( 'price' => 55000.0, 'has_warranty' => 1 ) ),
				$this->vehicle( 3, array( 'price' => 80000.0, 'mileage' => 10, 'condition_type' => 'new', 'first_registration_year' => 2024, 'model_key' => 'm3', 'model_name' => 'M3', 'fuel_type' => 'petrol' ) ),
				$this->vehicle( 4, array( 'make_key' => 'audi', 'make_name' => 'Audi', 'model_key' => 'a4', 'model_name' => 'A4', 'price' => 25000.0, 'body_type' => 'estate' ) ),
				$this->vehicle( 5, array( 'make_key' => 'audi', 'make_name' => 'Audi', 'model_key' => 'q7', 'model_name' => 'Q7', 'price' => 70000.0, 'previous_price' => 75000.0, 'has_warranty' => 1 ) ),
				$this->vehicle( 6, array( 'make_key' => 'volkswagen', 'make_name' => 'VW', 'model_key' => 'id-4', 'model_name' => 'ID.4', 'price' => 40000.0, 'fuel_type' => 'electric', 'range_km' => 500 ) ),
			)
		);
	}
}
