<?php
/**
 * Vehicle button.
 *
 * Override: yourtheme/dealer-inventory/parts/vehicle-button.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model.
 * @var array $labels  Interface labels.
 */

use DealerInventory\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === $vehicle['button'] ) {
	return;
}
?>
<a class="dinv-button dinv-button--primary dinv-vehicle__cta" <?php echo $vehicle['link_attrs']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer::vehicle(). ?>>
	<span><?php echo esc_html( $vehicle['button'] ); ?></span>
	<?php if ( $vehicle['new_tab'] ) : ?>
		<span class="screen-reader-text"><?php echo esc_html( $labels['opens_new_tab'] ); ?></span>
	<?php endif; ?>
	<?php echo Renderer::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
</a>
