<?php
declare(strict_types=1);

/**
 * GX4 A6: size-price columns for a product row, computed from WooCommerce's
 * own variation price cache — one case per real-catalogue shape (plan §0 F5).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PriceColumnsTest extends TestCase {

		/** @return list<\WP_Term> */
		private static function terms( array $slug_names ): array {
			$out = array();
			foreach ( $slug_names as $slug => $name ) {
				$out[] = new \WP_Term(
					array(
						'slug' => $slug,
						'name' => $name,
					)
				);
			}
			return $out;
		}

		/** Pizza: pa_crust x pa_size, gluten-free only in M/L (priced higher). */
		public static function pizza(): \WC_Product_Variable {
			$GLOBALS['lafka_test_attr_terms']['pa_size']  = self::terms(
				array(
					'small'  => 'Small',
					'medium' => 'Medium',
					'large'  => 'Large',
					'xlarge' => 'X-Large',
				)
			);
			$GLOBALS['lafka_test_attr_terms']['pa_crust'] = self::terms(
				array(
					'regular'     => 'Regular',
					'thin'        => 'Thin',
					'gluten-free' => 'Gluten-free',
				)
			);
			$regular = array(
				'small'  => 13.95,
				'medium' => 19.45,
				'large'  => 25.95,
				'xlarge' => 32.95,
			);
			$rows    = array();
			$vid     = 100;
			foreach ( array( 'regular', 'thin' ) as $crust ) {
				foreach ( $regular as $size => $price ) {
					$rows[ ++$vid ] = array(
						'price'      => $price,
						'attributes' => array(
							'attribute_pa_crust' => $crust,
							'attribute_pa_size'  => $size,
						),
					);
				}
			}
			foreach ( array( 'medium' => 22.45, 'large' => 28.95 ) as $size => $price ) {
				$rows[ ++$vid ] = array(
					'price'      => $price,
					'attributes' => array(
						'attribute_pa_crust' => 'gluten-free',
						'attribute_pa_size'  => $size,
					),
				);
			}
			return new \WC_Product_Variable(
				array(
					'id'                   => 7,
					'name'                 => 'Classic Combo',
					'variation_attributes' => array(
						'pa_crust' => array( 'regular', 'thin', 'gluten-free' ),
						'pa_size'  => array( 'small', 'medium', 'large', 'xlarge' ),
					),
					'variations'           => $rows,
				)
			);
		}

		private function columns( \WC_Product $p, array $args = array() ): array {
			return \lafka_price_columns( $p, $args );
		}

		private static function col_prices( array $r ): array {
			return array_map( static fn( $c ) => $c['price'], $r['columns'] );
		}

		private static function col_labels( array $r ): array {
			return array_map( static fn( $c ) => $c['label'], $r['columns'] );
		}

		public function test_poutine_regular_large_gives_two_columns(): void {
			$GLOBALS['lafka_test_attr_terms']['pa_size'] = self::terms(
				array(
					'regular' => 'Regular',
					'large'   => 'Large',
				)
			);
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array( 'pa_size' => array( 'regular', 'large' ) ),
					'variations'           => array(
						11 => array(
							'price'      => 14.99,
							'attributes' => array( 'attribute_pa_size' => 'large' ),
						),
						10 => array(
							'price'      => 10.99,
							'attributes' => array( 'attribute_pa_size' => 'regular' ),
						),
					),
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( 'columns', $r['type'] );
			$this->assertSame( 'pa_size', $r['attribute'] );
			$this->assertSame( 'Size', $r['attribute_label'] );
			$this->assertSame( array( 'Regular', 'Large' ), self::col_labels( $r ) );
			$this->assertSame( array( 10.99, 14.99 ), self::col_prices( $r ) );
			$this->assertSame( 10.99, $r['price'], 'price = the minimum' );
		}

		public function test_pizza_picks_size_with_min_over_crust(): void {
			$r = $this->columns( self::pizza() );
			$this->assertSame( 'columns', $r['type'] );
			$this->assertSame( 'pa_size', $r['attribute'], 'size drives the price (4 distinct mins vs crust 2)' );
			$this->assertSame( array( 'Small', 'Medium', 'Large', 'X-Large' ), self::col_labels( $r ) );
			$this->assertSame( array( 13.95, 19.45, 25.95, 32.95 ), self::col_prices( $r ) );
		}

		public function test_columns_follow_term_order_not_variation_order(): void {
			$GLOBALS['lafka_test_attr_terms']['pa_size'] = self::terms(
				array(
					'small'  => 'Small',
					'medium' => 'Medium',
					'large'  => 'Large',
					'xlarge' => 'X-Large',
				)
			);
			$rows = array();
			$vid  = 20;
			foreach ( array( 'large' => 12.5, 'medium' => 10.5, 'small' => 8.5, 'xlarge' => 14.5 ) as $size => $price ) {
				$rows[ ++$vid ] = array(
					'price'      => $price,
					'attributes' => array( 'attribute_pa_size' => $size ),
				);
			}
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array( 'pa_size' => array( 'large', 'medium', 'small', 'xlarge' ) ),
					'variations'           => $rows,
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( array( 'Small', 'Medium', 'Large', 'X-Large' ), self::col_labels( $r ) );
			$this->assertSame( array( 8.5, 10.5, 12.5, 14.5 ), self::col_prices( $r ) );
		}

		public function test_any_attribute_is_skipped_for_columns(): void {
			$GLOBALS['lafka_test_attr_terms']['pa_pieces'] = self::terms(
				array(
					'10' => '10 pieces',
					'20' => '20 pieces',
				)
			);
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array(
						'pa_pieces'    => array( '10', '20' ),
						'pa_seasoning' => array( 'hot', 'mild' ),
					),
					'variations'           => array(
						31 => array(
							'price'      => 10.99,
							'attributes' => array(
								'attribute_pa_pieces'    => '10',
								'attribute_pa_seasoning' => '',
							),
						),
						32 => array(
							'price'      => 19.99,
							'attributes' => array(
								'attribute_pa_pieces'    => '20',
								'attribute_pa_seasoning' => '',
							),
						),
					),
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( 'pa_pieces', $r['attribute'] );
			$this->assertSame( array( '10 pieces', '20 pieces' ), self::col_labels( $r ) );
		}

		public function test_more_columns_than_the_max_gives_from(): void {
			$sizes = array(
				'xs' => 'XS',
				's'  => 'S',
				'm'  => 'M',
				'l'  => 'L',
				'xl' => 'XL',
			);
			$GLOBALS['lafka_test_attr_terms']['pa_size'] = self::terms( $sizes );
			$rows = array();
			$i    = 0;
			foreach ( array_keys( $sizes ) as $size ) {
				$rows[ 40 + $i ] = array(
					'price'      => 5 + $i,
					'attributes' => array( 'attribute_pa_size' => $size ),
				);
				++$i;
			}
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array( 'pa_size' => array_keys( $sizes ) ),
					'variations'           => $rows,
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( 'from', $r['type'] );
			$this->assertSame( 5.0, $r['price'] );

			\add_filter(
				'lafka_price_columns_max',
				static function () {
					return 5;
				}
			);
			$this->assertSame( 'columns', $this->columns( $p )['type'], 'the max is filterable' );
		}

		public function test_all_same_prices_give_single(): void {
			$GLOBALS['lafka_test_attr_terms']['pa_size'] = self::terms(
				array(
					'a' => 'A',
					'b' => 'B',
				)
			);
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array( 'pa_size' => array( 'a', 'b' ) ),
					'variations'           => array(
						51 => array(
							'price'      => 9,
							'attributes' => array( 'attribute_pa_size' => 'a' ),
						),
						52 => array(
							'price'      => 9,
							'attributes' => array( 'attribute_pa_size' => 'b' ),
						),
					),
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( 'single', $r['type'] );
			$this->assertSame( 9.0, $r['price'] );
		}

		public function test_simple_product_gives_single(): void {
			$r = $this->columns( new \WC_Product( array( 'price' => '8.50' ) ) );
			$this->assertSame(
				array(
					'type'  => 'single',
					'price' => 8.5,
				),
				$r
			);
		}

		public function test_custom_attribute_uses_saved_option_order(): void {
			$p = new \WC_Product_Variable(
				array(
					'attributes'           => array(
						'size' => new \WC_Product_Attribute(
							array(
								'name'    => 'Size',
								'options' => array( 'Small', 'Medium', 'Large' ),
							)
						),
					),
					'variation_attributes' => array( 'Size' => array( 'Large', 'Small', 'Medium' ) ),
					'variations'           => array(
						61 => array(
							'price'      => 16,
							'attributes' => array( 'attribute_size' => 'Large' ),
						),
						62 => array(
							'price'      => 10,
							'attributes' => array( 'attribute_size' => 'Small' ),
						),
						63 => array(
							'price'      => 13,
							'attributes' => array( 'attribute_size' => 'Medium' ),
						),
					),
				)
			);
			$r = $this->columns( $p );
			$this->assertSame( 'columns', $r['type'] );
			$this->assertSame( array( 'Small', 'Medium', 'Large' ), self::col_labels( $r ) );
			$this->assertSame( array( 10.0, 13.0, 16.0 ), self::col_prices( $r ) );
		}

		public function test_result_is_filterable(): void {
			\add_filter(
				'lafka_price_columns',
				static function ( $r ) {
					$r['type'] = 'from';
					return $r;
				}
			);
			$this->assertSame( 'from', $this->columns( self::pizza() )['type'] );
		}
	}
}
