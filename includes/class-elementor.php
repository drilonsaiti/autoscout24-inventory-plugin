<?php
/**
 * Elementor integration.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Vehicle Inventory" widget when Elementor is active.
 * The widget class extends Elementor's base class, so it is only loaded
 * from Elementor's own hook.
 */
final class Elementor {

	public const WIDGET = 'dinv_inventory';

	/**
	 * Register the widget.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public static function register( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) || ! is_object( $widgets_manager ) || ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}
		$widgets_manager->register( new Integrations\Elementor_Widget() );
	}
}
