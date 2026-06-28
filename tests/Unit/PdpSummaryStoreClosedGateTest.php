<?php
declare(strict_types=1);

/**
 * Regression lock (audit f067): the redesigned PDP must REPLACE its own
 * <form class="cart"> with the closed-store card when the store is closed AND
 * the operator opted into lafka_order_hours_disable_add_to_cart.
 *
 * Before the fix, the plugin enforced the disable option only by swapping the
 * WC add-to-cart block on woocommerce_single_product_summary — a hook the
 * redesigned PDP (partials/pdp-summary.php) never fires. The redesign therefore
 * shipped a fully working Add to Cart on a closed store with NO closed
 * indication. This is a RENDER test (not a source grep): it includes the real
 * partial and asserts the produced HTML, so it cannot pass while the inline gate
 * is dead.
 *
 * The global-namespace block holds the WP/WC shims and the WC_Product /
 * Lafka_Order_Hours stubs the procedural partial resolves its calls against; the
 * test class stays under Lafka\Tests\Unit.
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// ---- Stub product the partial reads via `global $product` ---------------
	if ( ! class_exists( 'WC_Product' ) ) {
		class WC_Product {
			public function is_type( $type ) {
				return false; // simple product — keeps the test off the variable/pickers path.
			}
			public function get_permalink() {
				return 'http://example.test/product/test/';
			}
			public function get_name() {
				return 'Test Product';
			}
			public function get_short_description() {
				return 'Short description.';
			}
			public function get_price() {
				return '9.99';
			}
			public function get_id() {
				return 123;
			}
		}
	}

	// ---- Stub the plugin gate the partial consults --------------------------
	// $shop_open / $lafka_order_hours_options are mutated per scenario; the
	// echo_closed_store_message() output mirrors the real plugin card class so
	// the assertion is meaningful (the real method is static, as called here).
	if ( ! class_exists( 'Lafka_Order_Hours' ) ) {
		class Lafka_Order_Hours {
			public static $lafka_order_hours_options = array();
			public static $shop_open                  = true;

			public static function is_shop_open() {
				return self::$shop_open;
			}

			public static function echo_closed_store_message() {
				echo '<div class="lafka-store-closed-card"><p class="lafka-store-closed-card__title">Closed right now</p></div>';
			}
		}
	}

	// ---- WP / WC shims (guarded so they coexist with sibling render tests) ---
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $tag, $value = null ) {
			return $value;
		}
	}
	if ( ! function_exists( 'do_action' ) ) {
		function do_action( $tag ) {}
	}
	if ( ! function_exists( 'absint' ) ) {
		function absint( $value ) {
			return abs( (int) $value );
		}
	}
	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="woocommerce-Price-amount amount">' . $price . '</span>';
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_html_e' ) ) {
		function esc_html_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
	if ( ! function_exists( 'esc_attr_e' ) ) {
		function esc_attr_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url, $protocols = null, $context = 'display' ) {
			return $url;
		}
	}
	if ( ! function_exists( 'wp_kses_post' ) ) {
		function wp_kses_post( $data ) {
			return $data;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	/**
	 * This is a RENDER test: it includes the real partial and asserts its HTML
	 * against the lightweight stubs declared in this file's global block. Run it
	 * in an isolated process so sibling tests can't leak heavier collaborators
	 * into the shared global scope. In particular EditorialTemplatesTest
	 * require_once's the real lafka-plugin schema helpers, which redefines
	 * lafka_get_restaurant_info() as the full plugin resolver; the partial's
	 * assurances row calls that resolver, and it in turn calls
	 * Lafka_Order_Hours::get_schedule_display_hours_map() — a method this file's
	 * minimal gate stub deliberately does not declare. Without isolation that
	 * leak turns every scenario into a fatal "undefined method" error that has
	 * nothing to do with the closed-store gate under test. A fresh process keeps
	 * function_exists( 'lafka_get_restaurant_info' ) false here, so the partial
	 * resolves against this file's own stubs (the sibling
	 * PdpSummaryHookIntegrationTest render test isolates itself for the same
	 * reason).
	 */
	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PdpSummaryStoreClosedGateTest extends TestCase {

		private string $partial;

		protected function setUp(): void {
			parent::setUp();
			$this->partial = dirname( __DIR__, 2 ) . '/partials/pdp-summary.php';

			// The partial reads `global $product`.
			$GLOBALS['product'] = new \WC_Product();

			// Reset the gate stub to "open, not disabled" before each scenario.
			\Lafka_Order_Hours::$shop_open                 = true;
			\Lafka_Order_Hours::$lafka_order_hours_options = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['product'] );
			parent::tearDown();
		}

		private function render(): string {
			$this->assertFileExists( $this->partial );
			ob_start();
			require $this->partial;
			return (string) ob_get_clean();
		}

		/**
		 * @return array<string, array{0:bool,1:bool,2:bool}>
		 *   [ shop_open, disable_add_to_cart, expect_form ]
		 */
		public static function gateScenarios(): array {
			return array(
				// Store open: add-to-cart is always allowed, option irrelevant.
				'open + disable option on'        => array( true, true, true ),
				'open + disable option off'       => array( true, false, true ),
				// The bug case: closed + opted-in must HIDE the form and show the card.
				'closed + disable option on'      => array( false, true, false ),
				// Closed but not opted-in: customers may still build a cart, so the
				// form must remain (the plugin shows an informational card via its
				// own hook, not by removing the form here).
				'closed + disable option off'     => array( false, false, true ),
			);
		}

		#[DataProvider( 'gateScenarios' )]
		public function test_form_is_gated_by_store_status( bool $shop_open, bool $disable, bool $expect_form ): void {
			\Lafka_Order_Hours::$shop_open                 = $shop_open;
			\Lafka_Order_Hours::$lafka_order_hours_options = $disable
				? array( 'lafka_order_hours_disable_add_to_cart' => 1 )
				: array();

			$html = $this->render();

			if ( $expect_form ) {
				$this->assertStringContainsString(
					'<form class="cart"',
					$html,
					'add-to-cart form must render when the store is open or the disable option is off'
				);
				$this->assertStringNotContainsString(
					'lafka-store-closed-card',
					$html,
					'closed-store card must NOT replace the form unless closed AND disable_add_to_cart is on'
				);
			} else {
				$this->assertStringContainsString(
					'lafka-store-closed-card',
					$html,
					'closed-store card must replace the form when closed AND disable_add_to_cart is on'
				);
				$this->assertStringNotContainsString(
					'<form',
					$html,
					'no add-to-cart form may render on a closed store when disable_add_to_cart is on'
				);
				$this->assertStringNotContainsString(
					'data-lafka-add-to-cart',
					$html,
					'no Add-to-Cart submit control may render on a closed, disabled store'
				);
			}
		}

		/**
		 * Sanity: when the form renders it carries the redesign CTA hooks, proving
		 * the assertion above checks the real buy box (not an empty render).
		 */
		public function test_open_store_renders_redesign_cta(): void {
			\Lafka_Order_Hours::$shop_open = true;

			$html = $this->render();

			$this->assertStringContainsString( 'lafka-pdp-summary__cta', $html );
			$this->assertStringContainsString( 'lafka-pdp-mobile-cta__btn', $html );
		}
	}
}
