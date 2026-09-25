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
			$html = $this->render();
			$this->assertStringContainsString( 'lafka-favs__item', $html );
			$this->assertStringContainsString( 'Hand-cut fries with gravy. Made fresh.', $html );
		}

		public function test_counter_header_drops_the_duplicate_fulfilment_tabs(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_parts_live'] = true;
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
	}
}
