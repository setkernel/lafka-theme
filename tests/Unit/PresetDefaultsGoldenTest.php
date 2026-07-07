<?php
declare(strict_types=1);

/**
 * NX2-01 PRESET-DEFAULTS golden gate — closes the DynamicCssParityTest blind spot.
 *
 * DynamicCssParityTest's fixture OVERRIDES every theme_mod, so the default
 * branch of each `get_theme_mod( 'lafka_x', <default> )` never fires — it can't
 * catch a mistake in the theme_mod-default layer (the ~57 lafka_preset_default
 * wraps). THIS gate renders dynamic-css with ALL theme_mods UNSET, per active
 * preset, and byte-compares to a committed golden.
 *
 * CRITICAL ORDERING (PRESET_ENGINE.md §9, §12 step 3): the PEPPERY golden is
 * captured on the PRE-CHANGE dynamic-css (before the wraps). Peppery's chrome is
 * empty, so lafka_preset_default returns the shipped literal → the WRAPPED
 * dynamic-css must reproduce that golden BYTE-for-BYTE. A wrong extraction (a
 * changed literal / dropped key) shows up as a byte diff here.
 *
 * REGENERATE (only on an intentional default-path change):
 *   LAFKA_UPDATE_PRESET_GOLDEN=1 vendor/bin/phpunit --filter PresetDefaultsGoldenTest
 *
 * ISOLATION: WP shims live in the GLOBAL namespace and this class runs in a
 * SEPARATE PROCESS (sibling test files define the same shims) — here
 * get_theme_mod answers the DEFAULT for every key except lafka_active_preset,
 * and lafka_preset_default IS defined so the default path actually fires.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// All theme_mods UNSET => return the caller default. The active-preset slug
	// is steered per-test via $GLOBALS['lafka_active_slug'].
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			if ( 'lafka_active_preset' === $name ) {
				return isset( $GLOBALS['lafka_active_slug'] ) ? $GLOBALS['lafka_active_slug'] : 'peppery';
			}
			return $default;
		}
	}

	// Identity WP shims (same contract as DynamicCssParityTest).
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url, $protocols = null, $context = 'display' ) {
			return $url;
		}
	}
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook = '', $callback = null, $priority = 10, $args = 1 ) {
			return true;
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
	if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
		function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
			return 'https://example.test/wp-content/uploads/lafka-fixture-' . (int) $attachment_id . '.jpg';
		}
	}

	// The preset engine (defines lafka_preset_default so the wrap fires).
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';

	// The builder under test (buffer the stray newline between its two <?php blocks).
	if ( ! function_exists( 'lafka_dynamic_css_build' ) ) {
		ob_start();
		require dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
		ob_end_clean();
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetDefaultsGoldenTest extends TestCase {

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		/** @return array<string,array{0:string}> slug => [slug] over shipped presets. */
		public static function shippedPresetProvider(): array {
			$out = array();
			foreach ( (array) glob( self::root() . '/presets/*/preset.json' ) as $file ) {
				$slug         = basename( dirname( $file ) );
				$out[ $slug ] = array( $slug );
			}
			return $out;
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_all_unset_render_matches_golden( string $slug ): void {
			$this->assertTrue(
				function_exists( 'lafka_dynamic_css_build' ),
				'styles/dynamic-css.php did not define lafka_dynamic_css_build().'
			);

			$GLOBALS['lafka_active_slug'] = $slug;
			\Lafka_Presets::reset();

			$actual = \lafka_dynamic_css_build();
			$golden = self::root() . '/tests/fixtures/preset-defaults-' . $slug . '.css';

			if ( getenv( 'LAFKA_UPDATE_PRESET_GOLDEN' ) === '1' ) {
				file_put_contents( $golden, $actual );
			}

			$this->assertFileExists(
				$golden,
				"Golden missing for preset '{$slug}'. Generate with "
					. 'LAFKA_UPDATE_PRESET_GOLDEN=1 vendor/bin/phpunit --filter PresetDefaultsGoldenTest.'
			);
			$this->assertSame(
				(string) file_get_contents( $golden ),
				$actual,
				"All-unset dynamic-css for preset '{$slug}' diverged from its golden: the "
					. 'theme_mod-default layer (lafka_preset_default wraps) changed the emitted bytes.'
			);
		}

		/**
		 * Peppery is the provable no-op: its all-unset render must equal the
		 * DynamicCssParityTest baseline defaults exactly. This is what makes the
		 * PEPPERY golden a pre-change safety net — if wrapping dynamic-css changed
		 * any literal, peppery would diverge here.
		 */
		public function test_peppery_default_render_has_shipped_accent(): void {
			$GLOBALS['lafka_active_slug'] = 'peppery';
			\Lafka_Presets::reset();
			$css = \lafka_dynamic_css_build();
			$this->assertStringContainsString(
				'--lafka-color-accent-500:#dc2626;',
				$css,
				'peppery all-unset render must emit the shipped Peppery accent #dc2626'
			);
		}
	}
}
