<?php
/**
 * Range filter (price, first registration, mileage, power): two inputs, or a
 * dual slider when range_style is "slider". The number inputs carry the
 * values, so the filter also works without JavaScript.
 *
 * Override: yourtheme/dealer-inventory/parts/filter-range.php
 *
 * @package DealerInventory
 *
 * @var string $range          Range key: price, year, mileage or power.
 * @var array  $config         Instance configuration.
 * @var string $instance       Instance name.
 * @var array  $filters        Active filters.
 * @var array  $preset_filters Filters fixed by the shortcode / block.
 * @var array  $options        Filter options (with "bounds" and "years").
 * @var array  $labels         Interface labels.
 * @var string $uid            Unique element id.
 */

use DealerInventory\Format;
use DealerInventory\Schema;
use DealerInventory\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_bounds = $options['bounds'][ $range ] ?? null;
$dinv_from   = $filters[ $range . '_from' ] ?? '';
$dinv_to     = $filters[ $range . '_to' ] ?? '';

// Nothing to choose from and nothing selected.
if ( null === $dinv_bounds && '' === $dinv_from && '' === $dinv_to ) {
	return;
}

$dinv_min = (int) ( $dinv_bounds['min'] ?? 0 );
$dinv_max = (int) ( $dinv_bounds['max'] ?? 0 );

// Preset bounds narrow the range.
if ( isset( $preset_filters[ $range . '_from' ] ) ) {
	$dinv_min = max( $dinv_min, (int) $preset_filters[ $range . '_from' ] );
}
if ( isset( $preset_filters[ $range . '_to' ] ) ) {
	$dinv_max = $dinv_max ? min( $dinv_max, (int) $preset_filters[ $range . '_to' ] ) : (int) $preset_filters[ $range . '_to' ];
}

$dinv_steps = array(
	'price'   => 500,
	'year'    => 1,
	'mileage' => 5000,
	'power'   => 10,
);
$dinv_step  = $dinv_steps[ $range ];

// Round the slider ends to whole steps.
if ( 'year' !== $range ) {
	$dinv_min = (int) ( floor( $dinv_min / $dinv_step ) * $dinv_step );
	$dinv_max = (int) ( ceil( $dinv_max / $dinv_step ) * $dinv_step );
}

$dinv_units = array(
	'price'   => Format::currency( (string) $config['currency'] ),
	'year'    => '',
	'mileage' => 'km',
	/* translators: Abbreviation of horsepower. */
	'power'   => _x( 'hp', 'power unit', 'dealer-inventory-for-autoscout24' ),
);
$dinv_unit  = $dinv_units[ $range ];
$dinv_label = Schema::filter_labels()[ $range ];
$dinv_id    = $uid . '-f-' . $range;
$dinv_style = 'slider' === $config['range_style'] && $dinv_max > $dinv_min ? 'slider' : 'inputs';
$dinv_value = static fn( $value ): string => '' === $value ? '' : (string) (int) $value;

$dinv_placeholder = static function ( string $end ) use ( $dinv_bounds, $dinv_min, $dinv_max, $range, $labels ): string {
	if ( ! $dinv_bounds ) {
		return $labels['any'];
	}
	$value = 'from' === $end ? $dinv_min : $dinv_max;
	return 'year' === $range ? (string) $value : number_format_i18n( $value );
};

$dinv_years = array();
if ( 'year' === $range ) {
	foreach ( (array) ( $options['years'] ?? array() ) as $dinv_year ) {
		if ( (int) $dinv_year >= $dinv_min && ( ! $dinv_max || (int) $dinv_year <= $dinv_max ) ) {
			$dinv_years[] = (int) $dinv_year;
		}
	}
}
?>
<fieldset
	class="dinv-field dinv-range dinv-range--<?php echo esc_attr( $range ); ?> dinv-range--<?php echo esc_attr( $dinv_style ); ?>"
	data-dinv-filter="<?php echo esc_attr( $range ); ?>"
	data-dinv-range="<?php echo esc_attr( $dinv_style ); ?>"
	data-min="<?php echo esc_attr( (string) $dinv_min ); ?>"
	data-max="<?php echo esc_attr( (string) $dinv_max ); ?>"
	data-step="<?php echo esc_attr( (string) $dinv_step ); ?>"
	data-unit="<?php echo esc_attr( $dinv_unit ); ?>"
>
	<legend class="dinv-field__label">
		<?php echo esc_html( $dinv_label ); ?>
		<?php if ( '' !== $dinv_unit && 'price' !== $range ) : ?>
			<span class="dinv-range__unit">(<?php echo esc_html( $dinv_unit ); ?>)</span>
		<?php endif; ?>
	</legend>

	<?php if ( 'slider' === $dinv_style ) : ?>
		<div class="dinv-range__sliders" data-dinv-sliders hidden>
			<div class="dinv-range__track" aria-hidden="true"><span class="dinv-range__fill" data-dinv-fill></span></div>
			<input type="range" class="dinv-range__slider dinv-range__slider--from" min="<?php echo esc_attr( (string) $dinv_min ); ?>" max="<?php echo esc_attr( (string) $dinv_max ); ?>" step="<?php echo esc_attr( (string) $dinv_step ); ?>" value="<?php echo esc_attr( '' === $dinv_from ? (string) $dinv_min : $dinv_value( $dinv_from ) ); ?>" data-dinv-slider="from" aria-label="<?php echo esc_attr( $dinv_label . ' ' . $labels['from'] ); ?>">
			<input type="range" class="dinv-range__slider dinv-range__slider--to" min="<?php echo esc_attr( (string) $dinv_min ); ?>" max="<?php echo esc_attr( (string) $dinv_max ); ?>" step="<?php echo esc_attr( (string) $dinv_step ); ?>" value="<?php echo esc_attr( '' === $dinv_to ? (string) $dinv_max : $dinv_value( $dinv_to ) ); ?>" data-dinv-slider="to" aria-label="<?php echo esc_attr( $dinv_label . ' ' . $labels['to'] ); ?>">
		</div>
	<?php endif; ?>

	<div class="dinv-range__inputs">
		<?php
		foreach ( array( 'from', 'to' ) as $dinv_end ) :
			$dinv_current = 'from' === $dinv_end ? $dinv_from : $dinv_to;
			$dinv_name    = Shortcode::request_key( $range . '_' . $dinv_end, $instance );
			?>
			<label class="dinv-range__end" for="<?php echo esc_attr( $dinv_id . '-' . $dinv_end ); ?>">
				<span class="dinv-range__end-label"><?php echo esc_html( $labels[ $dinv_end ] ); ?></span>
				<?php if ( 'year' === $range && 'inputs' === $dinv_style && $dinv_years ) : ?>
					<span class="dinv-select">
						<select id="<?php echo esc_attr( $dinv_id . '-' . $dinv_end ); ?>" name="<?php echo esc_attr( $dinv_name ); ?>" data-dinv-input="<?php echo esc_attr( $dinv_end ); ?>">
							<option value=""><?php echo esc_html( $labels['any'] ); ?></option>
							<?php foreach ( $dinv_years as $dinv_year ) : ?>
								<option value="<?php echo esc_attr( (string) $dinv_year ); ?>" <?php selected( $dinv_value( $dinv_current ), (string) $dinv_year ); ?>><?php echo esc_html( (string) $dinv_year ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				<?php else : ?>
					<span class="dinv-input-wrap<?php echo '' !== $dinv_unit && 'price' === $range ? ' has-unit' : ''; ?>">
						<?php if ( 'price' === $range && '' !== $dinv_unit ) : ?>
							<span class="dinv-input-wrap__unit" aria-hidden="true"><?php echo esc_html( $dinv_unit ); ?></span>
						<?php endif; ?>
						<input
							class="dinv-input"
							id="<?php echo esc_attr( $dinv_id . '-' . $dinv_end ); ?>"
							type="number"
							inputmode="numeric"
							name="<?php echo esc_attr( $dinv_name ); ?>"
							value="<?php echo esc_attr( $dinv_value( $dinv_current ) ); ?>"
							min="<?php echo esc_attr( 'year' === $range ? '1900' : '0' ); ?>"
							step="1"
							placeholder="<?php echo esc_attr( $dinv_placeholder( $dinv_end ) ); ?>"
							data-dinv-input="<?php echo esc_attr( $dinv_end ); ?>"
						>
					</span>
				<?php endif; ?>
			</label>
		<?php endforeach; ?>
	</div>
</fieldset>
