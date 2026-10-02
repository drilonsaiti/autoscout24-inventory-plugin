<?php
/**
 * Results: vehicles, empty and error states, pagination.
 *
 * Override: yourtheme/dealer-inventory/parts/results.php
 *
 * @package DealerInventory
 *
 * @var array                     $config   Instance configuration.
 * @var array                     $results  Search results.
 * @var array                     $labels   Interface labels.
 * @var \DealerInventory\Renderer $renderer Result renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dinv-results" data-dinv-results-wrap aria-busy="false">
	<div class="dinv-results__loading" data-dinv-loading hidden>
		<span class="dinv-spinner" aria-hidden="true"></span>
		<span><?php echo esc_html( $labels['loading'] ); ?></span>
	</div>

	<div class="dinv-results__items" data-dinv-results tabindex="-1"><?php echo $renderer->body( $results['items'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer and templates. ?></div>

	<div class="dinv-empty" data-dinv-empty <?php echo $results['total'] ? 'hidden' : ''; ?>>
		<h3 class="dinv-empty__title"><?php echo esc_html( $labels['empty_title'] ); ?></h3>
		<?php if ( $config['show_filters'] ) : ?>
			<p><?php echo esc_html( $labels['empty_text'] ); ?></p>
			<button type="button" class="dinv-button dinv-button--ghost" data-dinv-reset><?php echo esc_html( $labels['reset'] ); ?></button>
		<?php endif; ?>
	</div>

	<div class="dinv-error" data-dinv-error role="alert" hidden>
		<h3 class="dinv-error__title"><?php echo esc_html( $labels['error_title'] ); ?></h3>
		<p><?php echo esc_html( $labels['error_text'] ); ?></p>
		<button type="button" class="dinv-button dinv-button--ghost" data-dinv-retry><?php echo esc_html( $labels['retry'] ); ?></button>
	</div>

	<?php if ( $config['show_pagination'] ) : ?>
		<div class="dinv-results__pagination" data-dinv-pagination><?php echo $renderer->pagination( $results ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer. ?></div>
	<?php endif; ?>

	<div class="screen-reader-text" data-dinv-status role="status" aria-live="polite"></div>
</div>
