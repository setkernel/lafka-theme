<?php
declare(strict_types=1);

/**
 * Order-path glue (incl/woocommerce/lafka-order-path.php):
 *  - O-02: under the counter drawer, WooCommerce's "redirect to the cart after
 *    add" reads "no" on the front end, so adds open the drawer; the admin
 *    settings screen keeps the stored value; a filter keeps the redirect.
 *  - O-07: window.lafkaCfg carries the pickup shipping method ids.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-order-path.php';

	if ( ! function_exists( 'lafka_delivery_minimum' ) ) {
		function lafka_delivery_minimum() {
			return $GLOBALS['lafka_test_delivery_minimum'] ?? 0;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class OrderPathTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_delivery_minimum']        = 0;
			$GLOBALS['lafka_test_free_delivery_threshold'] = 0;
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_delivery_minimum'], $GLOBALS['lafka_test_free_delivery_threshold'] );
		}

		public function test_the_delivery_note_states_only_configured_rules(): void {
			$this->assertSame( 'The delivery fee shows at checkout once you enter your address.', lafka_counter_drawer_delivery_note(), 'Nothing configured: nothing invented.' );

			$GLOBALS['lafka_test_delivery_minimum']        = 30;
			$GLOBALS['lafka_test_free_delivery_threshold'] = 50;
			$note = lafka_counter_drawer_delivery_note();
			$this->assertMatchesRegularExpression( '/^Delivery on orders over \\D*30(\\.00)?\\. Free delivery over \\D*50(\\.00)?\\. The delivery fee shows/', $note );
		}

		public function test_maps_hints_only_where_a_map_can_load(): void {
			$GLOBALS['lafka_test_is_cart']     = false;
			$GLOBALS['lafka_test_is_checkout'] = false;
			$this->assertFalse( lafka_page_may_load_google_maps(), 'A content page (e.g. /contact-us/) with no map.' );

			$GLOBALS['lafka_test_is_checkout'] = true;
			$this->assertTrue( lafka_page_may_load_google_maps() );

			$GLOBALS['lafka_test_is_checkout'] = false;
			add_filter( 'lafka_page_may_load_google_maps', '__return_true' );
			$this->assertTrue( lafka_page_may_load_google_maps(), 'Filterable (e.g. a page builder map).' );
			unset( $GLOBALS['lafka_test_is_cart'], $GLOBALS['lafka_test_is_checkout'] );
		}

		public function test_the_counter_drawer_turns_the_cart_redirect_off_on_the_front_end(): void {
			set_theme_mod( 'lafka_drawer_layout', 'counter' );

			$this->assertSame( 'no', lafka_counter_cart_redirect_after_add( false ) );
		}

		public function test_the_classic_drawer_and_the_admin_screen_keep_the_stored_option(): void {
			set_theme_mod( 'lafka_drawer_layout', 'classic' );
			$this->assertFalse( lafka_counter_cart_redirect_after_add( false ), 'Classic drawer: WooCommerce decides.' );

			set_theme_mod( 'lafka_drawer_layout', 'counter' );
			$GLOBALS['lafka_test_is_admin'] = true;
			$this->assertFalse( lafka_counter_cart_redirect_after_add( false ), 'WooCommerce → Settings shows the stored value.' );
		}

		public function test_the_operator_can_keep_the_redirect(): void {
			set_theme_mod( 'lafka_drawer_layout', 'counter' );
			add_filter( 'lafka_counter_force_ajax_add', '__return_false' );

			$this->assertFalse( lafka_counter_cart_redirect_after_add( false ) );
		}

		public function test_the_fulfilment_config_lists_the_pickup_methods(): void {
			$this->assertSame(
				array( 'local_pickup', 'pickup_location' ),
				lafka_order_path_fulfilment_cfg( array( 'fulfilmentKey' => 'k' ) )['pickupMethods']
			);

			add_filter(
				'lafka_pickup_shipping_method_ids',
				static function ( $ids ) {
					$ids[] = 'my_counter_pickup';
					return $ids;
				}
			);
			$cfg = lafka_order_path_fulfilment_cfg( array( 'fulfilmentKey' => 'k' ) );
			$this->assertContains( 'my_counter_pickup', $cfg['pickupMethods'] );
			$this->assertSame( 'k', $cfg['fulfilmentKey'], 'The storage contract is kept.' );
		}
	}
}
