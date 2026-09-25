<?php
declare(strict_types=1);

/**
 * GX4 ratchet: the nine non-Peppery presets never move.
 *
 * GX4 turns Peppery into the "counter" design (new tokens, fonts, variants)
 * and adds no-op base tokens + a variable-font emitter path. None of that may
 * change a byte of what the OTHER nine presets emit. This test pins, per preset,
 * with every theme_mod unset:
 *   - the Preset-Token Layer (lafka_preset_ptl_css)
 *   - the pool @font-face block (lafka_preset_font_face_css)
 *   - the default dynamic-css (lafka_dynamic_css_build)
 * against byte fixtures captured on the pre-GX4 main.
 *
 * The fixtures are deliberately separate from PresetDefaultsGoldenTest's goldens:
 * regenerating those (LAFKA_UPDATE_PRESET_GOLDEN=1 rewrites all ten) must not be
 * able to silently absorb a change to another preset.
 *
 * REGENERATE (only when a change to another preset is intended and reviewed):
 *   LAFKA_WRITE_FIXTURES=1 vendor/bin/phpunit --filter OtherPresetsUnchangedTest
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-fonts.php';
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

	final class OtherPresetsUnchangedTest extends TestCase {

		private const SLUGS = array( 'azzurro', 'brioche', 'ember', 'fjord', 'koyo', 'midnight', 'saffron', 'terracotta', 'verde' );

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		/** @return array<string,array{0:string}> */
		public static function slugProvider(): array {
			$out = array();
			foreach ( self::SLUGS as $slug ) {
				$out[ $slug ] = array( $slug );
			}
			return $out;
		}

		protected function setUp(): void {
			$GLOBALS['lafka_test_theme_mods'] = array();
			\Lafka_Presets::reset();
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function force( string $slug ): void {
			\add_filter(
				'lafka_active_preset_slug',
				static function () use ( $slug ) {
					return $slug;
				},
				999
			);
			\Lafka_Presets::reset();
		}

		private function assert_fixture( string $file, string $actual, string $what ): void {
			$path = self::root() . '/tests/fixtures/' . $file;
			if ( '1' === getenv( 'LAFKA_WRITE_FIXTURES' ) ) {
				file_put_contents( $path, $actual );
			}
			$this->assertFileExists( $path, "Fixture {$file} missing; capture with LAFKA_WRITE_FIXTURES=1 on a reviewed tree." );
			$this->assertSame( (string) file_get_contents( $path ), $actual, $what );
		}

		#[DataProvider( 'slugProvider' )]
		public function test_ptl_and_font_faces_are_byte_identical( string $slug ): void {
			$this->force( $slug );
			$preset = \lafka_active_preset();
			$this->assertSame( $slug, $preset->slug(), "preset '{$slug}' must be registered" );

			$actual = "/* ptl */\n" . \lafka_preset_ptl_css( $preset ) . "\n/* font-face */\n" . \lafka_preset_font_face_css( $preset ) . "\n";
			$this->assert_fixture( 'ptl-' . $slug . '.css', $actual, "Preset '{$slug}' PTL / @font-face output moved." );
		}

		#[DataProvider( 'slugProvider' )]
		public function test_default_dynamic_css_is_byte_identical( string $slug ): void {
			$this->force( $slug );
			$this->assert_fixture( 'dyncss-default-' . $slug . '.css', \lafka_dynamic_css_build(), "Preset '{$slug}' default dynamic-css moved." );
		}
	}
}
