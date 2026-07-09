<?php
declare(strict_types=1);

/**
 * NX2-04 Task 2: per-preset preview payload builder.
 *
 * The Customizer preview swaps three server-emitted CSS blocks per preset
 * (PTL, font-faces, dynamic-css) with ZERO client-side style math — so each
 * payload must be EXACTLY what a real render with that preset active would
 * emit. This proves the builder runs the REAL emitters (lafka_preset_ptl_css,
 * lafka_preset_font_face_css, lafka_dynamic_css_build) with the slug forced
 * through the `lafka_active_preset_slug` filter, that peppery remains the
 * no-op identity, and that saved operator theme_mods keep winning inside
 * every payload.
 *
 * ISOLATION: the WP shims (get_theme_mod / add_filter / apply_filters /
 * remove_filter / sanitize_key + the dynamic-css set) live in the GLOBAL
 * namespace where the preset code resolves them; sibling test files define
 * some of the same shims, so this class runs in a SEPARATE PROCESS with
 * global state discarded so THESE shims win.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			$store = isset( $GLOBALS['lafka_test_theme_mods'] ) ? $GLOBALS['lafka_test_theme_mods'] : array();
			return array_key_exists( $name, $store ) ? $store[ $name ] : $default;
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
			$GLOBALS['lafka_test_filters'][ $hook ][] = $cb;
			return true;
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook, $value = null, ...$rest ) {
			if ( ! empty( $GLOBALS['lafka_test_filters'][ $hook ] ) ) {
				foreach ( $GLOBALS['lafka_test_filters'][ $hook ] as $cb ) {
					$value = $cb( $value, ...$rest );
				}
			}
			return $value;
		}
	}
	if ( ! function_exists( 'remove_filter' ) ) {
		function remove_filter( $hook, $cb, $priority = 10 ) {
			if ( ! empty( $GLOBALS['lafka_test_filters'][ $hook ] ) ) {
				foreach ( $GLOBALS['lafka_test_filters'][ $hook ] as $i => $registered ) {
					if ( $registered === $cb ) {
						unset( $GLOBALS['lafka_test_filters'][ $hook ][ $i ] );
					}
				}
			}
			return true;
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}

	// ---- extra shims styles/dynamic-css.php needs (Task 1 shim set) --------
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
			return true;
		}
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url ) {
			return $url;
		}
	}
	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
		function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail' ) {
			return '';
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-fonts.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-customizer.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetPreviewPayloadTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_filters']    = array();
			$GLOBALS['lafka_test_theme_mods'] = array();
			\Lafka_Presets::reset();
		}

		public function test_payloads_cover_every_registry_preset(): void {
			$payloads = \lafka_preset_preview_payloads();
			$this->assertSame(
				array_keys( \lafka_presets()->all() ),
				array_keys( $payloads ),
				'One payload per discovered preset, registry order.'
			);
			foreach ( $payloads as $slug => $p ) {
				foreach ( array( 'label', 'description', 'dark', 'ptl', 'fonts', 'dynamicCss' ) as $key ) {
					$this->assertArrayHasKey( $key, $p, "$slug payload missing $key" );
				}
			}
		}

		public function test_peppery_payload_is_the_identity(): void {
			$payloads = \lafka_preset_preview_payloads();
			$this->assertSame( '', $payloads['peppery']['ptl'], 'Peppery emits an empty PTL — the no-op guarantee.' );
			$this->assertFalse( $payloads['peppery']['dark'] );
		}

		public function test_dark_preset_payload_differs_and_flags_dark(): void {
			$payloads = \lafka_preset_preview_payloads();
			$this->assertTrue( $payloads['midnight']['dark'] );
			$this->assertStringContainsString( ':root[data-theme="dark"]', $payloads['midnight']['ptl'] );
			$this->assertNotSame(
				$payloads['peppery']['dynamicCss'],
				$payloads['midnight']['dynamicCss'],
				'dynamic-css must be rebuilt per preset (midnight chrome{} sets accent/menu colors).'
			);
			$this->assertStringContainsString( '#22d3ee', $payloads['midnight']['dynamicCss'], "midnight's chrome accent must reach its dynamic-css payload" );
		}

		public function test_operator_mods_still_win_inside_payloads(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_accent_color'] = '#123456';
			$payloads = \lafka_preset_preview_payloads();
			$this->assertStringContainsString( '#123456', $payloads['midnight']['dynamicCss'], 'A saved operator accent must beat the preset chrome in every payload.' );
		}

		public function test_sanitize_preset_slug(): void {
			$this->assertSame( 'ember', \lafka_sanitize_preset_slug( 'ember' ) );
			$this->assertSame( 'peppery', \lafka_sanitize_preset_slug( 'no-such-preset' ) );
			$this->assertSame( 'peppery', \lafka_sanitize_preset_slug( '' ) );
		}
	}
}
