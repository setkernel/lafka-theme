<?php
declare(strict_types=1);

/**
 * GX4 A4: variable-font pool entries + per-preset font-display.
 *
 * Bricolage Grotesque ships as ONE variable file per subset (weight axis
 * 200–800), Atkinson Hyperlegible Next as static 400/700. A preset's
 * `fonts.<role>.font_display` (swap|optional, default swap) sets the
 * @font-face font-display; the nine existing presets keep swap byte-for-byte
 * (OtherPresetsUnchangedTest).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-fonts.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class FontPoolVariableTest extends TestCase {

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		private function preset( string $display = 'optional' ): \Lafka_Preset {
			return new \Lafka_Preset(
				array(
					'slug'   => 'fv-fixture',
					'schema' => 1,
					'fonts'  => array(
						'body'    => array(
							'family'       => 'Atkinson Hyperlegible Next',
							'source'       => 'pool',
							'font_display' => $display,
						),
						'display' => array(
							'family'       => 'Bricolage Grotesque',
							'source'       => 'pool',
							'font_display' => $display,
						),
					),
				)
			);
		}

		public function test_both_families_are_pooled(): void {
			$this->assertSame( 'bricolage-grotesque', \lafka_font_pool_slug( 'Bricolage Grotesque' ) );
			$this->assertSame( 'atkinson-hyperlegible-next', \lafka_font_pool_slug( 'Atkinson Hyperlegible Next' ) );
		}

		public function test_variable_entry_emits_a_weight_range(): void {
			$css = \lafka_preset_font_face_css( $this->preset() );
			$this->assertStringContainsString( 'font-family:"Bricolage Grotesque";', $css );
			$this->assertStringContainsString( 'font-weight:200 800;', $css );
			$this->assertStringContainsString( '/assets/fonts/bricolage-grotesque/BricolageGrotesque-opsz.woff2', $css );
			$this->assertStringContainsString( '/assets/fonts/bricolage-grotesque/BricolageGrotesque-opsz-ext.woff2', $css );
			$this->assertSame( 2, substr_count( $css, 'font-family:"Bricolage Grotesque";' ), 'one face per subset' );
			$this->assertSame( 4, substr_count( $css, 'font-family:"Atkinson Hyperlegible Next";' ), '400/700 x latin/latin-ext' );
		}

		public function test_font_display_comes_from_the_preset_and_defaults_to_swap(): void {
			$this->assertStringContainsString( 'font-display:optional;', \lafka_preset_font_face_css( $this->preset( 'optional' ) ) );
			$this->assertStringNotContainsString( 'font-display:swap;', \lafka_preset_font_face_css( $this->preset( 'optional' ) ) );

			$no_display = new \Lafka_Preset(
				array(
					'slug'   => 'fv-swap',
					'schema' => 1,
					'fonts'  => array(
						'body'    => array( 'family' => 'Inter', 'source' => 'pool' ),
						'display' => array( 'family' => 'Bricolage Grotesque', 'source' => 'pool' ),
					),
				)
			);
			$css = \lafka_preset_font_face_css( $no_display );
			$this->assertStringContainsString( 'font-display:swap;', $css );
			$this->assertStringNotContainsString( 'font-display:optional;', $css );
		}

		public function test_font_display_is_validated(): void {
			$bad = new \Lafka_Preset(
				array(
					'slug'   => 'fv-bad',
					'schema' => 1,
					'fonts'  => array(
						'body' => array(
							'family'       => 'Inter',
							'source'       => 'pool',
							'font_display' => 'block;}',
						),
					),
				)
			);
			$this->assertNotSame( array(), $bad->validate() );
			$this->assertSame( array(), $this->preset()->validate() );
		}

		public function test_files_exist_with_licence_and_pool_stays_under_budget(): void {
			$total = 0;
			foreach ( LAFKA_FONT_POOL as $slug => $entry ) {
				$dir   = self::root() . '/assets/fonts/' . $entry['dir'] . '/';
				$files = array();
				foreach ( (array) $entry['weights'] as $subsets ) {
					$files = array_merge( $files, array_values( $subsets ) );
				}
				if ( ! empty( $entry['variable']['files'] ) ) {
					$files = array_merge( $files, array_values( $entry['variable']['files'] ) );
				}
				$this->assertNotEmpty( $files, "{$slug} ships no files" );
				foreach ( $files as $file ) {
					$this->assertFileExists( $dir . $file );
					$total += (int) filesize( $dir . $file );
				}
				$this->assertFileExists( $dir . $entry['license'], "{$slug} licence" );
			}
			$this->assertLessThan( 1.5 * 1024 * 1024, $total, 'the whole font pool must stay under 1.5 MB' );
		}
	}
}
