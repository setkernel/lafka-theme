<?php
declare(strict_types=1);

/**
 * woocommerce/single-product/meta.php (reconciled with core 11.2.0): the
 * category list is requested in the order core's
 * woocommerce_product_meta_category_orderby filter picks ('breadcrumb' by
 * default; only 'name' / 'breadcrumb' / '' are passed on), and a non-string
 * result (WP_Error) renders nothing instead of reaching the output.
 */

namespace Automattic\WooCommerce\Enums {
	if ( ! class_exists( ProductType::class ) ) {
		final class ProductType {
			public const SIMPLE   = 'simple';
			public const VARIABLE = 'variable';
		}
	}
}

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-wc-template-compat.php';

	if ( ! function_exists( 'wc_get_product_category_list' ) ) {
		function wc_get_product_category_list( $product_id, $sep = ', ', $before = '', $after = '', $orderby = '' ) {
			$GLOBALS['lafka_test_category_list_calls'][] = func_get_args();
			return $GLOBALS['lafka_test_category_list_result'] ?? '';
		}
	}
	if ( ! function_exists( 'wc_get_product_tag_list' ) ) {
		function wc_get_product_tag_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
			return '';
		}
	}
	if ( ! function_exists( 'wc_product_sku_enabled' ) ) {
		function wc_product_sku_enabled() {
			return false;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ProductMetaTemplateTest extends TestCase {

		private const TEMPLATE = __DIR__ . '/../../woocommerce/single-product/meta.php';

		protected function setUp(): void {
			$GLOBALS['lafka_test_category_list_calls']  = array();
			$GLOBALS['lafka_test_category_list_result'] = '<a href="http://example.test/c/pizza/">Pizza</a>';
		}

		protected function tearDown(): void {
			unset( $GLOBALS['product'], $GLOBALS['lafka_test_category_list_calls'], $GLOBALS['lafka_test_category_list_result'] );
		}

		private function render(): string {
			$GLOBALS['product'] = new class() extends \WC_Product {
				public function get_category_ids() {
					return array( 5 );
				}
				public function get_tag_ids() {
					return array();
				}
				public function get_sku() {
					return '';
				}
			};
			ob_start();
			require self::TEMPLATE;
			return (string) ob_get_clean();
		}

		private function requested_orderby(): mixed {
			return $GLOBALS['lafka_test_category_list_calls'][0][4] ?? null;
		}

		public function test_categories_default_to_breadcrumb_order(): void {
			$html = $this->render();

			$this->assertSame( 'breadcrumb', $this->requested_orderby() );
			$this->assertStringContainsString( '<span class="posted_in">Category:</span><a href="http://example.test/c/pizza/">Pizza</a>', $html );
		}

		public function test_core_filter_picks_the_order(): void {
			\add_filter( 'woocommerce_product_meta_category_orderby', static fn() => 'name' );

			$this->render();

			$this->assertSame( 'name', $this->requested_orderby() );
		}

		public function test_unsupported_order_falls_back_to_term_order(): void {
			\add_filter( 'woocommerce_product_meta_category_orderby', static fn() => 'random' );

			$this->render();

			$this->assertSame( '', $this->requested_orderby() );
		}

		public function test_non_string_category_list_renders_nothing(): void {
			$GLOBALS['lafka_test_category_list_result'] = new \stdClass(); // e.g. a WP_Error.

			$this->assertStringNotContainsString( 'posted_in', $this->render() );
		}
	}
}
