<?php
/**
 * Toolbar: count, filter button, view switch, per page and sort.
 *
 * Override: yourtheme/dealer-inventory/parts/toolbar.php
 *
 * @package DealerInventory
 *
 * @var array  $config   Instance configuration.
 * @var array  $results  Search results.
 * @var string $sort     Active sort.
 * @var string $view     Visitor view.
 * @var string $instance Instance name.
 * @var array  $labels   Interface labels.
 * @var string $uid      Unique element id.
 */

use DealerInventory\Inventory;
use DealerInventory\Labels;
use DealerInventory\Renderer;
use DealerInventory\Schema;
use DealerInventory\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dinv_filter_toggle = $config['show_filters'] && ( $config['mobile_drawer'] || ( $config['filters_collapsed'] && 'top' === $config['filter_position'] ) );
$dinv_sort_options  = array_intersect_key( Schema::sort_options(), array_flip( (array) $config['sort_options'] ) );
if ( ! isset( $dinv_sort_options[ $sort ] ) ) {
	$dinv_sort_options = array( $sort => Schema::sort_options()[ $sort ] ) + $dinv_sort_options;
}
// Keep the configured order.
$dinv_sort_options = array_merge( array_intersect_key( array_flip( (array) $config['sort_options'] ), $dinv_sort_options ), $dinv_sort_options );

$dinv_has_actions = $config['show_sort'] || $dinv_filter_toggle || $config['view_switcher'] || $config['per_page_selector'];

if ( ! $config['show_count'] && ! $dinv_has_actions ) {
	return;
}

$dinv_form    = $uid . '-filters';
$dinv_current = '' !== $view ? $view : ( 'list' === $config['layout'] ? 'list' : 'grid' );
?>
<div class="dinv-toolbar">
	<?php if ( $config['show_count'] ) : ?>
		<h2 class="dinv-toolbar__count">
			<span data-dinv-count data-total="<?php echo esc_attr( (string) (int) $results['total'] ); ?>"><?php echo esc_html( number_format_i18n( (int) $results['total'] ) ); ?></span>
			<span data-dinv-count-label><?php echo esc_html( Labels::vehicle_noun( (int) $results['total'] ) ); ?></span>
		</h2>
	<?php endif; ?>

	<?php if ( $dinv_has_actions ) : ?>
		<div class="dinv-toolbar__actions">
			<?php if ( $dinv_filter_toggle ) : ?>
				<button type="button" class="dinv-button dinv-button--ghost dinv-toolbar__filters<?php echo $config['filters_collapsed'] && 'top' === $config['filter_position'] ? ' is-always' : ''; ?>" data-dinv-filters-toggle aria-controls="<?php echo esc_attr( $dinv_form ); ?>" aria-expanded="<?php echo $config['filters_collapsed'] ? 'false' : 'true'; ?>">
					<?php echo Renderer::icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					<span><?php echo esc_html( $labels['filters'] ); ?></span>
					<span class="dinv-toolbar__badge" data-dinv-active-count hidden></span>
				</button>
			<?php endif; ?>

			<?php if ( $config['view_switcher'] ) : ?>
				<div class="dinv-views" role="group" aria-label="<?php echo esc_attr( $labels['view'] ); ?>">
					<?php foreach ( array( 'grid', 'list' ) as $dinv_view ) : ?>
						<button type="button" class="dinv-views__button" data-dinv-view="<?php echo esc_attr( $dinv_view ); ?>" aria-pressed="<?php echo $dinv_current === $dinv_view ? 'true' : 'false'; ?>" title="<?php echo esc_attr( $labels[ 'view_' . $dinv_view ] ); ?>">
							<?php echo Renderer::icon( $dinv_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
							<span class="screen-reader-text"><?php echo esc_html( $labels[ 'view_' . $dinv_view ] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $config['per_page_selector'] ) : ?>
				<label class="dinv-select dinv-select--compact">
					<span class="screen-reader-text"><?php echo esc_html( $labels['per_page'] ); ?></span>
					<select name="<?php echo esc_attr( Shortcode::request_key( 'per_page', $instance ) ); ?>" data-dinv-per-page form="<?php echo esc_attr( $dinv_form ); ?>">
						<?php foreach ( Inventory::per_page_choices( $config ) as $dinv_choice ) : ?>
							<option value="<?php echo esc_attr( (string) $dinv_choice ); ?>" <?php selected( (int) $config['per_page'], $dinv_choice ); ?>>
								<?php
								/* translators: %d: number of vehicles per page. */
								echo esc_html( sprintf( __( '%d per page', 'dealer-inventory-for-autoscout24' ), $dinv_choice ) );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<?php if ( $config['show_sort'] && count( $dinv_sort_options ) > 1 ) : ?>
				<label class="dinv-select">
					<span class="screen-reader-text"><?php echo esc_html( $labels['sort_by'] ); ?></span>
					<select name="<?php echo esc_attr( Shortcode::request_key( 'sort', $instance ) ); ?>" data-dinv-sort form="<?php echo esc_attr( $dinv_form ); ?>">
						<?php foreach ( $dinv_sort_options as $dinv_value => $dinv_text ) : ?>
							<option value="<?php echo esc_attr( $dinv_value ); ?>" <?php selected( $sort, $dinv_value ); ?>><?php echo esc_html( $dinv_text ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
