<?php
declare(strict_types=1);

/**
 * M-04 / O-15: a product search (`?s=…&post_type=product`) — which WooCommerce
 * ALSO reports as is_shop() — renders the matching items, echoes the query,
 * and gets a real empty state; it never falls into the grouped "full menu".
 * Category archives share the WooCommerce category order with /menu/ (M-12)
 * and no longer render the duplicate JUMP TO strip.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/counter-chrome.php';

	// Page-state shims (same contract as Ga4ViewEventsRenderTest's).
	foreach ( array( 'lafka_test_have_posts' => 0, 'lafka_test_is_shop' => false, 'lafka_test_is_cat' => false, 'lafka_test_is_tag' => false, 'lafka_test_is_search' => false, 'lafka_test_search_query' => '' ) as $lafka_k => $lafka_v ) {
		if ( ! isset( $GLOBALS[ $lafka_k ] ) ) {
			$GLOBALS[ $lafka_k ] = $lafka_v;
		}
	}
	unset( $lafka_k, $lafka_v );
	if ( ! function_exists( 'get_header' ) ) {
		function get_header( $name = '' ) {}
	}
	if ( ! function_exists( 'get_footer' ) ) {
		function get_footer( $name = '' ) {}
	}
	if ( ! function_exists( 'have_posts' ) ) {
		function have_posts() {
			if ( $GLOBALS['lafka_test_have_posts'] > 0 ) {
				$GLOBALS['lafka_test_have_posts']--;
				return true;
			}
			return false;
		}
	}
	if ( ! function_exists( 'the_post' ) ) {
		function the_post() {}
	}
	if ( ! function_exists( 'is_shop' ) ) {
		function is_shop() {
			return (bool) $GLOBALS['lafka_test_is_shop'];
		}
	}
	if ( ! function_exists( 'is_product_category' ) ) {
		function is_product_category() {
			return (bool) $GLOBALS['lafka_test_is_cat'];
		}
	}
	if ( ! function_exists( 'is_product_tag' ) ) {
		function is_product_tag() {
			return (bool) $GLOBALS['lafka_test_is_tag'];
		}
	}
	if ( ! function_exists( 'is_search' ) ) {
		function is_search() {
			return (bool) ( $GLOBALS['lafka_test_is_search'] ?? false );
		}
	}
	if ( ! function_exists( 'get_search_query' ) ) {
		function get_search_query( $escaped = true ) {
			return (string) ( $GLOBALS['lafka_test_search_query'] ?? '' );
		}
	}
	if ( ! function_exists( 'woocommerce_product_loop' ) ) {
		function woocommerce_product_loop() {
			return true;
		}
	}
	if ( ! function_exists( 'get_queried_object' ) ) {
		function get_queried_object() {
			return null;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ProductSearchArchiveTest extends TestCase {

		protected function setUp(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_taxonomies']   = array( 'product_cat' );
			$GLOBALS['lafka_test_is_shop']      = true; // WooCommerce says so for a product search.
			$GLOBALS['lafka_test_is_cat']       = false;
			$GLOBALS['lafka_test_is_tag']       = false;
			$GLOBALS['lafka_test_is_search']    = true;
			$GLOBALS['lafka_test_search_query'] = '';
			$GLOBALS['lafka_test_have_posts']   = 0;
			$GLOBALS['wp_query']                = (object) array(
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		}

		protected function tearDown(): void {
			$GLOBALS['lafka_test_is_search']  = false;
			$GLOBALS['lafka_test_is_shop']    = false;
			$GLOBALS['lafka_test_have_posts'] = 0;
			unset( $GLOBALS['wp_query'], $GLOBALS['product'] );
		}

		private function render(): string {
			ob_start();
			require dirname( __DIR__, 2 ) . '/woocommerce/archive-product.php';
			return (string) ob_get_clean();
		}

		public function test_search_results_render_matches_not_the_full_menu(): void {
			$GLOBALS['lafka_test_search_query'] = 'pizza';
			$GLOBALS['lafka_test_have_posts']   = 3; // The counter shim: the template's guard call consumes one.
			$GLOBALS['wp_query']->found_posts   = 2;
			$GLOBALS['product']                 = $GLOBALS['lafka_test_products'][105];
			$html = $this->render();

			$this->assertStringContainsString( '<h1 class="lafka-menu__title">Results for “pizza”</h1>', $html );
			$this->assertStringContainsString( '2 items on the menu match.', $html );
			$this->assertSame( 2, substr_count( $html, 'data-lafka-product-name="Pizza item 1"' ), 'the search loop, one row per hit' );
			$this->assertStringNotContainsString( 'class="lafka-menu__group"', $html, 'never the grouped full menu' );
			$this->assertStringNotContainsString( 'lafka-menu__toc', $html, 'no duplicate JUMP TO strip' );
			$this->assertStringContainsString( 'value="pizza"', $html, 'the query is prefilled' );
			$this->assertStringContainsString( 'data-lafka-menu-search-mode="server"', $html );
			$this->assertStringContainsString( '<span aria-current="page">Search results</span>', $html );
		}

		public function test_no_result_search_says_so_and_offers_the_menu(): void {
			$GLOBALS['lafka_test_search_query'] = 'xyzzy';
			$html = $this->render();

			$this->assertStringContainsString( 'Nothing on the menu matches “xyzzy”', $html );
			$this->assertStringContainsString( 'See the full menu', $html );
			$this->assertStringNotContainsString( 'class="lafka-menu__group"', $html );
		}

		public function test_category_chips_follow_the_shared_wc_order(): void {
			$html = $this->render();
			preg_match_all( '#class="lafka-menu__cat-chip[^"]*"\s+href="http://example.test/product-category/([a-z]+)/"#', $html, $m );
			$this->assertSame( array_map( static fn( $t ) => $t->slug, \lafka_menu_top_categories() ), $m[1] );
			$this->assertStringNotContainsString( 'is-active', $html, 'a search is not "All"' );
		}

		public function test_shop_view_renders_the_grouped_menu(): void {
			$GLOBALS['lafka_test_is_search'] = false;
			$html = $this->render();

			preg_match_all( '#<section class="lafka-menu__group" id="lafka-menu-cat-([a-z]+)"#', $html, $m );
			$this->assertSame( array_map( static fn( $t ) => $t->slug, \lafka_menu_top_categories() ), $m[1] );
			$this->assertStringNotContainsString( 'lafka-menu__toc', $html );
		}
	}
}
