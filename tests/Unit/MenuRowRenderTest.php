<?php
declare(strict_types=1);

/**
 * GX4 C3: the counter product row (partials/counter/menu-row.php) and the
 * single hand-off in woocommerce/loop/lafka-product-card.php.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-archive-quickadd.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/open-status.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/hours-display.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/counter-chrome.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class MenuRowRenderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_parts_live'] = true;
		}

		private function row( \WC_Product $product, array $args = array() ): string {
			ob_start();
			\get_template_part( 'partials/counter/menu-row', null, array( 'product' => $product ) + $args );
			return (string) ob_get_clean();
		}

		public function test_variable_row_has_columns_in_order_and_a_chooser_button(): void {
			$html = $this->row( \Lafka_Test_Catalog::pizza() );
			preg_match_all( '#<dt>([^<]+)</dt>\s*<dd>([^<]+)</dd>#', $html, $m );
			$this->assertSame( array( 'Small', 'Medium', 'Large', 'X-Large' ), $m[1] );
			$this->assertSame( array( '$13.95', '$19.45', '$25.95', '$32.95' ), $m[2] );
			$this->assertStringContainsString( 'data-lafka-add-mode="chooser"', $html );
			$this->assertStringContainsString( 'Add<span class="screen-reader-text"> Classic Combo</span></button>', $html );
			$this->assertStringContainsString( 'lafka-row--wide-prices', $html );
			$this->assertArrayHasKey( 7, $GLOBALS['lafka_chooser_registry'], 'variable rows register their chooser payload' );
		}

		public function test_simple_row_adds_directly(): void {
			$html = $this->row( new \WC_Product( array( 'price' => '8.50' ) ) );
			$this->assertStringContainsString( 'data-lafka-add-mode="direct"', $html );
			$this->assertStringContainsString( '<p class="lafka-row__price">$8.50</p>', $html );
			$this->assertSame( array(), $GLOBALS['lafka_chooser_registry'] );
		}

		public function test_required_addons_and_any_attribute_link_to_the_product_page(): void {
			$GLOBALS['lafka_test_required_addons'][7] = true;
			$html = $this->row( \Lafka_Test_Catalog::pizza() );
			$this->assertStringNotContainsString( '<button', $html );
			$this->assertStringContainsString( 'class="lafka-counter-btn lafka-counter-btn--choose lafka-row__add" href="http://example.test/product/classic-combo/"', $html );
			$this->assertStringContainsString( 'options for Classic Combo', $html );
		}

		public function test_quick_add_toggle_off_links_to_the_product_page(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_archive_quickadd_enabled'] = false;
			$this->assertStringNotContainsString( 'data-lafka-add=', $this->row( new \WC_Product() ) );
		}

		public function test_no_button_inside_a_link(): void {
			$GLOBALS['lafka_test_attachments'][55] = 'http://example.test/dish.png';
			$p                                     = \Lafka_Test_Catalog::two_size( 9 );
			$p->data['image_id']                   = 55;
			$html                                  = $this->row( $p );
			$this->assertStringContainsString( '<button', $html );
			$this->assertDoesNotMatchRegularExpression( '#<a\b[^>]*>(?:(?!</a>).)*<button#s', $html );
		}

		public function test_image_link_is_out_of_the_tab_order_and_named_link_carries_select_item(): void {
			$p                                     = \Lafka_Test_Catalog::two_size( 9 );
			$p->data['image_id']                   = 55;
			$GLOBALS['lafka_test_attachments'][55] = 'http://example.test/dish.png';
			$GLOBALS['lafka_test_post_terms'][9]   = array(
				'product_cat' => array( 'Fries' ),
				'product_tag' => array( 'Vegetarian' ),
			);

			$html = $this->row( $p, array( 'list' => 'Home' ) );
			$this->assertStringContainsString( 'class="lafka-row__media" href="http://example.test/product/loaded-fries/" tabindex="-1" aria-hidden="true"', $html );
			$this->assertStringContainsString( 'sizes="(min-width: 1024px) 140px, 104px"', $html );
			foreach ( array( 'data-lafka-item-id="9"', 'data-lafka-item-name="Loaded Fries"', 'data-lafka-item-category="Fries"', 'data-lafka-item-price="10.99"', 'data-lafka-list-name="Home"' ) as $attr ) {
				$this->assertStringContainsString( $attr, $html );
			}
			$this->assertStringContainsString( 'data-lafka-product-name="Loaded Fries"', $html, 'menu-controls search keeps working' );
			$this->assertStringContainsString( 'data-lafka-product-tags="vegetarian"', $html, 'menu-controls dietary chips keep working' );
		}

		public function test_compact_row_can_drop_the_thumbnail(): void {
			$p                                     = \Lafka_Test_Catalog::two_size( 9 );
			$p->data['image_id']                   = 55;
			$GLOBALS['lafka_test_attachments'][55] = 'http://example.test/dish.png';
			$html                                  = $this->row(
				$p,
				array(
					'style'  => 'compact',
					'thumbs' => false,
				)
			);
			$this->assertStringContainsString( 'lafka-row--compact lafka-row--no-img', $html );
			$this->assertStringNotContainsString( '<img', $html );
		}

		public function test_card_hands_off_only_under_the_counter_menu(): void {
			$lafka_arch_p = new \WC_Product();
			\lafka_test_use_classic_layouts();

			ob_start();
			require dirname( __DIR__, 2 ) . '/woocommerce/loop/lafka-product-card.php';
			$classic = (string) ob_get_clean();
			$this->assertStringContainsString( 'lafka-favs__item', $classic, 'classic layout keeps the classic card' );
			$this->assertStringNotContainsString( 'lafka-row', $classic );

			$GLOBALS['lafka_test_theme_mods']['lafka_menu_layout'] = 'counter';
			ob_start();
			require dirname( __DIR__, 2 ) . '/woocommerce/loop/lafka-product-card.php';
			$counter = (string) ob_get_clean();
			$this->assertStringContainsString( '<li', $counter );
			$this->assertStringContainsString( 'class="lafka-row lafka-row--photo', $counter );
			$this->assertStringNotContainsString( 'lafka-favs__item', $counter );
		}
	}
}
