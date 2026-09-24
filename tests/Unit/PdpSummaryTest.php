<?php
declare(strict_types=1);

/**
 * Render tests for the redesigned PDP summary (partials/pdp-summary.php).
 *
 * - Store-closed gate (audit f067): when the store is closed AND the operator
 *   opted into lafka_order_hours_disable_add_to_cart, the partial must replace
 *   its own <form class="cart"> with the closed-store card. The plugin enforced
 *   that option only on woocommerce_single_product_summary, a hook the redesign
 *   never fires.
 * - Summary hook (audit f084): the partial fires lafka_pdp_summary below the
 *   title/price and above the buy box, so the integrations that rode
 *   woocommerce_single_product_summary (nutrition, weight, social proof, sale
 *   countdown, promo tooltips, popup link) still render; single-product.php
 *   re-homes them onto it.
 *
 * The partial is required for real and the produced HTML asserted, so neither
 * test can pass while the gate or the hook is missing.
 */

namespace {

	if ( ! class_exists( 'Lafka_Nutrition_Display' ) ) {
		class Lafka_Nutrition_Display {
			public function display_nutrition() {
				echo '<div class="lafka-nutrition-info" data-test-marker="nutrition">Calories</div>';
			}
			public function display_weight() {
				echo '<p class="lafka-product-weight" data-test-marker="weight">350g</p>';
			}
		}
	}

	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="woocommerce-Price-amount amount">' . $price . '</span>';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\TestCase;

	final class PdpSummaryTest extends TestCase {

		private const PARTIAL    = __DIR__ . '/../../partials/pdp-summary.php';
		private const CONTROLLER = __DIR__ . '/../../woocommerce/single-product.php';

		protected function setUp(): void {
			// A simple product keeps the render off the variable/pickers path.
			$GLOBALS['product']                 = new \WC_Product();
			$GLOBALS['Lafka_Nutrition_Display'] = new \Lafka_Nutrition_Display();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['product'], $GLOBALS['Lafka_Nutrition_Display'] );
		}

		private function render(): string {
			ob_start();
			require self::PARTIAL;
			return (string) ob_get_clean();
		}

		/**
		 * @return array<string, array{0:bool,1:bool,2:bool}> [ shop_open, disable_add_to_cart, expect_form ]
		 */
		public static function gateScenarios(): array {
			return array(
				'open + disable option on'   => array( true, true, true ),
				'open + disable option off'  => array( true, false, true ),
				'closed + disable option on' => array( false, true, false ),
				// Closed but not opted in: customers may still build a cart.
				'closed + disable option off' => array( false, false, true ),
			);
		}

		#[DataProvider( 'gateScenarios' )]
		public function test_form_is_gated_by_store_status( bool $shop_open, bool $disable, bool $expect_form ): void {
			\Lafka_Order_Hours::$shop_open                 = $shop_open;
			\Lafka_Order_Hours::$lafka_order_hours_options = $disable ? array( 'lafka_order_hours_disable_add_to_cart' => 1 ) : array();

			$html = $this->render();

			if ( $expect_form ) {
				$this->assertStringContainsString( '<form class="cart"', $html );
				$this->assertStringContainsString( 'lafka-pdp-summary__cta', $html );
				$this->assertStringContainsString( 'lafka-pdp-mobile-cta__btn', $html );
				$this->assertStringNotContainsString( 'lafka-store-closed-card', $html );
			} else {
				$this->assertStringContainsString( 'lafka-store-closed-card', $html );
				$this->assertStringNotContainsString( '<form', $html );
				$this->assertStringNotContainsString( 'data-lafka-add-to-cart', $html );
			}
		}

		/**
		 * Integrations wired onto lafka_pdp_summary at the production priorities
		 * render, in priority order, above the buy box.
		 */
		public function test_summary_hook_renders_integrations_in_order_above_the_buy_box(): void {
			$marker = static function ( string $name ): callable {
				return static function () use ( $name ) {
					echo '<div data-test-marker="' . $name . '"></div>';
				};
			};
			\add_action( 'lafka_pdp_summary', $marker( 'social-proof' ), 6 );
			\add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_weight' ), 7 );
			\add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_nutrition' ), 8 );
			\add_action( 'lafka_pdp_summary', $marker( 'countdown' ), 9 );
			\add_action( 'lafka_pdp_summary', $marker( 'popup' ), 12 );
			\add_action( 'lafka_pdp_summary', $marker( 'tooltip-below-atc' ), 39 );

			$html = $this->render();

			$positions = array();
			foreach ( array( 'social-proof', 'weight', 'nutrition', 'countdown', 'popup', 'tooltip-below-atc' ) as $name ) {
				$positions[ $name ] = strpos( $html, 'data-test-marker="' . $name . '"' );
				$this->assertNotFalse( $positions[ $name ], "{$name} must render on the redesigned PDP." );
			}
			$sorted = $positions;
			asort( $sorted );
			$this->assertSame( array_keys( $positions ), array_keys( $sorted ), 'Integrations must render in hook-priority order.' );
			$this->assertLessThan( strpos( $html, '<form class="cart"' ), $positions['tooltip-below-atc'], 'Integrations must render above the buy box.' );
		}

		/**
		 * The controller side, which the render test cannot reach without the full
		 * template: single-product.php re-homes each orphaned callback onto the hook.
		 */
		public function test_controller_rehomes_the_orphaned_callbacks(): void {
			$controller = (string) file_get_contents( self::CONTROLLER );
			foreach (
				array(
					"add_action( 'lafka_pdp_summary', 'lafka_social_proof_render_pdp', 6 )",
					"array( \$GLOBALS['Lafka_Nutrition_Display'], 'display_nutrition' ), 8 )",
					"add_action( 'lafka_pdp_summary', 'lafka_product_sale_countdown', 9 )",
					"add_action( 'lafka_pdp_summary', 'lafka_show_custom_product_popup_link', 12 )",
					"lafka_output_info_tooltips( 'above-price' )",
				) as $wiring
			) {
				$this->assertStringContainsString( $wiring, $controller );
			}
		}
	}
}
