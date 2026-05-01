<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CheckoutMapHideTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_css_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/styles/checkout-tweaks.css' );
	}

	public function test_css_has_display_none_rule(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/checkout-tweaks.css' );
		$this->assertMatchesRegularExpression(
			'/display:\s*none\s*!important/',
			$css,
			'must use display: none !important to override 3rd-party plugin inline styles'
		);
	}

	public function test_css_targets_address_autocomplete_map(): void {
		// Selector should target the address-field-autocomplete-for-woocommerce
		// 3rd-party plugin's map container. Recon on production checkout
		// (pepperypizzapoutine.com, plugin v1.3.1) showed the actual map div is
		// `#billing_address_map_map.address_map` (and the shipping equivalent).
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/checkout-tweaks.css' );
		$this->assertMatchesRegularExpression(
			'/\.address_map|address-field-autocomplete|wc-block-checkout.*map/',
			$css,
			'CSS must target the 3rd-party map container — discovered selector documented'
		);
	}

	public function test_css_includes_attribution_comment(): void {
		// The CSS must explain why this rule exists, naming the 3rd-party
		// plugin so future maintainers know what they're hiding.
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/checkout-tweaks.css' );
		$this->assertStringContainsString( 'address-field-autocomplete-for-woocommerce', $css );
	}

	public function test_enqueue_handle_present(): void {
		$this->assertStringContainsString( "'lafka-checkout-tweaks'", $this->src );
		$this->assertStringContainsString( 'styles/checkout-tweaks.css', $this->src );
	}

	public function test_enqueue_gated_to_checkout_page(): void {
		$this->assertMatchesRegularExpression(
			"/['\"]lafka-checkout-tweaks['\"][\s\S]{0,500}is_checkout\(\)|is_checkout\(\)[\s\S]{0,500}['\"]lafka-checkout-tweaks['\"]/",
			$this->src,
			'checkout-tweaks CSS enqueue must be gated to is_checkout()'
		);
	}

	public function test_enqueue_depends_on_lafka_style(): void {
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*['\"]lafka-checkout-tweaks['\"][\s\S]*?array\(\s*['\"]lafka-style['\"]/",
			$this->src,
			'checkout-tweaks CSS must depend on lafka-style for cascade order'
		);
	}
}
