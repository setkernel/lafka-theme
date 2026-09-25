<?php
declare(strict_types=1);

/**
 * H-27 / H-31: counter storefronts skip WordPress's emoji script (operator
 * toggle, default on) and the legacy full-screen preloader.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/counter-chrome.php';
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-preloader.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterHeadAssetsTest extends TestCase {

		public function test_counter_layout_skips_emoji_by_default_and_the_toggle_restores_it(): void {
			$this->assertTrue( \lafka_counter_disable_emoji_enabled(), 'Peppery (default preset) = counter header' );
			$GLOBALS['lafka_test_theme_mods']['lafka_disable_wp_emoji'] = false;
			$this->assertFalse( \lafka_counter_disable_emoji_enabled() );
		}

		public function test_classic_layout_keeps_emoji_and_the_preloader_option(): void {
			\lafka_test_use_classic_layouts();
			$this->assertFalse( \lafka_counter_disable_emoji_enabled() );
			$this->assertFalse( \lafka_preloader_enabled(), 'GX T-01: off unless the operator kept it on' );
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = 1;
			$this->assertTrue( \lafka_preloader_enabled() );
			$GLOBALS['lafka_test_theme_mods']['lafka_show_preloader'] = false;
			$this->assertFalse( \lafka_preloader_enabled() );
		}

		public function test_counter_header_never_prints_the_preloader(): void {
			$this->assertFalse( \lafka_preloader_enabled() );
		}
	}
}
