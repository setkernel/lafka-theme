<?php
declare(strict_types=1);

/**
 * The cart drawer (partials/cart-drawer.php) is a theme shell around
 * lafka-plugin's renderers: each line comes from lafka_cart_drawer_render_item()
 * (one call per cart line, or one no-argument call for the empty state), and
 * the upsell and the subtotal/free-delivery block come from
 * lafka_cart_drawer_render_upsell() / lafka_cart_drawer_render_total(). The
 * plugin re-renders those same callables for the AJAX fragments, so the theme
 * must not inline its own copies.
 */

namespace {
	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return $GLOBALS['lafka_test_wc'];
		}
	}
	if ( ! function_exists( 'wc_get_checkout_url' ) ) {
		function wc_get_checkout_url() {
			return 'http://example.test/checkout/';
		}
	}
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		function wc_get_cart_url() {
			return 'http://example.test/cart/';
		}
	}
	if ( ! function_exists( 'lafka_cart_drawer_render_item' ) ) {
		function lafka_cart_drawer_render_item( ...$args ) {
			$GLOBALS['lafka_test_drawer_calls'][] = $args;
			echo '<li data-test="item-' . ( $args ? esc_attr( $args[0] ) : 'empty' ) . '"></li>';
		}
	}
	if ( ! function_exists( 'lafka_cart_drawer_render_upsell' ) ) {
		function lafka_cart_drawer_render_upsell() {
			echo '<div data-test="upsell"></div>';
		}
	}
	if ( ! function_exists( 'lafka_cart_drawer_render_total' ) ) {
		function lafka_cart_drawer_render_total() {
			echo '<div data-test="total"></div>';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CartDrawerRenderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_drawer_calls'] = array();
			\lafka_test_use_classic_layouts(); // The classic drawer (GX4: Peppery defaults to counter).
		}

		private function render( array $lines ): string {
			$cart = new class( $lines ) {
				public function __construct( private array $lines ) {}
				public function get_cart_contents_count() {
					return array_sum( array_column( $this->lines, 'quantity' ) );
				}
				public function get_cart() {
					return $this->lines;
				}
			};
			$GLOBALS['lafka_test_wc'] = (object) array( 'cart' => $cart );

			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/cart-drawer.php';
			return (string) ob_get_clean();
		}

		public function test_each_cart_line_is_rendered_by_the_plugin_renderer(): void {
			$lines = array(
				'abc' => array( 'quantity' => 2 ),
				'def' => array( 'quantity' => 1 ),
			);

			$html = $this->render( $lines );

			$this->assertSame(
				array( array( 'abc', $lines['abc'] ), array( 'def', $lines['def'] ) ),
				$GLOBALS['lafka_test_drawer_calls']
			);
			$this->assertMatchesRegularExpression( '#<ul class="lafka-cart-drawer__items">\s*<li data-test="item-abc"></li><li data-test="item-def"></li>\s*</ul>#', $html );
			$this->assertStringContainsString( '<div data-test="upsell"></div>', $html );
			$this->assertMatchesRegularExpression( '#<footer class="lafka-cart-drawer__footer">\s*<div data-test="total"></div>#', $html );
		}

		public function test_empty_cart_keeps_the_fragment_target_and_asks_for_the_empty_state(): void {
			$html = $this->render( array() );

			$this->assertSame( array( array() ), $GLOBALS['lafka_test_drawer_calls'] );
			$this->assertStringContainsString( '<li data-test="item-empty"></li>', $html );
			$this->assertStringContainsString( '<div data-test="total"></div>', $html );
		}
	}
}
