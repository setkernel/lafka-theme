<?php
declare(strict_types=1);

/**
 * GX T-01: the full-screen preloader is off by default, never renders under a
 * counter layout, and — when an operator keeps it on — its rules print once,
 * inside the critical bundle, with a fail-safe that uncovers the page.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-critical-css.php';
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-preloader.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PreloaderTest extends TestCase {

		private function inline(): string {
			ob_start();
			\lafka_inline_critical_css();
			return (string) ob_get_clean();
		}

		public function test_new_install_has_no_preloader(): void {
			\lafka_test_use_classic_layouts();
			unset( $GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] );

			$this->assertFalse( \lafka_preloader_enabled() );
			// Readers that pass the legacy `true` default still resolve to off.
			$this->assertSame( 0, \lafka_preloader_theme_mod( true ) );
			$this->assertSame( '', \lafka_preloader_css() );
		}

		public function test_every_get_theme_mod_reader_goes_through_the_resolver(): void {
			$this->assertContains(
				'lafka_preloader_theme_mod',
				$GLOBALS['lafka_test_filters']['theme_mod_lafka_show_preloader'][10] ?? array()
			);
		}

		public function test_classic_site_that_kept_it_on_still_gets_it(): void {
			\lafka_test_use_classic_layouts();
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = '1';

			$this->assertTrue( \lafka_preloader_enabled() );
			$this->assertSame( 1, \lafka_preloader_theme_mod( false ) );
		}

		public function test_any_counter_layout_forces_it_off_even_with_the_legacy_value(): void {
			\lafka_test_use_classic_layouts();
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = '1';
			$GLOBALS['lafka_test_theme_mods']['lafka_footer_layout']  = 'counter';

			$this->assertFalse( \lafka_preloader_enabled() );
			$this->assertSame( 0, \lafka_preloader_theme_mod( '1' ) );
		}

		public function test_filter_has_the_last_word(): void {
			\lafka_test_use_classic_layouts();
			$GLOBALS['lafka_test_theme_mods']['lafka_header_layout'] = 'counter';
			\add_filter( 'lafka_preloader_enabled', '__return_true' );

			$this->assertTrue( \lafka_preloader_enabled() );
		}

		public function test_preloader_rules_print_once_inside_the_critical_bundle_when_on(): void {
			\lafka_test_use_classic_layouts();
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = 1;

			$out = $this->inline();
			$this->assertSame( 1, substr_count( $out, '.mask {' ), 'the mask is styled exactly once' );
			$this->assertMatchesRegularExpression( '/\.mask \{[^}]*animation: lafka-mask-out [^}]*2s forwards/', $out, 'fail-safe: the mask uncovers the page even if no script runs' );
			$this->assertStringContainsString( '@keyframes lafka-mask-out', $out );
		}

		public function test_no_preloader_rules_when_off(): void {
			\lafka_test_use_classic_layouts();
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = 0;

			$this->assertStringNotContainsString( '.mask', $this->inline() );
		}
	}
}
