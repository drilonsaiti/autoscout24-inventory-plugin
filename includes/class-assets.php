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
 * script only when an instance is interactive (filters, sort, pagination,
 * view or page-size switch) or a vehicle detail page is shown.
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
	 * Register the stylesheet and script (also used as block editor styles).
	 */
	public static function register(): void {
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

	/**
	 * Whether an inventory needs the script.
	 *
	 * @param array $config Resolved configuration.
	 */
	public static function is_interactive( array $config ): bool {
		return $config['show_filters'] || $config['show_sort'] || $config['show_pagination'] || $config['view_switcher'] || $config['per_page_selector'];
	}

	/**
	 * Detect inventories in the queried post (shortcodes, blocks and
	 * Elementor data) before wp_head, so styles land in the head and static
	 * inventories load no JavaScript.
	 */
	public static function maybe_enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		if ( Detail::is_detail_request() ) {
			self::enqueue( true );
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$post    = get_post( $post_id );
		$content = $post instanceof \WP_Post ? (string) $post->post_content : '';
		$configs = array();

		foreach ( self::find_blocks( parse_blocks( $content ) ) as $attributes ) {
			$configs[] = Schema::resolve( Block::to_atts( $attributes ) );
		}

		$haystack = $content . "\n" . (string) get_post_meta( $post_id, '_elementor_data', true );
		if ( false !== strpos( $haystack, '[' . Shortcode::TAG ) ) {
			// Elementor stores widget content as JSON with escaped quotes; normalize
			// for detection only. Stored data is never modified.
			$haystack = html_entity_decode( str_replace( array( '\\"', "\\'" ), array( '"', "'" ), $haystack ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			preg_match_all( '/\[' . Shortcode::TAG . '\b([^\]]*)\]/i', $haystack, $matches );
			foreach ( $matches[1] ?? array() as $attributes ) {
				$parsed    = shortcode_parse_atts( (string) $attributes );
				$configs[] = Schema::resolve( is_array( $parsed ) ? $parsed : array() );
			}
		}

		if ( ! $configs && false !== strpos( $haystack, '"widgetType":"' . Elementor::WIDGET ) ) {
			$configs[] = Schema::resolve( array() );
		}

		if ( ! $configs ) {
			return;
		}

		$script = false;
		foreach ( $configs as $config ) {
			$script = $script || self::is_interactive( $config );
		}
		self::enqueue( $script );
	}

	/**
	 * Attributes of every inventory block, including nested blocks.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array[]
	 */
	private static function find_blocks( array $blocks ): array {
		$found = array();
		foreach ( $blocks as $block ) {
			if ( Block::NAME === ( $block['blockName'] ?? '' ) ) {
				$found[] = (array) ( $block['attrs'] ?? array() );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find_blocks( $block['innerBlocks'] ) );
			}
		}
		return $found;
	}

	/**
	 * Enqueue the stylesheet, design tokens and (optionally) the script.
	 *
	 * @param bool $script The page has an interactive inventory or a detail view.
	 */
	public static function enqueue( bool $script ): void {
		self::$page_has_inventory = true;

		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			self::register();
		}

		wp_enqueue_style( self::HANDLE );

		if ( ! self::$design_added ) {
			wp_add_inline_style( self::HANDLE, Design::css() );
			self::$design_added = true;
		}

		if ( ! $script ) {
			return;
		}

		wp_enqueue_script( self::HANDLE );

		if ( ! self::$script_localized ) {
			wp_localize_script(
				self::HANDLE,
				'DinvInventory',
				array(
					'restBase' => esc_url_raw( rest_url( Rest::NAMESPACE ) ),
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
