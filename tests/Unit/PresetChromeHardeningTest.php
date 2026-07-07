<?php
declare(strict_types=1);

/**
 * Hardening locks for the chrome (theme_mod-default) layer.
 *
 * Two gaps found in the 2026-07 line-by-line audit:
 *
 * 1. CRASH CLASS — dynamic-css decoded the composite typography `style`
 *    sub-field with json_decode(), which throws an uncaught TypeError when a
 *    preset/filter supplies the natural ARRAY shape instead of the legacy
 *    JSON-string shape → site-wide fatal from wp_enqueue_scripts. The
 *    whitelist explicitly invites 9 composite keys onto this path.
 *    Fix: lafka_dynamic_css_style_pair() accepts both shapes.
 *
 * 2. INJECTION CLASS — PTL token values are stripped by
 *    lafka_preset_css_value(), but chrome values reached dynamic-css's
 *    :root{} block with only esc_attr() (which keeps `;{}`), so a hostile
 *    3rd-party preset via the `lafka_presets` filter could break out of the
 *    rule (`red;}body{display:none`).
 *    Fix: lafka_preset_default() routes preset-supplied values through
 *    lafka_preset_sanitize_chrome_value() — recursive over arrays, and
 *    leaf-sanitising (not brace-stripping) the legacy JSON `style` string.
 *
 * ISOLATION: global-namespace WP shims, separate process (same pattern as
 * PresetCascadeTest) so sibling files' shims never interfere.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
			return true;
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
			return true;
		}
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data, $options = 0, $depth = 512 ) {
			return json_encode( $data, $options, $depth );
		}
	}

	require_once dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetChromeHardeningTest extends TestCase {

		// ── 1. Composite style decode: crash class ───────────────────────────

		public function test_style_pair_decodes_the_legacy_json_string(): void {
			$pair = \lafka_dynamic_css_style_pair(
				array(
					'size'  => '15px',
					'style' => '{"font-weight":"600","font-style":"italic"}',
				)
			);
			$this->assertSame( array( '600', 'italic' ), $pair );
		}

		public function test_style_pair_accepts_the_natural_array_shape_without_fataling(): void {
			// The exact input that used to throw TypeError from wp_enqueue_scripts.
			$pair = \lafka_dynamic_css_style_pair(
				array(
					'size'  => '60px',
					'style' => array(
						'font-weight' => '700',
						'font-style'  => 'normal',
					),
				)
			);
			$this->assertSame( array( '700', 'normal' ), $pair );
		}

		public function test_style_pair_falls_back_on_garbage(): void {
			$this->assertSame( array( 'normal', 'normal' ), \lafka_dynamic_css_style_pair( array( 'size' => '15px' ) ) );
			$this->assertSame( array( 'normal', 'normal' ), \lafka_dynamic_css_style_pair( array( 'style' => 'not-json' ) ) );
			$this->assertSame( array( 'normal', 'normal' ), \lafka_dynamic_css_style_pair( 'scalar' ) );
			$this->assertSame( array( 'normal', 'normal' ), \lafka_dynamic_css_style_pair( null ) );
		}

		// ── 2. Chrome sanitisation: injection class ──────────────────────────

		public function test_hostile_scalar_breakout_is_stripped(): void {
			$out = \lafka_preset_sanitize_chrome_value( 'red;}body{display:none' );
			$this->assertStringNotContainsString( ';', $out );
			$this->assertStringNotContainsString( '{', $out );
			$this->assertStringNotContainsString( '}', $out );
		}

		public function test_arrays_sanitise_recursively(): void {
			$out = \lafka_preset_sanitize_chrome_value(
				array(
					'color' => '#0f0f10;}*{display:none',
					'image' => '',
				)
			);
			$this->assertSame( '#0f0f10*display:none', $out['color'] );
			$this->assertSame( '', $out['image'] );
		}

		public function test_legacy_json_style_string_survives_byte_identical(): void {
			$legacy = '{"font-weight":"600","font-style":"normal"}';
			$this->assertSame(
				$legacy,
				\lafka_preset_sanitize_chrome_value( $legacy ),
				'Clean legacy composite style must pass through byte-identical (parity guarantee).'
			);
		}

		public function test_hostile_leaf_inside_json_style_is_stripped_braces_kept(): void {
			$out = \lafka_preset_sanitize_chrome_value( '{"font-weight":"600;}body{display:none","font-style":"normal"}' );
			$decoded = json_decode( (string) $out, true );
			$this->assertIsArray( $decoded, 'The composite must still decode — its structural braces survive.' );
			$this->assertStringNotContainsString( ';', $decoded['font-weight'] );
			$this->assertStringNotContainsString( '}', $decoded['font-weight'] );
		}

		public function test_clean_first_party_values_pass_byte_identical(): void {
			foreach ( array( '#22d3ee', '15px', 'scroll', 'Rubik' ) as $clean ) {
				$this->assertSame( $clean, \lafka_preset_sanitize_chrome_value( $clean ) );
			}
			$this->assertSame( 7, \lafka_preset_sanitize_chrome_value( 7 ), 'Non-strings pass through untouched.' );
		}
	}
}
