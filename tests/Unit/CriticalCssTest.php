<?php
declare(strict_types=1);

/**
 * Critical-CSS module (incl/system/lafka-critical-css.php): the inlined
 * above-the-fold bundle, and (GX T-02) render-blocking by default with the
 * print-media deferral reserved for off-screen modules + vendored libraries.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-critical-css.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CriticalCssTest extends TestCase {

		private const LINK = "<link rel='stylesheet' id='lafka-components-css' href='http://example.test/c.css' media='all' />";

		private const ASYNC = 'media="print" onload="this.media=\'all\'; this.onload=null;"';

		private function tag( string $handle, string $media = 'all' ): string {
			return (string) \apply_filters( 'style_loader_tag', self::LINK, $handle, 'http://example.test/c.css', $media );
		}

		public function test_offscreen_stylesheet_is_deferred_with_noscript_fallback(): void {
			$html = $this->tag( 'lafka-cart-drawer' );

			$this->assertStringContainsString( self::ASYNC, $html );
			$this->assertStringEndsWith( '<noscript>' . self::LINK . '</noscript>', $html, 'Non-JS visitors must still get the blocking tag.' );
		}

		public function test_above_the_fold_stylesheets_stay_render_blocking(): void {
			// GX T-02: deferring these moved the whole page (CLS ~1.0).
			$first_viewport = array( 'lafka-base', 'lafka-components', 'lafka-counter', 'lafka-header-chrome', 'lafka-announce-bar', 'lafka-style', 'lafka-responsive', 'lafka-menu-archive', 'lafka-home-v2', 'lafka-pdp-redesign', 'lafka-pdp-handoff', 'lafka-cart-handoff', 'lafka-checkout-handoff', 'lafka-page', 'lafka-child-style', 'some-plugin-style' );
			foreach ( $first_viewport as $handle ) {
				$this->assertSame( self::LINK, $this->tag( $handle ), "{$handle} must stay render-blocking." );
			}
		}

		public function test_offscreen_modules_and_vendored_libraries_load_async(): void {
			foreach ( array( 'lafka-mobile-nav', 'lafka-cart-drawer', 'lafka-dialog', 'lafka-footer-chrome', 'lafka-search', 'owl-carousel', 'owl-carousel-animate', 'lafka-flexslider', 'photoswipe', 'wc-blocks-style', 'lafka-free-delivery-progress' ) as $handle ) {
				$this->assertStringContainsString( self::ASYNC, $this->tag( $handle ), "{$handle} is off-screen and may load async." );
			}
		}

		public function test_free_delivery_progress_blocks_where_it_is_in_the_page(): void {
			$GLOBALS['lafka_test_is_cart'] = true;
			$this->assertSame( self::LINK, $this->tag( 'lafka-free-delivery-progress' ), 'on /cart/ the bar is in the page, not the drawer' );

			$GLOBALS['lafka_test_is_cart']     = false;
			$GLOBALS['lafka_test_is_checkout'] = true;
			$this->assertSame( self::LINK, $this->tag( 'lafka-free-delivery-progress' ) );
		}

		public function test_async_set_is_filterable_and_keep_blocking_still_wins(): void {
			\add_filter(
				'lafka_critical_css_async_handles',
				static function ( $handles ) {
					$handles[] = 'lafka-notices';
					return array_diff( $handles, array( 'lafka-footer-chrome' ) );
				}
			);
			$this->assertStringContainsString( self::ASYNC, $this->tag( 'lafka-notices' ) );
			$this->assertSame( self::LINK, $this->tag( 'lafka-footer-chrome' ) );

			\add_filter(
				'lafka_critical_css_keep_blocking',
				static function ( $keep, $handle ) {
					return 'lafka-cart-drawer' === $handle ? true : $keep;
				},
				10,
				2
			);
			$this->assertSame( self::LINK, $this->tag( 'lafka-cart-drawer' ) );
		}

		public function test_non_screen_media_and_admin_are_left_alone(): void {
			$this->assertSame( self::LINK, $this->tag( 'lafka-cart-drawer', 'print' ) );
			$GLOBALS['lafka_test_is_admin'] = true;
			$this->assertSame( self::LINK, $this->tag( 'lafka-cart-drawer' ) );
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

		public function test_shipped_bundle_holds_the_offscreen_shells_closed(): void {
			ob_start();
			\lafka_inline_critical_css();
			$out = (string) ob_get_clean();

			// The drawer / nav sheets are async, so their closed state is inlined.
			$this->assertMatchesRegularExpression( '/\.lafka-cart-drawer, \.lafka-mobile-nav \{[^}]*position: fixed;[^}]*visibility: hidden;/', $out );
			$this->assertStringNotContainsString( '.mask', $out, 'no preloader rules while the preloader is off' );
		}
	}
}
