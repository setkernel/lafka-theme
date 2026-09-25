<?php
declare(strict_types=1);

/**
 * GX4 C8: /menu/ (page-menu.php) under the counter menu layout — the category
 * list is lafka_menu_top_categories() (the same list the counter home uses),
 * every product renders as a counter row that the search / dietary filters
 * still address, and section heads carry the category tagline.
 *
 * Shims: have_posts()/get_header()/get_footer() from Ga4ViewEventsRenderTest,
 * the catalogue store from CounterHomeRenderTest::seed().
 *
 * @package Lafka\Tests
 */

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterMenuPageRenderTest extends TestCase {

		private function render(): string {
			$GLOBALS['lafka_test_taxonomies'] = array( 'product_cat' );
			$GLOBALS['lafka_test_have_posts'] = 1;
			ob_start();
			require dirname( __DIR__, 2 ) . '/page-menu.php';
			return (string) ob_get_clean();
		}

		public function test_category_list_is_the_shared_helper(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_menu_layout'] = 'counter';
			$html = $this->render();

			preg_match_all( '#href="\#lafka-menu-cat-([a-z]+)"#', $html, $chips );
			$this->assertSame(
				array_map( static fn( $t ) => $t->slug, \lafka_menu_top_categories() ),
				$chips[1]
			);
		}

		public function test_counter_rows_keep_the_filter_contract_and_taglines(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_menu_layout'] = 'counter';
			$GLOBALS['lafka_test_post_terms'][103]                 = array( 'product_tag' => array( 'vegetarian' ) );
			$html = $this->render();

			$this->assertSame( 10, substr_count( $html, 'class="lafka-row lafka-row--photo' ) );
			$this->assertStringNotContainsString( 'lafka-favs__item', $html );
			$this->assertStringContainsString( 'data-lafka-product-name="Fries item 1"', $html );
			$this->assertStringContainsString( 'data-lafka-product-tags="vegetarian"', $html );
			$this->assertStringContainsString( '<p class="lafka-menu__group-blurb">Hand-cut fries with gravy.</p>', $html );
		}

		public function test_classic_menu_keeps_cards_and_the_full_description(): void {
			CounterHomeRenderTest::seed();
			\lafka_test_use_classic_layouts();
			$html = $this->render();
			$this->assertStringContainsString( 'lafka-favs__item', $html );
			$this->assertStringContainsString( 'Hand-cut fries with gravy. Made fresh.', $html );
		}

		public function test_counter_header_drops_the_duplicate_fulfilment_tabs(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_parts_live'] = true;
			$GLOBALS['lafka_test_terms']['product_tag'] = array(
				new \WP_Term( array( 'term_id' => 90, 'slug' => 'vegetarian', 'name' => 'Vegetarian', 'taxonomy' => 'product_tag', 'count' => 3 ) ),
			);
			\lafka_test_use_classic_layouts();
			ob_start();
			\get_template_part( 'partials/menu-controls' );
			$classic = (string) ob_get_clean();
			$this->assertStringContainsString( 'lafka-menu__tabs', $classic );

			$GLOBALS['lafka_test_theme_mods']['lafka_header_layout'] = 'counter';
			ob_start();
			\get_template_part( 'partials/menu-controls' );
			$counter = (string) ob_get_clean();
			$this->assertStringNotContainsString( 'lafka-menu__tabs', $counter );
			$this->assertStringContainsString( 'data-lafka-menu-search', $counter, 'search stays' );
			$this->assertStringContainsString( 'data-lafka-filter="vegetarian"', $counter, 'dietary filters stay' );
		}
	
		public function test_filter_chips_render_only_when_a_product_matches(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_parts_live'] = true;
			$GLOBALS['lafka_test_terms']['product_tag'] = array(
				new \WP_Term( array( 'term_id' => 90, 'slug' => 'vegan', 'name' => 'Vegan', 'taxonomy' => 'product_tag', 'count' => 2 ) ),
				new \WP_Term( array( 'term_id' => 91, 'slug' => 'spicy', 'name' => 'Spicy', 'taxonomy' => 'product_tag', 'count' => 0 ) ),
			);
			ob_start();
			\get_template_part( 'partials/menu-controls' );
			$html = (string) ob_get_clean();

			$this->assertStringContainsString( 'data-lafka-filter="vegan"', $html );
			$this->assertStringNotContainsString( 'data-lafka-filter="spicy"', $html, 'a tag with no products would empty the menu' );
			$this->assertStringNotContainsString( 'data-lafka-filter="vegetarian"', $html );
			$this->assertStringNotContainsString( 'data-lafka-filter="popular"', $html, 'no featured products, no Popular chip' );
		}

		public function test_featured_products_enable_the_popular_chip_and_no_tags_hide_the_row(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_parts_live'] = true;
			ob_start();
			\get_template_part( 'partials/menu-controls' );
			$none = (string) ob_get_clean();
			$this->assertStringNotContainsString( 'data-lafka-menu-filters', $none, 'no chip has a product: no filter row' );

			$GLOBALS['lafka_test_catalog'][0]->data['featured'] = true;
			ob_start();
			\get_template_part( 'partials/menu-controls' );
			$featured = (string) ob_get_clean();
			$this->assertStringContainsString( 'data-lafka-filter="popular"', $featured );
		}

		public function test_search_box_is_a_real_product_search_form(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_parts_live'] = true;
			ob_start();
			\get_template_part(
				'partials/menu-controls',
				null,
				array(
					'search_query' => 'jalapeño',
					'search_mode'  => 'server',
				)
			);
			$html = (string) ob_get_clean();

			$this->assertStringContainsString( 'method="get"', $html );
			$this->assertStringContainsString( 'name="s"', $html );
			$this->assertStringContainsString( 'value="jalapeño"', $html, 'the query is echoed back' );
			$this->assertStringContainsString( '<input type="hidden" name="post_type" value="product">', $html );
			$this->assertStringContainsString( 'data-lafka-menu-search-mode="server"', $html );
			$this->assertStringNotContainsString( 'onsubmit', $html );
		}
	}
}
