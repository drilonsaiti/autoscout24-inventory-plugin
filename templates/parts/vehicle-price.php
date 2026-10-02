<?php
/**
 * Price, previous price and monthly rate.
 *
 * Override: yourtheme/dealer-inventory/parts/vehicle-price.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === $vehicle['price'] && '' === $vehicle['monthly'] ) {
	return;
}
?>
<div class="dinv-price">
	<?php if ( '' !== $vehicle['price'] ) : ?>
		<span class="dinv-price__amount"><?php echo esc_html( $vehicle['price'] ); ?></span>
		<?php if ( '' !== $vehicle['price_old'] ) : ?>
			<del class="dinv-price__old"><?php echo esc_html( $vehicle['price_old'] ); ?></del>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( '' !== $vehicle['monthly'] ) : ?>
		<span class="dinv-price__monthly"><?php echo esc_html( $vehicle['monthly'] ); ?></span>
	<?php endif; ?>
</div>
