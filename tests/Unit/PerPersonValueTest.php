<?php
declare(strict_types=1);

/**
 * GX4 A7: the per-person deal line renders only from a real "serves" value.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/deal-value.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PerPersonValueTest extends TestCase {

		public function test_rounding_and_whole_amounts(): void {
			$this->assertStringContainsString( 'about $11.50 each', \lafka_per_person_text( 22.99, 2 ) );
			$this->assertStringContainsString( 'about $10 each', \lafka_per_person_text( 29.99, 3 ) );
			$this->assertStringContainsString( 'about $16 each', \lafka_per_person_text( 31.99, 2 ) );
			$this->assertSame( 'For 2: about $11.50 each', \lafka_per_person_text( 22.99, 2 ) );
		}

		public function test_nothing_below_two_people(): void {
			$this->assertSame( '', \lafka_per_person_text( 22.99, 1 ) );
			$this->assertSame( '', \lafka_per_person_text( 22.99, 0 ) );
		}

		public function test_serves_comes_only_from_the_filter(): void {
			$combo = new \WC_Product(
				array(
					'id'   => 5,
					'name' => '2 Large Pizzas for two',
				)
			);
			$this->assertSame( 0, \lafka_serves_for( $combo ), 'never inferred from the name' );

			\add_filter(
				'lafka_product_serves',
				static function ( $n, $p ) {
					return 5 === $p->get_id() ? 2 : $n;
				},
				10,
				2
			);
			$this->assertSame( 2, \lafka_serves_for( $combo ) );
		}

		public function test_copy_is_filterable(): void {
			\add_filter(
				'lafka_per_person_text',
				static function ( $text, $price, $serves ) {
					return "Feeds {$serves}";
				},
				10,
				3
			);
			$this->assertSame( 'Feeds 3', \lafka_per_person_text( 29.99, 3 ) );
		}
	}
}
