<?php
declare(strict_types=1);

/**
 * GX4 polish: products carrying an unregistered legacy product_type (e.g. a
 * migrated store's `combo`) stay in wc_get_products() listings — the counter
 * deals, the /menu/ groups — instead of silently vanishing (the prod clone's
 * Combos category rendered one deal out of ten).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-listing-product-types.php';

	if ( ! function_exists( 'wc_get_product_types' ) ) {
		function wc_get_product_types() {
			return array(
				'simple'   => 'Simple product',
				'grouped'  => 'Grouped product',
				'external' => 'External/Affiliate product',
				'variable' => 'Variable product',
			);
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ListingProductTypesTest extends TestCase {

		protected function setUp(): void {
			foreach ( array( 'simple', 'variable', 'combo' ) as $i => $slug ) {
				$GLOBALS['lafka_test_terms']['product_type'][] = new \WP_Term(
					array(
						'term_id'  => 90 + $i,
						'slug'     => $slug,
						'name'     => $slug,
						'taxonomy' => 'product_type',
						'count'    => 3,
					)
				);
			}
		}

		public function test_default_type_list_gains_in_use_legacy_types(): void {
			$args = \apply_filters( 'woocommerce_product_object_query_args', array( 'type' => array( 'simple', 'grouped', 'external', 'variable' ) ) );
			$this->assertContains( 'combo', $args['type'] );
			$this->assertContains( 'simple', $args['type'] );
		}

		public function test_an_explicit_type_request_is_left_alone(): void {
			$args = \apply_filters( 'woocommerce_product_object_query_args', array( 'type' => array( 'variable' ) ) );
			$this->assertSame( array( 'variable' ), $args['type'] );
		}

		public function test_nothing_extra_when_every_type_is_registered(): void {
			$GLOBALS['lafka_test_terms']['product_type'] = array_slice( $GLOBALS['lafka_test_terms']['product_type'], 0, 2 );
			$this->assertSame( array(), \lafka_listing_extra_product_types() );
		}
	}
}
