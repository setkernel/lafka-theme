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
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/pdp-selection.php';
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

		public function test_sold_out_size_is_not_offered(): void {
			$p = \Lafka_Test_Catalog::pizza();
			// Variation 702 = regular crust, medium; WC keeps it in the price cache.
			$GLOBALS['lafka_test_products'][702] = new \WC_Product(
				array(
					'id'       => 702,
					'in_stock' => false,
				)
			);
			$payload = \lafka_chooser_payload( $p );
			$size    = self::attr( $payload, 'pa_size' );
			$avail   = array_combine( array_column( $size['options'], 'value' ), array_column( $size['options'], 'available' ) );
			$this->assertFalse( $avail['medium'], 'a sold-out size is never offered as "Add to order"' );
			$this->assertTrue( $avail['small'] );
			$this->assertNotContains( 702, array_column( $payload['variations'], 'id' ) );
			$this->assertSame( 'columns', \lafka_price_columns( $p )['type'], 'the price columns still list every size' );
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

		public function test_a_single_variation_is_a_direct_add_of_that_variation(): void {
			$p = new \WC_Product_Variable(
				array(
					'id'                   => 31,
					'name'                 => 'Platter',
					'variation_attributes' => array( 'pa_size' => array( 'large' ) ),
					'variations'           => array(
						3101 => array(
							'price'      => 13.99,
							'attributes' => array( 'attribute_pa_size' => 'large' ),
						),
					),
				)
			);
			$payload = \lafka_chooser_payload( $p );
			$this->assertTrue( $payload['addable'] );
			$this->assertSame( 'direct', $payload['mode'], 'one size is not a choice (H-20)' );
			$this->assertSame( 3101, $payload['variation_id'] );
		}

		public function test_a_deal_with_add_on_groups_is_chosen_on_its_page(): void {
			require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
			$GLOBALS['lafka_test_terms']['product_cat'] = array(
				new \WP_Term( array( 'term_id' => 30, 'slug' => 'combos', 'name' => 'Combos', 'count' => 3 ) ),
				new \WP_Term( array( 'term_id' => 31, 'slug' => 'pizza', 'name' => 'Pizza', 'count' => 3, 'order' => 1 ) ),
			);
			\add_filter( 'lafka_product_has_addons', '__return_true' );
			$deal = new \WC_Product( array( 'id' => 70, 'category_ids' => array( 30 ) ) );
			$this->assertSame( 'deal_choices', \lafka_chooser_payload( $deal )['reason'], 'H-04: "Any 2 pizzas" with add-ons → Choose' );
			$pizza = new \WC_Product( array( 'id' => 71, 'category_ids' => array( 31 ) ) );
			$this->assertTrue( \lafka_chooser_payload( $pizza )['addable'], 'optional add-ons elsewhere keep one-tap Add' );
		}

		public function test_a_deal_without_add_ons_stays_a_one_tap_add(): void {
			require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
			$GLOBALS['lafka_test_terms']['product_cat'] = array(
				new \WP_Term( array( 'term_id' => 30, 'slug' => 'combos', 'name' => 'Combos', 'count' => 3 ) ),
			);
			$this->assertTrue( \lafka_chooser_payload( new \WC_Product( array( 'id' => 72, 'category_ids' => array( 30 ) ) ) )['addable'] );
		}

		public function test_lowercase_attribute_labels_are_capitalised(): void {
			$GLOBALS['lafka_test_attr_labels']['pa_size'] = 'size';
			$payload = \lafka_chooser_payload( \Lafka_Test_Catalog::pizza() );
			$this->assertSame( 'Size', self::attr( $payload, 'pa_size' )['label'] );
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
