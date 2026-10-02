<?php
/**
 * Vehicle in the "Compact grid" layout.
 *
 * Override: yourtheme/dealer-inventory/loop/grid.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model (see Renderer::vehicle()).
 * @var array $config  Instance configuration.
 * @var array $labels  Interface labels.
 */

use DealerInventory\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_parts = array(
	'vehicle' => $vehicle,
	'labels'  => $labels,
);
?>
<article class="dinv-vehicle dinv-tile" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['id'] ); ?>">
	<?php if ( isset( $vehicle['fields']['image'] ) ) : ?>
		<?php echo Template::render( 'parts/vehicle-media.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
	<?php endif; ?>
	<div class="dinv-tile__body">
		<?php echo Template::render( 'parts/vehicle-title.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
		<?php if ( $vehicle['specs'] ) : ?>
			<p class="dinv-tile__specs"><?php echo esc_html( implode( ' · ', $vehicle['specs'] ) ); ?></p>
		<?php endif; ?>
		<?php echo Template::render( 'parts/vehicle-price.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
	</div>
</article>
