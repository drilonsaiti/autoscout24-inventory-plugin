<?php
/**
 * Vehicle row helpers.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small, presentation-independent helpers for vehicle rows.
 */
final class Vehicle {

	/**
	 * "Make Model Version" without repeating the model when the version
	 * already starts with it ("BMW X6 X6 M50i" becomes "BMW X6 M50i").
	 *
	 * @param array $vehicle Vehicle row.
	 */
	public static function title( array $vehicle ): string {
		$make    = trim( sanitize_text_field( (string) ( $vehicle['make_name'] ?? '' ) ) );
		$model   = trim( sanitize_text_field( (string) ( $vehicle['model_name'] ?? '' ) ) );
		$version = trim( sanitize_text_field( (string) ( $vehicle['version_full_name'] ?? '' ) ) );

		if ( '' !== $model && '' !== $version ) {
			$model_normalized   = (string) preg_replace( '/\s+/', ' ', mb_strtolower( $model ) );
			$version_normalized = (string) preg_replace( '/\s+/', ' ', mb_strtolower( $version ) );
			if ( str_starts_with( $version_normalized, $model_normalized ) ) {
				$model = '';
			}
		}

		return trim( implode( ' ', array_filter( array( $make, $model, $version ) ) ) );
	}
}
