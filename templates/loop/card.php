<?php
/**
 * Vehicle in the "Cards" layout.
 *
 * Override: yourtheme/dealer-inventory/loop/card.php
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
<article class="dinv-vehicle dinv-card" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['id'] ); ?>">
	<?php if ( isset( $vehicle['fields']['image'] ) ) : ?>
		<?php echo Template::render( 'parts/vehicle-media.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
	<?php endif; ?>
	<div class="dinv-card__body">
		<?php if ( '' !== $vehicle['label'] ) : ?>
			<span class="dinv-card__label"><?php echo esc_html( $vehicle['label'] ); ?></span>
		<?php endif; ?>
		<?php echo Template::render( 'parts/vehicle-title.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
		<?php if ( '' !== $vehicle['teaser'] ) : ?>
			<p class="dinv-vehicle__teaser"><?php echo esc_html( $vehicle['teaser'] ); ?></p>
		<?php endif; ?>
		<?php if ( $vehicle['specs'] ) : ?>
			<ul class="dinv-chips">
				<?php foreach ( $vehicle['specs'] as $dinv_type => $dinv_text ) : ?>
					<li class="dinv-chip dinv-chip--<?php echo esc_attr( $dinv_type ); ?>"><?php echo esc_html( $dinv_text ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<div class="dinv-card__footer">
			<?php echo Template::render( 'parts/vehicle-price.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
			<?php echo Template::render( 'parts/vehicle-button.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
		</div>
	</div>
</article>
