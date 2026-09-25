<?php
declare(strict_types=1);

/**
 * woocommerce/cart/cart-shipping.php (O-06 / O-16):
 *
 * - the shipping options render as full-width choice cards, the chosen one
 *   marked, with core's input names/ids/values intact (the cart.js /
 *   checkout.js update contract);
 * - on /cart/ a PICKUP choice prints no "Shipping to {destination}." line and
 *   no shipping calculator — nothing is shipped to that address;
 * - a delivery choice keeps both, exactly as core.
 */

namespace {
	if ( ! function_exists( 'wc_cart_totals_shipping_method_label' ) ) {
		function wc_cart_totals_shipping_method_label( $method ) {
			return $method->label;
		}
	}
	if ( ! function_exists( 'woocommerce_shipping_calculator' ) ) {
		function woocommerce_shipping_calculator( $button_text = '' ) {
			echo '<form class="woocommerce-shipping-calculator">' . $button_text . '</form>'; // phpcs:ignore
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CartShippingTemplateTest extends TestCase {

		private const TEMPLATE = __DIR__ . '/../../woocommerce/cart/cart-shipping.php';

		protected function setUp(): void {
			$GLOBALS['lafka_test_is_cart'] = true;
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_is_cart'] );
		}

		private static function rate( string $id, string $label ): object {
			return (object) array(
				'id'    => $id,
				'label' => $label,
			);
		}

		/** Render the template with WooCommerce's wc_cart_totals_shipping_html() arguments. */
		private static function render( string $chosen, array $methods ): string {
			$available_methods        = array();
			foreach ( $methods as $method ) {
				$available_methods[ $method->id ] = $method;
			}
			$package                  = array( 'destination' => array( 'country' => 'CA' ) );
			$formatted_destination    = 'NS';
			$has_calculated_shipping  = true;
			$show_shipping_calculator = true;
			$show_package_details     = false;
			$package_details          = '';
			$package_name             = 'Shipping';
			$index                    = 0;
			$chosen_method            = $chosen;

			ob_start();
			include self::TEMPLATE;
			return (string) ob_get_clean();
		}

		private static function both(): array {
			return array(
				self::rate( 'local_pickup:9', 'Pickup from store' ),
				self::rate( 'distance_rate:8', 'Delivery' ),
			);
		}

		public function test_options_are_choice_cards_with_the_chosen_one_marked(): void {
			$html = self::render( 'distance_rate:8', self::both() );

			$this->assertStringContainsString( '<td colspan="2" data-title="Shipping">', $html, 'The options span the whole totals table.' );
			$this->assertStringContainsString( '<span class="lafka-shipping-totals__label" id="lafka-shipping-label-0">Shipping</span>', $html );
			$this->assertStringContainsString( '<ul id="shipping_method" class="woocommerce-shipping-methods lafka-shipping-choices" aria-labelledby="lafka-shipping-label-0">', $html );
			$this->assertSame( 2, substr_count( $html, 'class="lafka-shipping-choice' ) );
			$this->assertMatchesRegularExpression( '/<li class="lafka-shipping-choice is-selected">\s*<input type="radio" name="shipping_method\[0\]" data-index="0" id="shipping_method_0_[^"]+" value="distance_rate:8" class="shipping_method"\s+checked=\'checked\'/', $html );
			$this->assertMatchesRegularExpression( '/<li class="lafka-shipping-choice">\s*<input type="radio" name="shipping_method\[0\]" data-index="0" id="shipping_method_0_[^"]+" value="local_pickup:9" class="shipping_method"\s+\/>/', $html );
		}

		public function test_pickup_prints_no_destination_and_no_calculator(): void {
			$html = self::render( 'local_pickup:9', self::both() );

			$this->assertStringNotContainsString( 'Shipping to', $html );
			$this->assertStringNotContainsString( 'woocommerce-shipping-calculator', $html );
			$this->assertStringContainsString( 'lafka-shipping-totals--pickup', $html );
		}

		public function test_a_single_pickup_rate_counts_as_the_choice(): void {
			$html = self::render( '', array( self::rate( 'pickup_location:0', 'Pickup' ) ) );

			$this->assertStringContainsString( '<input type="hidden" name="shipping_method[0]"', $html );
			$this->assertStringNotContainsString( 'Shipping to', $html );
			$this->assertStringNotContainsString( 'woocommerce-shipping-calculator', $html );
		}

		public function test_delivery_keeps_the_destination_line_and_calculator(): void {
			$html = self::render( 'distance_rate:8', self::both() );

			$this->assertStringContainsString( 'Shipping to <strong>NS</strong>.', $html );
			$this->assertStringContainsString( '<form class="woocommerce-shipping-calculator">Change address</form>', $html );
			$this->assertStringNotContainsString( 'lafka-shipping-totals--pickup', $html );
		}

		public function test_checkout_never_prints_the_cart_destination_line(): void {
			$GLOBALS['lafka_test_is_cart'] = false;

			$html = self::render( 'distance_rate:8', self::both() );

			$this->assertStringNotContainsString( 'Shipping to', $html );
		}
	}
}
