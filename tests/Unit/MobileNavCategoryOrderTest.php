<?php
declare(strict_types=1);

/**
 * H-12: the mobile nav lists the top-level categories in WooCommerce order
 * (the same list as /menu/ and the counter home), not by product count.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';

	if ( ! function_exists( 'bloginfo' ) ) {
		function bloginfo( $show = '' ) {
			echo esc_html( (string) get_bloginfo( $show ) );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class MobileNavCategoryOrderTest extends TestCase {

		public function test_categories_follow_wc_order_top_level_only(): void {
			$GLOBALS['lafka_test_taxonomies'] = array( 'product_cat' );
			$GLOBALS['lafka_test_terms']['product_cat'] = array(
				new \WP_Term( array( 'term_id' => 1, 'slug' => 'sauces', 'name' => 'Sauces', 'order' => 5, 'count' => 13 ) ),
				new \WP_Term( array( 'term_id' => 2, 'slug' => 'combos', 'name' => 'Combos', 'order' => 0, 'count' => 10 ) ),
				new \WP_Term( array( 'term_id' => 3, 'slug' => 'pizza', 'name' => 'Pizza', 'order' => 1, 'count' => 31 ) ),
				new \WP_Term( array( 'term_id' => 4, 'slug' => 'vegan', 'name' => 'Vegan pizzas', 'order' => 0, 'count' => 2, 'parent' => 3 ) ),
			);
			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/mobile-nav.php';
			$html = (string) ob_get_clean();

			$cats = substr( $html, (int) strpos( $html, '>Categories</h2>' ) );
			preg_match_all( '#/product-category/([a-z]+)/#', $cats, $m );
			$this->assertStringNotContainsString( '>Menu</h2>', $html, 'no duplicate "Menu" heading' );
			$this->assertSame( array( 'combos', 'pizza', 'sauces' ), $m[1] );
		}
	}
}
