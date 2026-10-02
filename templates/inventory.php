<?php
/**
 * Inventory template.
 *
 * @package DealerInventory
 *
 * @var array                         $config        Resolved instance configuration.
 * @var string                        $instance      Instance name.
 * @var array                         $results       Search results.
 * @var array                         $filters       Active filters.
 * @var string                        $sort          Active sort.
 * @var array                         $options       Filter options.
 * @var array                         $labels        Interface labels.
 * @var \DealerInventory\Renderer     $renderer      Result renderer.
 * @var array                         $preset_params Preset filters as request parameters.
 * @var array                         $client_config Instance settings for REST refreshes.
 * @var string                        $dealer_url    Dealer page URL.
 * @var bool                          $interactive   Instance has filters, sort or pagination.
 */

use DealerInventory\Labels;
use DealerInventory\Renderer;
use DealerInventory\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_layout  = (string) $config['layout'];
$dinv_columns = max( 1, min( 4, (int) $config['columns'] ) );
$dinv_uid     = 'dinv-' . ( '' !== $instance ? $instance : wp_unique_id() );
$dinv_show    = static fn( string $filter ): bool => ! empty( $config[ 'show_' . $filter ] );
$dinv_filters = Schema::filter_labels();
$dinv_title   = '' !== (string) $config['header_title']
	? (string) $config['header_title']
	: get_bloginfo( 'name' ) . ' · ' . $labels['vehicle_search'];

/**
 * Select filter.
 *
 * @param string $name    Request name.
 * @param string $label   Label.
 * @param array  $choices value => label.
 * @param string $current Selected value.
 * @param string $extra   Extra attributes (static).
 */
$dinv_select = static function ( string $name, string $label, array $choices, string $current, string $extra = '' ) use ( $labels ): void {
	?>
	<label>
		<span><?php echo esc_html( $label ); ?></span>
		<select name="<?php echo esc_attr( $name ); ?>" <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string. ?>>
			<option value=""><?php echo esc_html( $labels['all'] ); ?></option>
			<?php foreach ( $choices as $value => $text ) : ?>
				<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $current, (string) $value ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
};

/**
 * Number filter.
 *
 * @param string $name    Request name.
 * @param string $label   Label.
 * @param mixed  $current Current value.
 * @param int    $step    Step.
 * @param string $extra   Extra attributes (static).
 */
$dinv_number = static function ( string $name, string $label, $current, int $step, string $extra = '' ) use ( $labels ): void {
	?>
	<label>
		<span><?php echo esc_html( $label ); ?></span>
		<input type="number" inputmode="numeric" min="0" step="<?php echo esc_attr( (string) $step ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( '' === $current ? '' : (string) (int) $current ); ?>" placeholder="<?php echo esc_attr( $labels['any'] ); ?>" <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string. ?>>
	</label>
	<?php
};

$dinv_choices = static function ( string $group, string $enum_group ) use ( $options ): array {
	$out = array();
	foreach ( (array) ( $options[ $group ] ?? array() ) as $row ) {
		$out[ (string) $row['value'] ] = Labels::enum( $enum_group, (string) $row['value'] );
	}
	return $out;
};

$dinv_years = array();
foreach ( (array) ( $options['years'] ?? array() ) as $dinv_year ) {
	$dinv_years[ (string) $dinv_year ] = (string) $dinv_year;
}
?>
<section
	id="<?php echo esc_attr( $dinv_uid ); ?>"
	class="dinv-inventory dinv-layout-<?php echo esc_attr( $dinv_layout ); ?> dinv-columns-<?php echo esc_attr( (string) $dinv_columns ); ?>"
	data-instance="<?php echo esc_attr( $instance ); ?>"
	data-interactive="<?php echo $interactive ? '1' : '0'; ?>"
	data-url-state="<?php echo $config['url_state'] ? '1' : '0'; ?>"
	data-show-pagination="<?php echo $config['show_pagination'] ? '1' : '0'; ?>"
	data-show-count="<?php echo $config['show_count'] ? '1' : '0'; ?>"
	data-preset-params="<?php echo esc_attr( (string) wp_json_encode( (object) $preset_params ) ); ?>"
	data-preset-sort="<?php echo esc_attr( (string) $config['sort'] ); ?>"
	data-config="<?php echo esc_attr( (string) wp_json_encode( (object) $client_config ) ); ?>"
	data-locale="<?php echo esc_attr( determine_locale() ); ?>"
	data-version="<?php echo esc_attr( (string) \DealerInventory\Sync::version() ); ?>"
	data-rendered="<?php echo esc_attr( (string) time() ); ?>"
	data-layout="<?php echo esc_attr( $dinv_layout ); ?>"
>
	<?php if ( $config['show_header'] ) : ?>
		<header class="dinv-inventory__brandbar">
			<div>
				<strong><?php echo esc_html( $dinv_title ); ?></strong>
				<?php if ( $config['show_powered_by'] ) : ?>
					<span><?php echo esc_html( $labels['powered_by'] ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $config['show_dealer_link'] && '' !== $dealer_url ) : ?>
				<a class="dinv-inventory__external" href="<?php echo esc_url( $dealer_url ); ?>" target="_blank" rel="noopener">
					<span class="dinv-inventory__external-text dinv-inventory__external-text--desktop"><?php echo esc_html( $labels['open_dealer_page'] ); ?></span>
					<span class="dinv-inventory__external-text dinv-inventory__external-text--mobile" aria-hidden="true"><?php echo esc_html( $labels['open_dealer_short'] ); ?></span>
					<span class="screen-reader-text"><?php echo esc_html( $labels['opens_new_tab'] ); ?></span>
					<?php echo Renderer::icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
				</a>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<div class="dinv-inventory__body">
		<?php if ( $config['show_count'] || $config['show_sort'] || $config['show_filters'] ) : ?>
			<div class="dinv-toolbar">
				<?php if ( $config['show_count'] ) : ?>
					<h2>
						<span data-dinv-count data-total="<?php echo esc_attr( (string) (int) $results['total'] ); ?>"><?php echo esc_html( number_format_i18n( (int) $results['total'] ) ); ?></span>
						<span data-dinv-count-label><?php echo esc_html( Labels::vehicle_noun( (int) $results['total'] ) ); ?></span>
					</h2>
				<?php endif; ?>

				<?php if ( $config['show_sort'] || $config['show_filters'] ) : ?>
					<div class="dinv-toolbar__actions">
						<?php if ( $config['show_filters'] ) : ?>
							<button class="dinv-toolbar__button dinv-toolbar__button--advanced" type="button" data-dinv-advanced aria-haspopup="dialog">
								<span class="dinv-toolbar__icon" aria-hidden="true"><?php echo Renderer::icon( 'advanced' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
								<span><?php echo esc_html( $labels['advanced'] ); ?></span>
							</button>
							<button class="dinv-toolbar__button dinv-toolbar__button--reset" type="button" data-dinv-reset>
								<span class="dinv-toolbar__icon" aria-hidden="true"><?php echo Renderer::icon( 'reset' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
								<span><?php echo esc_html( $labels['reset'] ); ?></span>
							</button>
						<?php endif; ?>

						<?php if ( $config['show_sort'] ) : ?>
							<label class="dinv-sort">
								<span class="screen-reader-text"><?php echo esc_html( $labels['sort_by'] ); ?></span>
								<select name="dinv_sort" data-dinv-sort>
									<?php foreach ( Schema::sort_options() as $dinv_value => $dinv_text ) : ?>
										<option value="<?php echo esc_attr( $dinv_value ); ?>" <?php selected( $sort, $dinv_value ); ?>><?php echo esc_html( $dinv_text ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $config['show_filters'] ) : ?>
			<form class="dinv-filters" data-dinv-form method="get" action="" aria-label="<?php echo esc_attr( $labels['filters'] ); ?>">
				<div class="dinv-filters__grid">
					<?php
					if ( $dinv_show( 'category' ) ) {
						$dinv_select( 'dinv_category', $dinv_filters['category'], $dinv_choices( 'categories', 'category' ), (string) ( $filters['vehicle_category'] ?? '' ) );
					}

					if ( $dinv_show( 'make' ) ) :
						$dinv_selected_make  = (string) ( $filters['make'] ?? '' );
						$dinv_selected_label = $labels['all'];
						foreach ( (array) ( $options['makes'] ?? array() ) as $dinv_make ) {
							if ( $dinv_selected_make === (string) $dinv_make['value'] ) {
								$dinv_selected_label = (string) $dinv_make['label'];
							}
						}
						?>
						<div class="dinv-make-picker" data-dinv-make-picker>
							<span class="dinv-make-picker__label" id="<?php echo esc_attr( $dinv_uid ); ?>-make-label"><?php echo esc_html( $dinv_filters['make'] ); ?></span>
							<select class="dinv-make-picker__native" name="dinv_make" data-dinv-make aria-labelledby="<?php echo esc_attr( $dinv_uid ); ?>-make-label" tabindex="-1">
								<option value=""><?php echo esc_html( $labels['all'] ); ?></option>
								<?php foreach ( (array) ( $options['makes'] ?? array() ) as $dinv_make ) : ?>
									<option value="<?php echo esc_attr( (string) $dinv_make['value'] ); ?>" <?php selected( $dinv_selected_make, (string) $dinv_make['value'] ); ?>><?php echo esc_html( (string) $dinv_make['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<button class="dinv-make-picker__trigger" type="button" data-dinv-make-trigger aria-haspopup="listbox" aria-expanded="false" aria-labelledby="<?php echo esc_attr( $dinv_uid ); ?>-make-label <?php echo esc_attr( $dinv_uid ); ?>-make-value">
								<span data-dinv-make-trigger-text id="<?php echo esc_attr( $dinv_uid ); ?>-make-value"><?php echo esc_html( $dinv_selected_label ); ?></span>
								<?php echo Renderer::icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
							</button>
							<div class="dinv-make-picker__panel" data-dinv-make-panel hidden>
								<div class="dinv-make-picker__search-wrap">
									<?php echo Renderer::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
									<input class="dinv-make-picker__search" type="search" data-dinv-make-search placeholder="<?php echo esc_attr( $labels['make_search'] ); ?>" aria-label="<?php echo esc_attr( $labels['make_search'] ); ?>" autocomplete="off">
								</div>
								<div class="dinv-make-picker__list" data-dinv-make-list role="listbox" aria-labelledby="<?php echo esc_attr( $dinv_uid ); ?>-make-label">
									<button class="dinv-make-picker__option<?php echo '' === $dinv_selected_make ? ' is-selected' : ''; ?>" type="button" data-dinv-make-option data-value="" data-label="<?php echo esc_attr( $labels['all'] ); ?>" role="option" aria-selected="<?php echo '' === $dinv_selected_make ? 'true' : 'false'; ?>">
										<span><?php echo esc_html( $labels['all'] ); ?></span>
									</button>
									<?php foreach ( (array) ( $options['makes'] ?? array() ) as $dinv_make ) : ?>
										<?php $dinv_is_selected = $dinv_selected_make === (string) $dinv_make['value']; ?>
										<button class="dinv-make-picker__option<?php echo $dinv_is_selected ? ' is-selected' : ''; ?>" type="button" data-dinv-make-option data-value="<?php echo esc_attr( (string) $dinv_make['value'] ); ?>" data-label="<?php echo esc_attr( (string) $dinv_make['label'] ); ?>" role="option" aria-selected="<?php echo $dinv_is_selected ? 'true' : 'false'; ?>">
											<span><?php echo esc_html( (string) $dinv_make['label'] ); ?></span>
											<strong><?php echo esc_html( number_format_i18n( (int) $dinv_make['count'] ) ); ?></strong>
										</button>
									<?php endforeach; ?>
								</div>
								<div class="dinv-make-picker__empty" data-dinv-make-empty hidden><?php echo esc_html( $labels['no_make_results'] ); ?></div>
							</div>
						</div>
						<?php
					endif;

					if ( $dinv_show( 'version' ) ) :
						?>
						<label>
							<span><?php echo esc_html( $dinv_filters['version'] ); ?></span>
							<input type="search" name="dinv_version" value="<?php echo esc_attr( (string) ( $filters['version'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $labels['version_hint'] ); ?>" maxlength="80">
						</label>
						<?php
					endif;

					if ( $dinv_show( 'price_to' ) ) {
						$dinv_number( 'dinv_price_to', $dinv_filters['price_to'], $filters['price_to'] ?? '', 500 );
					}
					if ( $dinv_show( 'year_from' ) ) {
						$dinv_select( 'dinv_year_from', $dinv_filters['year_from'], $dinv_years, (string) ( $filters['year_from'] ?? '' ) );
					}
					if ( $dinv_show( 'mileage_to' ) ) {
						$dinv_number( 'dinv_mileage_to', $dinv_filters['mileage_to'], $filters['mileage_to'] ?? '', 5000 );
					}
					if ( $dinv_show( 'fuel' ) ) {
						$dinv_select( 'dinv_fuel', $dinv_filters['fuel'], $dinv_choices( 'fuels', 'fuel' ), (string) ( $filters['fuel'] ?? '' ) );
					}
					?>
				</div>

				<dialog class="dinv-advanced" data-dinv-dialog aria-labelledby="<?php echo esc_attr( $dinv_uid ); ?>-advanced-title">
					<div class="dinv-advanced__header">
						<div>
							<span class="dinv-advanced__eyebrow"><?php echo esc_html( $dinv_title ); ?></span>
							<h3 id="<?php echo esc_attr( $dinv_uid ); ?>-advanced-title"><?php echo esc_html( $labels['advanced'] ); ?></h3>
						</div>
						<button type="button" class="dinv-advanced__close" data-dinv-close aria-label="<?php echo esc_attr( $labels['close'] ); ?>">
							<?php echo Renderer::icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
						</button>
					</div>

					<div class="dinv-advanced__grid">
						<?php
						if ( $dinv_show( 'price_from' ) ) {
							$dinv_number( 'dinv_price_from', $dinv_filters['price_from'], $filters['price_from'] ?? '', 500 );
						}
						if ( $dinv_show( 'price_to' ) ) {
							$dinv_number( 'dinv_price_to_advanced', $dinv_filters['price_to'], $filters['price_to'] ?? '', 500, 'data-mirror-name="dinv_price_to"' );
						}
						if ( $dinv_show( 'year_from' ) ) {
							$dinv_number( 'dinv_year_from_advanced', $dinv_filters['year_from'], $filters['year_from'] ?? '', 1, 'min="1900" data-mirror-name="dinv_year_from"' );
						}
						if ( $dinv_show( 'year_to' ) ) {
							$dinv_number( 'dinv_year_to', $dinv_filters['year_to'], $filters['year_to'] ?? '', 1, 'min="1900"' );
						}
						if ( $dinv_show( 'mileage_from' ) ) {
							$dinv_number( 'dinv_mileage_from', $dinv_filters['mileage_from'], $filters['mileage_from'] ?? '', 5000 );
						}
						if ( $dinv_show( 'mileage_to' ) ) {
							$dinv_number( 'dinv_mileage_to_advanced', $dinv_filters['mileage_to'], $filters['mileage_to'] ?? '', 5000, 'data-mirror-name="dinv_mileage_to"' );
						}
						if ( $dinv_show( 'transmission' ) ) {
							$dinv_select( 'dinv_transmission', $dinv_filters['transmission'], $dinv_choices( 'transmissions', 'transmission' ), (string) ( $filters['transmission'] ?? '' ) );
						}
						if ( $dinv_show( 'body' ) ) {
							$dinv_select( 'dinv_body', $dinv_filters['body'], $dinv_choices( 'body_types', 'body' ), (string) ( $filters['body_type'] ?? '' ) );
						}
						if ( $dinv_show( 'drive' ) ) {
							$dinv_select( 'dinv_drive', $dinv_filters['drive'], $dinv_choices( 'drive_types', 'drive' ), (string) ( $filters['drive_type'] ?? '' ) );
						}
						if ( $dinv_show( 'condition' ) ) {
							$dinv_select( 'dinv_condition', $dinv_filters['condition'], $dinv_choices( 'conditions', 'condition' ), (string) ( $filters['condition'] ?? '' ) );
						}
						if ( $dinv_show( 'power_from' ) ) {
							$dinv_number( 'dinv_power_from', $dinv_filters['power_from'], $filters['power_from'] ?? '', 10 );
						}
						if ( $dinv_show( 'power_to' ) ) {
							$dinv_number( 'dinv_power_to', $dinv_filters['power_to'], $filters['power_to'] ?? '', 10 );
						}
						if ( $dinv_show( 'warranty' ) && \DealerInventory\Settings::get( 'sync_warranty', true ) ) :
							?>
							<label class="dinv-check">
								<input type="checkbox" name="dinv_warranty" value="1" <?php checked( ! empty( $filters['has_warranty'] ) ); ?>>
								<span><?php echo esc_html( $dinv_filters['warranty'] ); ?></span>
							</label>
						<?php endif; ?>
					</div>

					<div class="dinv-advanced__footer">
						<button type="button" class="dinv-button dinv-button--ghost" data-dinv-reset>
							<span class="dinv-toolbar__icon" aria-hidden="true"><?php echo Renderer::icon( 'reset' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
							<span><?php echo esc_html( $labels['reset'] ); ?></span>
						</button>
						<button type="submit" class="dinv-button dinv-button--primary"><?php echo esc_html( $labels['apply'] ); ?></button>
					</div>
				</dialog>
			</form>
		<?php else : ?>
			<form data-dinv-form hidden>
				<?php foreach ( $preset_params as $dinv_name => $dinv_value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( (string) $dinv_name ); ?>" value="<?php echo esc_attr( (string) $dinv_value ); ?>">
				<?php endforeach; ?>
			</form>
		<?php endif; ?>

		<div class="dinv-results" aria-busy="false">
			<div class="dinv-results__loading" data-dinv-loading role="status" hidden>
				<span class="dinv-results__spinner" aria-hidden="true"></span>
				<span><?php echo esc_html( $labels['loading'] ); ?></span>
			</div>

			<div class="dinv-results__items" data-dinv-results tabindex="-1"><?php echo $renderer->items( $results['items'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer. ?></div>

			<div class="dinv-empty" data-dinv-empty <?php echo $results['total'] ? 'hidden' : ''; ?>>
				<h3><?php echo esc_html( $labels['empty_title'] ); ?></h3>
				<p><?php echo esc_html( $labels['empty_text'] ); ?></p>
				<?php if ( $config['show_filters'] ) : ?>
					<button type="button" class="dinv-button dinv-button--ghost" data-dinv-reset><?php echo esc_html( $labels['reset'] ); ?></button>
				<?php endif; ?>
			</div>

			<div class="dinv-error" data-dinv-error role="alert" hidden>
				<h3><?php echo esc_html( $labels['error_title'] ); ?></h3>
				<p><?php echo esc_html( $labels['error_text'] ); ?></p>
				<button type="button" class="dinv-button dinv-button--ghost" data-dinv-retry><?php echo esc_html( $labels['retry'] ); ?></button>
			</div>

			<?php if ( $config['show_pagination'] ) : ?>
				<div data-dinv-pagination><?php echo $renderer->pagination( $results ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer. ?></div>
			<?php endif; ?>

			<div class="screen-reader-text" data-dinv-status role="status" aria-live="polite"></div>
		</div>
	</div>
</section>
