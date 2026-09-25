<?php
declare(strict_types=1);

/**
 * GX4 A6: the 2-tap size chooser payload — pure data the page embeds as JSON
 * for js/lafka-size-chooser.js. `addable` is false (row shows "Choose" -> the
 * product page) whenever a 2-tap add could build a wrong or invalid cart line.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ChooserPayloadTest extends TestCase {

		private static function attr( array $payload, string $name ): array {
			foreach ( $payload['attributes'] as $attr ) {
				if ( $attr['name'] === $name ) {
					return $attr;
				}
			}
			return array();
		}

		public function test_pizza_payload_shape(): void {
			$p       = \Lafka_Test_Catalog::pizza();
			$payload = \lafka_chooser_payload( $p );

			$this->assertSame( 7, $payload['id'] );
			$this->assertSame( 'Classic Combo', $payload['name'] );
			$this->assertTrue( $payload['addable'] );
			$this->assertSame( '', $payload['reason'] );
			$this->assertSame( 'attribute_pa_size', $payload['primary'] );
			$this->assertCount( 10, $payload['variations'] );

			$size = self::attr( $payload, 'pa_size' );
			$this->assertTrue( $size['primary'] );
			$this->assertSame( 'Size', $size['label'] );
			$this->assertSame( array( 'small', 'medium', 'large', 'xlarge' ), array_column( $size['options'], 'value' ) );

			$crust = self::attr( $payload, 'pa_crust' );
			$this->assertFalse( $crust['primary'] );
			$this->assertSame( 'regular', $crust['default'], 'no product default -> the first term' );
			$this->assertSame( array( 'Regular', 'Thin', 'Gluten-free' ), array_column( $crust['options'], 'label' ) );

			$first = $payload['variations'][0];
			$this->assertArrayHasKey( 'attributes', $first );
			$this->assertArrayHasKey( 'price', $first );
			$this->assertArrayHasKey( 'price_text', $first );
		}

		public function test_secondary_default_comes_from_the_product_default(): void {
			$p                                   = \Lafka_Test_Catalog::pizza();
			$p->data['default_attributes']       = array( 'pa_crust' => 'thin' );
			$this->assertSame( 'thin', self::attr( \lafka_chooser_payload( $p ), 'pa_crust' )['default'] );
		}

		public function test_missing_size_for_the_chosen_crust_is_unavailable(): void {
			$p                             = \Lafka_Test_Catalog::pizza();
			$p->data['default_attributes'] = array( 'pa_crust' => 'gluten-free' );
			$size                          = self::attr( \lafka_chooser_payload( $p ), 'pa_size' );
			$available                     = array_combine( array_column( $size['options'], 'value' ), array_column( $size['options'], 'available' ) );
			$this->assertSame(
				array(
					'small'  => false,
					'medium' => true,
					'large'  => true,
					'xlarge' => false,
				),
				$available
			);
		}

		public function test_any_attribute_is_not_addable(): void {
			$p = new \WC_Product_Variable(
				array(
					'variation_attributes' => array(
						'pa_pieces'    => array( '10' ),
						'pa_seasoning' => array( 'hot' ),
					),
					'variations'           => array(
						31 => array(
							'price'      => 10.99,
							'attributes' => array(
								'attribute_pa_pieces'    => '10',
								'attribute_pa_seasoning' => '',
							),
						),
					),
				)
			);
			$payload = \lafka_chooser_payload( $p );
			$this->assertFalse( $payload['addable'] );
			$this->assertSame( 'any_attribute', $payload['reason'] );
		}

		public function test_required_addons_are_not_addable(): void {
			$p = \Lafka_Test_Catalog::pizza();
			$GLOBALS['lafka_test_required_addons'][7] = true;
			$payload = \lafka_chooser_payload( $p );
			$this->assertFalse( $payload['addable'] );
			$this->assertSame( 'required_addons', $payload['reason'] );
		}

		public function test_out_of_stock_is_not_addable(): void {
			$p                     = \Lafka_Test_Catalog::pizza();
			$p->data['in_stock']   = false;
			$payload               = \lafka_chooser_payload( $p );
			$this->assertFalse( $payload['addable'] );
			$this->assertSame( 'unavailable', $payload['reason'] );

			$simple = \lafka_chooser_payload( new \WC_Product( array( 'purchasable' => false ) ) );
			$this->assertFalse( $simple['addable'] );
		}

		public function test_simple_product_is_directly_addable(): void {
			$payload = \lafka_chooser_payload( new \WC_Product() );
			$this->assertTrue( $payload['addable'] );
			$this->assertSame( 'direct', $payload['mode'] );
			$this->assertSame( array(), $payload['variations'] );
		}

		public function test_registered_payloads_print_as_one_json_island(): void {
			\lafka_chooser_register( \Lafka_Test_Catalog::pizza() );
			\lafka_chooser_register( \Lafka_Test_Catalog::pizza() ); // deduped by id.
			ob_start();
			\lafka_chooser_print_data();
			$html = (string) ob_get_clean();
			$this->assertStringContainsString( '<script type="application/json" id="lafka-chooser-data">', $html );
			preg_match( '#<script type="application/json" id="lafka-chooser-data">(.*?)</script>#s', $html, $m );
			$data = json_decode( $m[1], true );
			$this->assertSame( array( '7' ), array_map( 'strval', array_keys( $data ) ) );
			$this->assertStringNotContainsString( '</script><', substr( $m[1], 0, -1 ), 'JSON is script-safe' );
			\lafka_chooser_reset();
		}
	}
}
