<?php
/**
 * Result markup: vehicles, result wrapper and pagination.
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
 * Builds a plain view model per vehicle (texts already formatted, URLs
 * already chosen) and passes it to an overridable template:
 * loop/card.php, loop/grid.php, loop/list.php or loop/table-row.php.
 */
final class Renderer {

	/**
	 * Resolved configuration (effective layout and page size).
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
	 * Visible card parts (as keys).
	 *
	 * @var array<string, int>
	 */
	private array $fields;

	/**
	 * Base URL for page links ('' renders buttons).
	 *
	 * @var string
	 */
	private string $page_base = '';

	/**
	 * Query parameters kept in page links.
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
		$this->fields   = array_flip( (array) ( $config['card_fields'] ?? array() ) );
	}

	/**
	 * Render pagination as real links (crawlable, work without JavaScript).
	 *
	 * @param string $base     Same-site relative URL of the inventory page.
	 * @param array  $query    Instance-scoped filter, sort, per-page and view parameters.
	 * @param string $instance Instance name.
	 */
	public function link_pages( string $base, array $query, string $instance ): void {
		$this->page_base  = $base;
		$this->page_query = $query;
		$this->page_param = Shortcode::request_key( 'page', $instance );
	}

	/**
	 * Vehicle items only (for appending with "Load more").
	 *
	 * @param array[] $items       Vehicle rows.
	 * @param int     $first_index Position of the first item in the page sequence.
	 */
	public function items( array $items, int $first_index = 0 ): string {
		$layout   = (string) $this->config['layout'];
		$template = 'table' === $layout ? 'loop/table-row.php' : 'loop/' . $layout . '.php';

		$html = '';
		foreach ( array_values( $items ) as $index => $row ) {
			$html .= Template::render(
				$template,
				array(
					'vehicle' => $this->vehicle( $row, $first_index + $index ),
					'config'  => $this->config,
					'labels'  => $this->labels,
				)
			);
		}
		return $html;
	}

	/**
	 * Result wrapper with the items (grid, list or table).
	 *
	 * @param array[] $items Vehicle rows.
	 */
	public function body( array $items ): string {
		$layout = (string) $this->config['layout'];
		$hover  = 'dinv-hover-' . sanitize_html_class( (string) ( $this->config['hover_effect'] ?? 'lift' ) );

		if ( ! $items ) {
			return '';
		}

		if ( 'table' !== $layout ) {
			return '<div class="dinv-items dinv-items--' . esc_attr( $layout ) . ' ' . esc_attr( $hover ) . '" data-dinv-items>' . $this->items( $items ) . '</div>';
		}

		$columns = array( 'image' => '' );
		foreach ( array(
			'title'   => __( 'Vehicle', 'dealer-inventory-for-autoscout24' ),
			'year'    => Schema::card_field_labels()['year'],
			'mileage' => Schema::card_field_labels()['mileage'],
			'fuel'    => Schema::card_field_labels()['fuel'],
			'power'   => Schema::card_field_labels()['power'],
			'price'   => Schema::card_field_labels()['price'],
		) as $key => $label ) {
			if ( isset( $this->fields[ $key ] ) ) {
				$columns[ $key ] = $label;
			}
		}
		if ( ! isset( $this->fields['image'] ) ) {
			unset( $columns['image'] );
		}

		$head = '';
		foreach ( $columns as $key => $label ) {
			$head .= '<th scope="col" class="dinv-table__' . esc_attr( $key ) . '">' . ( '' === $label ? '<span class="screen-reader-text">' . esc_html( Schema::card_field_labels()['image'] ) . '</span>' : esc_html( $label ) ) . '</th>';
		}
		if ( isset( $this->fields['button'] ) && 'none' !== $this->config['link_to'] ) {
			$head .= '<th scope="col" class="dinv-table__action"><span class="screen-reader-text">' . esc_html( $this->labels['view_vehicle'] ) . '</span></th>';
		}

		return '<div class="dinv-table-wrap ' . esc_attr( $hover ) . '"><table class="dinv-table"><thead><tr>' . $head . '</tr></thead><tbody data-dinv-items>' . $this->items( $items ) . '</tbody></table></div>';
	}

	/**
	 * View model of one vehicle.
	 *
	 * @param array $row   Vehicle row.
	 * @param int   $index Position on the page.
	 * @return array<string, mixed>
	 */
	public function vehicle( array $row, int $index ): array {
		$link_to = (string) ( $this->config['link_to'] ?? 'autoscout' );
		$url     = Shortcode::vehicle_link( $row, $link_to );
		$new_tab = '' !== $url && 'autoscout' === $link_to && ! empty( $this->config['new_tab'] );

		$price          = isset( $row['price'] ) && '' !== (string) $row['price'] ? (float) $row['price'] : null;
		$previous_price = isset( $row['previous_price'] ) && '' !== (string) $row['previous_price'] ? (float) $row['previous_price'] : null;
		$reduced        = null !== $price && null !== $previous_price && $previous_price > $price;

		$title = isset( $this->fields['version'] )
			? Vehicle::title( $row )
			: trim( (string) ( $row['make_name'] ?? '' ) . ' ' . (string) ( $row['model_name'] ?? '' ) );

		$data = array(
			'id'          => (int) $row['external_id'],
			'index'       => $index,
			'fields'      => $this->fields,
			'url'         => $url,
			'link_attrs'  => '' === $url ? '' : 'href="' . esc_url( $url ) . '"' . ( $new_tab ? ' target="_blank" rel="noopener"' : '' ),
			'new_tab'     => $new_tab,
			'title'       => $title,
			'label'       => (string) ( $this->config['card_label'] ?? '' ),
			'teaser'      => isset( $this->fields['teaser'] ) ? sanitize_text_field( (string) ( $row['teaser'] ?? '' ) ) : '',
			'price'       => isset( $this->fields['price'] ) && null !== $price ? Format::price( $price, $this->currency ) : '',
			'price_old'   => isset( $this->fields['price'] ) && $reduced ? Format::price( (float) $previous_price, $this->currency ) : '',
			'monthly'     => '',
			'specs'       => $this->specs( $row ),
			'badges'      => $this->badges( $row, $reduced ),
			'images'      => isset( $this->fields['image'] ) ? $this->images( $row, $title, $index ) : array(),
			'button'      => isset( $this->fields['button'] ) && '' !== $url
				? ( '' !== (string) $this->config['button_text'] ? (string) $this->config['button_text'] : $this->labels['view_vehicle'] )
				: '',
			'raw'         => $row,
			'image_sizes' => 'list' === $this->config['layout'] ? '(max-width: 700px) 100vw, 320px' : '(max-width: 760px) 100vw, 420px',
		);

		if ( isset( $this->fields['monthly_rate'] ) && ! empty( $row['leasing_monthly_rate'] ) ) {
			/* translators: %s: formatted monthly leasing rate. */
			$data['monthly'] = sprintf( __( 'from %s / month', 'dealer-inventory-for-autoscout24' ), Format::price( (float) $row['leasing_monthly_rate'], $this->currency ) );
		}

		/**
		 * Filters the view model of a vehicle before it is rendered.
		 *
		 * @param array $data   View model.
		 * @param array $row    Vehicle row.
		 * @param array $config Instance configuration.
		 */
		return (array) apply_filters( 'dinv_card_data', $data, $row, $this->config );
	}

	/**
	 * Formatted key specifications in display order.
	 *
	 * @param array $row Vehicle row.
	 * @return array<string, string> type => text
	 */
	private function specs( array $row ): array {
		$specs = array(
			'year'         => Format::registration( (string) ( $row['first_registration_date'] ?? '' ), $row['first_registration_year'] ?? '', (string) ( $this->config['date_format'] ?? 'auto' ) ),
			'mileage'      => isset( $row['mileage'] ) && '' !== (string) $row['mileage'] ? Format::mileage( (int) $row['mileage'] ) : '',
			'fuel'         => Labels::enum( 'fuel', (string) ( $row['fuel_type'] ?? '' ) ),
			'transmission' => Labels::enum( 'transmission', (string) ( $row['transmission_type'] ?? '' ) ),
			'power'        => Format::power(
				isset( $row['horse_power'] ) && '' !== (string) $row['horse_power'] ? (int) $row['horse_power'] : null,
				isset( $row['kilo_watts'] ) && '' !== (string) $row['kilo_watts'] ? (int) $row['kilo_watts'] : null,
				(string) ( $this->config['power_unit'] ?? 'hp' )
			),
			'drive'        => Labels::enum( 'drive', (string) ( $row['drive_type'] ?? '' ) ),
		);

		return array_filter(
			$specs,
			fn( $text, $key ) => '' !== $text && isset( $this->fields[ $key ] ),
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Badges for a vehicle.
	 *
	 * @param array $row     Vehicle row.
	 * @param bool  $reduced Price was reduced.
	 * @return array<string, string> key => label
	 */
	private function badges( array $row, bool $reduced ): array {
		if ( ! isset( $this->fields['badges'] ) ) {
			return array();
		}

		$enabled = array_flip( (array) ( $this->config['badges'] ?? array() ) );
		$labels  = Schema::field( 'badges' )['options'];
		$badges  = array();

		if ( isset( $enabled['new'] ) && 'new' === ( $row['condition_type'] ?? '' ) ) {
			$badges['new'] = $labels['new'];
		}
		if ( isset( $enabled['price_reduced'] ) && $reduced ) {
			$badges['price_reduced'] = $labels['price_reduced'];
		}
		if ( isset( $enabled['warranty'] ) && ! empty( $row['has_warranty'] ) ) {
			$badges['warranty'] = $labels['warranty'];
		}

		/**
		 * Filters the badges of a vehicle.
		 *
		 * @param array $badges key => label.
		 * @param array $row    Vehicle row.
		 */
		return (array) apply_filters( 'dinv_vehicle_badges', $badges, $row );
	}

	/**
	 * Image sources (first image eager on the first card, others lazy).
	 *
	 * @param array  $row   Vehicle row.
	 * @param string $alt   Alt text.
	 * @param int    $index Position on the page.
	 * @return array<int, array{src: string, srcset: string, alt: string, loading: string}>
	 */
	private function images( array $row, string $alt, int $index ): array {
		$urls = json_decode( (string) ( $row['images_json'] ?? '' ), true );
		$urls = is_array( $urls ) && $urls ? $urls : array_filter( array( (string) ( $row['image_url'] ?? '' ) ) );
		$urls = array_slice( array_values( $urls ), 0, max( 1, (int) ( $this->config['image_count'] ?? 1 ) ) );

		$images = array();
		foreach ( $urls as $position => $url ) {
			$src = Shortcode::image_url( (string) $url, 768 );
			if ( '' === $src ) {
				continue;
			}
			$images[] = array(
				'src'     => $src,
				'srcset'  => Shortcode::image_srcset( (string) $url ),
				'alt'     => 0 === $position ? $alt : '',
				'loading' => 0 === $index && 0 === $position ? 'eager' : 'lazy',
			);
		}
		return $images;
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
	 * @param int    $page      Target page.
	 * @param string $css_class CSS class.
	 * @param string $inner     Inner HTML (escaped).
	 * @param bool   $disabled  Disabled.
	 * @param string $extra     Extra attributes (escaped).
	 */
	private function page_control( int $page, string $css_class, string $inner, bool $disabled, string $extra = '' ): string {
		if ( '' !== $this->page_base && ! $disabled ) {
			return sprintf( '<a class="%1$s" href="%2$s" data-page="%3$d"%4$s>%5$s</a>', esc_attr( $css_class ), esc_url( $this->page_url( $page ) ), $page, $extra, $inner );
		}
		return sprintf( '<button type="button" class="%1$s" data-page="%2$d"%3$s%4$s>%5$s</button>', esc_attr( $css_class ), $page, $disabled ? ' disabled' : '', $extra, $inner );
	}

	/**
	 * Pagination for the configured type.
	 *
	 * @param array $results Search results.
	 */
	public function pagination( array $results ): string {
		$type = (string) ( $this->config['pagination'] ?? 'numbers' );
		return 'numbers' === $type ? $this->numbers( $results ) : $this->load_more( $results, 'infinite' === $type );
	}

	/**
	 * Numbered pagination.
	 *
	 * @param array $results Search results.
	 */
	private function numbers( array $results ): string {
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
		if ( $start > 1 ) {
			$html .= $this->page_control( 1, 'dinv-pagination__page', '1', false, ' aria-label="' . esc_attr( sprintf( $this->labels['page_n'], 1 ) ) . '"' );
			if ( $start > 2 ) {
				$html .= '<span class="dinv-pagination__gap" aria-hidden="true">…</span>';
			}
		}
		for ( $i = $start; $i <= $end; $i++ ) {
			$html .= $this->page_control(
				$i,
				'dinv-pagination__page' . ( $i === $current ? ' is-active' : '' ),
				(string) $i,
				false,
				( $i === $current ? ' aria-current="page"' : '' ) . ' aria-label="' . esc_attr( sprintf( $this->labels['page_n'], $i ) ) . '"'
			);
		}
		if ( $end < $pages ) {
			if ( $end < $pages - 1 ) {
				$html .= '<span class="dinv-pagination__gap" aria-hidden="true">…</span>';
			}
			$html .= $this->page_control( $pages, 'dinv-pagination__page', (string) $pages, false, ' aria-label="' . esc_attr( sprintf( $this->labels['page_n'], $pages ) ) . '"' );
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
	 * "Load more" button (also used as the trigger for infinite scrolling).
	 *
	 * Rendered as a link to the next page so crawlers and visitors without
	 * JavaScript can still reach every vehicle.
	 *
	 * @param array $results  Search results.
	 * @param bool  $infinite Load automatically when it scrolls into view.
	 */
	private function load_more( array $results, bool $infinite ): string {
		$current = (int) $results['page'];
		$pages   = (int) $results['total_pages'];
		$shown   = min( (int) $results['total'], $current * (int) $results['per_page'] );

		/* translators: 1: number of vehicles shown, 2: total number of vehicles. */
		$status = sprintf( __( '%1$s of %2$s vehicles', 'dealer-inventory-for-autoscout24' ), number_format_i18n( $shown ), number_format_i18n( (int) $results['total'] ) );

		$html = '<div class="dinv-loadmore"' . ( $infinite ? ' data-dinv-infinite' : '' ) . '><p class="dinv-loadmore__status" data-dinv-loadmore-status>' . esc_html( $status ) . '</p>';
		if ( $current < $pages ) {
			$html .= $this->page_control( $current + 1, 'dinv-button dinv-button--ghost dinv-loadmore__button', '<span>' . esc_html( $this->labels['load_more'] ) . '</span>', false, ' data-dinv-more' );
		}
		return $html . '</div>';
	}

	/**
	 * Inline SVG icon (static markup, safe to print).
	 *
	 * @param string $name Icon name.
	 */
	public static function icon( string $name ): string {
		$open  = '<svg class="dinv-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
		$paths = array(
			'filter'       => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
			'reset'        => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
			'year'         => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
			'mileage'      => '<path d="M5 21 8.5 3M19 21 15.5 3M12 4.5v3M12 11v3M12 17.5V20"/>',
			'transmission' => '<circle cx="6" cy="4" r="1.5"/><circle cx="18" cy="4" r="1.5"/><circle cx="12" cy="20" r="1.5"/><path d="M6 5.5V19M18 5.5v6.5M6 9h12M12 9v9.5M12 15h6"/>',
			'fuel'         => '<path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17M4 21h13M8 7h5v5H8zM16 8h2l2 2v8a2 2 0 0 1-4 0v-4"/>',
			'power'        => '<path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z"/>',
			'drive'        => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><path d="M12 3v6M12 15v6M3 12h6M15 12h6"/>',
			'arrow'        => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'back'         => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
			'close'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
			'external'     => '<path d="M14 5h5v5"/><path d="M10 14 19 5"/><path d="M19 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
			'chevron'      => '<path d="m7 10 5 5 5-5"/>',
			'search'       => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
			'grid'         => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
			'list'         => '<rect x="3" y="4" width="6" height="6" rx="1"/><rect x="3" y="14" width="6" height="6" rx="1"/><path d="M13 6h8M13 9h5M13 16h8M13 19h5"/>',
			'phone'        => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>',
			'pin'          => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'check'        => '<path d="M20 6 9 17l-5-5"/>',
		);

		return isset( $paths[ $name ] ) ? $open . $paths[ $name ] . '</svg>' : '';
	}
}
