<?php
/**
 * Front-end asset loading.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads CSS and JavaScript only on pages that show an inventory, and the
 * script only when an instance is interactive (filters, sort or pagination).
 */
final class Assets {

	public const HANDLE = 'dinv-inventory';

	/**
	 * Whether the current page contains an inventory.
	 *
	 * @var bool
	 */
	private static bool $page_has_inventory = false;

	/**
	 * Design CSS printed once.
	 *
	 * @var bool
	 */
	private static bool $design_added = false;

	/**
	 * Script data printed once.
	 *
	 * @var bool
	 */
	private static bool $script_localized = false;

	/**
	 * Detect inventories in the queried post (content and Elementor data)
	 * before wp_head, so styles land in the head and static blocks load no JS.
	 */
	public static function maybe_enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$post     = get_post( $post_id );
		$haystack = ( $post instanceof \WP_Post ? (string) $post->post_content : '' ) . "\n" . (string) get_post_meta( $post_id, '_elementor_data', true );

		if ( false === strpos( $haystack, '[' . Shortcode::TAG ) ) {
			return;
		}

		// Elementor stores widget content as JSON with escaped quotes; normalize
		// for detection only. Stored data is never modified.
		$haystack = html_entity_decode( str_replace( array( '\\"', "\\'" ), array( '"', "'" ), $haystack ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		preg_match_all( '/\[' . Shortcode::TAG . '\b([^\]]*)\]/i', $haystack, $matches );

		$with_filters = false;
		$with_script  = false;
		foreach ( $matches[1] ?? array() as $attributes ) {
			$parsed = shortcode_parse_atts( (string) $attributes );
			$config = Schema::resolve( is_array( $parsed ) ? $parsed : array() );

			$with_filters = $with_filters || $config['show_filters'];
			$with_script  = $with_script || $config['show_filters'] || $config['show_sort'] || $config['show_pagination'];
		}

		if ( $matches[1] ?? array() ) {
			self::$page_has_inventory = true;
			self::enqueue( $with_filters, $with_script );
		}
	}

	/**
	 * Enqueue the stylesheet, design tokens and (optionally) the script.
	 *
	 * @param bool $with_filters The page shows filters.
	 * @param bool $with_script  The page has an interactive instance.
	 */
	public static function enqueue( bool $with_filters, bool $with_script ): void {
		self::$page_has_inventory = true;

		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style( self::HANDLE, DINV_PLUGIN_URL . 'public/css/inventory.css', array(), DINV_VERSION );
			wp_register_script(
				self::HANDLE,
				DINV_PLUGIN_URL . 'public/js/inventory.js',
				array(),
				DINV_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		wp_enqueue_style( self::HANDLE );

		if ( ! self::$design_added ) {
			wp_add_inline_style( self::HANDLE, Design::css() );
			self::$design_added = true;
		}

		if ( ! $with_script ) {
			return;
		}

		wp_enqueue_script( self::HANDLE );

		if ( ! self::$script_localized ) {
			wp_localize_script(
				self::HANDLE,
				'DinvInventory',
				array(
					'restBase' => esc_url_raw( rest_url( 'dinv/v1' ) ),
					'labels'   => Labels::ui(),
				)
			);
			self::$script_localized = true;
		}
	}

	/**
	 * Preconnect to the image CDN on inventory pages.
	 *
	 * @param array  $urls          Resource hints.
	 * @param string $relation_type Relation type.
	 * @return array
	 */
	public static function resource_hints( array $urls, string $relation_type ): array {
		if ( ! self::$page_has_inventory || 'preconnect' !== $relation_type ) {
			return $urls;
		}

		foreach ( Connection::current()->provider()->asset_origins() as $origin ) {
			$urls[] = array(
				'href'        => $origin,
				'crossorigin' => 'anonymous',
			);
		}

		return $urls;
	}
}
