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
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {
	use PHPUnit\Framework\TestCase;

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

		/**
		 * The engine no-op: a preset with an empty PTL (the identity fixture —
		 * Peppery until GX4) attaches NO inline CSS, zero bytes.
		 */
		public function test_identity_preset_attaches_no_inline_style(): void {
			\lafka_test_activate_identity_preset();
			\lafka_preset_register_ptl();
			$this->assertArrayNotHasKey(
				'lafka-preset',
				$GLOBALS['lafka_test_inline'],
				'an empty PTL must attach no inline style'
			);
		}

		/** GX4: Peppery now ships the counter palette, attached after the base tokens. */
		public function test_peppery_attaches_its_counter_palette(): void {
			\lafka_preset_register_ptl();
			$this->assertStringContainsString( '--lafka-color-text-primary:#1F1B18;', implode( '', $GLOBALS['lafka_test_inline']['lafka-preset'] ?? array() ) );
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
	}
}
