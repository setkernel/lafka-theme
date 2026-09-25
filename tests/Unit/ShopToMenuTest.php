<?php
declare(strict_types=1);

/**
 * M-14 / T-08 / M-13: one menu URL. The WooCommerce shop page hands off to
 * /menu/ (never a product search, never a loop or a redirect into a 404), a
 * stray /shop/ 404 lands on the menu, the shop page leaves the sitemap, and
 * the WooCommerce breadcrumb says "Menu" → /menu/ like the JSON-LD.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-shop-to-menu.php';

	if ( ! function_exists( 'wc_get_page_id' ) ) {
		function wc_get_page_id( $page ) {
			return (int) ( $GLOBALS['lafka_test_wc_page_ids'][ $page ] ?? -1 );
		}
	}
	if ( ! function_exists( 'url_to_postid' ) ) {
		function url_to_postid( $url ) {
			return (int) ( $GLOBALS['lafka_test_url_to_postid'][ (string) $url ] ?? 0 );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ShopToMenuTest extends TestCase {

		private const MENU = 'https://example.test/menu/';
		private const SHOP = 'https://example.test/order/';

		protected function setUp(): void {
			$GLOBALS['lafka_test_wc_page_ids']   = array();
			$GLOBALS['lafka_test_url_to_postid'] = array();
		}

		/** @param array<string,mixed> $over */
		private function ctx( array $over = array() ): array {
			return array_merge(
				array(
					'enabled'      => true,
					'is_shop'      => false,
					'is_search'    => false,
					'is_404'       => false,
					'path'         => '',
					'menu_url'     => self::MENU,
					'shop_url'     => self::SHOP,
					'menu_is_page' => true,
					'legacy_paths' => array( 'shop' ),
				),
				$over
			);
		}

		public function test_shop_archive_redirects_to_the_menu(): void {
			$this->assertSame( self::MENU, \lafka_shop_to_menu_target( $this->ctx( array( 'is_shop' => true ) ) ) );
		}

		public function test_product_search_is_never_redirected(): void {
			$this->assertSame( '', \lafka_shop_to_menu_target( $this->ctx( array( 'is_shop' => true, 'is_search' => true ) ) ) );
		}

		public function test_a_legacy_shop_404_lands_on_the_menu(): void {
			$this->assertSame( self::MENU, \lafka_shop_to_menu_target( $this->ctx( array( 'is_404' => true, 'path' => 'shop' ) ) ) );
			$this->assertSame( '', \lafka_shop_to_menu_target( $this->ctx( array( 'is_404' => true, 'path' => 'shopping' ) ) ), 'other 404s stay 404s' );
		}

		public function test_no_redirect_when_disabled_missing_or_looping(): void {
			$this->assertSame( '', \lafka_shop_to_menu_target( $this->ctx( array( 'is_shop' => true, 'enabled' => false ) ) ) );
			$this->assertSame( '', \lafka_shop_to_menu_target( $this->ctx( array( 'is_shop' => true, 'menu_is_page' => false ) ) ), 'never redirect into a 404' );
			$this->assertSame( '', \lafka_shop_to_menu_target( $this->ctx( array( 'is_shop' => true, 'shop_url' => 'http://example.test/menu' ) ) ), 'the menu page IS the shop page' );
		}

		public function test_enabled_by_default_and_customizable(): void {
			$this->assertTrue( \lafka_shop_to_menu_enabled() );
			$GLOBALS['lafka_test_theme_mods']['lafka_shop_to_menu_redirect'] = false;
			$this->assertFalse( \lafka_shop_to_menu_enabled() );
		}

		public function test_redirected_shop_page_leaves_the_page_sitemap(): void {
			$GLOBALS['lafka_test_wc_page_ids']['shop']                                = 12;
			$GLOBALS['lafka_test_url_to_postid'][ \lafka_theme_menu_url() ]           = 34;
			$args = \lafka_shop_to_menu_sitemap_args( array( 'post__not_in' => array( 5 ) ), 'page' );
			$this->assertSame( array( 5, 12 ), $args['post__not_in'] );
			$this->assertSame( array( 'x' => 1 ), \lafka_shop_to_menu_sitemap_args( array( 'x' => 1 ), 'post' ) );
		}

		public function test_breadcrumb_shop_crumb_becomes_menu(): void {
			$crumbs = array(
				array( 'Home', 'https://example.test/' ),
				array( 'Order', self::SHOP ),
				array( 'Pizza', 'https://example.test/order/pizza/' ),
				array( 'Works', 'https://example.test/order/pizza/works/' ),
			);
			$out    = \lafka_breadcrumb_menu_crumbs(
				$crumbs,
				array(
					'shop_url'     => self::SHOP,
					'menu_url'     => self::MENU,
					'menu_label'   => 'Menu',
					'menu_context' => true,
				)
			);
			$this->assertSame( array( 'Home', 'Menu', 'Pizza', 'Works' ), array_column( $out, 0 ) );
			$this->assertSame( self::MENU, $out[1][1] );
		}

		public function test_breadcrumb_gains_a_menu_crumb_on_menu_pages_only(): void {
			$crumbs = array(
				array( 'Home', 'https://example.test/' ),
				array( 'Wings', 'https://example.test/product-category/wings/' ),
			);
			$ctx    = array(
				'shop_url'     => '',
				'menu_url'     => self::MENU,
				'menu_label'   => 'Menu',
				'menu_context' => true,
			);
			$this->assertSame( array( 'Home', 'Menu', 'Wings' ), array_column( \lafka_breadcrumb_menu_crumbs( $crumbs, $ctx ), 0 ) );

			$ctx['menu_context'] = false;
			$this->assertSame( array( 'Home', 'Wings' ), array_column( \lafka_breadcrumb_menu_crumbs( $crumbs, $ctx ), 0 ) );
		}
	}
}
