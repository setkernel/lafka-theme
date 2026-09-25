<?php
declare(strict_types=1);

/**
 * O-31: the classic-checkout inline-error helper (js/lafka-checkout-fields.js)
 * loads on the classic checkout FORM page only (not order-received, not a
 * block checkout), after WooCommerce's wc-checkout, with its phone-digits
 * rule and plain-language strings handed over through the filterable config.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-checkout-fields.php';

	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		function is_wc_endpoint_url( $endpoint = false ) {
			return in_array( $endpoint, (array) ( $GLOBALS['lafka_test_wc_endpoints'] ?? array() ), true );
		}
	}
	// Same store + shape as ScriptLoadingStrategyTest's shim (whichever file
	// loads first defines it), plus the dependencies.
	if ( ! function_exists( 'wp_enqueue_script' ) ) {
		function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {
			if ( ! isset( $GLOBALS['lafka_test_scripts'][ $handle ] ) ) {
				$GLOBALS['lafka_test_scripts'][ $handle ] = array( 'src' => $src, 'args' => $args, 'deps' => $deps );
			}
			$GLOBALS['lafka_test_scripts'][ $handle ]['enqueued'] = true;
		}
	}
	if ( ! function_exists( 'wp_localize_script' ) ) {
		function wp_localize_script( $handle, $object_name, $l10n ) {
			$GLOBALS['lafka_test_localized'][ $handle ][ $object_name ] = $l10n;
			return true;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CheckoutFieldsScriptTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_is_checkout']      = true;
			$GLOBALS['lafka_test_wc_endpoints']     = array();
			$GLOBALS['lafka_test_scripts']          = array();
			$GLOBALS['lafka_test_localized']        = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_is_checkout'], $GLOBALS['lafka_test_wc_endpoints'], $GLOBALS['lafka_test_scripts'], $GLOBALS['lafka_test_localized'] );
		}

		public function test_enqueued_on_the_classic_checkout_form_after_wc_checkout(): void {
			lafka_enqueue_checkout_fields_script();

			$script = $GLOBALS['lafka_test_scripts']['lafka-checkout-fields'] ?? null;
			$this->assertNotNull( $script );
			$this->assertStringEndsWith( '/js/lafka-checkout-fields.js', $script['src'] );
			if ( array_key_exists( 'deps', $script ) ) { // Absent when ScriptLoadingStrategyTest's shim loaded first.
				$this->assertSame( array( 'jquery', 'wc-checkout' ), $script['deps'] );
			}
			$this->assertTrue( $script['args'], 'In the footer.' );
			$this->assertSame( 7, $GLOBALS['lafka_test_localized']['lafka-checkout-fields']['lafkaCheckoutFields']['phoneMinDigits'] );
		}

		public function test_not_on_order_received_or_off_checkout(): void {
			$GLOBALS['lafka_test_wc_endpoints'] = array( 'order-received' );
			lafka_enqueue_checkout_fields_script();
			$this->assertSame( array(), $GLOBALS['lafka_test_scripts'] );

			$GLOBALS['lafka_test_wc_endpoints'] = array();
			$GLOBALS['lafka_test_is_checkout']  = false;
			lafka_enqueue_checkout_fields_script();
			$this->assertSame( array(), $GLOBALS['lafka_test_scripts'] );
		}

		public function test_config_is_filterable_and_worded_plainly(): void {
			add_filter( 'lafka_checkout_phone_min_digits', static fn() => 10 );

			$config = lafka_checkout_fields_js_config();

			$this->assertSame( 10, $config['phoneMinDigits'] );
			$this->assertSame( '%s is required.', $config['i18n']['required'] );
			$this->assertSame( 'Enter a valid email address.', $config['i18n']['email'] );
			$this->assertSame( 'Enter a valid phone number.', $config['i18n']['phone'] );
		}

		public function test_a_negative_minimum_means_no_check(): void {
			add_filter( 'lafka_checkout_phone_min_digits', static fn() => -3 );

			$this->assertSame( 0, lafka_checkout_fields_js_config()['phoneMinDigits'] );
		}
	}
}
