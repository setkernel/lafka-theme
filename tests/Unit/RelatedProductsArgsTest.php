<?php
declare(strict_types=1);

/**
 * Related-products args must reach WooCommerce as integers.
 *
 * wc_get_related_products() logs "Invalid limit type passed to
 * wc_get_related_products. Expected integer, got string" on EVERY product page
 * when posts_per_page is a string — which is what a legacy-migrated
 * lafka_number_related_products theme_mod holds ("6"). The theme must coerce
 * the Customizer value to a clamped int before handing it to WC, and the
 * "0 = hide related products" switch must keep working off the same value.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-related-products.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\TestCase;

	final class RelatedProductsArgsTest extends TestCase {

		/**
		 * @return array<string, array{0:mixed, 1:int}>
		 */
		public static function provide_theme_mods(): array {
			return array(
				'legacy string'        => array( '6', 6 ),
				'string four'          => array( '4', 4 ),
				'int passes through'   => array( 3, 3 ),
				'zero string hides'    => array( '0', 0 ),
				'garbage → default'    => array( 'lots', 6 ),
				'empty → default'      => array( '', 6 ),
				'negative → absolute'  => array( '-2', 2 ),
				'huge → clamped'       => array( '500', 24 ),
				'float string → floor' => array( '5.7', 5 ),
			);
		}

		#[DataProvider( 'provide_theme_mods' )]
		public function test_posts_per_page_is_a_clamped_int( $stored, int $expected ): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_number_related_products'] = $stored;

			$args = \lafka_related_products_args( array( 'posts_per_page' => 4, 'columns' => 4, 'orderby' => 'rand' ) );

			$this->assertSame( $expected, $args['posts_per_page'] );
			$this->assertSame( 1, $args['columns'] );
			$this->assertSame( 'rand', $args['orderby'], 'Other WC args must pass through untouched.' );
		}

		public function test_unset_theme_mod_uses_the_default_count(): void {
			$this->assertSame( 6, \lafka_related_products_count() );
			$this->assertSame( 6, \lafka_related_products_args( array() )['posts_per_page'] );
		}

		public function test_args_filter_is_registered(): void {
			$this->assertNotFalse( has_filter( 'woocommerce_output_related_products_args', 'lafka_related_products_args' ) );
		}
	}
}
