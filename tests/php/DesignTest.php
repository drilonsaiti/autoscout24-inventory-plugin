<?php
/**
 * Design presets: WCAG AA contrast and CSS output.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Tests;

use DealerInventory\Design;

/**
 * @covers \DealerInventory\Design
 */
class DesignTest extends Test_Case {

	/**
	 * Relative luminance of a hex color.
	 *
	 * @param string $hex Color.
	 */
	private static function luminance( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		$rgb = array();
		foreach ( array( 0, 2, 4 ) as $offset ) {
			$c     = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$rgb[] = $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		}
		return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
	}

	/**
	 * WCAG contrast ratio.
	 *
	 * @param string $a Color.
	 * @param string $b Color.
	 */
	private static function contrast( string $a, string $b ): float {
		$l1 = self::luminance( $a );
		$l2 = self::luminance( $b );
		return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
	}

	/**
	 * Presets.
	 */
	public function presets(): array {
		$out = array();
		foreach ( array_keys( Design::presets() ) as $name ) {
			$out[ $name ] = array( $name );
		}
		return $out;
	}

	/**
	 * Text pairs need 4.5:1 (WCAG 2.2 AA, normal text).
	 *
	 * @dataProvider presets
	 *
	 * @param string $name Preset.
	 */
	public function test_preset_text_contrast( string $name ): void {
		$p     = Design::presets()[ $name ];
		$pairs = array(
			'text on background'          => array( 'design_text', 'design_bg' ),
			'text on secondary bg'        => array( 'design_text', 'design_bg_alt' ),
			'text on panels'              => array( 'design_text', 'design_panel' ),
			'text on inputs and cards'    => array( 'design_text', 'design_panel_2' ),
			'muted text on background'    => array( 'design_muted', 'design_bg' ),
			'muted text on secondary bg'  => array( 'design_muted', 'design_bg_alt' ),
			'muted text on cards'         => array( 'design_muted', 'design_panel_2' ),
			'button text on accent'       => array( 'design_on_accent', 'design_accent' ),
			'button text on accent hover' => array( 'design_on_accent', 'design_accent_hover' ),
			'links on background'         => array( 'design_accent', 'design_bg' ),
			'links on secondary bg'       => array( 'design_accent', 'design_bg_alt' ),
		);

		foreach ( $pairs as $label => $pair ) {
			$ratio = self::contrast( $p[ $pair[0] ], $p[ $pair[1] ] );
			$this->assertGreaterThanOrEqual( 4.5, $ratio, sprintf( '%s: %s (%.2f:1)', $name, $label, $ratio ) );
		}
	}

	/**
	 * Focus rings and highlighted borders need 3:1 against the background
	 * (WCAG 2.2 non-text contrast).
	 *
	 * @dataProvider presets
	 *
	 * @param string $name Preset.
	 */
	public function test_preset_non_text_contrast( string $name ): void {
		$p = Design::presets()[ $name ];
		$this->assertGreaterThanOrEqual( 3.0, self::contrast( $p['design_accent'], $p['design_bg'] ), $name . ': focus outline' );
		$this->assertGreaterThanOrEqual( 3.0, self::contrast( $p['design_accent'], $p['design_panel_2'] ), $name . ': selected controls' );
	}

	public function test_css_contains_tokens_and_sanitizes_colors(): void {
		$css = Design::css(
			array(
				'design_accent' => 'red;}body{display:none',
				'design_font'   => 'custom',
				'design_font_custom' => 'x;}body{color:red',
			)
		);

		$this->assertStringStartsWith( '.dinv-inventory,.dinv-detail{', $css );
		$this->assertStringContainsString( '--dinv-accent:#2457D6', $css );
		$this->assertStringNotContainsString( 'display:none', $css );
		$this->assertStringNotContainsString( 'color:red', $css );
	}

	public function test_theme_styles_use_theme_variables(): void {
		$css = Design::css( array( 'use_theme_styles' => true ) );
		$this->assertStringContainsString( 'var(--wp--preset--color--', $css );
	}
}
