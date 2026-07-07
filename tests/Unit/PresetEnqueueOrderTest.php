<?php
declare(strict_types=1);

/**
 * NX2-01 preset ENQUEUE-ORDER gate.
 *
 * Locks the dependency EDGE that makes the three-layer cascade robust (not
 * print-order luck): the `lafka-preset` handle sits BETWEEN `lafka-tokens` and
 * `lafka-style` in the dependency graph, and is inline-only (src=false → no
 * extra HTTP request). Peppery attaches no inline CSS. See PRESET_ENGINE.md §4.
 *
 * ISOLATION: WP shims live in the GLOBAL namespace; runs in a SEPARATE PROCESS
 * so these shims win over sibling test files' definitions.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! class_exists( 'Lafka_Test_Theme_Stub' ) ) {
		class Lafka_Test_Theme_Stub {
			public function get( $key ) {
				return 'Version' === $key ? '9.9.9' : '';
			}
		}
	}

	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			$store = isset( $GLOBALS['lafka_test_theme_mods'] ) ? $GLOBALS['lafka_test_theme_mods'] : array();
			return array_key_exists( $name, $store ) ? $store[ $name ] : $default;
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook, $value = null, ...$rest ) {
			return $value;
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}
	if ( ! function_exists( 'get_template' ) ) {
		function get_template() {
			return 'lafka-theme';
		}
	}
	if ( ! function_exists( 'wp_get_theme' ) ) {
		function wp_get_theme( $stylesheet = null ) {
			return new \Lafka_Test_Theme_Stub();
		}
	}
	if ( ! function_exists( 'wp_register_style' ) ) {
		function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
			$GLOBALS['lafka_test_registered'][ $handle ] = array(
				'src'  => $src,
				'deps' => $deps,
				'ver'  => $ver,
			);
			return true;
		}
	}
	if ( ! function_exists( 'wp_add_inline_style' ) ) {
		function wp_add_inline_style( $handle, $data ) {
			$GLOBALS['lafka_test_inline'][ $handle ][] = $data;
			return true;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetEnqueueOrderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_registered']  = array();
			$GLOBALS['lafka_test_inline']      = array();
			$GLOBALS['lafka_test_theme_mods']  = array(); // all unset -> peppery active.
			\Lafka_Presets::reset();
		}

		private static function coreFunctions(): string {
			return (string) file_get_contents( dirname( __DIR__, 2 ) . '/incl/system/core-functions.php' );
		}

		/** lafka-preset is registered inline-only (src=false), depending on lafka-tokens. */
		public function test_preset_handle_is_inline_only_after_tokens(): void {
			\lafka_preset_register_ptl();

			$this->assertArrayHasKey(
				'lafka-preset',
				$GLOBALS['lafka_test_registered'],
				'lafka_preset_register_ptl() must register the lafka-preset handle'
			);
			$reg = $GLOBALS['lafka_test_registered']['lafka-preset'];
			$this->assertFalse( $reg['src'], 'lafka-preset must be inline-only (src=false → no HTTP request)' );
			$this->assertSame(
				array( 'lafka-tokens' ),
				$reg['deps'],
				'lafka-preset must depend on lafka-tokens so the PTL prints AFTER the base tokens'
			);
		}

		/** Peppery (empty PTL) attaches NO inline CSS — zero bytes for the default. */
		public function test_peppery_attaches_no_inline_style(): void {
			\lafka_preset_register_ptl();
			$this->assertArrayNotHasKey(
				'lafka-preset',
				$GLOBALS['lafka_test_inline'],
				'peppery emits an empty PTL, so no inline style may be attached'
			);
		}

		/** lafka-style depends on lafka-preset (so the operator inline prints last). */
		public function test_lafka_style_depends_on_preset(): void {
			$src = self::coreFunctions();
			$this->assertMatchesRegularExpression(
				'/\$lafka_style_deps\[\]\s*=\s*\'lafka-preset\';/',
				$src,
				'core-functions.php must append lafka-preset to the lafka-style dependency list'
			);
			$this->assertMatchesRegularExpression(
				"/wp_enqueue_style\(\s*'lafka-style',[^,]+,\s*\\\$lafka_style_deps/",
				$src,
				'the lafka-style enqueue must use the $lafka_style_deps list (which includes lafka-preset)'
			);
		}

		/** The register call must precede the lafka-style enqueue (edge ordering). */
		public function test_preset_registered_before_style_enqueue(): void {
			$src            = self::coreFunctions();
			$register_pos   = strpos( $src, 'lafka_preset_register_ptl()' );
			$enqueue_pos    = strpos( $src, "wp_enqueue_style( 'lafka-style'" );
			$this->assertNotFalse( $register_pos, 'lafka_preset_register_ptl() must be called in core-functions.php' );
			$this->assertNotFalse( $enqueue_pos, "the lafka-style enqueue must exist" );
			$this->assertLessThan(
				$enqueue_pos,
				$register_pos,
				'lafka-preset must be registered BEFORE lafka-style is enqueued'
			);
		}

		/**
		 * The dependency graph: lafka-tokens (base, no deps) < lafka-preset
		 * (deps: lafka-tokens) < lafka-style (deps include lafka-preset).
		 */
		public function test_dependency_graph_orders_tokens_preset_style(): void {
			\lafka_preset_register_ptl();
			$preset_deps = $GLOBALS['lafka_test_registered']['lafka-preset']['deps'];
			$this->assertContains( 'lafka-tokens', $preset_deps, 'lafka-preset must come after lafka-tokens' );

			$src = self::coreFunctions();
			// lafka-style's dep list is seeded from lafka-tokens then appended with lafka-preset.
			$this->assertMatchesRegularExpression(
				'/\$lafka_style_deps\s*=\s*array\(\s*\'lafka-tokens\'\s*\);/',
				$src,
				'lafka-style deps must start from lafka-tokens'
			);
		}
	}
}
