<?php
/**
 * Vehicle title (linked when the vehicle has a URL).
 *
 * Override: yourtheme/dealer-inventory/parts/vehicle-title.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $vehicle['fields']['title'] ) && ! isset( $vehicle['fields']['version'] ) ) {
	return;
}
?>
<h3 class="dinv-vehicle__title">
	<?php if ( '' !== $vehicle['url'] ) : ?>
		<a class="dinv-vehicle__link" <?php echo $vehicle['link_attrs']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer::vehicle(). ?>><?php echo esc_html( $vehicle['title'] ); ?></a>
	<?php else : ?>
		<?php echo esc_html( $vehicle['title'] ); ?>
	<?php endif; ?>
</h3>
