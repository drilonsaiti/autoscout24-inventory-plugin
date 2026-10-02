<?php
/**
 * Live preview for the admin (design screen and shortcode builder).
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders one inventory on a bare front-end page with the theme's styles,
 * for an iframe in the admin. Only for users who can manage the plugin;
 * unsaved settings come from the query string and are never stored.
 */
final class Preview {

	public const ACTION = 'dinv_preview';

	/**
	 * Preview URL.
	 *
	 * @param array $atts   Shortcode attributes.
	 * @param array $design Unsaved site-wide settings.
	 */
	public static function url( array $atts = array(), array $design = array() ): string {
		return add_query_arg(
			array(
				self::ACTION => 1,
				'_wpnonce'   => wp_create_nonce( self::ACTION ),
				'atts'       => $atts,
				'settings'   => $design,
			),
			home_url( '/' )
		);
	}

	/**
	 * Output the preview page and stop.
	 */
	public static function maybe_render(): void {
		if ( ! isset( $_GET[ self::ACTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checked below.
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), self::ACTION ) ) {
			wp_die( esc_html__( 'The preview link has expired. Reload the settings page.', 'dealer-inventory-for-autoscout24' ), '', array( 'response' => 403 ) );
		}

		$settings = isset( $_GET['settings'] ) && is_array( $_GET['settings'] ) ? map_deep( wp_unslash( $_GET['settings'] ), 'sanitize_text_field' ) : array();
		$atts     = isset( $_GET['atts'] ) && is_array( $_GET['atts'] ) ? map_deep( wp_unslash( $_GET['atts'] ), 'sanitize_text_field' ) : array();

		if ( $settings ) {
			Settings::preview( $settings );
		}

		$atts              = array_filter(
			array_map( static fn( $value ) => is_array( $value ) ? implode( ',', $value ) : (string) $value, $atts ),
			static fn( $value ) => '' !== $value
		);
		$atts['url_state'] = 'false';

		nocache_headers();
		add_filter( 'show_admin_bar', '__return_false' );
		add_filter(
			'wp_robots',
			static function ( array $robots ): array {
				$robots['noindex'] = true;
				return $robots;
			}
		);

		// Render first so the inventory's styles are enqueued before wp_head.
		$html = Shortcode::render_inventory( Schema::resolve( $atts ) );
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	<style>body.dinv-preview{margin:0;padding:24px}.dinv-preview__main a{pointer-events:none}</style>
</head>
<body <?php body_class( 'dinv-preview' ); ?>>
	<main class="dinv-preview__main">
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by the plugin templates. ?>
	</main>
		<?php wp_footer(); ?>
</body>
</html>
		<?php
		exit;
	}
}
