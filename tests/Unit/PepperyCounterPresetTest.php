<?php
declare(strict_types=1);

/**
 * GX4 D1: Peppery ships design direction C ("The counter") — the plan §4
 * palette/type/radii, tomato-red chrome, the pool font pair with
 * font-display:optional, every counter variant, the checkered motif — and its
 * palette clears the older-audience contrast bar (body text ≥ 7:1).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-color-contrast.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PepperyCounterPresetTest extends TestCase {

		private static function peppery(): \Lafka_Preset {
			return \Lafka_Presets::from_dirs( array( dirname( __DIR__, 2 ) . '/presets' ) )->get( 'peppery' );
		}

		public function test_palette_type_and_radii(): void {
			$tokens   = self::peppery()->tokens();
			$expected = array(
				'--lafka-color-surface-page'     => '#FFFFFF',
				'--lafka-color-surface-muted'    => '#F6F3EE',
				'--lafka-color-surface-active'   => '#E3DCD2',
				'--lafka-color-text-primary'     => '#1F1B18',
				'--lafka-color-text-secondary'   => '#3F3832',
				'--lafka-color-text-muted'       => '#57504A',
				'--lafka-color-border-subtle'    => '#E3DCD2',
				'--lafka-color-border-default'   => '#D9D0C4',
				'--lafka-color-border-strong'    => '#8C8177',
				'--lafka-color-accent-600'       => '#962018',
				'--lafka-color-accent-700'       => '#7E1B14',
				'--lafka-color-accent-50'        => '#FBEFED',
				'--lafka-color-success-500'      => '#2E6A3B',
				'--lafka-color-error-500'        => '#9E231A',
				'--lafka-color-surface-announce' => '#3F3832',
				'--lafka-color-surface-footer'   => '#FFFFFF',
				'--lafka-font-size-body'         => '1.0625rem',
				'--lafka-font-size-body-desk'    => '1.125rem',
				'--lafka-font-size-h2-desk'      => '2.75rem',
				'--lafka-font-weight-display'    => '800',
				'--lafka-radius-button'          => '8px',
				'--lafka-radius-md'              => '8px',
				'--lafka-radius-pill'            => '999px',
				'--lafka-line-display'           => '1.05',
			);
			foreach ( $expected as $token => $value ) {
				$this->assertSame( $value, $tokens[ $token ] ?? null, $token );
			}
			$this->assertStringStartsWith( '"Atkinson Hyperlegible Next"', $tokens['--lafka-font-family-body'] );
			$this->assertStringStartsWith( '"Bricolage Grotesque"', $tokens['--lafka-font-family-display'] );
			$this->assertStringStartsWith( 'drop-shadow(', $tokens['--lafka-dish-shadow'] );
			$this->assertStringStartsWith( 'rgba(', $tokens['--lafka-dish-contact'] );
		}

		public function test_chrome_covers_every_whitelisted_key(): void {
			$chrome = self::peppery()->chrome();
			$this->assertSame( '#B0271D', $chrome['lafka_accent_color'] );
			$this->assertSame( '#2E6A3B', $chrome['lafka_brand_color'] );
			$this->assertSame( 'Atkinson Hyperlegible Next', $chrome['lafka_body_font']['face'] );
			$this->assertSame( '18px', $chrome['lafka_body_font']['size'] );
			$this->assertSame( '#FFFFFF', $chrome['lafka_footer_background']['color'] );
			$missing = array_diff( LAFKA_PRESET_CHROME_WHITELIST, array_keys( $chrome ), array( 'lafka_page_title_default_bckgr_image' ) );
			$this->assertSame( array(), array_values( $missing ), 'every legacy chrome var agrees with C (no stale #dc2626 / Rubik / dark footer)' );
		}

		public function test_variants_and_fonts(): void {
			$p = self::peppery();
			$this->assertSame(
				array(
					'header_layout' => 'counter',
					'home_layout'   => 'counter',
					'menu_layout'   => 'counter',
					'footer_layout' => 'counter',
					'drawer_layout' => 'counter',
					'motif'         => 'check',
				),
				$p->variants()
			);
			$fonts = $p->fonts();
			foreach ( array( 'body', 'display' ) as $role ) {
				$this->assertSame( 'pool', $fonts[ $role ]['source'] );
				$this->assertSame( 'optional', $fonts[ $role ]['font_display'] );
			}
			$this->assertSame( array(), $p->validate() );
			$this->assertSame( array(), $p->contrast_exceptions() );
		}

		public function test_contrast_for_older_readers(): void {
			$t   = self::peppery()->tokens();
			$c   = \Lafka_Color_Contrast::class;
			$ink = array( $t['--lafka-color-text-primary'], $t['--lafka-color-text-secondary'] );
			foreach ( $ink as $fg ) {
				foreach ( array( $t['--lafka-color-surface-page'], $t['--lafka-color-surface-muted'] ) as $bg ) {
					$this->assertGreaterThanOrEqual( 7.0, $c::ratio( $fg, $bg ), "{$fg} on {$bg} must be >= 7:1" );
				}
			}
			$this->assertGreaterThanOrEqual( 7.0, $c::ratio( $t['--lafka-color-text-muted'], $t['--lafka-color-surface-muted'] ) );
			// accent-text = color-mix(accent 85%, #000) -> #962119.
			$this->assertGreaterThanOrEqual( 7.0, $c::ratio( '#962119', '#FFFFFF' ) );
			$this->assertGreaterThanOrEqual( 4.5, $c::ratio( '#FFFFFF', self::peppery()->chrome()['lafka_accent_color'] ) );
			$this->assertGreaterThanOrEqual( 3.0, $c::ratio( $t['--lafka-color-border-strong'], '#FFFFFF' ), 'control edges (WCAG 1.4.11)' );
			$this->assertGreaterThanOrEqual( 4.5, $c::ratio( '#FFFFFF', $t['--lafka-color-success-500'] ) );
		}
	}
}
