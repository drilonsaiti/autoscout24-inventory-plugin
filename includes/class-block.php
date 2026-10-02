<?php
/**
 * Gutenberg block.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Vehicle Inventory" block. Its controls are generated from the settings
 * schema in the editor, and it renders through the same code as the
 * shortcode. All overrides live in one "settings" attribute; empty values
 * inherit the site-wide settings.
 */
final class Block {

	public const NAME = 'dealer-inventory/inventory';

	public const EDITOR_HANDLE = 'dinv-block-editor';

	/**
	 * Register the block and its editor script.
	 */
	public static function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			self::EDITOR_HANDLE,
			DINV_PLUGIN_URL . 'blocks/inventory/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			DINV_VERSION,
			true
		);
		wp_set_script_translations( self::EDITOR_HANDLE, 'dealer-inventory-for-autoscout24', DINV_PLUGIN_DIR . 'languages' );

		register_block_type(
			DINV_PLUGIN_DIR . 'blocks/inventory',
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);

		add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_data' ) );
	}

	/**
	 * Schema and design tokens for the editor.
	 */
	public static function editor_data(): void {
		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.DinvBlock = ' . wp_json_encode(
				array(
					'schema'      => Schema::editor_schema(),
					'settingsUrl' => admin_url( 'admin.php?page=dinv-display' ),
				)
			) . ';',
			'before'
		);
		wp_add_inline_style( Assets::HANDLE, Design::css() . '.editor-styles-wrapper .dinv-inventory a,.editor-styles-wrapper .dinv-inventory button,.editor-styles-wrapper .dinv-inventory select,.editor-styles-wrapper .dinv-inventory input{pointer-events:none}' );
	}

	/**
	 * Shortcode attributes from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<string, string>
	 */
	public static function to_atts( array $attributes ): array {
		$atts = array();
		foreach ( (array) ( $attributes['settings'] ?? array() ) as $key => $value ) {
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				$atts[ sanitize_key( (string) $key ) ] = (string) $value;
			}
		}
		return $atts;
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 */
	public static function render( $attributes ): string {
		$html = Shortcode::render( self::to_atts( (array) $attributes ) );
		if ( '' === $html ) {
			return '';
		}
		return '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
	}
}
