<?php
/**
 * Public read-only REST endpoints.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local inventory endpoints used by the front-end script.
 *
 * They expose only data that is already public on the inventory page and
 * never call the marketplace API, so they are intentionally public
 * (permission_callback returns true).
 */
final class Rest {

	public const NAMESPACE = 'dinv/v1';

	/**
	 * Register routes.
	 */
	public static function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/vehicles',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'vehicles' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'status' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/models',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'models' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'make' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Filtered, sorted and paginated vehicles as rendered HTML.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function vehicles( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_query_params();

		// Render in the visitor's language (multilingual sites pass the page locale).
		$switched = self::switch_locale( (string) ( $params['dinv_locale'] ?? '' ) );

		// Display settings of the instance (only display/filter/format groups).
		$atts = array();
		foreach ( Schema::instance_fields() as $attr => $field ) {
			if ( in_array( $field['group'], Shortcode::CLIENT_GROUPS, true ) && isset( $params[ $attr ] ) && ! is_array( $params[ $attr ] ) ) {
				$atts[ $attr ] = wp_unslash( (string) $params[ $attr ] );
			}
		}
		$config = Schema::resolve( $atts );

		$filters = Shortcode::filters_from_request( $params );
		$page    = max( 1, absint( $params['dinv_page'] ?? 1 ) );
		$sort    = Shortcode::valid_sort( sanitize_key( (string) ( $params['dinv_sort'] ?? '' ) ), (string) $config['sort'] );

		$results  = Repository::search( $filters, $page, (int) $config['per_page'], $sort, $config['show_pagination'] || $config['show_count'] );
		$renderer = new Renderer( $config );

		$base = Shortcode::sanitize_base_url( (string) wp_unslash( $params['dinv_base'] ?? '' ) );
		if ( '' !== $base ) {
			$instance = sanitize_key( (string) ( $params['dinv_instance'] ?? '' ) );
			$renderer->link_pages( $base, Shortcode::url_params( $filters, $sort, (string) $config['sort'], $instance ), $instance );
		}

		$response = self::response(
			array(
				'success'    => true,
				'total'      => $results['total'],
				'countLabel' => Labels::vehicle_noun( (int) $results['total'] ),
				'page'       => $results['page'],
				'totalPages' => $results['total_pages'],
				'html'       => $renderer->items( $results['items'] ),
				'pagination' => $config['show_pagination'] ? $renderer->pagination( $results ) : '',
			),
			(string) ( $params['v'] ?? '' ) === (string) Sync::version()
		);

		if ( $switched ) {
			restore_previous_locale();
		}

		return $response;
	}

	/**
	 * Switch to an installed locale.
	 *
	 * @param string $locale Requested locale, e.g. "fr_FR".
	 * @return bool Whether the locale was switched.
	 */
	private static function switch_locale( string $locale ): bool {
		$locale = preg_replace( '/[^A-Za-z_]/', '', $locale );
		if ( '' === $locale || determine_locale() === $locale ) {
			return false;
		}
		if ( 'en_US' !== $locale && ! in_array( $locale, get_available_languages(), true ) ) {
			return false;
		}
		add_filter( 'dinv_current_language', static fn() => I18n::two_letter( $locale ) );
		return switch_to_locale( $locale );
	}

	/**
	 * Current inventory version (changes after every successful sync).
	 */
	public static function status(): WP_REST_Response {
		return self::response( array( 'version' => Sync::version() ), false );
	}

	/**
	 * Models of a make with vehicle counts.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function models( WP_REST_Request $request ): WP_REST_Response {
		return self::response( array( 'models' => Repository::models_for_make( (string) $request->get_param( 'make' ) ) ), false );
	}

	/**
	 * Response that is publicly cacheable when it is tied to the current
	 * inventory version (the URL then changes after every sync), and not
	 * cacheable otherwise.
	 *
	 * @param array $data      Body.
	 * @param bool  $cacheable Version-bound response.
	 */
	private static function response( array $data, bool $cacheable ): WP_REST_Response {
		$response = new WP_REST_Response( $data, 200 );

		if ( $cacheable && ! is_user_logged_in() ) {
			$response->header( 'Cache-Control', 'public, max-age=600, s-maxage=600' );
			return $response;
		}

		foreach ( wp_get_nocache_headers() as $name => $value ) {
			if ( '' !== $value ) {
				$response->header( $name, $value );
			}
		}
		return $response;
	}
}
