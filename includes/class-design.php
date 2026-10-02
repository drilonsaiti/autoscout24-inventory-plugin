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
 */
final class Design {

	public const DEFAULT_PRESET = 'light';

	/**
	 * Color presets. Every preset passes WCAG AA contrast for body text,
	 * muted text and button text.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function presets(): array {
		return array(
			'light'        => array(
				'label'                => __( 'Light', 'dealer-inventory-for-autoscout24' ),
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
			),
			'dark'         => array(
				'label'                => __( 'Dark', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#5B8CFF',
				'design_accent_hover'  => '#7FA5FF',
				'design_on_accent'     => '#0B0C0E',
				'design_bg'            => '#0F1012',
				'design_bg_alt'        => '#141518',
				'design_panel'         => '#191A1E',
				'design_panel_2'       => '#121316',
				'design_text'          => '#F5F6F7',
				'design_muted'         => '#A8ABB2',
				'design_border'        => '#2B2D33',
				'design_border_hi'     => '#4A4D55',
				'design_border_accent' => '#2C3F6E',
			),
			'dark_neutral' => array(
				'label'                => __( 'Dark monochrome', 'dealer-inventory-for-autoscout24' ),
				'design_accent'        => '#E5E7EB',
				'design_accent_hover'  => '#FFFFFF',
				'design_on_accent'     => '#111113',
				'design_bg'            => '#0F1012',
				'design_bg_alt'        => '#141518',
				'design_panel'         => '#191A1E',
				'design_panel_2'       => '#121316',
				'design_text'          => '#F7F7F8',
				'design_muted'         => '#A8ABB2',
				'design_border'        => '#2B2D33',
				'design_border_hi'     => '#4A4D55',
				'design_border_accent' => '#3B3D43',
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
	 */
	public static function css(): string {
		$s     = Settings::all();
		$color = static function ( string $key ) use ( $s ): string {
			$value = sanitize_hex_color( (string) ( $s[ $key ] ?? '' ) );
			return $value ? $value : (string) Schema::field( $key )['default'];
		};

		$tokens = array(
			'--dinv-accent'             => $color( 'design_accent' ),
			'--dinv-accent-hover'       => $color( 'design_accent_hover' ),
			'--dinv-on-accent'          => $color( 'design_on_accent' ),
			'--dinv-focus-ring'         => self::rgba( $color( 'design_accent' ), 0.18 ),
			'--dinv-bg'                 => $color( 'design_bg' ),
			'--dinv-bg-alt'             => $color( 'design_bg_alt' ),
			'--dinv-panel'              => $color( 'design_panel' ),
			'--dinv-panel-2'            => $color( 'design_panel_2' ),
			'--dinv-text'               => $color( 'design_text' ),
			'--dinv-muted'              => $color( 'design_muted' ),
			'--dinv-border'             => $color( 'design_border' ),
			'--dinv-border-hi'          => $color( 'design_border_hi' ),
			'--dinv-border-accent'      => $color( 'design_border_accent' ),
			'--dinv-wrap'               => absint( $s['design_max_width'] ) . 'px',
			'--dinv-pad'                => absint( $s['design_padding'] ) . 'px',
			'--dinv-radius'             => absint( $s['design_radius'] ) . 'px',
			'--dinv-radius-lg'          => absint( $s['design_radius_large'] ) . 'px',
			'--dinv-radius-sm'          => absint( $s['design_radius_small'] ) . 'px',
			'--dinv-gap'                => absint( $s['design_gap'] ) . 'px',
			'--dinv-image-width'        => absint( $s['design_image_width'] ) . 'px',
			'--dinv-image-ratio'        => self::ratio( (string) $s['design_image_ratio'] ),
			'--dinv-image-ratio-mobile' => self::ratio( (string) $s['design_mobile_ratio'] ),
			'--dinv-font'               => self::font( (string) $s['design_font'] ),
		);

		$css = '.dinv-inventory{';
		foreach ( $tokens as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}
		$css .= '}';

		$hidden = array(
			'design_show_teaser' => '.dinv-vehicle__teaser,.dinv-card__teaser',
			'design_show_specs'  => '.dinv-vehicle__specs,.dinv-card__chips',
			'design_show_cta'    => '.dinv-vehicle__cta',
		);
		foreach ( $hidden as $key => $selector ) {
			if ( empty( $s[ $key ] ) ) {
				$css .= implode( ',', array_map( static fn( $part ) => '.dinv-inventory ' . $part, explode( ',', $selector ) ) ) . '{display:none!important;}';
			}
		}

		return $css;
	}

	/**
	 * Font stack for a font setting.
	 *
	 * @param string $font Font key.
	 */
	private static function font( string $font ): string {
		$stacks = array(
			'inherit' => 'inherit',
			'system'  => 'system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif',
			'serif'   => 'Georgia,"Times New Roman",serif',
		);
		return $stacks[ $font ] ?? 'inherit';
	}

	/**
	 * CSS aspect-ratio for a ratio key.
	 *
	 * @param string $ratio Ratio key like "4-3".
	 */
	private static function ratio( string $ratio ): string {
		return preg_match( '/^(\d{1,2})-(\d{1,2})$/', $ratio, $m ) ? $m[1] . ' / ' . $m[2] : '4 / 3';
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
