<?php
declare(strict_types=1);

/**
 * GX4 C6: the counter's sticky mobile bar — Call + Order online, then
 * "View order · n · subtotal" once the cart has items (also a cart fragment);
 * never on cart / checkout / product pages.
 *
 * Helpers are loaded by CounterHeaderRenderTest / CounterHomeRenderTest (every
 * test file is included before any test runs).
 *
 * @package Lafka\Tests
 */

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class MobileBarRenderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_parts_live']                        = true;
			$GLOBALS['lafka_test_wc']                                = null;
			$GLOBALS['lafka_test_theme_mods']['lafka_header_layout'] = 'counter';
			$GLOBALS['lafka_test_restaurant_info']                   = array(
				'phone_e164'    => '+15550100',
				'phone_display' => '(555) 0100',
			);
		}

		private static function cart( int $count, string $subtotal ): object {
			return (object) array(
				'cart' => new class( $count, $subtotal ) {
					public function __construct( private int $count, private string $subtotal ) {}
					public function get_cart_contents_count() {
						return $this->count;
					}
					public function get_cart_subtotal() {
						return '<span class="amount"><bdi><span>&#36;</span>' . $this->subtotal . '</bdi></span>';
					}
				},
			);
		}

		private function render(): string {
			ob_start();
			\lafka_counter_render_mobile_bar();
			return (string) ob_get_clean();
		}

		public function test_empty_cart_offers_call_and_order_online(): void {
			$html = $this->render();
			$this->assertStringContainsString( 'href="tel:+15550100"', $html );
			$this->assertStringContainsString( '<span>Call</span>', $html );
			$this->assertMatchesRegularExpression( '#class="lafka-counter-bar__order[^"]*" href="http://example.test/menu/"><span>Order online</span>#', $html );
			$this->assertStringNotContainsString( 'data-lafka-cart-open', $html );
		}

		public function test_filled_cart_views_the_order(): void {
			$GLOBALS['lafka_test_wc'] = self::cart( 3, '42.94' );
			$html                     = $this->render();
			$this->assertStringContainsString( 'data-lafka-cart-open', $html );
			$this->assertStringContainsString( 'aria-label="View order, 3 items, $42.94"', $html );
			$this->assertStringContainsString( '<span>View order</span>', $html );
		}

		public function test_hidden_on_cart_checkout_and_product_pages(): void {
			foreach ( array( 'lafka_test_is_cart', 'lafka_test_is_checkout', 'lafka_test_is_product' ) as $flag ) {
				$GLOBALS[ $flag ] = true;
				$this->assertSame( '', $this->render(), "{$flag}: the page's own CTA takes over" );
				$GLOBALS[ $flag ] = false;
			}
			$GLOBALS['lafka_test_theme_mods']['lafka_counter_mobile_bar'] = false;
			$this->assertSame( '', $this->render(), 'Customizer toggle off' );
			unset( $GLOBALS['lafka_test_theme_mods']['lafka_counter_mobile_bar'] );
			$GLOBALS['lafka_test_theme_mods']['lafka_header_layout'] = 'classic';
			$this->assertSame( '', $this->render(), 'classic header: the classic sticky cart bar stays' );
		}

		public function test_order_control_is_a_cart_fragment_and_body_reserves_room(): void {
			$GLOBALS['lafka_test_wc'] = self::cart( 1, '8.50' );
			$fragments                = \apply_filters( 'woocommerce_add_to_cart_fragments', array() );
			$this->assertArrayHasKey( 'a.lafka-counter-bar__order', $fragments );
			$this->assertStringContainsString( 'View order, 1 item, $8.50', $fragments['a.lafka-counter-bar__order'] );
			$this->assertStringContainsString( 'aria-label="Cart, 1 item"', $fragments['a.lafka-counter-header__cart'], 'the header Cart name + badge refresh too' );
			$this->assertStringNotContainsString( 'is-empty', $fragments['a.lafka-counter-header__cart'] );
			$this->assertContains( 'lafka-has-counter-bar', \apply_filters( 'body_class', array() ) );
		}
	}
}
