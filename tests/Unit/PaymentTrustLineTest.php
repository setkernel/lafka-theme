<?php
declare(strict_types=1);

/**
 * The payment trust line under the cart-drawer / cart-page checkout CTA must
 * only ever name payment methods the store has ENABLED.
 *
 * It used to be the literal "Secure checkout · Apple Pay · Visa · Mastercard"
 * — a false claim on any store (the flagship takes card + cash, no Apple Pay).
 * It is now built from WooCommerce's enabled gateways (known core ids mapped to
 * a neutral short label, e.g. cod → "Cash"; everything else by its own title),
 * with a Customizer override (empty = auto), an on/off toggle, and the
 * `lafka_payment_trust_line` filter.
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
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/payment-trust.php';
	require_once dirname( __DIR__, 2 ) . '/incl/customizer-order-flow.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PaymentTrustLineTest extends TestCase {

		protected function setUp(): void {
			$this->use_gateways( array() );
		}

		/** A WC_Payment_Gateway look-alike. */
		private static function gateway( string $id, string $title, string $enabled = 'yes' ): object {
			return new class( $id, $title, $enabled ) {
				public function __construct( public string $id, private string $title, public string $enabled ) {}
				public function get_title() {
					return $this->title;
				}
			};
		}

		/** Point the WC() shim at a store whose registry holds $gateways. */
		private function use_gateways( array $gateways ): void {
			$registry = array();
			foreach ( $gateways as $gateway ) {
				$registry[ $gateway->id ] = $gateway;
			}
			$GLOBALS['lafka_test_wc'] = new class( $registry ) {
				public $cart;
				public function __construct( private array $registry ) {
					$this->cart = new class() {
						public function get_cart_contents_count() {
							return 0;
						}
						public function get_cart() {
							return array();
						}
					};
				}
				public function payment_gateways() {
					return new class( $this->registry ) {
						public function __construct( private array $registry ) {}
						public function payment_gateways() {
							return $this->registry;
						}
					};
				}
			};
		}

		public function test_line_names_only_enabled_gateways(): void {
			$this->use_gateways(
				array(
					self::gateway( 'authorize_net_cim_credit_card', 'Credit Card' ),
					self::gateway( 'cod', 'Cash on delivery' ),
					self::gateway( 'bacs', 'Direct bank transfer', 'no' ),
				)
			);

			$line = \lafka_payment_trust_line();

			$this->assertSame( 'Secure checkout · Credit Card · Cash', $line );
			$this->assertStringNotContainsString( 'Apple Pay', $line );
			$this->assertStringNotContainsString( 'Bank transfer', $line, 'A disabled gateway must never be advertised.' );
		}

		public function test_known_core_ids_get_a_neutral_label_even_when_retitled(): void {
			// The plugin retitles COD per fulfilment ("Pay at pickup"); the trust
			// line stays neutral.
			$this->use_gateways( array( self::gateway( 'cod', 'Pay at pickup' ) ) );

			$this->assertSame( 'Secure checkout · Cash', \lafka_payment_trust_line() );
		}

		public function test_label_map_is_filterable(): void {
			add_filter(
				'lafka_payment_trust_label_map',
				static function ( array $map ) {
					$map['my_card_gateway'] = 'Card';
					return $map;
				}
			);
			$this->use_gateways( array( self::gateway( 'my_card_gateway', 'Pay with card via SomeProcessor' ) ) );

			$this->assertSame( 'Secure checkout · Card', \lafka_payment_trust_line() );
		}

		public function test_duplicate_labels_collapse_and_markup_in_titles_is_stripped(): void {
			$this->use_gateways(
				array(
					self::gateway( 'gw_a', '<strong>Credit card</strong>' ),
					self::gateway( 'gw_b', 'credit card' ),
					self::gateway( 'gw_c', '   ' ),
				)
			);

			$this->assertSame( 'Secure checkout · Credit card', \lafka_payment_trust_line() );
		}

		public function test_no_enabled_gateways_says_only_secure_checkout(): void {
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery', 'no' ) ) );

			$this->assertSame( 'Secure checkout', \lafka_payment_trust_line() );
		}

		public function test_customizer_override_replaces_the_auto_line(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_payment_trust_text'] = '  Card or cash at the counter  ';
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$this->assertSame( 'Card or cash at the counter', \lafka_payment_trust_line() );
		}

		public function test_toggle_off_hides_the_line(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_payment_trust_enabled'] = false;
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$this->assertSame( '', \lafka_payment_trust_line() );
			$this->assertSame( '', $this->capture( static fn() => \lafka_payment_trust_render( 'x' ) ) );
		}

		public function test_filter_receives_line_labels_and_gateways(): void {
			$seen = array();
			add_filter(
				'lafka_payment_trust_line',
				static function ( $line, $labels, $gateways ) use ( &$seen ) {
					$seen = array( $line, $labels, array_keys( $gateways ) );
					return 'Filtered';
				},
				10,
				3
			);
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$this->assertSame( 'Filtered', \lafka_payment_trust_line() );
			$this->assertSame( array( 'Secure checkout · Cash', array( 'cod' => 'Cash' ), array( 'cod' ) ), $seen );
		}

		public function test_render_prints_the_line_in_the_requested_class(): void {
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$html = $this->capture( static fn() => \lafka_payment_trust_render( 'lafka-cart-trust' ) );

			$this->assertMatchesRegularExpression(
				'#^<p class="lafka-cart-trust"><span aria-hidden="true">🔒</span>\s*Secure checkout · Cash</p>$#u',
				trim( $html )
			);
		}

		public function test_cart_page_hooks_the_line_after_the_totals(): void {
			$this->assertNotFalse( has_action( 'woocommerce_after_cart_totals', 'lafka_cart_totals_trust_line' ) );
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$html = $this->capture( static fn() => \lafka_cart_totals_trust_line() );

			$this->assertStringContainsString( 'class="lafka-cart-trust"', $html );
			$this->assertStringContainsString( 'Secure checkout · Cash', $html );
		}

		public function test_cart_drawer_renders_the_built_line_not_a_hardcoded_one(): void {
			$this->use_gateways( array( self::gateway( 'cod', 'Cash on delivery' ) ) );

			$html = $this->capture(
				static function () {
					require dirname( __DIR__, 2 ) . '/partials/cart-drawer.php';
				}
			);

			$this->assertStringContainsString( 'class="lafka-cart-drawer__trust"', $html );
			$this->assertStringContainsString( 'Secure checkout · Cash', $html );
			foreach ( array( 'Apple Pay', 'Visa', 'Mastercard' ) as $brand ) {
				$this->assertStringNotContainsString( $brand, $html );
			}
		}

		public function test_no_stylesheet_hardcodes_a_payment_method_list(): void {
			$root  = dirname( __DIR__, 2 );
			$files = array_merge( array( $root . '/style.css' ), glob( $root . '/styles/*.css' ) ?: array() );
			$hits  = array();
			foreach ( $files as $file ) {
				if ( str_ends_with( $file, '.min.css' ) ) {
					continue;
				}
				// Comments stripped first so prose can't trip the scan.
				$css = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );
				foreach ( array( 'Apple Pay', 'Mastercard', 'Visa' ) as $brand ) {
					if ( str_contains( $css, $brand ) ) {
						$hits[] = basename( $file ) . ': ' . $brand;
					}
				}
			}
			$this->assertSame( array(), $hits, 'Payment methods come from the enabled gateways, never CSS content literals.' );
		}

		public function test_customizer_registers_override_and_toggle_with_auto_defaults(): void {
			$manager = new \WP_Customize_Manager();

			\lafka_order_flow_customizer_register( $manager );

			$this->assertSame( '', $manager->settings['lafka_payment_trust_text']['default'] );
			$this->assertSame( 'sanitize_text_field', $manager->settings['lafka_payment_trust_text']['sanitize_callback'] );
			$this->assertTrue( $manager->settings['lafka_payment_trust_enabled']['default'] );
			$this->assertSame( 'lafka_order_flow_trust', $manager->controls['lafka_payment_trust_text']['section'] );
			$this->assertSame( 'lafka_order_flow_trust', $manager->controls['lafka_payment_trust_enabled']['section'] );
			$this->assertSame( 'lafka_order_flow', $manager->sections['lafka_order_flow_trust']['panel'] );
		}

		private function capture( callable $fn ): string {
			ob_start();
			$fn();
			return (string) ob_get_clean();
		}
	}
}
