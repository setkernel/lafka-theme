<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductCardImageHelperTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php' );
	}

	public function test_helper_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php' );
	}

	public function test_helper_function_defined(): void {
		$this->assertStringContainsString( 'function lafka_product_card_image_html', $this->src );
	}

	public function test_helper_tries_product_image_first(): void {
		$product_pos  = strpos( $this->src, 'get_image_id' );
		$customizer_pos = strpos( $this->src, 'lafka_product_card_fallback_image_id' );
		$this->assertNotFalse( $product_pos, 'helper must call $product->get_image_id()' );
		$this->assertNotFalse( $customizer_pos, 'helper must read the customizer fallback setting' );
		$this->assertLessThan( $customizer_pos, $product_pos, 'product image must be tried before customizer override' );
	}

	public function test_helper_falls_back_to_customizer_override(): void {
		$this->assertStringContainsString( "get_theme_mod( 'lafka_product_card_fallback_image_id'", $this->src );
	}

	public function test_helper_falls_back_to_bundled_svg(): void {
		$this->assertStringContainsString( 'assets/images/product-card-fallback.svg', $this->src );
		$this->assertStringContainsString( 'get_template_directory_uri()', $this->src );
	}

	public function test_helper_uses_wp_get_attachment_image_for_attachments(): void {
		$this->assertStringContainsString( 'wp_get_attachment_image', $this->src );
	}

	public function test_helper_emits_lazy_loading_attribute(): void {
		$this->assertStringContainsString( "'loading'  => 'lazy'", $this->src );
	}

	public function test_helper_emits_alt_text_from_product_name(): void {
		$this->assertStringContainsString( 'get_name()', $this->src );
	}

	public function test_bundled_svg_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/assets/images/product-card-fallback.svg' );
	}

	public function test_functions_php_requires_helper(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			"/require(_once)?\s+.*template-helpers\/product-card-image\.php/",
			$src,
			'functions.php must require the product-card-image helper.'
		);
	}

	public function test_helper_emits_decoding_async(): void {
		// Modern web-perf guidance: loading="lazy" should be paired with
		// decoding="async" to let the browser decode off the main thread.
		$this->assertStringContainsString( "'decoding' => 'async'", $this->src );
		$this->assertStringContainsString( 'decoding="async"', $this->src );
	}

	public function test_helper_guards_against_non_wc_product(): void {
		// Without this guard, passing null/false/non-WC_Product fatals with
		// "Call to a member function get_name() on null" — fatal in a product
		// loop blanks the entire shop page.
		$this->assertStringContainsString( "is_a( \$product, 'WC_Product' )", $this->src );
	}
}
