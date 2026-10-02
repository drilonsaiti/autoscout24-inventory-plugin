<?php
/**
 * Result markup: vehicle cards, list rows and pagination.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders vehicles for one resolved instance configuration.
 *
 * Used for the server-rendered first paint and for REST refreshes, so both
 * always produce identical markup.
 */
final class Renderer {

	/**
	 * Resolved instance configuration (see Schema::resolve()).
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Interface labels.
	 *
	 * @var array<string, string>
	 */
	private array $labels;

	/**
	 * Price currency.
	 *
	 * @var string
	 */
	private string $currency;

	/**
	 * Base URL for pagination links ('' renders buttons).
	 *
	 * @var string
	 */
	private string $page_base = '';

	/**
	 * Query parameters kept in pagination links.
	 *
	 * @var array<string, string>
	 */
	private array $page_query = array();

	/**
	 * Page parameter name.
	 *
	 * @var string
	 */
	private string $page_param = 'dinv_page';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Resolved configuration.
	 */
	public function __construct( array $config ) {
		$this->config   = $config;
		$this->labels   = Labels::ui();
		$this->currency = Format::currency( (string) ( $config['currency'] ?? 'auto' ) );
	}

	/**
	 * Markup for a list of vehicles.
	 *
	 * @param array[] $items Vehicle rows.
	 */
	public function items( array $items ): string {
		$html = '';
		foreach ( $items as $index => $vehicle ) {
			$html .= 'list' === $this->config['layout']
				? $this->list_row( $vehicle, (int) $index )
				: $this->card( $vehicle, (int) $index );
		}
		return $html;
	}

	/**
	 * Render pagination as real links (crawlable, work without JavaScript).
	 *
	 * @param string $base     Same-site relative URL of the inventory page.
	 * @param array  $query    Instance-scoped filter and sort parameters.
	 * @param string $instance Instance name.
	 */
	public function link_pages( string $base, array $query, string $instance ): void {
		$this->page_base  = $base;
		$this->page_query = $query;
		$this->page_param = Shortcode::request_key( 'page', $instance );
	}

	/**
	 * URL of a result page.
	 *
	 * @param int $page Page number.
	 */
	private function page_url( int $page ): string {
		$query = $this->page_query;
		if ( $page > 1 ) {
			$query[ $this->page_param ] = (string) $page;
		}
		return $query ? add_query_arg( array_map( 'rawurlencode', $query ), $this->page_base ) : $this->page_base;
	}

	/**
	 * One pagination control: a link when URLs are enabled, otherwise a button.
	 *
	 * @param int    $page     Target page.
	 * @param string $css_class CSS class.
	 * @param string $inner    Inner HTML (escaped).
	 * @param bool   $disabled Disabled.
	 * @param string $extra    Extra attributes (escaped).
	 */
	private function page_control( int $page, string $css_class, string $inner, bool $disabled, string $extra = '' ): string {
		if ( '' !== $this->page_base && ! $disabled ) {
			return sprintf( '<a class="%1$s" href="%2$s" data-page="%3$d"%4$s>%5$s</a>', esc_attr( $css_class ), esc_url( $this->page_url( $page ) ), $page, $extra, $inner );
		}
		return sprintf( '<button type="button" class="%1$s" data-page="%2$d"%3$s%4$s>%5$s</button>', esc_attr( $css_class ), $page, $disabled ? ' disabled' : '', $extra, $inner );
	}

	/**
	 * Pagination markup.
	 *
	 * @param array $results Search results.
	 */
	public function pagination( array $results ): string {
		$current = (int) $results['page'];
		$pages   = (int) $results['total_pages'];
		if ( $pages <= 1 ) {
			return '';
		}

		$start = max( 1, $current - 2 );
		$end   = min( $pages, $current + 2 );

		$html  = '<nav class="dinv-pagination" aria-label="' . esc_attr( $this->labels['pagination'] ) . '">';
		$html .= $this->page_control(
			max( 1, $current - 1 ),
			'dinv-pagination__button dinv-pagination__button--prev',
			self::icon( 'arrow' ) . '<span>' . esc_html( $this->labels['previous'] ) . '</span>',
			1 === $current,
			' rel="prev"'
		);
		$html .= '<div class="dinv-pagination__pages">';
		for ( $i = $start; $i <= $end; $i++ ) {
			$html .= $this->page_control(
				$i,
				'dinv-pagination__page' . ( $i === $current ? ' is-active' : '' ),
				(string) $i,
				false,
				( $i === $current ? ' aria-current="page"' : '' ) . ' aria-label="' . esc_attr( sprintf( $this->labels['page_n'], $i ) ) . '"'
			);
		}
		$html .= '</div>';
		$html .= $this->page_control(
			min( $pages, $current + 1 ),
			'dinv-pagination__button dinv-pagination__button--next',
			'<span>' . esc_html( $this->labels['next'] ) . '</span>' . self::icon( 'arrow' ),
			$pages === $current,
			' rel="next"'
		);
		$html .= '</nav>';

		return $html;
	}

	/**
	 * One card (grid layout).
	 *
	 * @param array $vehicle Vehicle row.
	 * @param int   $index   Position on the page.
	 */
	private function card( array $vehicle, int $index ): string {
		$title        = Vehicle::title( $vehicle );
		$link         = $this->link_attributes( $vehicle );
		$registration = Format::registration( (string) ( $vehicle['first_registration_date'] ?? '' ), $vehicle['first_registration_year'] ?? '' );
		$card_label   = (string) $this->config['card_label'];
		$teaser       = '' === $card_label ? sanitize_text_field( (string) ( $vehicle['teaser'] ?? '' ) ) : '';

		$chips = array();
		if ( '' === $card_label && '' !== $registration ) {
			$chips[] = $registration;
		}
		if ( isset( $vehicle['mileage'] ) && '' !== (string) $vehicle['mileage'] ) {
			$chips[] = Format::mileage( (int) $vehicle['mileage'] );
		}
		$power = $this->power( $vehicle );
		if ( '' !== $power ) {
			$chips[] = $power;
		}
		if ( ! empty( $vehicle['drive_type'] ) ) {
			$chips[] = Labels::enum( 'drive', (string) $vehicle['drive_type'] );
		}

		$button = '' !== (string) $this->config['button_text'] ? (string) $this->config['button_text'] : $this->labels['view_vehicle'];

		ob_start();
		?>
		<article class="dinv-vehicle dinv-vehicle--card" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['external_id'] ); ?>">
			<a class="dinv-vehicle__image-wrap dinv-card__image-wrap" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?> tabindex="-1" aria-hidden="true">
				<?php echo $this->image( $vehicle, $title, $index, '(max-width: 760px) 100vw, 420px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in image(). ?>
			</a>
			<div class="dinv-card__content">
				<?php if ( '' !== $card_label ) : ?>
					<div class="dinv-card__meta">
						<span class="dinv-card__eyebrow"><?php echo esc_html( $card_label ); ?></span>
						<?php if ( '' !== $registration ) : ?>
							<span class="dinv-card__registration"><?php echo esc_html( $registration ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<a class="dinv-vehicle__title-link" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?>>
					<h3 class="dinv-vehicle__title dinv-card__title"><?php echo esc_html( $title ); ?></h3>
				</a>
				<?php if ( '' !== $teaser ) : ?>
					<p class="dinv-card__teaser"><?php echo esc_html( $teaser ); ?></p>
				<?php endif; ?>
				<?php if ( $chips ) : ?>
					<div class="dinv-card__chips">
						<?php foreach ( $chips as $chip ) : ?>
							<span><?php echo esc_html( $chip ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="dinv-card__footer">
					<?php echo $this->price( $vehicle, 'dinv-vehicle__price dinv-card__price' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in price(). ?>
					<a class="dinv-vehicle__cta dinv-card__cta dinv-vehicle__link" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?>>
						<span><?php echo esc_html( $button ); ?></span><?php echo $this->new_tab_hint(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in new_tab_hint(). ?>
						<?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					</a>
				</div>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One horizontal list row.
	 *
	 * @param array $vehicle Vehicle row.
	 * @param int   $index   Position on the page.
	 */
	private function list_row( array $vehicle, int $index ): string {
		$title  = Vehicle::title( $vehicle );
		$link   = $this->link_attributes( $vehicle );
		$button = '' !== (string) $this->config['button_text'] ? (string) $this->config['button_text'] : $this->labels['view_vehicle'];

		$specs = array(
			'registration' => Format::registration( (string) ( $vehicle['first_registration_date'] ?? '' ), $vehicle['first_registration_year'] ?? '' ),
			'fuel'         => Labels::enum( 'fuel', (string) ( $vehicle['fuel_type'] ?? '' ) ),
			'mileage'      => isset( $vehicle['mileage'] ) && '' !== (string) $vehicle['mileage'] ? Format::mileage( (int) $vehicle['mileage'] ) : '',
			'power'        => $this->power( $vehicle ),
		);

		ob_start();
		?>
		<article class="dinv-vehicle" data-vehicle-id="<?php echo esc_attr( (string) $vehicle['external_id'] ); ?>">
			<a class="dinv-vehicle__image-wrap" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?> tabindex="-1" aria-hidden="true">
				<?php echo $this->image( $vehicle, $title, $index, '(max-width: 700px) 100vw, 320px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in image(). ?>
			</a>
			<div class="dinv-vehicle__content">
				<a class="dinv-vehicle__title-link" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?>>
					<h3 class="dinv-vehicle__title"><?php echo esc_html( $title ); ?></h3>
				</a>
				<?php if ( ! empty( $vehicle['teaser'] ) ) : ?>
					<p class="dinv-vehicle__teaser"><?php echo esc_html( (string) $vehicle['teaser'] ); ?></p>
				<?php endif; ?>
				<?php echo $this->price( $vehicle, 'dinv-vehicle__price' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in price(). ?>
				<div class="dinv-vehicle__specs">
					<?php foreach ( $specs as $type => $text ) : ?>
						<?php if ( '' !== $text ) : ?>
							<div class="dinv-spec dinv-spec--<?php echo esc_attr( $type ); ?>">
								<span class="dinv-spec__icon" aria-hidden="true"><?php echo self::icon( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
								<span class="dinv-spec__text"><?php echo esc_html( $text ); ?></span>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<a class="dinv-vehicle__cta dinv-vehicle__link" <?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in link_attributes(). ?>>
					<span><?php echo esc_html( $button ); ?></span><?php echo $this->new_tab_hint(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in new_tab_hint(). ?>
					<?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
				</a>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Escaped href/target/rel attributes for vehicle links.
	 *
	 * @param array $vehicle Vehicle row.
	 */
	private function link_attributes( array $vehicle ): string {
		$url  = Shortcode::vehicle_link( $vehicle, (string) $this->config['link_to'] );
		$html = 'href="' . esc_url( $url ) . '"';
		if ( ! empty( $this->config['new_tab'] ) ) {
			$html .= ' target="_blank" rel="noopener"';
		}
		return $html;
	}

	/**
	 * Screen-reader hint for links that open a new tab.
	 */
	private function new_tab_hint(): string {
		return empty( $this->config['new_tab'] )
			? ''
			: '<span class="screen-reader-text"> ' . esc_html( $this->labels['opens_new_tab'] ) . '</span>';
	}

	/**
	 * Responsive vehicle image (first image eager, others lazy).
	 *
	 * @param array  $vehicle Vehicle row.
	 * @param string $alt     Alt text.
	 * @param int    $index   Position on the page.
	 * @param string $sizes   Sizes attribute.
	 */
	private function image( array $vehicle, string $alt, int $index, string $sizes ): string {
		$original = (string) ( $vehicle['image_url'] ?? '' );
		$src      = Shortcode::image_url( $original, 768 );

		if ( '' === $src ) {
			return '<span class="dinv-vehicle__image dinv-vehicle__image--fallback" aria-hidden="true"></span>';
		}

		$srcset = Shortcode::image_srcset( $original );

		return sprintf(
			'<img class="dinv-vehicle__image" src="%1$s"%2$s alt="%3$s" loading="%4$s"%5$s decoding="async" width="768" height="512">',
			esc_url( $src ),
			'' !== $srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"' : '',
			esc_attr( $alt ),
			0 === $index ? 'eager' : 'lazy',
			0 === $index ? ' fetchpriority="high"' : ''
		);
	}

	/**
	 * Price element or empty string.
	 *
	 * @param array  $vehicle Vehicle row.
	 * @param string $classes CSS classes.
	 */
	private function price( array $vehicle, string $classes ): string {
		if ( ! isset( $vehicle['price'] ) || '' === (string) $vehicle['price'] ) {
			return '';
		}
		return '<div class="' . esc_attr( $classes ) . '">' . esc_html( Format::price( (float) $vehicle['price'], $this->currency ) ) . '</div>';
	}

	/**
	 * Power text in the configured unit.
	 *
	 * @param array $vehicle Vehicle row.
	 */
	private function power( array $vehicle ): string {
		return Format::power(
			isset( $vehicle['horse_power'] ) && '' !== (string) $vehicle['horse_power'] ? (int) $vehicle['horse_power'] : null,
			isset( $vehicle['kilo_watts'] ) && '' !== (string) $vehicle['kilo_watts'] ? (int) $vehicle['kilo_watts'] : null,
			(string) ( $this->config['power_unit'] ?? 'hp' )
		);
	}

	/**
	 * Inline SVG icon (static markup, safe to print).
	 *
	 * @param string $name Icon name.
	 */
	public static function icon( string $name ): string {
		$open  = '<svg class="dinv-ui-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round">';
		$paths = array(
			'advanced'     => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
			'reset'        => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
			'registration' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
			'mileage'      => '<path d="M5 21 8.5 3M19 21 15.5 3M12 4.5v3M12 11v3M12 17.5V20"/>',
			'transmission' => '<circle cx="6" cy="4" r="1.5"/><circle cx="18" cy="4" r="1.5"/><circle cx="12" cy="20" r="1.5"/><path d="M6 5.5V19M18 5.5v6.5M6 9h12M12 9v9.5M12 15h6"/>',
			'fuel'         => '<path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17M4 21h13M8 7h5v5H8zM16 8h2l2 2v8a2 2 0 0 1-4 0v-4"/>',
			'power'        => '<path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z"/>',
			'arrow'        => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'close'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
			'external'     => '<path d="M14 5h5v5"/><path d="M10 14 19 5"/><path d="M19 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
			'chevron'      => '<path d="m7 10 5 5 5-5"/>',
			'search'       => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		);

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		$open = 'arrow' === $name ? str_replace( 'dinv-ui-icon', 'dinv-link-arrow', $open ) : $open;
		return $open . $paths[ $name ] . '</svg>';
	}
}
