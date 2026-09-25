<?php
declare(strict_types=1);

/**
 * The grouped "All" menu (partials/menu-groups.php — /menu/ and the shop view
 * of archive-product.php): every item is reachable, subcategories get their
 * own heading, and the search / filter empty state is always present.
 *
 * History: f054 capped each group at 24 with a "See all" link; GX QA (M-03)
 * found 7 pizzas missing from /menu/ and the "See all" archive unpaginated.
 * The default is now no cap; an operator cap still links out.
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
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class MenuGroupedViewLimitTest extends TestCase {

		/**
		 * Pizza (parent, id 20) with Classic (21) and Vegan (22) children; Wings
		 * (30) flat. 30 pizzas: 1-12 Classic, 13-18 Vegan, 16-18 ALSO Classic,
		 * 19-30 filed only under the parent.
		 */
		private function seed(): void {
			$GLOBALS['lafka_test_parts_live'] = true;
			$GLOBALS['lafka_test_terms']['product_cat'] = array(
				new \WP_Term( array( 'term_id' => 20, 'slug' => 'pizza', 'name' => 'Pizza', 'order' => 0, 'count' => 30 ) ),
				new \WP_Term( array( 'term_id' => 21, 'slug' => 'classic', 'name' => 'Classic pizzas', 'parent' => 20, 'order' => 0, 'count' => 15 ) ),
				new \WP_Term( array( 'term_id' => 22, 'slug' => 'vegan', 'name' => 'Vegan pizzas', 'parent' => 20, 'order' => 1, 'count' => 6 ) ),
				new \WP_Term( array( 'term_id' => 30, 'slug' => 'wings', 'name' => 'Wings', 'order' => 1, 'count' => 2 ) ),
			);
			for ( $n = 1; $n <= 30; $n++ ) {
				$cats = array( 'pizza' );
				if ( $n <= 12 || ( $n >= 16 && $n <= 18 ) ) {
					$cats[] = 'classic';
				}
				if ( $n >= 13 && $n <= 18 ) {
					$cats[] = 'vegan';
				}
				$this->product( 100 + $n, sprintf( 'Pizza %02d', $n ), $cats );
			}
			$this->product( 201, 'Wings 1', array( 'wings' ) );
			$this->product( 202, 'Wings 2', array( 'wings' ) );
		}

		private function product( int $id, string $name, array $cats ): void {
			$product = new \WC_Product(
				array(
					'id'        => $id,
					'name'      => $name,
					'price'     => '10',
					'cats'      => $cats,
					'permalink' => "http://example.test/product/{$id}/",
				)
			);
			$GLOBALS['lafka_test_catalog'][]       = $product;
			$GLOBALS['lafka_test_products'][ $id ] = $product;
		}

		private function render(): string {
			ob_start();
			\get_template_part(
				'partials/menu-groups',
				null,
				array(
					'terms'     => \lafka_menu_top_categories(),
					'reset_url' => 'http://example.test/menu/',
				)
			);
			return (string) ob_get_clean();
		}

		/** @return list<string> Row names in render order. */
		private function names( string $html ): array {
			preg_match_all( '#data-lafka-product-name="([^"]+)"#', $html, $m );
			return $m[1];
		}

		public function test_every_item_renders_by_default_with_no_see_all_link(): void {
			$this->seed();
			$html  = $this->render();
			$names = $this->names( $html );

			$this->assertCount( 32, $names, 'all 30 pizzas + 2 wings — no silent cap' );
			$this->assertSame( count( $names ), count( array_unique( $names ) ), 'an item filed in two subcategories renders once' );
			$this->assertStringNotContainsString( 'lafka-menu__group-all', $html );
		}

		public function test_operator_cap_truncates_and_links_to_the_full_archive(): void {
			$this->seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_menu_group_limit'] = 5;
			$html = $this->render();

			$this->assertStringContainsString( '<a class="lafka-menu__group-all" href="http://example.test/product-category/pizza/">', $html );
			$this->assertStringContainsString( 'See all 30 items', $html );
			$this->assertSame( 5 + 2, count( $this->names( $html ) ) );
		}

		public function test_cap_is_filterable_per_category(): void {
			$this->seed();
			\add_filter(
				'lafka_menu_group_limit',
				static fn( $limit, $term ) => 'wings' === $term->slug ? 1 : $limit,
				10,
				2
			);
			$html = $this->render();

			$this->assertContains( 'Wings 1', $this->names( $html ) );
			$this->assertNotContains( 'Wings 2', $this->names( $html ) );
			$this->assertStringContainsString( 'See all 2 items', $html );
		}

		public function test_sections_are_top_level_categories_only(): void {
			$this->seed();
			$html = $this->render();

			preg_match_all( '#<section class="lafka-menu__group" id="lafka-menu-cat-([a-z]+)"#', $html, $m );
			$this->assertSame( array( 'pizza', 'wings' ), $m[1] );
		}

		public function test_subcategories_get_headings_in_wc_order_after_loose_items(): void {
			$this->seed();
			$html  = $this->render();
			$names = $this->names( $html );

			preg_match_all( '#<h3 class="lafka-menu__subhead" id="lafka-menu-cat-([a-z]+)">#', $html, $m );
			$this->assertSame( array( 'classic', 'vegan' ), $m[1] );

			// Loose parent-only items first, then Classic (incl. 16-18), then Vegan (13-15).
			$this->assertSame( 'Pizza 19', $names[0] );
			$classic_at = strpos( $html, 'id="lafka-menu-cat-classic"' );
			$vegan_at   = strpos( $html, 'id="lafka-menu-cat-vegan"' );
			$this->assertGreaterThan( $classic_at, strpos( $html, 'data-lafka-product-name="Pizza 17"' ) );
			$this->assertLessThan( $vegan_at, strpos( $html, 'data-lafka-product-name="Pizza 17"' ), 'a Classic + Vegan item sits under the first subcategory' );
			$this->assertGreaterThan( $vegan_at, strpos( $html, 'data-lafka-product-name="Pizza 14"' ) );
		}

		public function test_subheads_can_be_turned_off(): void {
			$this->seed();
			$GLOBALS['lafka_test_theme_mods']['lafka_menu_subheads'] = false;
			$html = $this->render();

			$this->assertStringNotContainsString( 'lafka-menu__subhead', $html );
			$this->assertCount( 32, $this->names( $html ) );
		}

		public function test_empty_state_is_always_present_hidden_with_a_reset(): void {
			$this->seed();
			$html = $this->render();

			$this->assertMatchesRegularExpression( '#<div class="lafka-menu__empty" data-lafka-menu-empty hidden>#', $html );
			$this->assertStringContainsString( 'data-lafka-menu-reset', $html );
			$this->assertStringContainsString( 'data-lafka-menu-status aria-live="polite"', $html );
		}

		public function test_empty_catalogue_shows_the_empty_state_with_a_way_back(): void {
			$GLOBALS['lafka_test_parts_live'] = true;
			$html = $this->render();

			$this->assertMatchesRegularExpression( '#<div class="lafka-menu__empty" data-lafka-menu-empty>#', $html );
			$this->assertStringContainsString( 'href="http://example.test/menu/"', $html );
		}
	}
}
