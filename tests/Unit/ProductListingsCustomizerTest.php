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

	public function test_setting_uses_url_to_id_sanitizer(): void {
		// WP_Customize_Image_Control stores the URL string, not an ID. Using
		// 'absint' here would silently zero every save (absint of a URL → 0).
		// We use a custom sanitizer that resolves URL → attachment ID via
		// attachment_url_to_postid().
		$this->assertStringContainsString(
			"'sanitize_callback' => 'lafka_sanitize_attachment_id_from_url'",
			$this->src,
			'sanitize_callback must be the URL→ID resolver, NOT absint — image control stores URLs.'
		);
		$this->assertStringNotContainsString(
			"'sanitize_callback' => 'absint'",
			$this->src,
			'absint sanitizer would silently zero URL-string saves from WP_Customize_Image_Control.'
		);
	}

	public function test_url_to_id_sanitizer_is_defined(): void {
		// The custom sanitizer must be defined in this same file so it's
		// available when customize_register fires.
		$this->assertStringContainsString( 'function lafka_sanitize_attachment_id_from_url', $this->src );
		$this->assertStringContainsString( 'attachment_url_to_postid', $this->src );
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
