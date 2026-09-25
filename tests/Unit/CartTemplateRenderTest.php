<?php
declare(strict_types=1);

/**
 * woocommerce/cart/cart.php renders a line through the same contract as core
 * cart/cart.php 11.2.0:
 *
 * - WooCommerce 11.2+ (WC_Cart::get_item_product_name() exists): the
 *   selected-variation-aware name is what woocommerce_cart_item_name filters
 *   receive (plain and linked) and what wc_get_formatted_cart_item_data() gets
 *   as its third argument, so attributes already in the name are not repeated.
 * - Older WooCommerce: the product's own get_name(), exactly as before.
 * - Every one of the 12 core cart actions still fires, in core order, and the
 *   form still carries the woocommerce-cart nonce (the cart submit path).
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-wc-template-compat.php';

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return $GLOBALS['lafka_test_wc'];
		}
	}
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		function wc_get_cart_url() {
			return 'http://example.test/cart/';
		}
	}
	if ( ! function_exists( 'lafka_theme_menu_url' ) ) {
		function lafka_theme_menu_url() {
			return 'http://example.test/menu/';
		}
	}
	if ( ! function_exists( 'get_template_part' ) ) {
		function get_template_part( $slug, $name = '', $args = array() ) {}
	}
	if ( ! function_exists( 'wc_coupons_enabled' ) ) {
		function wc_coupons_enabled() {
			return true;
		}
	}
	if ( ! function_exists( 'wp_nonce_field' ) ) {
		function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
			echo '<input type="hidden" name="' . $name . '" data-action="' . $action . '" />'; // phpcs:ignore
		}
	}
	if ( ! function_exists( 'wc_get_cart_remove_url' ) ) {
		function wc_get_cart_remove_url( $cart_item_key ) {
			return 'http://example.test/cart/?remove_item=' . $cart_item_key;
		}
	}
	if ( ! function_exists( 'woocommerce_quantity_input' ) ) {
		function woocommerce_quantity_input( $args = array(), $product = null, $echo = true ) {
			return '<input class="qty" name="' . $args['input_name'] . '" data-product-name="' . $args['product_name'] . '" />';
		}
	}
	if ( ! function_exists( 'wc_get_formatted_cart_item_data' ) ) {
		function wc_get_formatted_cart_item_data( $cart_item, $flat = false, $product_name = null ) {
			$GLOBALS['lafka_test_item_data_calls'][] = func_get_args();
			return '';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CartTemplateRenderTest extends TestCase {

		private const TEMPLATE = __DIR__ . '/../../woocommerce/cart/cart.php';

		/** The 12 do_action hooks of core cart/cart.php, in core order. */
		private const CORE_ACTIONS = array(
			'woocommerce_before_cart',
			'woocommerce_before_cart_table',
			'woocommerce_before_cart_contents',
			'woocommerce_after_cart_item_name',
			'woocommerce_cart_contents',
			'woocommerce_after_cart_contents',
			'woocommerce_cart_coupon',
			'woocommerce_cart_actions',
			'woocommerce_after_cart_table',
			'woocommerce_before_cart_collaterals',
			'woocommerce_cart_collaterals',
			'woocommerce_after_cart',
		);

		protected function setUp(): void {
			$GLOBALS['lafka_test_item_data_calls'] = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_wc'], $GLOBALS['lafka_test_item_data_calls'] );
		}

		private static function product(): \WC_Product {
			return new class( array( 'id' => 77, 'name' => 'Poutine' ) ) extends \WC_Product {
				public function exists() {
					return true;
				}
				public function is_visible() {
					return true;
				}
				public function get_permalink( $cart_item = null ) {
					return 'http://example.test/product/poutine/';
				}
				public function get_image() {
					return '<img src="http://example.test/poutine.jpg" alt="">';
				}
				public function backorders_require_notification() {
					return false;
				}
				public function is_on_backorder( $qty = 0 ) {
					return false;
				}
				public function is_sold_individually() {
					return false;
				}
				public function get_max_purchase_quantity() {
					return -1;
				}
				public function get_sku() {
					return '';
				}
			};
		}

		/** @param bool $modern Whether the cart exposes WC 11.2's get_item_product_name(). */
		private function render( bool $modern ): string {
			$lines = array(
				'k1' => array(
					'data'       => self::product(),
					'product_id' => 77,
					'quantity'   => 1,
					'variation'  => array( 'attribute_size' => 'Large' ),
				),
			);
			$cart  = $modern
				? new class( $lines ) {
					public function __construct( private array $lines ) {}
					public function get_cart() {
						return $this->lines;
					}
					public function get_product_price( $product ) {
						return '$9.00';
					}
					public function get_product_subtotal( $product, $qty ) {
						return '$9.00';
					}
					public function get_item_product_name( $cart_item, $product = null ) {
						return $product->get_name() . ' - ' . $cart_item['variation']['attribute_size'];
					}
				}
				: new class( $lines ) {
					public function __construct( private array $lines ) {}
					public function get_cart() {
						return $this->lines;
					}
					public function get_product_price( $product ) {
						return '$9.00';
					}
					public function get_product_subtotal( $product, $qty ) {
						return '$9.00';
					}
				};
			$GLOBALS['lafka_test_wc'] = (object) array( 'cart' => $cart );

			ob_start();
			require self::TEMPLATE;
			return (string) ob_get_clean();
		}

		private function record_names(): \ArrayObject {
			$names = new \ArrayObject();
			\add_filter(
				'woocommerce_cart_item_name',
				static function ( $name ) use ( $names ) {
					$names[] = $name;
					return $name;
				}
			);
			return $names;
		}

		public function test_wc_11_2_variation_aware_name_feeds_the_name_filters_and_item_data(): void {
			$names = $this->record_names();

			$html = $this->render( true );

			$this->assertSame(
				array( 'Poutine - Large', '<a href="http://example.test/product/poutine/">Poutine - Large</a>' ),
				$names->getArrayCopy()
			);
			$this->assertSame( 'Poutine - Large', $GLOBALS['lafka_test_item_data_calls'][0][2] ?? null );
			$this->assertStringContainsString( 'data-product-name="Poutine - Large"', $html );
			$this->assertStringContainsString( 'aria-label="Remove Poutine - Large from cart"', $html );
		}

		public function test_older_wc_keeps_the_product_name(): void {
			$names = $this->record_names();

			$this->render( false );

			$this->assertSame(
				array( 'Poutine', '<a href="http://example.test/product/poutine/">Poutine</a>' ),
				$names->getArrayCopy()
			);
			$this->assertSame( 'Poutine', $GLOBALS['lafka_test_item_data_calls'][0][2] ?? null );
		}

		public function test_all_core_cart_actions_fire_in_order_with_the_cart_nonce(): void {
			$fired = new \ArrayObject();
			foreach ( self::CORE_ACTIONS as $hook ) {
				\add_action(
					$hook,
					static function () use ( $fired, $hook ) {
						$fired[] = $hook;
					}
				);
			}

			$html = $this->render( true );

			$this->assertSame( self::CORE_ACTIONS, $fired->getArrayCopy() );
			$this->assertStringContainsString( 'name="woocommerce-cart-nonce" data-action="woocommerce-cart"', $html );
			$this->assertStringContainsString( 'name="cart[k1][qty]"', $html );
		}
	}
}
