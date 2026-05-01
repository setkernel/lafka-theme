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
}
