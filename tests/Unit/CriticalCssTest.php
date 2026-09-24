<?php
declare(strict_types=1);

/**
 * Critical-CSS module (incl/system/lafka-critical-css.php): the inlined
 * above-the-fold bundle and the print-media deferral of every other stylesheet.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-critical-css.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CriticalCssTest extends TestCase {

		private const LINK = "<link rel='stylesheet' id='lafka-components-css' href='http://example.test/c.css' media='all' />";

		public function test_non_critical_stylesheet_is_deferred_with_noscript_fallback(): void {
			$html = \apply_filters( 'style_loader_tag', self::LINK, 'lafka-components', 'http://example.test/c.css', 'all' );

			$this->assertStringContainsString( 'media="print" onload="this.media=\'all\'; this.onload=null;"', $html );
			$this->assertStringEndsWith( '<noscript>' . self::LINK . '</noscript>', $html, 'Non-JS visitors must still get the blocking tag.' );
		}

		public function test_tokens_and_payment_styles_stay_render_blocking(): void {
			foreach ( array( 'lafka-tokens', 'wc-authorize-net-cim-credit-card-checkout-block' ) as $handle ) {
				$this->assertSame( self::LINK, \apply_filters( 'style_loader_tag', self::LINK, $handle, 'http://example.test/c.css', 'all' ), "{$handle} must not be deferred." );
			}
		}

		public function test_inlined_bundle_resolves_relative_urls_against_the_stylesheet(): void {
			$dir = sys_get_temp_dir() . '/lafka-critical-' . uniqid( '', true );
			mkdir( $dir . '/styles', 0777, true );
			file_put_contents(
				$dir . '/styles/critical.css',
				"/* comment */\n@font-face { src: url('../assets/fonts/a.woff2'); }\n.x { background: url(data:image/png;base64,AA==); }"
			);
			$GLOBALS['lafka_test_tpl_dir'] = $dir;

			ob_start();
			\lafka_inline_critical_css();
			$out = (string) ob_get_clean();

			unlink( $dir . '/styles/critical.css' );
			rmdir( $dir . '/styles' );
			rmdir( $dir );

			$this->assertStringStartsWith( "\n<style id=\"lafka-critical-css\">", $out );
			$this->assertStringContainsString( 'url(http://example.test/wp-content/themes/lafka/assets/fonts/a.woff2)', $out );
			$this->assertStringContainsString( 'url(data:image/png;base64,AA==)', $out );
			$this->assertStringNotContainsString( 'comment', $out );
		}
	}
}
