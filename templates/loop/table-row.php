<?php
/**
 * Vehicle in the "Table" layout (one table row).
 *
 * Override: yourtheme/dealer-inventory/loop/table-row.php
 *
 * @package DealerInventory
 *
 * @var array $vehicle View model (see Renderer::vehicle()).
 * @var array $config  Instance configuration.
 * @var array $labels  Interface labels.
 */

use DealerInventory\Renderer;
use DealerInventory\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_fields = $vehicle['fields'];
$dinv_names  = Schema::card_field_labels();
$dinv_image  = $vehicle['images'][0] ?? null;
?>
<tr class="dinv-vehicle dinv-table__row" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['id'] ); ?>">
	<?php if ( isset( $dinv_fields['image'] ) ) : ?>
		<td class="dinv-table__image">
			<?php if ( $dinv_image ) : ?>
				<img src="<?php echo esc_url( $dinv_image['src'] ); ?>" alt="" loading="lazy" decoding="async" width="160" height="120">
			<?php endif; ?>
		</td>
	<?php endif; ?>
	<?php if ( isset( $dinv_fields['title'] ) ) : ?>
		<th scope="row" class="dinv-table__title">
			<?php if ( '' !== $vehicle['url'] ) : ?>
				<a class="dinv-vehicle__link" <?php echo $vehicle['link_attrs']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer::vehicle(). ?>><?php echo esc_html( $vehicle['title'] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $vehicle['title'] ); ?>
			<?php endif; ?>
			<?php if ( $vehicle['badges'] ) : ?>
				<span class="dinv-badges dinv-badges--inline">
					<?php foreach ( $vehicle['badges'] as $dinv_key => $dinv_label ) : ?>
						<span class="dinv-badge dinv-badge--<?php echo esc_attr( $dinv_key ); ?>"><?php echo esc_html( $dinv_label ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</th>
	<?php endif; ?>
	<?php foreach ( array( 'year', 'mileage', 'fuel', 'power' ) as $dinv_key ) : ?>
		<?php if ( isset( $dinv_fields[ $dinv_key ] ) ) : ?>
			<td class="dinv-table__<?php echo esc_attr( $dinv_key ); ?>" data-label="<?php echo esc_attr( $dinv_names[ $dinv_key ] ); ?>"><?php echo esc_html( $vehicle['specs'][ $dinv_key ] ?? '–' ); ?></td>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if ( isset( $dinv_fields['price'] ) ) : ?>
		<td class="dinv-table__price" data-label="<?php echo esc_attr( $dinv_names['price'] ); ?>">
			<strong><?php echo esc_html( '' !== $vehicle['price'] ? $vehicle['price'] : '–' ); ?></strong>
			<?php if ( '' !== $vehicle['monthly'] ) : ?>
				<small><?php echo esc_html( $vehicle['monthly'] ); ?></small>
			<?php endif; ?>
		</td>
	<?php endif; ?>
	<?php if ( isset( $dinv_fields['button'] ) && 'none' !== $config['link_to'] ) : ?>
		<td class="dinv-table__action">
			<?php if ( '' !== $vehicle['url'] ) : ?>
				<a class="dinv-icon-link" <?php echo $vehicle['link_attrs']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer::vehicle(). ?> aria-label="<?php echo esc_attr( $vehicle['button'] . ': ' . $vehicle['title'] ); ?>"><?php echo Renderer::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></a>
			<?php endif; ?>
		</td>
	<?php endif; ?>
</tr>
