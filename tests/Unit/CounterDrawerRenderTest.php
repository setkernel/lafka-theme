<?php
declare(strict_types=1);

/**
 * GX4 C7: the counter order drawer keeps the drawer contract (shell, plugin
 * fragment targets, trust line from the gx0 helper) and adds the counter
 * blocks in the board's order. The classic drawer stays as it was.
 *
 * WC() / the plugin renderer shims come from CartDrawerRenderTest, the
 * counter helpers from the other Counter*RenderTest files (all test files are
 * loaded before any test runs).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/payment-trust.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterDrawerRenderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_drawer_calls']                      = array();
			$GLOBALS['lafka_test_theme_mods']['lafka_drawer_layout'] = 'counter';
			$GLOBALS['lafka_test_restaurant_info']                   = array(
				'phone_e164'    => '+15550100',
				'phone_display' => '(555) 0100',
				'address_short' => '1 Example St, Exampletown',
			);
		}

		private function render( array $lines, string $subtotal = '42.94' ): string {
			$cart = new class( $lines, $subtotal ) {
				public function __construct( private array $lines, private string $subtotal ) {}
				public function get_cart_contents_count() {
					return array_sum( array_column( $this->lines, 'quantity' ) );
				}
				public function get_cart() {
					return $this->lines;
				}
				public function get_cart_subtotal() {
					return '<span class="amount">&#36;' . $this->subtotal . '</span>';
				}
			};
			$GLOBALS['lafka_test_wc'] = (object) array( 'cart' => $cart );

			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/cart-drawer.php';
			return (string) ob_get_clean();
		}

		public function test_blocks_follow_the_board_order_and_keep_the_contract(): void {
			$html = $this->render(
				array(
					'a' => array( 'quantity' => 2 ),
					'b' => array( 'quantity' => 1 ),
				)
			);
			$this->assertStringContainsString( 'class="lafka-cart-drawer lafka-cart-drawer--counter"', $html );
			$this->assertStringContainsString( 'data-lafka-cart-drawer', $html );
			$this->assertStringContainsString( 'aria-modal="true"', $html );
			$order = array(
				'>Your order</h2>',
				'<span class="lafka-drawer__summary">3 items</span>',
				'<ul class="lafka-cart-drawer__items">',
				'<div data-test="upsell"></div>',
				'Pickup or delivery?',
				'Pick up at 1 Example St, Exampletown',
				'<div data-test="total"></div>',
				'Taxes are added at checkout.',
				'<span class="lafka-drawer__checkout-total">Go to checkout — $42.94</span>',
				'Prefer to call?',
			);
			$last = -1;
			foreach ( $order as $needle ) {
				$pos = strpos( $html, $needle );
				$this->assertNotFalse( $pos, "missing: {$needle}" );
				$this->assertGreaterThan( $last, $pos, "out of order: {$needle}" );
				$last = $pos;
			}
			$this->assertSame( 2, count( $GLOBALS['lafka_test_drawer_calls'] ), 'rows come from the plugin renderer' );
		}

		public function test_phone_comes_from_the_resolver_and_trust_from_the_helper(): void {
			$html = $this->render( array( 'a' => array( 'quantity' => 1 ) ) );
			$this->assertStringContainsString( 'href="tel:+15550100"', $html );
			$this->assertStringContainsString( 'lafka-cart-drawer__trust', $html, 'the gx0 trust line helper renders in the counter drawer' );
		}

		public function test_one_mode_hides_the_choice_and_the_classic_drawer_is_untouched(): void {
			$GLOBALS['lafka_test_fulfilment_modes'] = array( 'delivery' );
			$this->assertStringNotContainsString( 'Pickup or delivery?', $this->render( array() ) );

			$GLOBALS['lafka_test_theme_mods']['lafka_drawer_layout'] = 'classic';
			$classic = $this->render( array() );
			$this->assertStringNotContainsString( 'lafka-cart-drawer--counter', $classic );
			$this->assertStringContainsString( '<h2 id="lafka-cart-drawer-title" class="lafka-cart-drawer__title">', $classic );
		}

		public function test_drawer_fragments_and_upsell_wording(): void {
			$this->render( array( 'a' => array( 'quantity' => 1 ) ), '8.50' );
			$fragments = \apply_filters( 'woocommerce_add_to_cart_fragments', array() );
			$this->assertSame( '<span class="lafka-drawer__checkout-total">Go to checkout — $8.50</span>', $fragments['span.lafka-drawer__checkout-total'] );
			$this->assertSame( '<span class="lafka-drawer__summary">1 item</span>', $fragments['span.lafka-drawer__summary'] );
			$this->assertSame( 'Add a little extra?', \apply_filters( 'lafka_cart_drawer_upsell_heading', 'Complete your meal' ) );
			$this->assertSame( 'Add', \apply_filters( 'lafka_cart_drawer_upsell_button_label', '+ Add' ) );
			$note = \apply_filters( 'lafka_cart_drawer_upsell_row_note', '', new \WC_Product( array( 'short_description' => 'Creamy garlic dipping sauce made fresh in house every day' ) ) );
			$this->assertLessThanOrEqual( 41, mb_strlen( $note ) );
			$this->assertStringEndsWith( '…', $note );
		}
	}
}
