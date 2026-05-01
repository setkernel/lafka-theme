<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductListingsCustomizerTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-product-listings.php' );
	}

	public function test_customizer_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/incl/customizer-product-listings.php' );
	}

	public function test_registers_fallback_image_setting(): void {
		$this->assertStringContainsString( "'lafka_product_card_fallback_image_id'", $this->src );
	}

	public function test_setting_uses_absint_sanitization(): void {
		$this->assertStringContainsString( "'sanitize_callback' => 'absint'", $this->src );
	}

	public function test_uses_wp_customize_cropped_or_image_control(): void {
		$this->assertMatchesRegularExpression(
			'/WP_Customize_(Cropped_)?Image_Control/',
			$this->src,
			'must use WP_Customize_Image_Control or _Cropped_Image_Control for image picker UX'
		);
	}

	public function test_hooks_into_customize_register(): void {
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]customize_register['\"]/",
			$this->src
		);
	}

	public function test_default_is_zero(): void {
		$this->assertStringContainsString( "'default' => 0", $this->src );
	}

	public function test_functions_php_requires_customizer(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			"/require(_once)?\s+.*customizer-product-listings\.php/",
			$src,
			'functions.php must require customizer-product-listings.php.'
		);
	}
}
