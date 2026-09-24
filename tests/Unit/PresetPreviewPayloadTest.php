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
 * @package Lafka\Tests
 */

namespace {
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
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;
	use PHPUnit\Framework\TestCase;

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

		// lafka_preset_preview_payloads() memoizes per request, so this one runs
		// in a fresh process where the payloads are built with the mod set.
		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
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
