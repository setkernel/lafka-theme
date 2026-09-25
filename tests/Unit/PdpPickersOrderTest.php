<?php
declare(strict_types=1);

/**
 * The PDP size chips (partials/pdp-pickers.php) follow the plugin's option
 * order. The live store showed "Medium / Large / Small / X-Large" because the
 * partial iterated get_variation_attributes(), which returns values in
 * database order.
 */

namespace {

	if ( ! function_exists( 'wc_format_decimal' ) ) {
		function wc_format_decimal( $number, $dp = false ) {
			return number_format( (float) $number, (int) $dp, '.', '' );
		}
	}
	if ( ! function_exists( 'wc_attribute_label' ) ) {
		function wc_attribute_label( $name, $product = '' ) {
			return 'Size';
		}
	}
	if ( ! function_exists( 'taxonomy_exists' ) ) {
		function taxonomy_exists( $taxonomy ) {
			return false;
		}
	}
	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="woocommerce-Price-amount amount">' . $price . '</span>';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\RunInSeparateProcess;
	use PHPUnit\Framework\TestCase;

	final class PdpPickersOrderTest extends TestCase {

		private const PARTIAL = __DIR__ . '/../../partials/pdp-pickers.php';

		/** Sizes as the database returns them, with their prices. */
		private const DB_ORDER = array(
			'medium'  => 12.5,
			'large'   => 17.5,
			'small'   => 8.5,
			'x-large' => 21.5,
		);

		private function render(): string {
			// The partial requires a WC_Product: extend the shared stub.
			$product = new class( self::DB_ORDER ) extends \WC_Product {
				public function __construct( private array $sizes ) {
					parent::__construct( array( 'type' => 'variable' ) );
				}
				public function get_variation_attributes() {
					return array( 'pa_size' => array_keys( $this->sizes ) );
				}
				public function get_available_variations() {
					$out = array();
					foreach ( $this->sizes as $slug => $price ) {
						$out[] = array(
							'attributes'    => array( 'attribute_pa_size' => $slug ),
							'display_price' => $price,
						);
					}
					return $out;
				}
			};

			ob_start();
			require self::PARTIAL;
			$html = (string) ob_get_clean();

			preg_match_all( '/name="attribute_pa_size" value="([^"]+)"/', $html, $m );
			return implode( ',', $m[1] );
		}

		public function test_chips_follow_the_plugin_order(): void {
			require_once dirname( __DIR__ ) . '/fixtures/plugin-variation-order.php';
			$GLOBALS['lafka_test_option_prices'] = self::DB_ORDER;

			$this->assertSame( 'small,medium,large,x-large', $this->render() );
		}

		#[RunInSeparateProcess]
		public function test_without_the_plugin_the_chips_keep_woocommerce_order(): void {
			$this->assertSame( 'medium,large,small,x-large', $this->render() );
		}
	}
}
