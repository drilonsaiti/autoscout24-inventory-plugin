<?php
/**
 * Design presets and CSS custom properties.
 *
 * @package DealerInventory
 */

namespace DealerInventory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the design settings into CSS custom properties on .dinv-inventory.
 *
 * Every visual value in the stylesheet reads a --dinv-* custom property, so
 * themes can restyle the plugin by overriding those properties, without
 * fighting specificity.
 */
final class Design {

	public const DEFAULT_PRESET = 'classic';

	/**
	 * Presets: colors plus sizes. Every preset passes WCAG AA contrast for
	 * body text, secondary text and button text.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function presets(): array {
		return array(
			'classic'      => array(
				'label'                => __( 'Classic', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#2457D6',
				'design_accent_hover'  => '#1B46A8',
				'design_on_accent'     => '#FFFFFF',
				'design_bg'            => '#FFFFFF',
				'design_bg_alt'        => '#F6F7F9',
				'design_panel'         => '#F1F3F6',
				'design_panel_2'       => '#FFFFFF',
				'design_text'          => '#14171C',
				'design_muted'         => '#5B6370',
				'design_border'        => '#DDE1E7',
				'design_border_hi'     => '#B6BDC8',
				'design_border_accent' => '#C9D6F5',
				'design_max_width'     => 1280,
				'design_padding'       => 32,
				'design_gap'           => 16,
				'design_radius_large'  => 16,
				'design_radius'        => 12,
				'design_radius_small'  => 8,
				'design_shadow'        => 'soft',
			),
			'minimal'      => array(
				'label'                => __( 'Minimal', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#111827',
				'design_accent_hover'  => '#374151',
				'design_on_accent'     => '#FFFFFF',
				'design_bg'            => '#FFFFFF',
				'design_bg_alt'        => '#FAFAFA',
				'design_panel'         => '#F4F4F5',
				'design_panel_2'       => '#FFFFFF',
				'design_text'          => '#111827',
				'design_muted'         => '#4B5563',
				'design_border'        => '#E5E7EB',
				'design_border_hi'     => '#9CA3AF',
				'design_border_accent' => '#D1D5DB',
				'design_max_width'     => 1280,
				'design_padding'       => 24,
				'design_gap'           => 16,
				'design_radius_large'  => 4,
				'design_radius'        => 4,
				'design_radius_small'  => 2,
				'design_shadow'        => 'none',
			),
			'premium_dark' => array(
				'label'                => __( 'Premium dark', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#C8A96A',
				'design_accent_hover'  => '#DCC08A',
				'design_on_accent'     => '#111111',
				'design_bg'            => '#0D0D0F',
				'design_bg_alt'        => '#141416',
				'design_panel'         => '#1A1A1D',
				'design_panel_2'       => '#121214',
				'design_text'          => '#F4F4F5',
				'design_muted'         => '#A1A1AA',
				'design_border'        => '#2A2A2E',
				'design_border_hi'     => '#4A4A50',
				'design_border_accent' => '#4A3F2A',
				'design_max_width'     => 1320,
				'design_padding'       => 40,
				'design_gap'           => 18,
				'design_radius_large'  => 20,
				'design_radius'        => 14,
				'design_radius_small'  => 10,
				'design_shadow'        => 'strong',
			),
			'compact'      => array(
				'label'                => __( 'Compact', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#0F766E',
				'design_accent_hover'  => '#115E59',
				'design_on_accent'     => '#FFFFFF',
				'design_bg'            => '#FFFFFF',
				'design_bg_alt'        => '#F8FAFC',
				'design_panel'         => '#F1F5F9',
				'design_panel_2'       => '#FFFFFF',
				'design_text'          => '#0F172A',
				'design_muted'         => '#475569',
				'design_border'        => '#E2E8F0',
				'design_border_hi'     => '#94A3B8',
				'design_border_accent' => '#99F6E4',
				'design_max_width'     => 1200,
				'design_padding'       => 16,
				'design_gap'           => 10,
				'design_radius_large'  => 8,
				'design_radius'        => 6,
				'design_radius_small'  => 4,
				'design_shadow'        => 'none',
			),
		);
	}

	/**
	 * Labels of the color settings.
	 *
	 * @return array<string, string>
	 */
	public static function color_labels(): array {
		return array(
			'design_accent'        => __( 'Accent', 'dealer-inventory-for-autoscout24' ),
			'design_accent_hover'  => __( 'Accent on hover', 'dealer-inventory-for-autoscout24' ),
			'design_on_accent'     => __( 'Text on accent', 'dealer-inventory-for-autoscout24' ),
			'design_bg'            => __( 'Background', 'dealer-inventory-for-autoscout24' ),
			'design_bg_alt'        => __( 'Secondary background', 'dealer-inventory-for-autoscout24' ),
			'design_panel'         => __( 'Panels', 'dealer-inventory-for-autoscout24' ),
			'design_panel_2'       => __( 'Inputs', 'dealer-inventory-for-autoscout24' ),
			'design_text'          => __( 'Text', 'dealer-inventory-for-autoscout24' ),
			'design_muted'         => __( 'Secondary text', 'dealer-inventory-for-autoscout24' ),
			'design_border'        => __( 'Borders', 'dealer-inventory-for-autoscout24' ),
			'design_border_hi'     => __( 'Highlighted borders', 'dealer-inventory-for-autoscout24' ),
			'design_border_accent' => __( 'Accent borders', 'dealer-inventory-for-autoscout24' ),
		);
	}

	/**
	 * CSS custom properties for the current settings.
	 *
	 * @param array|null $settings Settings to render (defaults to the saved ones).
	 */
	public static function css( ?array $settings = null ): string {
		$tokens = self::tokens( $settings ?? Settings::all() );

		$css = '.dinv-inventory,.dinv-detail{';
		foreach ( $tokens as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}

		// Native controls (checkboxes, scrollbars, date pickers) follow dark designs.
		$settings = $settings ?? Settings::all();
		$bg       = sanitize_hex_color( (string) ( $settings['design_bg'] ?? '' ) );
		if ( empty( $settings['use_theme_styles'] ) && $bg && self::luminance( $bg ) < 0.2 ) {
			$css .= 'color-scheme:dark;';
		}
		return $css . '}';
	}

	/**
	 * Custom property values.
	 *
	 * @param array $s Settings.
	 * @return array<string, string>
	 */
	public static function tokens( array $s ): array {
		$color = static function ( string $key ) use ( $s ): string {
			$value = sanitize_hex_color( (string) ( $s[ $key ] ?? '' ) );
			return $value ? $value : (string) self::presets()[ self::DEFAULT_PRESET ][ $key ];
		};
		$int   = static fn( string $key ): int => absint( $s[ $key ] ?? self::presets()[ self::DEFAULT_PRESET ][ $key ] ?? 0 );

		$shadows = array(
			'none'   => array( 'none', 'none' ),
			'soft'   => array( '0 1px 2px rgb(0 0 0 / 6%), 0 4px 14px rgb(0 0 0 / 6%)', '0 10px 28px rgb(0 0 0 / 12%)' ),
			'strong' => array( '0 2px 4px rgb(0 0 0 / 18%), 0 12px 32px rgb(0 0 0 / 28%)', '0 18px 44px rgb(0 0 0 / 40%)' ),
		);
		$shadow  = $shadows[ (string) ( $s['design_shadow'] ?? 'soft' ) ] ?? $shadows['soft'];

		$tokens = array(
			'--dinv-accent'             => $color( 'design_accent' ),
			'--dinv-accent-hover'       => $color( 'design_accent_hover' ),
			'--dinv-on-accent'          => $color( 'design_on_accent' ),
			'--dinv-focus-ring'         => self::rgba( $color( 'design_accent' ), 0.28 ),
			'--dinv-bg'                 => $color( 'design_bg' ),
			'--dinv-bg-alt'             => $color( 'design_bg_alt' ),
			'--dinv-panel'              => $color( 'design_panel' ),
			'--dinv-panel-2'            => $color( 'design_panel_2' ),
			'--dinv-text'               => $color( 'design_text' ),
			'--dinv-muted'              => $color( 'design_muted' ),
			'--dinv-border'             => $color( 'design_border' ),
			'--dinv-border-hi'          => $color( 'design_border_hi' ),
			'--dinv-border-accent'      => $color( 'design_border_accent' ),
			'--dinv-font'               => self::font( (string) ( $s['design_font'] ?? 'inherit' ), (string) ( $s['design_font_custom'] ?? '' ) ),
			'--dinv-wrap'               => $int( 'design_max_width' ) . 'px',
			'--dinv-pad'                => $int( 'design_padding' ) . 'px',
			'--dinv-gap'                => $int( 'design_gap' ) . 'px',
			'--dinv-radius-lg'          => $int( 'design_radius_large' ) . 'px',
			'--dinv-radius'             => $int( 'design_radius' ) . 'px',
			'--dinv-radius-sm'          => $int( 'design_radius_small' ) . 'px',
			'--dinv-shadow'             => $shadow[0],
			'--dinv-shadow-hover'       => $shadow[1],
			'--dinv-image-width'        => absint( $s['design_image_width'] ?? 280 ) . 'px',
			'--dinv-image-ratio-mobile' => self::ratio( (string) ( $s['design_mobile_ratio'] ?? '16-10' ) ),
		);

		if ( ! empty( $s['use_theme_styles'] ) ) {
			// Colors and font come from the theme (theme.json presets when present).
			$tokens = array_merge(
				$tokens,
				array(
					'--dinv-accent'        => 'var(--wp--preset--color--primary,var(--wp--preset--color--accent-1,var(--wp--preset--color--contrast,currentColor)))',
					'--dinv-accent-hover'  => 'var(--dinv-accent)',
					'--dinv-on-accent'     => 'var(--wp--preset--color--base,#fff)',
					'--dinv-focus-ring'    => 'color-mix(in srgb,var(--dinv-accent) 35%,transparent)',
					'--dinv-bg'            => 'transparent',
					'--dinv-bg-alt'        => 'transparent',
					'--dinv-panel'         => 'color-mix(in srgb,currentColor 5%,transparent)',
					'--dinv-panel-2'       => 'var(--wp--preset--color--base,transparent)',
					'--dinv-text'          => 'inherit',
					'--dinv-muted'         => 'color-mix(in srgb,currentColor 72%,transparent)',
					'--dinv-border'        => 'color-mix(in srgb,currentColor 16%,transparent)',
					'--dinv-border-hi'     => 'color-mix(in srgb,currentColor 36%,transparent)',
					'--dinv-border-accent' => 'color-mix(in srgb,var(--dinv-accent) 40%,transparent)',
					'--dinv-font'          => 'inherit',
				)
			);
		}

		/**
		 * Filters the design tokens (CSS custom property => value).
		 *
		 * @param array $tokens   Tokens.
		 * @param array $settings Settings.
		 */
		return (array) apply_filters( 'dinv_design_tokens', $tokens, $s );
	}

	/**
	 * CSS aspect-ratio for a ratio key.
	 *
	 * @param string $ratio Ratio key like "4-3".
	 */
	public static function ratio( string $ratio ): string {
		return preg_match( '/^(\d{1,2})-(\d{1,2})$/', $ratio, $m ) ? $m[1] . ' / ' . $m[2] : '4 / 3';
	}

	/**
	 * Font stack.
	 *
	 * @param string $font   Font key.
	 * @param string $custom Custom family.
	 */
	private static function font( string $font, string $custom ): string {
		if ( 'custom' === $font && '' !== $custom && preg_match( '/^[A-Za-z0-9 ,\'"\-]+$/', $custom ) ) {
			return $custom;
		}
		$stacks = array(
			'inherit' => 'inherit',
			'system'  => 'system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif',
			'serif'   => 'Georgia,"Times New Roman",serif',
		);
		return $stacks[ $font ] ?? 'inherit';
	}

	/**
	 * Relative luminance (WCAG) of a hex color.
	 *
	 * @param string $hex #RRGGBB.
	 */
	public static function luminance( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return 1.0;
		}
		$channels = array();
		foreach ( array( 0, 2, 4 ) as $offset ) {
			$c          = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$channels[] = $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		}
		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/**
	 * Hex color to rgba().
	 *
	 * @param string $hex   #RRGGBB.
	 * @param float  $alpha Alpha.
	 */
	private static function rgba( string $hex, float $alpha ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return 'rgba(36,87,214,' . $alpha . ')';
		}
		return sprintf( 'rgba(%d,%d,%d,%.2f)', hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), $alpha );
	}
}
