<?php
declare(strict_types=1);

/**
 * Self-hosted font locks.
 *
 * P6-PERF-3 regression: Rubik must stay self-hosted (never pulled from the
 * Google CDN).
 *
 * NX2-03 font pool: the curated 8-family OFL pool
 * (incl/presets/lafka-preset-fonts.php) must be fully self-hosted on disk
 * (woff2 + licence per family), and the per-preset @font-face emitter must load
 * ONLY the active preset's two families — Peppery still Rubik + Fraunces
 * (source:"base", emitted by the static CSS, so the engine adds no @font-face
 * and the goldens stay byte/pixel-identical).
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

	final class SelfHostedFontsTest extends TestCase {
		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		protected function setUp(): void {
			$GLOBALS['lafka_test_registered'] = array();
			$GLOBALS['lafka_test_inline']     = array();
			$GLOBALS['lafka_test_theme_mods'] = array(); // all unset -> peppery active.
			\Lafka_Presets::reset();
		}

		private function peppery(): \Lafka_Preset {
			$data = json_decode( (string) file_get_contents( self::root() . '/presets/peppery/preset.json' ), true );
			return new \Lafka_Preset( (array) $data );
		}

		public function test_style_css_uses_font_display_optional_for_rubik(): void {
			$css = file_get_contents( self::root() . '/style.css' );
			preg_match_all( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*\}/s', $css, $matches );
			$this->assertCount( 3, $matches[0], 'Expected exactly 3 @font-face for Rubik' );
			foreach ( $matches[0] as $face ) {
				$this->assertMatchesRegularExpression( '/font-display:\s*optional/', $face );
			}
		}

		public function test_every_pool_family_has_woff2_and_license_on_disk(): void {
			foreach ( LAFKA_FONT_POOL as $slug => $entry ) {
				$dir = self::root() . '/assets/fonts/' . $entry['dir'] . '/';
				$this->assertDirectoryExists( $dir, "assets/fonts/{$entry['dir']}/ is missing for '{$slug}'" );

				// GX4: a variable entry ships one file per subset instead of weights.
				$this->assertTrue( ! empty( $entry['weights'] ) || ! empty( $entry['variable']['files'] ), "'{$slug}' declares no weights and no variable files" );
				foreach ( (array) ( $entry['variable']['files'] ?? array() ) as $subset => $file ) {
					$this->assertFileExists( $dir . $file, "'{$slug}' variable {$subset} woff2 missing: {$entry['dir']}/{$file}" );
				}
				foreach ( $entry['weights'] as $weight => $files ) {
					foreach ( $files as $subset => $file ) {
						$this->assertFileExists(
							$dir . $file,
							"'{$slug}' weight {$weight} subset {$subset} woff2 missing: {$entry['dir']}/{$file}"
						);
					}
				}

				$this->assertFileExists(
					$dir . $entry['license'],
					"'{$slug}' is missing its OFL licence on disk: {$entry['dir']}/{$entry['license']}"
				);
			}
		}

		public function test_pool_families_are_woff2_only(): void {
			foreach ( LAFKA_FONT_POOL as $slug => $entry ) {
				foreach ( $entry['weights'] as $weight => $files ) {
					foreach ( $files as $file ) {
						$this->assertStringEndsWith( '.woff2', $file, "'{$slug}' file {$file} must be woff2" );
					}
				}
			}
		}

		// ---- NX2-03 (b): only the ACTIVE preset's two families enqueue ---------

		public function test_peppery_selects_rubik_and_fraunces_from_base(): void {
			$sel = \lafka_preset_font_selection( $this->peppery() );
			$this->assertSame( 'Rubik', $sel['body']['family'] );
			$this->assertSame( 'base', $sel['body']['source'] );
			$this->assertSame( 'Fraunces', $sel['display']['family'] );
			$this->assertSame( 'base', $sel['display']['source'] );
		}

		public function test_peppery_emits_no_font_face_css(): void {
			$this->assertSame(
				'',
				\lafka_preset_font_face_css( $this->peppery() ),
				'Peppery uses base fonts (Rubik + Fraunces from the static CSS) — the engine must emit no @font-face.'
			);
		}

		public function test_peppery_register_attaches_no_inline_font_css(): void {
			\lafka_preset_register_fonts();
			$this->assertArrayHasKey(
				'lafka-preset-fonts',
				$GLOBALS['lafka_test_registered'],
				'lafka_preset_register_fonts() must register the inline-only lafka-preset-fonts handle'
			);
			$this->assertFalse(
				$GLOBALS['lafka_test_registered']['lafka-preset-fonts']['src'],
				'lafka-preset-fonts must be inline-only (src=false → no HTTP request for the CSS)'
			);
			$this->assertArrayNotHasKey(
				'lafka-preset-fonts',
				$GLOBALS['lafka_test_inline'],
				'Peppery (base fonts) must attach NO @font-face inline — zero bytes for the default preset.'
			);
		}

		public function test_pool_preset_emits_only_its_two_families(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'testpool',
					'schema' => 1,
					'fonts'  => array(
						'body'    => array( 'family' => 'Inter', 'source' => 'pool' ),
						'display' => array( 'family' => 'Space Grotesk', 'source' => 'pool' ),
					),
				)
			);
			$css = \lafka_preset_font_face_css( $preset );

			// The two referenced families ARE emitted...
			$this->assertStringContainsString( 'font-family:"Inter";', $css );
			$this->assertStringContainsString( 'font-family:"Space Grotesk";', $css );
			// ...as real self-hosted woff2 @font-face with a subset range and swap.
			$this->assertStringContainsString( '@font-face{', $css );
			$this->assertStringContainsString( 'format("woff2")', $css );
			$this->assertStringContainsString( 'font-display:swap;', $css );
			$this->assertStringContainsString( 'unicode-range:', $css );
			$this->assertStringContainsString( '/assets/fonts/inter/Inter-700.woff2', $css );
			$this->assertStringContainsString( '/assets/fonts/space-grotesk/SpaceGrotesk-400-ext.woff2', $css );

			// ...and NONE of the other pool families, nor the base families, leak.
			foreach ( array( 'Archivo', 'Lora', 'Manrope', 'DM Serif Display', 'Rubik', 'Fraunces' ) as $absent ) {
				$this->assertStringNotContainsString( 'font-family:"' . $absent . '";', $css, "{$absent} must not leak onto a preset that does not reference it." );
			}
		}

		public function test_pool_preset_emits_all_shipped_weight_subset_faces(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'onlyinter',
					'schema' => 1,
					'fonts'  => array(
						'body'    => array( 'family' => 'Inter', 'source' => 'pool' ),
						'display' => array( 'family' => 'Inter', 'source' => 'pool' ),
					),
				)
			);
			$css = \lafka_preset_font_face_css( $preset );
			// Inter ships 3 weights × 2 subsets = 6 faces; deduped (body == display).
			$this->assertSame( 6, substr_count( $css, '@font-face{' ), 'Inter must emit 6 faces (3 weights × latin/latin-ext), deduped across roles.' );
		}

		public function test_base_family_never_emits_font_face(): void {
			$this->assertSame( '', \lafka_font_face_css_for_slug( 'rubik' ), 'base Rubik is in the static CSS; the emitter must skip it.' );
			$this->assertSame( '', \lafka_font_face_css_for_slug( 'fraunces' ), 'base Fraunces is in the static CSS; the emitter must skip it.' );
			$this->assertSame( '', \lafka_font_face_css_for_slug( 'not-a-family' ), 'unknown slug -> empty.' );
		}

		public function test_peppery_emits_no_display_preload_href(): void {
			$this->assertSame(
				'',
				\lafka_preset_display_preload_href(),
				'Peppery display font is base Fraunces (statically preloaded) — the engine must add no preload link, keeping the head byte-identical.'
			);
		}
	}
}
