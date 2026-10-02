<?php
/**
 * Inventory header bar.
 *
 * Override: yourtheme/dealer-inventory/parts/header.php
 *
 * @package DealerInventory
 *
 * @var array  $config     Instance configuration.
 * @var array  $labels     Interface labels.
 * @var string $dealer_url Dealer page URL.
 */

use DealerInventory\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_title = '' !== (string) $config['header_title']
	? (string) $config['header_title']
	: get_bloginfo( 'name' ) . ' · ' . $labels['vehicle_search'];
?>
<header class="dinv-header">
	<div class="dinv-header__text">
		<strong class="dinv-header__title"><?php echo esc_html( $dinv_title ); ?></strong>
		<?php if ( $config['show_powered_by'] ) : ?>
			<span class="dinv-header__note"><?php echo esc_html( $labels['powered_by'] ); ?></span>
		<?php endif; ?>
	</div>
	<?php if ( $config['show_dealer_link'] && '' !== $dealer_url ) : ?>
		<a class="dinv-header__link" href="<?php echo esc_url( $dealer_url ); ?>" target="_blank" rel="noopener">
			<span class="dinv-header__link-long"><?php echo esc_html( $labels['open_dealer_page'] ); ?></span>
			<span class="dinv-header__link-short" aria-hidden="true"><?php echo esc_html( $labels['open_dealer_short'] ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( $labels['opens_new_tab'] ); ?></span>
			<?php echo Renderer::icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
		</a>
	<?php endif; ?>
</header>
