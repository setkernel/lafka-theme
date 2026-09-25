<?php
declare(strict_types=1);

/**
 * GX4 A4: font preloads follow the active preset.
 *
 *  - A preset whose fonts use font-display:optional must preload every file the
 *    first view needs (display variable latin + body 400/700 latin), otherwise
 *    `optional` would drop the face on a cold load. Zero font CLS by construction.
 *  - The static Fraunces preloads (base display face) print for base-display
 *    presets AND for the legacy swap presets (their head stays byte-identical);
 *    an `optional` pool-display preset does not download Fraunces at all.
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

	final class FontPreloadTest extends TestCase {

		private const URI = 'http://example.test/wp-content/themes/lafka/assets/fonts/';

		protected function setUp(): void {
			\Lafka_Presets::reset();
			\add_filter(
				'lafka_presets',
				static function ( $presets ) {
					$presets['fp-optional'] = new \Lafka_Preset(
						array(
							'slug'   => 'fp-optional',
							'schema' => 1,
							'fonts'  => array(
								'body'    => array(
									'family'       => 'Atkinson Hyperlegible Next',
									'source'       => 'pool',
									'font_display' => 'optional',
								),
								'display' => array(
									'family'       => 'Bricolage Grotesque',
									'source'       => 'pool',
									'font_display' => 'optional',
								),
							),
						)
					);
					$presets['fp-base'] = new \Lafka_Preset(
						array(
							'slug'   => 'fp-base',
							'schema' => 1,
							'fonts'  => array(
								'body'    => array( 'family' => 'Rubik', 'source' => 'base' ),
								'display' => array( 'family' => 'Fraunces', 'source' => 'base' ),
							),
						)
					);
					return $presets;
				}
			);
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function activate( string $slug ): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = $slug;
			\Lafka_Presets::reset();
		}

		private function head(): string {
			ob_start();
			\lafka_preset_print_font_preloads();
			return (string) ob_get_clean();
		}

		public function test_optional_pair_preloads_display_variable_and_body_400_700(): void {
			$this->activate( 'fp-optional' );
			$this->assertSame(
				array(
					self::URI . 'bricolage-grotesque/BricolageGrotesque-opsz.woff2',
					self::URI . 'atkinson-hyperlegible-next/AtkinsonHyperlegibleNext-400.woff2',
					self::URI . 'atkinson-hyperlegible-next/AtkinsonHyperlegibleNext-700.woff2',
				),
				\lafka_preset_font_preload_hrefs()
			);
			$this->assertSame( self::URI . 'bricolage-grotesque/BricolageGrotesque-opsz.woff2', \lafka_preset_display_preload_href(), 'back-compat wrapper returns the display href' );
		}

		public function test_base_fonts_preload_nothing_from_the_pool(): void {
			$this->activate( 'fp-base' );
			$this->assertSame( array(), \lafka_preset_font_preload_hrefs() );
			$this->assertSame( '', \lafka_preset_display_preload_href() );
		}

		public function test_fraunces_preloads_only_for_base_or_legacy_swap_display(): void {
			$this->activate( 'fp-base' );
			$head = $this->head();
			$this->assertSame( 2, substr_count( $head, 'fraunces/Fraunces-' ), 'base display face -> the two static Fraunces preloads' );
			$this->assertSame( 2, substr_count( $head, 'rel="preload"' ) );

			$this->activate( 'koyo' );
			$head = $this->head();
			$this->assertSame( 2, substr_count( $head, 'fraunces/Fraunces-' ), 'a legacy swap preset keeps its head byte-identical' );
			$this->assertStringContainsString( 'dm-serif-display/DMSerifDisplay-400.woff2', $head );

			$this->activate( 'fp-optional' );
			$head = $this->head();
			$this->assertStringNotContainsString( 'Fraunces', $head, 'an optional pool-display preset never downloads Fraunces' );
			$this->assertSame( 3, substr_count( $head, 'rel="preload"' ) );
			$this->assertSame( 3, substr_count( $head, 'crossorigin="anonymous"' ) );
		}
	}
}
