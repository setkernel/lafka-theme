<?php
declare(strict_types=1);

/**
 * H-13 / T-06: the "Skip to content" link is the first focusable element of
 * <body> — before anything wp_body_open prints (the promotions banner and its
 * close button among them) — and it is not a .screen-reader-text element,
 * whose clip rules kept it invisible when focused.
 */

namespace {
	if ( ! function_exists( 'language_attributes' ) ) {
		function language_attributes( $doctype = 'html' ) {
			echo 'lang="en-US"';
		}
	}
	if ( ! function_exists( 'bloginfo' ) ) {
		function bloginfo( $show = '' ) {
			echo esc_html( get_bloginfo( $show ) );
		}
	}
	if ( ! function_exists( 'wp_head' ) ) {
		function wp_head() {
			do_action( 'wp_head' );
		}
	}
	if ( ! function_exists( 'body_class' ) ) {
		function body_class( $css_class = '' ) {
			echo 'class="home"';
		}
	}
	if ( ! function_exists( 'wp_body_open' ) ) {
		function wp_body_open() {
			do_action( 'wp_body_open' );
		}
	}
	if ( ! function_exists( 'get_site_icon_url' ) ) {
		function get_site_icon_url( $size = 512 ) {
			return '';
		}
	}
	if ( ! function_exists( 'lafka_theme_menu_url' ) ) {
		function lafka_theme_menu_url() {
			return 'http://example.test/menu/';
		}
	}
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		function wc_get_cart_url() {
			return 'http://example.test/cart/';
		}
	}
	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return $GLOBALS['lafka_test_wc'];
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class HeaderSkipLinkTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_wc'] = (object) array(
				'cart' => new class() {
					public function get_cart_contents_count() {
						return 0;
					}
				},
			);
			// A plugin banner with its own button, printed at wp_body_open.
			add_action(
				'wp_body_open',
				static function () {
					echo '<div id="lafka-bogo-banner"><button class="lafka-bogo-close">×</button></div>';
				},
				5
			);
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_wc'] );
		}

		private static function render(): string {
			ob_start();
			include dirname( __DIR__, 2 ) . '/header.php';
			return (string) ob_get_clean();
		}

		/** The markup after <body …>. */
		private static function body( string $html ): string {
			$start = strpos( $html, '<body' );
			self::assertNotFalse( $start );
			return substr( $html, (int) strpos( $html, '>', (int) $start ) + 1 );
		}

		public function test_skip_link_is_the_first_focusable_element_of_the_body(): void {
			$body = self::body( self::render() );

			$this->assertSame( 1, preg_match( '/<(a|button|input|select|textarea)\b[^>]*>/', $body, $first ) );
			$this->assertSame( '<a class="skip-link" href="#content">', $first[0] );
			$this->assertLessThan( strpos( $body, 'lafka-bogo-banner' ), strpos( $body, 'skip-link' ), 'Before the wp_body_open banner.' );
		}

		public function test_skip_link_is_not_clipped_by_screen_reader_text(): void {
			$body = self::body( self::render() );

			$this->assertSame( 1, preg_match( '/<a class="([^"]*)" href="#content">Skip to content<\/a>/', $body, $link ) );
			$this->assertNotContains( 'screen-reader-text', explode( ' ', $link[1] ) );
		}
	}
}
