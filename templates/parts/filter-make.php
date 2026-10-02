<?php
/**
 * Make and model control.
 *
 * Always renders two native selects (works without JavaScript). The script
 * turns them into a combined select or a searchable combobox depending on
 * data-dinv-make-mode.
 *
 * Override: yourtheme/dealer-inventory/parts/filter-make.php
 *
 * @package DealerInventory
 *
 * @var array  $config         Instance configuration.
 * @var string $instance       Instance name.
 * @var array  $filters        Active filters.
 * @var array  $preset_filters Filters fixed by the shortcode / block.
 * @var array  $tree           Makes with models.
 * @var array  $facets         Counts for the active filters.
 * @var array  $labels         Interface labels.
 * @var string $uid            Unique element id.
 */

use DealerInventory\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_mode       = (string) $config['make_model_mode'];
$dinv_make_fixed = isset( $preset_filters['make'] );
if ( isset( $preset_filters['model'] ) ) {
	return;
}

$dinv_make   = (string) ( $filters['make'] ?? '' );
$dinv_model  = (string) ( $filters['model'] ?? '' );
$dinv_counts = (bool) $config['filter_counts'];
$dinv_hide   = (bool) $config['hide_empty'];

$dinv_text = static function ( string $label, int $count ) use ( $dinv_counts ): string {
	return $dinv_counts ? sprintf( '%s (%s)', $label, number_format_i18n( $count ) ) : $label;
};

$dinv_models = array();
foreach ( $tree as $dinv_entry ) {
	if ( $dinv_entry['value'] === $dinv_make ) {
		$dinv_models = $dinv_entry['models'];
	}
}

if ( $dinv_make_fixed ) {
	$dinv_mode = 'separate';
	if ( count( $dinv_models ) < 2 && '' === $dinv_model ) {
		return;
	}
}
?>
<div
	class="dinv-field dinv-make-model dinv-make-model--<?php echo esc_attr( $dinv_mode ); ?>"
	data-dinv-filter="make"
	data-dinv-make-mode="<?php echo esc_attr( $dinv_mode ); ?>"
	data-counts="<?php echo $dinv_counts ? '1' : '0'; ?>"
	data-hide-empty="<?php echo $dinv_hide ? '1' : '0'; ?>"
	data-make-fixed="<?php echo esc_attr( $dinv_make_fixed ? $dinv_make : '' ); ?>"
>
	<div class="dinv-make-model__selects">
		<?php if ( ! $dinv_make_fixed ) : ?>
			<div class="dinv-make-model__make">
				<label class="dinv-field__label" for="<?php echo esc_attr( $uid ); ?>-make"><?php echo esc_html( $labels['make'] ); ?></label>
				<span class="dinv-select">
					<select id="<?php echo esc_attr( $uid ); ?>-make" name="<?php echo esc_attr( Shortcode::request_key( 'make', $instance ) ); ?>" data-dinv-make>
						<option value=""><?php echo esc_html( $labels['all_makes'] ); ?></option>
						<?php
						foreach ( $tree as $dinv_entry ) :
							$dinv_count    = (int) ( $facets['makes'][ $dinv_entry['value'] ] ?? 0 );
							$dinv_selected = $dinv_entry['value'] === $dinv_make;
							if ( 0 === $dinv_count && $dinv_hide && ! $dinv_selected ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $dinv_entry['value'] ); ?>" <?php selected( $dinv_selected ); ?> <?php disabled( 0 === $dinv_count && ! $dinv_selected ); ?>><?php echo esc_html( $dinv_text( $dinv_entry['label'], $dinv_count ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</div>
		<?php endif; ?>

		<div class="dinv-make-model__model">
			<label class="dinv-field__label" for="<?php echo esc_attr( $uid ); ?>-model"><?php echo esc_html( $labels['model'] ); ?></label>
			<span class="dinv-select">
				<select id="<?php echo esc_attr( $uid ); ?>-model" name="<?php echo esc_attr( Shortcode::request_key( 'model', $instance ) ); ?>" data-dinv-model <?php disabled( '' === $dinv_make ); ?>>
					<option value=""><?php echo esc_html( '' === $dinv_make ? $labels['choose_make_first'] : $labels['all_models'] ); ?></option>
					<?php
					foreach ( $dinv_models as $dinv_entry ) :
						$dinv_count    = (int) ( $facets['models'][ $dinv_make ][ $dinv_entry['value'] ] ?? 0 );
						$dinv_selected = $dinv_entry['value'] === $dinv_model;
						if ( 0 === $dinv_count && $dinv_hide && ! $dinv_selected ) {
							continue;
						}
						?>
						<option value="<?php echo esc_attr( $dinv_entry['value'] ); ?>" <?php selected( $dinv_selected ); ?> <?php disabled( 0 === $dinv_count && ! $dinv_selected ); ?>><?php echo esc_html( $dinv_text( $dinv_entry['label'], $dinv_count ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
	</div>

	<script type="application/json" data-dinv-tree>
	<?php
	echo wp_json_encode(
		array(
			'tree'   => $tree,
			'facets' => $facets,
		),
		JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
	); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HTML characters hex-encoded. 
	?>
	</script>
</div>
