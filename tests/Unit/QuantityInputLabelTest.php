<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * O-43: woocommerce/global/quantity-input.php — on the cart the input is
 * named by its specific <label> ("Poutine quantity"); the generic
 * aria-label="Product quantity" (which overrides a label) is only printed
 * when WooCommerce passes no product name.
 */
final class QuantityInputLabelTest extends TestCase {

	private static function render( array $args ): string {
		$input_id    = 'quantity_1';
		$input_name  = 'cart[abc][qty]';
		$input_value = 2;
		$classes     = array( 'input-text', 'qty', 'text' );
		$type        = 'number';
		$readonly    = false;
		$min_value   = 0;
		$max_value   = -1;
		$step        = 1;
		$placeholder = '';
		$inputmode   = 'numeric';
		$autocomplete = 'off';

		ob_start();
		include dirname( __DIR__, 2 ) . '/woocommerce/global/quantity-input.php';
		return (string) ob_get_clean();
	}

	public function test_a_named_product_is_labelled_by_its_label_only(): void {
		$html = self::render( array( 'product_name' => 'Poutine' ) );

		$this->assertStringContainsString( '<label class="screen-reader-text" for="quantity_1">Poutine quantity</label>', $html );
		$this->assertStringNotContainsString( 'aria-label="Product quantity"', $html );
	}

	public function test_without_a_product_name_the_generic_name_stays(): void {
		$html = self::render( array() );

		$this->assertStringContainsString( 'aria-label="Product quantity"', $html );
	}
}
