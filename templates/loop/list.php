<?php
/**
 * Vehicle in the "List" layout.
 *
 * Override: yourtheme/dealer-inventory/loop/list.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model (see Renderer::vehicle()).
 * @var array $config  Instance configuration.
 * @var array $labels  Interface labels.
 */

use DealerInventory\Renderer;
use DealerInventory\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_parts = array(
	'vehicle' => $vehicle,
	'labels'  => $labels,
);
?>
<article class="dinv-vehicle dinv-row" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['id'] ); ?>">
	<?php if ( isset( $vehicle['fields']['image'] ) ) : ?>
		<?php echo Template::render( 'parts/vehicle-media.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
	<?php endif; ?>
	<div class="dinv-row__body">
		<div class="dinv-row__main">
			<?php if ( '' !== $vehicle['label'] ) : ?>
				<span class="dinv-card__label"><?php echo esc_html( $vehicle['label'] ); ?></span>
			<?php endif; ?>
			<?php echo Template::render( 'parts/vehicle-title.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
			<?php if ( '' !== $vehicle['teaser'] ) : ?>
				<p class="dinv-vehicle__teaser"><?php echo esc_html( $vehicle['teaser'] ); ?></p>
			<?php endif; ?>
			<?php if ( $vehicle['specs'] ) : ?>
				<ul class="dinv-specs">
					<?php foreach ( $vehicle['specs'] as $dinv_type => $dinv_text ) : ?>
						<li class="dinv-spec dinv-spec--<?php echo esc_attr( $dinv_type ); ?>"><?php echo Renderer::icon( $dinv_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?><span><?php echo esc_html( $dinv_text ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="dinv-row__side">
			<?php echo Template::render( 'parts/vehicle-price.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
			<?php echo Template::render( 'parts/vehicle-button.php', $dinv_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output. ?>
		</div>
	</div>
</article>
