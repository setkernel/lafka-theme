<?php
declare(strict_types=1);

/**
 * Regression lock (audit f084): the redesigned PDP must expose a summary
 * integration hook so the callbacks that rode woocommerce_single_product_summary
 * keep rendering — that WC hook is deliberately never fired by the redesign.
 *
 * Before the fix, partials/pdp-summary.php built the summary entirely from custom
 * markup and never fired any hook the orphaned integrations could ride, so
 * nutrition, weight, social-proof, the sale countdown, the promo tooltips and the
 * custom product popup link were ALL silently dropped on every product page.
 *
 * The fix introduces do_action( 'lafka_pdp_summary', $product ) in
 * partials/pdp-summary.php (just below the title/price) and re-homes the orphaned
 * callbacks onto it in woocommerce/single-product.php.
 *
 * This is a RENDER test, not a source grep: it registers callbacks on
 * lafka_pdp_summary at the production priorities, includes the real partial, and
 * asserts the produced HTML carries their markup — so it cannot pass while the
 * dedicated hook is missing or fired in the wrong place. (A JSON-LD assertion is
 * intentionally NOT used here: Product structured data is emitted via wp_head and
 * already passes; re-firing WC's native generator would risk a duplicate node.)
 *
 * Runs in an isolated process so the lightweight hook registry below is the one
 * the partial resolves do_action()/add_action() against, independent of the
 * shared-process shims other render tests install.
 *
 * The global-namespace block holds the WP/WC shims and stubs the procedural
 * partial resolves its calls against; the test class stays under
 * Lafka\Tests\Unit.
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// ---- Minimal priority-ordered hook registry ----------------------------
	// A real (not no-op) do_action so callbacks registered on lafka_pdp_summary
	// actually fire when the partial reaches the hook.
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
			$GLOBALS['__lafka_hookreg'][ $tag ][ (int) $priority ][] = $callback;
			return true;
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
			return add_action( $tag, $callback, $priority, $accepted_args );
		}
	}
	if ( ! function_exists( 'has_action' ) ) {
		function has_action( $tag, $callback = false ) {
			return ! empty( $GLOBALS['__lafka_hookreg'][ $tag ] );
		}
	}
	if ( ! function_exists( 'remove_action' ) ) {
		function remove_action( $tag, $callback, $priority = 10 ) {
			return true;
		}
	}
	if ( ! function_exists( 'do_action' ) ) {
		function do_action( $tag, ...$args ) {
			if ( empty( $GLOBALS['__lafka_hookreg'][ $tag ] ) ) {
				return;
			}
			$hooks = $GLOBALS['__lafka_hookreg'][ $tag ];
			ksort( $hooks );
			foreach ( $hooks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					call_user_func_array( $callback, $args );
				}
			}
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $tag, $value = null, ...$args ) {
			return $value;
		}
	}

	// ---- Stub product the partial reads via `global $product` --------------
	if ( ! class_exists( 'WC_Product' ) ) {
		class WC_Product {
			public function is_type( $type ) {
				return 'simple' === $type; // simple product — stays off the variable/pickers path.
			}
			public function get_permalink() {
				return 'http://example.test/product/margherita/';
			}
			public function get_name() {
				return 'Margherita Pizza';
			}
			public function get_short_description() {
				return '<p>Classic tomato and mozzarella.</p>';
			}
			public function get_price() {
				return '12.50';
			}
			public function get_variation_price( $min_or_max = 'min', $display = false ) {
				return '8.00';
			}
			public function get_id() {
				return 42;
			}
		}
	}

	// ---- Stub the order-hours gate the partial consults (store OPEN) -------
	if ( ! class_exists( 'Lafka_Order_Hours' ) ) {
		class Lafka_Order_Hours {
			public static $lafka_order_hours_options = array();
			public static $shop_open                  = true;

			public static function is_shop_open() {
				return self::$shop_open;
			}

			public static function echo_closed_store_message() {
				echo '<div class="lafka-store-closed-card"></div>';
			}
		}
	}

	// ---- Faithful stub of the plugin's nutrition display singleton ---------
	// Same class name + method names as the real plugin object so the methods
	// are wired exactly as woocommerce/single-product.php wires them. The
	// instance is created in setUp() (test runtime only), never at file load, so
	// nothing is registered when this file is merely discovered.
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

	// ---- WP / WC output shims ---------------------------------------------
	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="woocommerce-Price-amount amount">' . $price . '</span>';
		}
	}
	if ( ! function_exists( 'absint' ) ) {
		function absint( $value ) {
			return abs( (int) $value );
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

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PdpSummaryHookIntegrationTest extends TestCase {

		private string $summary_partial;
		private string $controller;

		protected function setUp(): void {
			parent::setUp();

			$this->summary_partial = dirname( __DIR__, 2 ) . '/partials/pdp-summary.php';
			$this->controller      = dirname( __DIR__, 2 ) . '/woocommerce/single-product.php';

			// Fresh registry + product per scenario.
			$GLOBALS['__lafka_hookreg']                    = array();
			$GLOBALS['product']                            = new \WC_Product();
			$GLOBALS['Lafka_Nutrition_Display']            = new \Lafka_Nutrition_Display();
			\Lafka_Order_Hours::$shop_open                 = true;
			\Lafka_Order_Hours::$lafka_order_hours_options = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['product'], $GLOBALS['Lafka_Nutrition_Display'], $GLOBALS['__lafka_hookreg'] );
			parent::tearDown();
		}

		/**
		 * Wire the genuinely-orphaned summary callbacks onto lafka_pdp_summary at
		 * the SAME relative priorities woocommerce/single-product.php uses. The
		 * nutrition methods are wired through the (faithfully named) singleton; the
		 * theme/plugin procedural callbacks are wired as marker-emitting closures
		 * so the render proves each zone fires.
		 */
		private function wire_orphaned_callbacks(): void {
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="lafka-social-proof" data-test-marker="social-proof"></div>';
			}, 6 );
			add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_weight' ), 7 );
			add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_nutrition' ), 8 );
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="count_holder" data-test-marker="countdown"></div>';
			}, 9 );
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="lafka-promo-above-price" data-test-marker="tooltip-above"></div>';
			}, 9 );
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="lafka-promo-below-price" data-test-marker="tooltip-below-price"></div>';
			}, 11 );
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="lafka-product-popup-link" data-test-marker="popup"></div>';
			}, 12 );
			add_action( 'lafka_pdp_summary', static function () {
				echo '<div class="lafka-promo-below-add-to-cart" data-test-marker="tooltip-below-atc"></div>';
			}, 39 );
		}

		private function render_summary(): string {
			$this->assertFileExists( $this->summary_partial );
			ob_start();
			require $this->summary_partial;
			return (string) ob_get_clean();
		}

		/**
		 * The orphaned summary integrations must render on the redesigned PDP via
		 * the dedicated lafka_pdp_summary hook.
		 */
		public function test_orphaned_summary_callbacks_render_on_redesigned_pdp(): void {
			$this->wire_orphaned_callbacks();

			$html = $this->render_summary();

			// The two integrations the brief calls out explicitly.
			$this->assertStringContainsString(
				'data-test-marker="nutrition"',
				$html,
				'Nutrition markup must render on the redesigned PDP — it rode woocommerce_single_product_summary, which the redesign never fires.'
			);
			$this->assertStringContainsString(
				'data-test-marker="social-proof"',
				$html,
				'Social-proof markup must render on the redesigned PDP via the lafka_pdp_summary hook.'
			);

			// The remaining genuinely-orphaned integrations must also be re-homed.
			$this->assertStringContainsString( 'data-test-marker="weight"', $html, 'Product weight must render.' );
			$this->assertStringContainsString( 'data-test-marker="countdown"', $html, 'Sale countdown must render.' );
			$this->assertStringContainsString( 'data-test-marker="tooltip-above"', $html, 'above-price promo tooltip zone must render.' );
			$this->assertStringContainsString( 'data-test-marker="tooltip-below-price"', $html, 'below-price promo tooltip zone must render.' );
			$this->assertStringContainsString( 'data-test-marker="tooltip-below-atc"', $html, 'below-add-to-cart promo tooltip zone must render.' );
			$this->assertStringContainsString( 'data-test-marker="popup"', $html, 'custom product popup link must render.' );
		}

		/**
		 * The hook fires in priority order and sits below the title/price but above
		 * the buy box — so summary integrations land where the redesign intends.
		 */
		public function test_summary_hook_fires_in_priority_order_above_the_buy_box(): void {
			$this->wire_orphaned_callbacks();

			$html = $this->render_summary();

			$social = strpos( $html, 'data-test-marker="social-proof"' );
			$weight = strpos( $html, 'data-test-marker="weight"' );
			$nutri  = strpos( $html, 'data-test-marker="nutrition"' );
			$form   = strpos( $html, '<form class="cart"' );

			$this->assertNotFalse( $social );
			$this->assertNotFalse( $nutri );
			$this->assertNotFalse( $form );

			// Priority order: social-proof @6 < weight @7 < nutrition @8.
			$this->assertLessThan( $weight, $social, 'social-proof (prio 6) must render before weight (prio 7).' );
			$this->assertLessThan( $nutri, $weight, 'weight (prio 7) must render before nutrition (prio 8).' );

			// The integration point sits above the redesign's Add-to-Cart form.
			$this->assertLessThan( $form, $nutri, 'summary integrations must render above the buy box.' );
		}

		/**
		 * Sanity: the partial still renders its rebuilt buy box (so the test above
		 * asserts against a real, fully-rendered summary — not an early return).
		 */
		public function test_redesign_buy_box_still_renders(): void {
			$this->wire_orphaned_callbacks();

			$html = $this->render_summary();

			$this->assertStringContainsString( 'lafka-pdp-summary__cta', $html );
			$this->assertStringContainsString( 'data-lafka-add-to-cart', $html );
		}

		/**
		 * Wiring lock (source): pdp-summary.php must fire the dedicated hook, and
		 * single-product.php must re-home the orphaned callbacks onto it at their
		 * original priorities. Complements the render test above; this is the
		 * controller side, which the render test cannot reach without dragging in
		 * the full template (get_header/loop/related products).
		 */
		public function test_controller_and_partial_wire_the_dedicated_hook(): void {
			$partial = (string) file_get_contents( $this->summary_partial );
			$this->assertStringContainsString(
				"do_action( 'lafka_pdp_summary', \$product )",
				$partial,
				'pdp-summary.php must fire the dedicated lafka_pdp_summary action.'
			);

			$controller = (string) file_get_contents( $this->controller );
			$this->assertStringContainsString(
				"add_action( 'lafka_pdp_summary', 'lafka_social_proof_render_pdp', 6 )",
				$controller,
				'single-product.php must re-home social-proof onto lafka_pdp_summary @6.'
			);
			$this->assertStringContainsString(
				"array( \$GLOBALS['Lafka_Nutrition_Display'], 'display_nutrition' ), 8 )",
				$controller,
				'single-product.php must re-home nutrition onto lafka_pdp_summary @8.'
			);
			$this->assertStringContainsString(
				"add_action( 'lafka_pdp_summary', 'lafka_show_custom_product_popup_link', 12 )",
				$controller,
				'single-product.php must re-home the custom product popup link onto lafka_pdp_summary @12.'
			);
			$this->assertStringContainsString(
				"lafka_output_info_tooltips( 'above-price' )",
				$controller,
				'single-product.php must re-home the promo info tooltip zones onto lafka_pdp_summary.'
			);
			$this->assertStringContainsString(
				"add_action( 'lafka_pdp_summary', 'lafka_product_sale_countdown', 9 )",
				$controller,
				'single-product.php must re-home the sale countdown onto lafka_pdp_summary @9.'
			);
		}
	}
}
