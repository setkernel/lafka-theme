<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PdpEnqueueTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_pdp_enqueue_gated_on_redesign_flag(): void {
		$this->assertStringContainsString(
			'lafka_pdp_redesign_enabled()',
			$this->src,
			'PDP enqueue must be gated by lafka_pdp_redesign_enabled() so flag-off operators get the legacy template.'
		);
	}

	public function test_pdp_enqueue_registers_pdp_redesign_css(): void {
		$this->assertStringContainsString( "'lafka-pdp-redesign'", $this->src );
		$this->assertStringContainsString( 'styles/pdp-redesign.css', $this->src );
	}

	public function test_pdp_enqueue_registers_all_five_js_handles(): void {
		foreach ( array( 'lafka-order-method', 'lafka-cart-drawer', 'lafka-pdp-pickers', 'lafka-upsell-modal', 'lafka-pdp-addons' ) as $handle ) {
			$this->assertStringContainsString( "'{$handle}'", $this->src, "Missing enqueue for {$handle}" );
		}
	}

	public function test_currency_localizer_uses_wc_helpers(): void {
		$this->assertStringContainsString( 'get_woocommerce_currency_symbol', $this->src );
		$this->assertStringContainsString( 'wc_get_price_thousand_separator', $this->src );
		$this->assertStringContainsString( 'wc_get_price_decimal_separator', $this->src );
		$this->assertStringContainsString( 'wc_get_price_decimals', $this->src );
	}

	public function test_order_method_bar_hooked_on_wp_body_open(): void {
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]wp_body_open['\"]/",
			$this->src,
			'order-method-bar partial must include via wp_body_open.'
		);
	}

	public function test_cart_drawer_hooked_on_wp_footer(): void {
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]wp_footer['\"]/",
			$this->src,
			'cart-drawer partial must include via wp_footer.'
		);
	}

	public function test_disabled_body_class_added_when_flag_off(): void {
		$this->assertStringContainsString( 'lafka-pdp-disabled', $this->src );
	}

	public function test_label_localizer_pulls_from_restaurant_info_resolver(): void {
		$this->assertStringContainsString( 'lafka_get_restaurant_info', $this->src );
		$this->assertStringContainsString( 'lafkaOrderMethodLabels', $this->src );
	}

	public function test_pdp_assets_resolve_against_template_not_stylesheet(): void {
		// PDP enqueue must use get_template_directory() not get_stylesheet_directory()
		// so the parent's assets load even when a child theme is active. This is
		// the entire raison d'être of A3 — without this assertion a future careless
		// copy-paste from child could silently break parent-only operators.
		$this->assertStringContainsString( 'get_template_directory_uri()', $this->src );
		$this->assertStringNotContainsString(
			'get_stylesheet_directory_uri()',
			$this->src,
			'Parent theme PDP enqueue must not use get_stylesheet_directory_uri() — that resolves to active child theme and breaks parent-only operators.'
		);
		$this->assertStringNotContainsString(
			'get_stylesheet_directory()',
			$this->src,
			'Parent theme PDP enqueue must not use get_stylesheet_directory() — that resolves to active child theme and breaks parent-only operators.'
		);
	}
}
