<?php
/**
 * Settings schema: the single source of truth for every option.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes every setting once: type, default, allowed values, scope, label.
 *
 * The admin forms, the stored settings, shortcode attributes, the shortcode
 * builder (and in later versions the block and the Elementor widget) all read
 * from here, so a default or an allowed value is never defined twice.
 *
 * Scopes:
 * - "global":   site-wide setting only (credentials, sync, design tokens).
 * - "instance": shortcode attribute only (filter presets, instance name).
 * - "both":     site-wide default that each inventory instance may override.
 *
 * Resolution order for an instance: instance attribute > global setting > default.
 */
final class Schema {

	public const SCOPE_GLOBAL   = 'global';
	public const SCOPE_INSTANCE = 'instance';
	public const SCOPE_BOTH     = 'both';

	/**
	 * Field definitions cached per locale.
	 *
	 * @var array<string, array<string, array>>
	 */
	private static array $cache = array();

	/**
	 * All field definitions keyed by setting name.
	 *
	 * @return array<string, array>
	 */
	public static function fields(): array {
		$locale = determine_locale();
		if ( isset( self::$cache[ $locale ] ) ) {
			return self::$cache[ $locale ];
		}

		$fields = array_merge(
			self::connection_fields(),
			self::sync_fields(),
			self::display_fields(),
			self::filter_visibility_fields(),
			self::format_fields(),
			self::design_fields(),
			self::preset_fields()
		);

		foreach ( $fields as $key => &$field ) {
			$field += array(
				'key'     => $key,
				'scope'   => self::SCOPE_GLOBAL,
				'section' => '',
				'help'    => '',
				'attr'    => $key,
			);
		}
		unset( $field );

		/**
		 * Filters the settings schema.
		 *
		 * @param array<string, array> $fields Field definitions.
		 */
		self::$cache[ $locale ] = (array) apply_filters( 'dinv_schema_fields', $fields );

		return self::$cache[ $locale ];
	}

	/**
	 * One field definition.
	 *
	 * @param string $key Setting name.
	 */
	public static function field( string $key ): ?array {
		return self::fields()[ $key ] ?? null;
	}

	/**
	 * Fields of one group, optionally only those stored globally.
	 *
	 * @param string $group Group name.
	 * @return array<string, array>
	 */
	public static function group( string $group ): array {
		return array_filter( self::fields(), static fn( array $field ) => $group === $field['group'] );
	}

	/**
	 * Defaults of every setting that is stored site-wide.
	 *
	 * @return array<string, mixed>
	 */
	public static function global_defaults(): array {
		$defaults = array();
		foreach ( self::fields() as $key => $field ) {
			if ( self::SCOPE_INSTANCE !== $field['scope'] ) {
				$defaults[ $key ] = $field['default'];
			}
		}
		return $defaults;
	}

	/**
	 * Validate and normalize a value. Invalid input returns the default.
	 *
	 * @param string $key   Setting name.
	 * @param mixed  $value Raw value (already unslashed).
	 * @return mixed
	 */
	public static function sanitize( string $key, $value ) {
		$field = self::field( $key );
		if ( null === $field ) {
			return null;
		}

		$default = $field['default'];

		switch ( $field['type'] ) {
			case 'bool':
				if ( is_bool( $value ) ) {
					return $value;
				}
				$value = strtolower( trim( (string) $value ) );
				if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
					return true;
				}
				if ( in_array( $value, array( '0', 'false', 'no', 'off', '' ), true ) ) {
					return false;
				}
				return (bool) $default;

			case 'int':
				if ( ! is_numeric( $value ) ) {
					return $default;
				}
				$value = (int) $value;
				if ( ( isset( $field['min'] ) && $value < $field['min'] ) || ( isset( $field['max'] ) && $value > $field['max'] ) ) {
					return $default;
				}
				return $value;

			case 'number':
				if ( '' === $value || null === $value ) {
					return '';
				}
				return is_numeric( $value ) && (float) $value >= 0 ? (float) $value : '';

			case 'enum':
				$value = is_int( $value ) ? $value : sanitize_key( (string) $value );
				foreach ( array_keys( $field['options'] ) as $option ) {
					if ( (string) $option === (string) $value ) {
						return $option;
					}
				}
				return $default;

			case 'color':
				$color = sanitize_hex_color( (string) $value );
				return $color ? strtoupper( $color ) : $default;

			case 'url':
				$url = esc_url_raw( trim( (string) $value ) );
				if ( '' !== $url && isset( $field['validate'] ) && is_callable( $field['validate'] ) && ! call_user_func( $field['validate'], $url ) ) {
					return $default;
				}
				return $url;

			case 'time':
				$value = sanitize_text_field( (string) $value );
				return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : $default;

			case 'key':
				return sanitize_key( (string) $value );

			case 'filter_value':
				return sanitize_key( (string) $value );

			case 'text':
			case 'secret':
			default:
				$value = sanitize_text_field( (string) $value );
				if ( isset( $field['max_length'] ) ) {
					$value = mb_substr( $value, 0, (int) $field['max_length'] );
				}
				return $value;
		}
	}

	/**
	 * Resolve the configuration of one inventory instance.
	 *
	 * Instance attribute > global setting > plugin default.
	 *
	 * @param array $atts Raw shortcode / block / widget attributes.
	 * @return array<string, mixed>
	 */
	public static function resolve( array $atts ): array {
		$config = array();

		foreach ( self::fields() as $key => $field ) {
			if ( self::SCOPE_GLOBAL === $field['scope'] ) {
				continue;
			}

			$attr = $field['attr'];
			if ( array_key_exists( $attr, $atts ) && null !== $atts[ $attr ] && '' !== $atts[ $attr ] ) {
				$config[ $key ] = self::sanitize( $key, $atts[ $attr ] );
				continue;
			}

			if ( self::SCOPE_BOTH === $field['scope'] ) {
				$config[ $key ] = Settings::get( $key, $field['default'] );
				continue;
			}

			$config[ $key ] = $field['default'];
		}

		return $config;
	}

	/**
	 * Fields a shortcode instance may set, keyed by attribute name.
	 *
	 * @return array<string, array>
	 */
	public static function instance_fields(): array {
		$out = array();
		foreach ( self::fields() as $field ) {
			if ( self::SCOPE_GLOBAL !== $field['scope'] ) {
				$out[ $field['attr'] ] = $field;
			}
		}
		return $out;
	}

	/**
	 * Admin section labels for a group.
	 *
	 * @param string $group Group name.
	 * @return array<string, string>
	 */
	public static function sections( string $group ): array {
		$sections = array();
		foreach ( self::group( $group ) as $field ) {
			if ( '' !== $field['section'] ) {
				$sections[ $field['section'] ] = $field['section'];
			}
		}
		return $sections;
	}

	/**
	 * Reset the per-request cache (tests, locale switches).
	 */
	public static function reset(): void {
		self::$cache = array();
	}

	/**
	 * Sort options with labels.
	 *
	 * @return array<string, string>
	 */
	public static function sort_options(): array {
		return array(
			'newest'          => __( 'Newest listings first', 'dealer-inventory-for-autoscout24' ),
			'oldest'          => __( 'Oldest listings first', 'dealer-inventory-for-autoscout24' ),
			'price_asc'       => __( 'Price: low to high', 'dealer-inventory-for-autoscout24' ),
			'price_desc'      => __( 'Price: high to low', 'dealer-inventory-for-autoscout24' ),
			'mileage_asc'     => __( 'Mileage: low to high', 'dealer-inventory-for-autoscout24' ),
			'mileage_desc'    => __( 'Mileage: high to low', 'dealer-inventory-for-autoscout24' ),
			'year_desc'       => __( 'Year: newest first', 'dealer-inventory-for-autoscout24' ),
			'year_asc'        => __( 'Year: oldest first', 'dealer-inventory-for-autoscout24' ),
			'power_desc'      => __( 'Power: highest first', 'dealer-inventory-for-autoscout24' ),
			'make_model_asc'  => __( 'Make: A to Z', 'dealer-inventory-for-autoscout24' ),
			'make_model_desc' => __( 'Make: Z to A', 'dealer-inventory-for-autoscout24' ),
		);
	}

	/**
	 * Filter fields shown in the front-end filter form, in display order.
	 *
	 * @return array<string, string> Filter key => label.
	 */
	public static function filter_labels(): array {
		return array(
			'category'     => __( 'Vehicle type', 'dealer-inventory-for-autoscout24' ),
			'make'         => __( 'Make', 'dealer-inventory-for-autoscout24' ),
			'version'      => __( 'Version / keyword', 'dealer-inventory-for-autoscout24' ),
			'price_from'   => __( 'Price from', 'dealer-inventory-for-autoscout24' ),
			'price_to'     => __( 'Price up to', 'dealer-inventory-for-autoscout24' ),
			'year_from'    => __( 'Year from', 'dealer-inventory-for-autoscout24' ),
			'year_to'      => __( 'Year up to', 'dealer-inventory-for-autoscout24' ),
			'mileage_from' => __( 'Mileage from', 'dealer-inventory-for-autoscout24' ),
			'mileage_to'   => __( 'Mileage up to', 'dealer-inventory-for-autoscout24' ),
			'fuel'         => __( 'Fuel', 'dealer-inventory-for-autoscout24' ),
			'transmission' => __( 'Transmission', 'dealer-inventory-for-autoscout24' ),
			'body'         => __( 'Body type', 'dealer-inventory-for-autoscout24' ),
			'drive'        => __( 'Drive', 'dealer-inventory-for-autoscout24' ),
			'condition'    => __( 'Condition', 'dealer-inventory-for-autoscout24' ),
			'warranty'     => __( 'With warranty', 'dealer-inventory-for-autoscout24' ),
			'power_from'   => __( 'Power from', 'dealer-inventory-for-autoscout24' ),
			'power_to'     => __( 'Power up to', 'dealer-inventory-for-autoscout24' ),
		);
	}

	/**
	 * Connection fields.
	 *
	 * @return array<string, array>
	 */
	private static function connection_fields(): array {
		$providers = array();
		foreach ( Providers::all() as $id => $provider ) {
			$providers[ $id ] = $provider->label();
		}

		$languages = array( 'auto' => __( 'Site language', 'dealer-inventory-for-autoscout24' ) );
		foreach ( array(
			'de' => __( 'German', 'dealer-inventory-for-autoscout24' ),
			'fr' => __( 'French', 'dealer-inventory-for-autoscout24' ),
			'it' => __( 'Italian', 'dealer-inventory-for-autoscout24' ),
			'en' => __( 'English', 'dealer-inventory-for-autoscout24' ),
		) as $code => $label ) {
			$languages[ $code ] = $label;
		}

		return array(
			'provider'         => array(
				'group'   => 'connection',
				'type'    => 'enum',
				'default' => Providers::DEFAULT_ID,
				'options' => $providers,
				'label'   => __( 'Marketplace', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'The AutoScout24 country site your listings are published on.', 'dealer-inventory-for-autoscout24' ),
			),
			'client_id'        => array(
				'group'    => 'connection',
				'type'     => 'text',
				'default'  => '',
				'constant' => 'DINV_CLIENT_ID',
				'label'    => __( 'Client ID', 'dealer-inventory-for-autoscout24' ),
			),
			'client_secret'    => array(
				'group'    => 'connection',
				'type'     => 'secret',
				'default'  => '',
				'constant' => 'DINV_CLIENT_SECRET',
				'label'    => __( 'Client Secret', 'dealer-inventory-for-autoscout24' ),
				'help'     => __( 'Stored encrypted. Leave empty to keep the saved secret.', 'dealer-inventory-for-autoscout24' ),
			),
			'seller_id'        => array(
				'group'    => 'connection',
				'type'     => 'int',
				'default'  => 0,
				'min'      => 0,
				'constant' => 'DINV_SELLER_ID',
				'label'    => __( 'Seller ID', 'dealer-inventory-for-autoscout24' ),
				'help'     => __( 'Your dealer number on AutoScout24.', 'dealer-inventory-for-autoscout24' ),
			),
			'dealer_url'       => array(
				'group'    => 'connection',
				'type'     => 'url',
				'default'  => '',
				'validate' => static fn( string $url ): bool => Connection::current()->provider()->is_valid_dealer_url( $url ),
				'label'    => __( 'Dealer page URL', 'dealer-inventory-for-autoscout24' ),
				'help'     => __( 'Optional. Your public dealer page on AutoScout24, used for the header link.', 'dealer-inventory-for-autoscout24' ),
			),
			'content_language' => array(
				'group'   => 'connection',
				'type'    => 'enum',
				'default' => 'auto',
				'options' => $languages,
				'label'   => __( 'Listing text language', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Language in which listing texts are downloaded. Unsupported languages fall back to the marketplace default.', 'dealer-inventory-for-autoscout24' ),
			),
		);
	}

	/**
	 * Synchronization fields.
	 *
	 * @return array<string, array>
	 */
	private static function sync_fields(): array {
		return array(
			'sync_mode'     => array(
				'group'   => 'sync',
				'type'    => 'enum',
				'default' => 'interval',
				'options' => array(
					'interval' => __( 'Recurring interval', 'dealer-inventory-for-autoscout24' ),
					'daily'    => __( 'Once a day at a fixed time', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Schedule', 'dealer-inventory-for-autoscout24' ),
			),
			'sync_interval' => array(
				'group'   => 'sync',
				'type'    => 'enum',
				'default' => 60,
				'options' => array(
					15  => __( 'Every 15 minutes', 'dealer-inventory-for-autoscout24' ),
					30  => __( 'Every 30 minutes', 'dealer-inventory-for-autoscout24' ),
					60  => __( 'Every hour', 'dealer-inventory-for-autoscout24' ),
					120 => __( 'Every 2 hours', 'dealer-inventory-for-autoscout24' ),
					240 => __( 'Every 4 hours', 'dealer-inventory-for-autoscout24' ),
					360 => __( 'Every 6 hours', 'dealer-inventory-for-autoscout24' ),
					720 => __( 'Every 12 hours', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Interval', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Used with the recurring schedule. Shorter intervals use more API requests.', 'dealer-inventory-for-autoscout24' ),
			),
			'sync_time'     => array(
				'group'   => 'sync',
				'type'    => 'time',
				'default' => '03:00',
				'label'   => __( 'Daily time', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Used with the daily schedule, in the site time zone.', 'dealer-inventory-for-autoscout24' ),
			),
			'sync_warranty' => array(
				'group'   => 'sync',
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Download warranty information', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Needed for the "With warranty" filter. Costs one extra pass over the listings per sync.', 'dealer-inventory-for-autoscout24' ),
			),
		);
	}

	/**
	 * Display fields (site-wide defaults, overridable per instance).
	 *
	 * @return array<string, array>
	 */
	private static function display_fields(): array {
		$results = __( 'Results', 'dealer-inventory-for-autoscout24' );
		$parts   = __( 'Visible parts', 'dealer-inventory-for-autoscout24' );
		$links   = __( 'Vehicle links', 'dealer-inventory-for-autoscout24' );

		return array(
			'per_page'         => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $results,
				'type'    => 'int',
				'default' => 12,
				'min'     => 1,
				'max'     => 48,
				'label'   => __( 'Vehicles per page', 'dealer-inventory-for-autoscout24' ),
			),
			'layout'           => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $results,
				'type'    => 'enum',
				'default' => 'card',
				'options' => array(
					'card' => __( 'Card grid', 'dealer-inventory-for-autoscout24' ),
					'list' => __( 'List', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Layout', 'dealer-inventory-for-autoscout24' ),
			),
			'columns'          => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $results,
				'type'    => 'int',
				'default' => 3,
				'min'     => 1,
				'max'     => 4,
				'label'   => __( 'Card columns (desktop)', 'dealer-inventory-for-autoscout24' ),
			),
			'sort'             => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $results,
				'type'    => 'enum',
				'default' => 'newest',
				'options' => self::sort_options(),
				'label'   => __( 'Default sort order', 'dealer-inventory-for-autoscout24' ),
			),
			'show_header'      => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Header bar', 'dealer-inventory-for-autoscout24' ),
			),
			'header_title'     => array(
				'group'      => 'display',
				'scope'      => self::SCOPE_BOTH,
				'section'    => $parts,
				'type'       => 'text',
				'default'    => '',
				'max_length' => 120,
				'label'      => __( 'Header title', 'dealer-inventory-for-autoscout24' ),
				'help'       => __( 'Leave empty to use "Site name · Vehicle search".', 'dealer-inventory-for-autoscout24' ),
			),
			'show_count'       => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Vehicle count', 'dealer-inventory-for-autoscout24' ),
			),
			'show_filters'     => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Filters', 'dealer-inventory-for-autoscout24' ),
			),
			'show_sort'        => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Sort menu', 'dealer-inventory-for-autoscout24' ),
			),
			'show_pagination'  => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Pagination', 'dealer-inventory-for-autoscout24' ),
			),
			'show_dealer_link' => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Link to the dealer page on AutoScout24', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Shown only when a dealer page URL is set.', 'dealer-inventory-for-autoscout24' ),
			),
			'show_powered_by'  => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( '"Listings from AutoScout24" note', 'dealer-inventory-for-autoscout24' ),
			),
			'url_state'        => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $parts,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Keep filters, sort and page in the URL', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Makes results shareable and paginated pages crawlable.', 'dealer-inventory-for-autoscout24' ),
			),
			'link_to'          => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $links,
				'type'    => 'enum',
				'default' => 'autoscout',
				'options' => array(
					'autoscout' => __( 'Listing on AutoScout24', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Vehicle links open', 'dealer-inventory-for-autoscout24' ),
			),
			'new_tab'          => array(
				'group'   => 'display',
				'scope'   => self::SCOPE_BOTH,
				'section' => $links,
				'type'    => 'bool',
				'default' => false,
				'label'   => __( 'Open external links in a new tab', 'dealer-inventory-for-autoscout24' ),
			),
			'button_text'      => array(
				'group'      => 'display',
				'scope'      => self::SCOPE_BOTH,
				'section'    => $links,
				'type'       => 'text',
				'default'    => '',
				'max_length' => 40,
				'label'      => __( 'Button text', 'dealer-inventory-for-autoscout24' ),
				'help'       => __( 'Leave empty for "View vehicle".', 'dealer-inventory-for-autoscout24' ),
			),
			'card_label'       => array(
				'group'      => 'display',
				'scope'      => self::SCOPE_BOTH,
				'section'    => $links,
				'type'       => 'text',
				'default'    => '',
				'max_length' => 80,
				'label'      => __( 'Card label', 'dealer-inventory-for-autoscout24' ),
				'help'       => __( 'Optional small label above each card title, for example "New arrival".', 'dealer-inventory-for-autoscout24' ),
			),
		);
	}

	/**
	 * Which filters are shown (site-wide default, overridable per instance).
	 *
	 * @return array<string, array>
	 */
	private static function filter_visibility_fields(): array {
		$fields = array();
		foreach ( self::filter_labels() as $filter => $label ) {
			$fields[ 'show_' . $filter ] = array(
				'group'   => 'filters',
				'scope'   => self::SCOPE_BOTH,
				'section' => __( 'Filters shown', 'dealer-inventory-for-autoscout24' ),
				'type'    => 'bool',
				'default' => true,
				'label'   => $label,
			);
		}
		return $fields;
	}

	/**
	 * Units and number formatting.
	 *
	 * @return array<string, array>
	 */
	private static function format_fields(): array {
		return array(
			'currency'   => array(
				'group'   => 'format',
				'scope'   => self::SCOPE_BOTH,
				'type'    => 'enum',
				'default' => 'auto',
				'options' => array(
					'auto' => __( 'Marketplace currency', 'dealer-inventory-for-autoscout24' ),
					'CHF'  => 'CHF',
					'EUR'  => 'EUR',
				),
				'label'   => __( 'Currency', 'dealer-inventory-for-autoscout24' ),
			),
			'power_unit' => array(
				'group'   => 'format',
				'scope'   => self::SCOPE_BOTH,
				'type'    => 'enum',
				'default' => 'hp',
				'options' => array(
					'hp'   => __( 'Horsepower', 'dealer-inventory-for-autoscout24' ),
					'kw'   => __( 'Kilowatts', 'dealer-inventory-for-autoscout24' ),
					'both' => __( 'Both', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Power unit', 'dealer-inventory-for-autoscout24' ),
			),
		);
	}

	/**
	 * Design tokens (site-wide).
	 *
	 * @return array<string, array>
	 */
	private static function design_fields(): array {
		$colors = __( 'Colors', 'dealer-inventory-for-autoscout24' );
		$layout = __( 'Typography and spacing', 'dealer-inventory-for-autoscout24' );
		$cards  = __( 'Cards', 'dealer-inventory-for-autoscout24' );
		$preset = Design::presets()[ Design::DEFAULT_PRESET ];

		$fields = array(
			'design_preset' => array(
				'group'   => 'design',
				'type'    => 'enum',
				'default' => Design::DEFAULT_PRESET,
				'options' => wp_list_pluck( Design::presets(), 'label' ),
				'label'   => __( 'Preset', 'dealer-inventory-for-autoscout24' ),
			),
		);

		foreach ( Design::color_labels() as $key => $label ) {
			$fields[ $key ] = array(
				'group'   => 'design',
				'section' => $colors,
				'type'    => 'color',
				'default' => $preset[ $key ],
				'label'   => $label,
			);
		}

		$ratios = array(
			'3-2'   => '3:2',
			'4-3'   => '4:3',
			'16-10' => '16:10',
			'16-9'  => '16:9',
			'1-1'   => '1:1',
		);

		return $fields + array(
			'design_font'         => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'enum',
				'default' => 'inherit',
				'options' => array(
					'inherit' => __( 'Theme font', 'dealer-inventory-for-autoscout24' ),
					'system'  => __( 'System UI', 'dealer-inventory-for-autoscout24' ),
					'serif'   => __( 'Serif', 'dealer-inventory-for-autoscout24' ),
				),
				'label'   => __( 'Font', 'dealer-inventory-for-autoscout24' ),
			),
			'design_max_width'    => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 1280,
				'min'     => 600,
				'max'     => 2400,
				'unit'    => 'px',
				'label'   => __( 'Maximum width', 'dealer-inventory-for-autoscout24' ),
			),
			'design_padding'      => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 32,
				'min'     => 0,
				'max'     => 96,
				'unit'    => 'px',
				'label'   => __( 'Side padding', 'dealer-inventory-for-autoscout24' ),
			),
			'design_radius_large' => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 16,
				'min'     => 0,
				'max'     => 48,
				'unit'    => 'px',
				'label'   => __( 'Outer radius', 'dealer-inventory-for-autoscout24' ),
			),
			'design_radius'       => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 12,
				'min'     => 0,
				'max'     => 40,
				'unit'    => 'px',
				'label'   => __( 'Card radius', 'dealer-inventory-for-autoscout24' ),
			),
			'design_radius_small' => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 8,
				'min'     => 0,
				'max'     => 24,
				'unit'    => 'px',
				'label'   => __( 'Input and button radius', 'dealer-inventory-for-autoscout24' ),
			),
			'design_gap'          => array(
				'group'   => 'design',
				'section' => $layout,
				'type'    => 'int',
				'default' => 16,
				'min'     => 4,
				'max'     => 48,
				'unit'    => 'px',
				'label'   => __( 'Base spacing', 'dealer-inventory-for-autoscout24' ),
			),
			'design_image_width'  => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'int',
				'default' => 280,
				'min'     => 160,
				'max'     => 480,
				'unit'    => 'px',
				'label'   => __( 'Image width in the list layout', 'dealer-inventory-for-autoscout24' ),
			),
			'design_image_ratio'  => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'enum',
				'default' => '4-3',
				'options' => $ratios,
				'label'   => __( 'Image ratio', 'dealer-inventory-for-autoscout24' ),
			),
			'design_mobile_ratio' => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'enum',
				'default' => '16-10',
				'options' => $ratios,
				'label'   => __( 'Image ratio on phones', 'dealer-inventory-for-autoscout24' ),
			),
			'design_show_teaser'  => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Show teaser text', 'dealer-inventory-for-autoscout24' ),
			),
			'design_show_specs'   => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Show key specifications', 'dealer-inventory-for-autoscout24' ),
			),
			'design_show_cta'     => array(
				'group'   => 'design',
				'section' => $cards,
				'type'    => 'bool',
				'default' => true,
				'label'   => __( 'Show button', 'dealer-inventory-for-autoscout24' ),
			),
		);
	}

	/**
	 * Per-instance preset filters and identifiers (shortcode only).
	 *
	 * @return array<string, array>
	 */
	private static function preset_fields(): array {
		$fields = array(
			'instance' => array(
				'group'      => 'preset',
				'scope'      => self::SCOPE_INSTANCE,
				'type'       => 'key',
				'default'    => '',
				'label'      => __( 'Instance name', 'dealer-inventory-for-autoscout24' ),
				'help'       => __( 'Unique name when several inventories are on one page; keeps their URL parameters apart.', 'dealer-inventory-for-autoscout24' ),
				'max_length' => 40,
			),
			'query'    => array(
				'group'   => 'preset',
				'scope'   => self::SCOPE_INSTANCE,
				'type'    => 'text',
				'default' => '',
				'label'   => __( 'Compact filter query', 'dealer-inventory-for-autoscout24' ),
				'help'    => __( 'Example: make=bmw&body=suv&price_to=60000. Values here win over the single filter fields.', 'dealer-inventory-for-autoscout24' ),
			),
		);

		$text = array(
			'category'     => __( 'Vehicle type', 'dealer-inventory-for-autoscout24' ),
			'make'         => __( 'Make', 'dealer-inventory-for-autoscout24' ),
			'model'        => __( 'Model', 'dealer-inventory-for-autoscout24' ),
			'fuel'         => __( 'Fuel', 'dealer-inventory-for-autoscout24' ),
			'transmission' => __( 'Transmission', 'dealer-inventory-for-autoscout24' ),
			'body'         => __( 'Body type', 'dealer-inventory-for-autoscout24' ),
			'drive'        => __( 'Drive', 'dealer-inventory-for-autoscout24' ),
			'condition'    => __( 'Condition', 'dealer-inventory-for-autoscout24' ),
		);
		foreach ( $text as $key => $label ) {
			$fields[ $key ] = array(
				'group'   => 'preset',
				'scope'   => self::SCOPE_INSTANCE,
				'type'    => 'filter_value',
				'default' => '',
				'label'   => $label,
			);
		}

		$fields['version']  = array(
			'group'   => 'preset',
			'scope'   => self::SCOPE_INSTANCE,
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Version contains', 'dealer-inventory-for-autoscout24' ),
		);
		$fields['warranty'] = array(
			'group'   => 'preset',
			'scope'   => self::SCOPE_INSTANCE,
			'type'    => 'bool',
			'default' => false,
			'label'   => __( 'Only vehicles with warranty', 'dealer-inventory-for-autoscout24' ),
		);

		foreach ( array(
			'price_from'   => __( 'Price from', 'dealer-inventory-for-autoscout24' ),
			'price_to'     => __( 'Price up to', 'dealer-inventory-for-autoscout24' ),
			'year_from'    => __( 'Year from', 'dealer-inventory-for-autoscout24' ),
			'year_to'      => __( 'Year up to', 'dealer-inventory-for-autoscout24' ),
			'mileage_from' => __( 'Mileage from', 'dealer-inventory-for-autoscout24' ),
			'mileage_to'   => __( 'Mileage up to', 'dealer-inventory-for-autoscout24' ),
			'power_from'   => __( 'Power from (hp)', 'dealer-inventory-for-autoscout24' ),
			'power_to'     => __( 'Power up to (hp)', 'dealer-inventory-for-autoscout24' ),
		) as $key => $label ) {
			$fields[ $key ] = array(
				'group'   => 'preset',
				'scope'   => self::SCOPE_INSTANCE,
				'type'    => 'number',
				'default' => '',
				'label'   => $label,
			);
		}

		return $fields;
	}
}
