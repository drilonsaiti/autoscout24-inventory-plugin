<?php
/**
 * Vehicle image, optional mini gallery and badges.
 *
 * Override: yourtheme/dealer-inventory/parts/vehicle-media.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model (see Renderer::vehicle()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_images = (array) $vehicle['images'];
$dinv_tag    = '' !== $vehicle['url'] ? 'a' : 'div';
$dinv_count  = count( $dinv_images );
?>
<<?php echo esc_html( $dinv_tag ); ?> class="dinv-media"<?php echo 'a' === $dinv_tag ? ' ' . $vehicle['link_attrs'] . ' tabindex="-1" aria-hidden="true"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link_attrs is escaped in Renderer::vehicle(). ?>>
	<?php if ( $dinv_images ) : ?>
		<span class="dinv-gallery<?php echo $dinv_count > 1 ? ' dinv-gallery--multi' : ''; ?>">
			<?php foreach ( $dinv_images as $dinv_image ) : ?>
				<img class="dinv-media__img" src="<?php echo esc_url( $dinv_image['src'] ); ?>"<?php echo '' !== $dinv_image['srcset'] ? ' srcset="' . esc_attr( $dinv_image['srcset'] ) . '" sizes="' . esc_attr( $vehicle['image_sizes'] ) . '"' : ''; ?> alt="<?php echo esc_attr( $dinv_image['alt'] ); ?>" loading="<?php echo esc_attr( $dinv_image['loading'] ); ?>"<?php echo 'eager' === $dinv_image['loading'] ? ' fetchpriority="high"' : ''; ?> decoding="async" width="768" height="576">
			<?php endforeach; ?>
			<?php if ( $dinv_count > 1 ) : ?>
				<span class="dinv-gallery__zones" aria-hidden="true"><?php echo str_repeat( '<span></span>', $dinv_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?></span>
				<span class="dinv-gallery__dots" aria-hidden="true"><?php echo str_repeat( '<i></i>', $dinv_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?></span>
			<?php endif; ?>
		</span>
	<?php else : ?>
		<span class="dinv-media__fallback" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( $vehicle['badges'] ) : ?>
		<span class="dinv-badges">
			<?php foreach ( $vehicle['badges'] as $dinv_key => $dinv_label ) : ?>
				<span class="dinv-badge dinv-badge--<?php echo esc_attr( $dinv_key ); ?>"><?php echo esc_html( $dinv_label ); ?></span>
			<?php endforeach; ?>
		</span>
	<?php endif; ?>
</<?php echo esc_html( $dinv_tag ); ?>>
