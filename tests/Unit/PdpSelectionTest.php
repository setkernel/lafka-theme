<?php
declare(strict_types=1);

/**
 * GX M-10 / M-11 / M-18: before the customer chooses, the PDP never shows a
 * variation nobody picked; single-option attributes and complete operator
 * defaults arrive preselected; the button prompt is translatable and names
 * the attribute ("Choose pieces"); simple products say "Add to order".
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/pdp-selection.php';

	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="woocommerce-Price-amount amount">' . $price . '</span>';
		}
	}
	if ( ! function_exists( 'wc_format_decimal' ) ) {
		function wc_format_decimal( $number, $dp = false ) {
			return false === $dp ? (string) $number : number_format( (float) $number, (int) $dp, '.', '' );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PdpSelectionTest extends TestCase {

		/** Fingers: small / medium / large (default attribute = large). */
		private function fingers( array $defaults = array() ): \WC_Product_Variable {
			\Lafka_Test_Catalog::attr_terms(
				'pa_size',
				array(
					'small'  => 'Small',
					'medium' => 'Medium',
					'large'  => 'Large',
				)
			);
			return new \WC_Product_Variable(
				array(
					'id'                   => 60,
					'name'                 => 'Garlic Fingers',
					'price'                => '10.50',
					'default_attributes'   => $defaults,
					'variation_attributes' => array( 'pa_size' => array( 'small', 'medium', 'large' ) ),
					'variations'           => array(
						6003 => array( 'price' => 22.50, 'attributes' => array( 'attribute_pa_size' => 'large' ) ),
						6001 => array( 'price' => 10.50, 'attributes' => array( 'attribute_pa_size' => 'small' ) ),
						6002 => array( 'price' => 16.50, 'attributes' => array( 'attribute_pa_size' => 'medium' ) ),
					),
				)
			);
		}

		public function test_no_default_means_from_min_and_nothing_preselected(): void {
			$p       = $this->fingers();
			$initial = \lafka_pdp_initial_selection( $p );

			$this->assertSame( array(), $initial['selection'] );
			$this->assertNull( $initial['variation'] );
			$html = \lafka_pdp_price_html( $p, $initial );
			$this->assertStringStartsWith( 'From ', $html );
			$this->assertMatchesRegularExpression( '#10\.50?\b#', $html, 'the lowest price, not the default Large' );
		}

		public function test_a_complete_default_preselects_and_prices_that_variation(): void {
			$p       = $this->fingers( array( 'pa_size' => 'large' ) );
			$initial = \lafka_pdp_initial_selection( $p );

			$this->assertSame( array( 'attribute_pa_size' => 'large' ), $initial['selection'] );
			$this->assertSame( 6003, $initial['variation']['id'] );
			$this->assertStringContainsString( '22.5', \lafka_pdp_price_html( $p, $initial ) );
		}

		public function test_a_half_default_on_a_pizza_is_not_applied(): void {
			$p       = \Lafka_Test_Catalog::pizza();
			$p->data['default_attributes'] = array( 'pa_crust' => 'thin' ); // No size default.
			$initial = \lafka_pdp_initial_selection( $p );

			$this->assertSame( array(), $initial['selection'], 'a partial default would price a variation nobody picked' );
			$this->assertStringStartsWith( 'From ', \lafka_pdp_price_html( $p, $initial ) );
		}

		public function test_a_single_option_attribute_is_preselected(): void {
			$p = new \WC_Product_Variable(
				array(
					'id'                   => 61,
					'name'                 => 'Platter',
					'variation_attributes' => array( 'pa_size' => array( 'large' ) ),
					'variations'           => array(
						6101 => array( 'price' => 13.99, 'attributes' => array( 'attribute_pa_size' => 'large' ) ),
					),
				)
			);
			$initial = \lafka_pdp_initial_selection( $p );
			$this->assertSame( array( 'attribute_pa_size' => 'large' ), $initial['selection'] );
			$this->assertSame( 6101, $initial['variation']['id'] );
		}

		public function test_same_price_everywhere_shows_one_price_not_from(): void {
			$p = new \WC_Product_Variable(
				array(
					'id'                   => 62,
					'name'                 => 'Fries',
					'variation_attributes' => array( 'pa_seasoning' => array( 'plain', 'seasoned' ) ),
					'variations'           => array(
						6201 => array( 'price' => 4.99, 'attributes' => array( 'attribute_pa_seasoning' => 'plain' ) ),
						6202 => array( 'price' => 4.99, 'attributes' => array( 'attribute_pa_seasoning' => 'seasoned' ) ),
					),
				)
			);
			$html = \lafka_pdp_price_html( $p, \lafka_pdp_initial_selection( $p ) );
			$this->assertStringNotContainsString( 'From', $html );
			$this->assertStringContainsString( '4.99', $html );
		}

		public function test_choose_prompt_names_the_attribute_and_is_filterable(): void {
			$this->assertSame( 'Choose pieces', \lafka_pdp_choose_label( 'Pieces', 'pa_pieces' ) );
			\add_filter( 'lafka_pdp_choose_label', static fn( $text, $name ) => 'pa_size' === $name ? 'Choose a size' : $text, 10, 2 );
			$this->assertSame( 'Choose a size', \lafka_pdp_choose_label( 'Size', 'pa_size' ) );
		}

		public function test_attribute_labels_only_capitalise_all_lowercase_names(): void {
			$this->assertSame( 'Size', \lafka_attribute_display_label( 'size' ) );
			$this->assertSame( 'pH level', \lafka_attribute_display_label( 'pH level' ) );
			$this->assertSame( 'Crust', \lafka_attribute_display_label( 'Crust' ) );
		}

		public function test_pickers_render_the_preselection_and_the_prompt(): void {
			$GLOBALS['lafka_test_taxonomies'] = array( 'pa_size' );
			$product           = $this->fingers( array( 'pa_size' => 'medium' ) );
			$lafka_pdp_initial = \lafka_pdp_initial_selection( $product );
			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/pdp-pickers.php';
			$html = (string) ob_get_clean();

			$this->assertMatchesRegularExpression( '#value="medium" checked>#', $html );
			$this->assertDoesNotMatchRegularExpression( '#value="large" checked#', $html );
			$this->assertStringContainsString( 'data-choose-label="Choose size"', $html );
			$this->assertStringContainsString( 'data-lafka-chip-na hidden', $html );
		}
	}
}
