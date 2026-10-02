<?php
/**
 * Translatable front-end labels.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All visitor-facing texts, translated through the plugin text domain.
 */
final class Labels {

	/**
	 * Interface labels used by the template and the inventory script.
	 *
	 * @return array<string, string>
	 */
	public static function ui(): array {
		return array(
			'vehicle_search'    => __( 'Vehicle search', 'dealer-inventory-for-autoscout24' ),
			'powered_by'        => __( 'Listings from AutoScout24', 'dealer-inventory-for-autoscout24' ),
			'open_dealer_page'  => __( 'View our dealer page on AutoScout24', 'dealer-inventory-for-autoscout24' ),
			'open_dealer_short' => __( 'AutoScout24', 'dealer-inventory-for-autoscout24' ),
			'advanced'          => __( 'More filters', 'dealer-inventory-for-autoscout24' ),
			'reset'             => __( 'Reset filters', 'dealer-inventory-for-autoscout24' ),
			'all'               => __( 'All', 'dealer-inventory-for-autoscout24' ),
			'any'               => __( 'Any', 'dealer-inventory-for-autoscout24' ),
			'make_search'       => __( 'Search make', 'dealer-inventory-for-autoscout24' ),
			'no_make_results'   => __( 'No make found.', 'dealer-inventory-for-autoscout24' ),
			'version_hint'      => __( 'e.g. Turbo, GTI, Quattro', 'dealer-inventory-for-autoscout24' ),
			'apply'             => __( 'Show results', 'dealer-inventory-for-autoscout24' ),
			'close'             => __( 'Close', 'dealer-inventory-for-autoscout24' ),
			'sort_by'           => __( 'Sort by', 'dealer-inventory-for-autoscout24' ),
			'filters'           => __( 'Filters', 'dealer-inventory-for-autoscout24' ),
			'view_vehicle'      => __( 'View vehicle', 'dealer-inventory-for-autoscout24' ),
			'pagination'        => __( 'Result pages', 'dealer-inventory-for-autoscout24' ),
			'previous'          => __( 'Previous', 'dealer-inventory-for-autoscout24' ),
			'next'              => __( 'Next', 'dealer-inventory-for-autoscout24' ),
			/* translators: %d: page number. */
			'page_n'            => __( 'Page %d', 'dealer-inventory-for-autoscout24' ),
			'empty_title'       => __( 'No vehicles found.', 'dealer-inventory-for-autoscout24' ),
			'empty_text'        => __( 'Try changing or resetting the filters.', 'dealer-inventory-for-autoscout24' ),
			'error_title'       => __( 'The vehicle list could not be loaded.', 'dealer-inventory-for-autoscout24' ),
			'error_text'        => __( 'Please try again in a moment.', 'dealer-inventory-for-autoscout24' ),
			'retry'             => __( 'Try again', 'dealer-inventory-for-autoscout24' ),
			'loading'           => __( 'Loading vehicles…', 'dealer-inventory-for-autoscout24' ),
			'opens_new_tab'     => __( '(opens in a new tab)', 'dealer-inventory-for-autoscout24' ),
			'warranty_badge'    => __( 'Warranty', 'dealer-inventory-for-autoscout24' ),
		);
	}

	/**
	 * "12 vehicles" with the correct plural form.
	 *
	 * @param int $count Number of vehicles.
	 */
	public static function vehicle_count( int $count ): string {
		/* translators: %s: number of vehicles. */
		return sprintf( _n( '%s vehicle', '%s vehicles', $count, 'dealer-inventory-for-autoscout24' ), number_format_i18n( $count ) );
	}

	/**
	 * The noun part only ("vehicle" / "vehicles"), for the live counter.
	 *
	 * @param int $count Number of vehicles.
	 */
	public static function vehicle_noun( int $count ): string {
		return trim( str_replace( number_format_i18n( $count ), '', self::vehicle_count( $count ) ) );
	}

	/**
	 * Human label for a marketplace enum value.
	 *
	 * Unknown values are shown in a readable form instead of their key.
	 *
	 * @param string $group Enum group: fuel, transmission, body, drive, condition, category, color.
	 * @param string $value Enum value.
	 */
	public static function enum( string $group, string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		$maps = self::enum_maps();
		if ( isset( $maps[ $group ][ $value ] ) ) {
			return $maps[ $group ][ $value ];
		}

		return ucwords( str_replace( array( '-', '_' ), ' ', $value ) );
	}

	/**
	 * Enum labels per group, cached per locale.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function enum_maps(): array {
		static $cache = array();
		$locale       = determine_locale();
		if ( isset( $cache[ $locale ] ) ) {
			return $cache[ $locale ];
		}

		$cache[ $locale ] = array(
			'fuel'         => array(
				'petrol'             => _x( 'Petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'diesel'             => _x( 'Diesel', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'electric'           => _x( 'Electric', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'hydrogen'           => _x( 'Hydrogen', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'cng-petrol'         => _x( 'Natural gas / petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'lpg-petrol'         => _x( 'LPG / petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'ethanol-petrol'     => _x( 'Ethanol / petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'hev-petrol'         => _x( 'Full hybrid petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'hev-diesel'         => _x( 'Full hybrid diesel', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'mhev-petrol'        => _x( 'Mild hybrid petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'mhev-diesel'        => _x( 'Mild hybrid diesel', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'phev-petrol'        => _x( 'Plug-in hybrid petrol', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'phev-diesel'        => _x( 'Plug-in hybrid diesel', 'fuel type', 'dealer-inventory-for-autoscout24' ),
				'two-stroke-mixture' => _x( 'Two-stroke mixture', 'fuel type', 'dealer-inventory-for-autoscout24' ),
			),
			'transmission' => array(
				'automatic'          => _x( 'Automatic', 'transmission', 'dealer-inventory-for-autoscout24' ),
				'automatic-stepless' => _x( 'Continuously variable automatic', 'transmission', 'dealer-inventory-for-autoscout24' ),
				'semi-automatic'     => _x( 'Semi-automatic', 'transmission', 'dealer-inventory-for-autoscout24' ),
				'manual'             => _x( 'Manual', 'transmission', 'dealer-inventory-for-autoscout24' ),
			),
			'body'         => array(
				'cabriolet'         => _x( 'Convertible', 'body type', 'dealer-inventory-for-autoscout24' ),
				'coupe'             => _x( 'Coupé', 'body type', 'dealer-inventory-for-autoscout24' ),
				'estate'            => _x( 'Estate', 'body type', 'dealer-inventory-for-autoscout24' ),
				'minivan'           => _x( 'Van / minivan', 'body type', 'dealer-inventory-for-autoscout24' ),
				'pickup'            => _x( 'Pickup', 'body type', 'dealer-inventory-for-autoscout24' ),
				'saloon'            => _x( 'Saloon', 'body type', 'dealer-inventory-for-autoscout24' ),
				'small-car'         => _x( 'Small car', 'body type', 'dealer-inventory-for-autoscout24' ),
				'suv'               => _x( 'SUV / off-roader', 'body type', 'dealer-inventory-for-autoscout24' ),
				'car'               => _x( 'Passenger car', 'body type', 'dealer-inventory-for-autoscout24' ),
				'other'             => _x( 'Other', 'body type', 'dealer-inventory-for-autoscout24' ),
				'box'               => _x( 'Panel van', 'body type', 'dealer-inventory-for-autoscout24' ),
				'box-double-cab'    => _x( 'Panel van, double cab', 'body type', 'dealer-inventory-for-autoscout24' ),
				'box-glazed'        => _x( 'Glazed van', 'body type', 'dealer-inventory-for-autoscout24' ),
				'bridge'            => _x( 'Dropside', 'body type', 'dealer-inventory-for-autoscout24' ),
				'bridge-double-cab' => _x( 'Dropside, double cab', 'body type', 'dealer-inventory-for-autoscout24' ),
				'bus'               => _x( 'Bus', 'body type', 'dealer-inventory-for-autoscout24' ),
				'chassis-cab'       => _x( 'Chassis cab', 'body type', 'dealer-inventory-for-autoscout24' ),
				'platform'          => _x( 'Platform', 'body type', 'dealer-inventory-for-autoscout24' ),
				'reefer'            => _x( 'Refrigerated', 'body type', 'dealer-inventory-for-autoscout24' ),
				'suitcase'          => _x( 'Box body', 'body type', 'dealer-inventory-for-autoscout24' ),
				'tipper'            => _x( 'Tipper', 'body type', 'dealer-inventory-for-autoscout24' ),
				'uploader'          => _x( 'Body builder', 'body type', 'dealer-inventory-for-autoscout24' ),
				'wood-transporter'  => _x( 'Timber transporter', 'body type', 'dealer-inventory-for-autoscout24' ),
				'alcove'            => _x( 'Alcove motorhome', 'body type', 'dealer-inventory-for-autoscout24' ),
				'integrated'        => _x( 'Integrated motorhome', 'body type', 'dealer-inventory-for-autoscout24' ),
				'semi-integrated'   => _x( 'Semi-integrated motorhome', 'body type', 'dealer-inventory-for-autoscout24' ),
				'motorhome'         => _x( 'Motorhome', 'body type', 'dealer-inventory-for-autoscout24' ),
				'caravan'           => _x( 'Caravan', 'body type', 'dealer-inventory-for-autoscout24' ),
			),
			'drive'        => array(
				'all'   => _x( 'All-wheel drive', 'drive type', 'dealer-inventory-for-autoscout24' ),
				'front' => _x( 'Front-wheel drive', 'drive type', 'dealer-inventory-for-autoscout24' ),
				'rear'  => _x( 'Rear-wheel drive', 'drive type', 'dealer-inventory-for-autoscout24' ),
			),
			'condition'    => array(
				'new'            => _x( 'New', 'vehicle condition', 'dealer-inventory-for-autoscout24' ),
				'used'           => _x( 'Used', 'vehicle condition', 'dealer-inventory-for-autoscout24' ),
				'demonstration'  => _x( 'Demonstrator', 'vehicle condition', 'dealer-inventory-for-autoscout24' ),
				'oldtimer'       => _x( 'Classic', 'vehicle condition', 'dealer-inventory-for-autoscout24' ),
				'pre-registered' => _x( 'Pre-registered', 'vehicle condition', 'dealer-inventory-for-autoscout24' ),
			),
			'category'     => array(
				'car'        => _x( 'Car', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
				'utility'    => _x( 'Utility vehicle', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
				'motorcycle' => _x( 'Motorcycle', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
				'truck'      => _x( 'Truck', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
				'camper'     => _x( 'Camper', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
				'trailer'    => _x( 'Trailer', 'vehicle category', 'dealer-inventory-for-autoscout24' ),
			),
		);

		return $cache[ $locale ];
	}
}
