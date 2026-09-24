<?php
declare(strict_types=1);

/**
 * NX2-01 PRESET-DEFAULTS golden gate — closes the DynamicCssParityTest blind spot.
 *
 * DynamicCssParityTest's fixture OVERRIDES every theme_mod, so the default
 * branch of each `get_theme_mod( 'lafka_x', <default> )` never fires — it can't
 * catch a mistake in the theme_mod-default layer (the 50 lafka_preset_default
 * call sites). THIS gate renders dynamic-css with ALL theme_mods UNSET, per active
 * preset, and byte-compares to a committed golden.
 *
 * CRITICAL ORDERING (PRESET_ENGINE.md §5, §9): the PEPPERY golden was
 * captured on the PRE-CHANGE dynamic-css (before the wraps). Peppery's chrome is
 * empty, so lafka_preset_default returns the shipped literal → the WRAPPED
 * dynamic-css must reproduce that golden BYTE-for-BYTE. A wrong extraction (a
 * changed literal / dropped key) shows up as a byte diff here.
 *
 * REGENERATE (only on an intentional default-path change):
 *   LAFKA_UPDATE_PRESET_GOLDEN=1 vendor/bin/phpunit --filter PresetDefaultsGoldenTest
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';

	if ( ! function_exists( 'lafka_dynamic_css_build' ) ) {
		ob_start();
		require dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
		ob_end_clean();
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\TestCase;

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

			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = $slug;
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
	}
}
