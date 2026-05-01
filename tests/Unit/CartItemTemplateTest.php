<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CartItemTemplateTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/woocommerce/cart/cart.php' );
	}

	public function test_template_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/woocommerce/cart/cart.php' );
	}

	public function test_template_uses_lafka_cart_class_on_outer_ul(): void {
		$this->assertMatchesRegularExpression(
			'/<ul\s+class="lafka-cart">/',
			$this->src,
			'outer wrapper must be <ul class="lafka-cart">'
		);
	}

	public function test_template_uses_li_lafka_cart_item_for_rows(): void {
		// Each cart row is <li class="lafka-cart-item ..."> — NOT WC's <tr class="cart_item">.
		$this->assertStringContainsString( 'lafka-cart-item', $this->src );
		$this->assertStringNotContainsString( '<tr class="cart_item">', $this->src );
		$this->assertStringNotContainsString( '<table', $this->src );
	}

	public function test_template_preserves_wc_outer_hooks(): void {
		// All cart-level hooks must fire so 3rd-party extensions work.
		foreach ( array(
			'woocommerce_before_cart',
			'woocommerce_before_cart_table',
			'woocommerce_before_cart_contents',
			'woocommerce_cart_contents',
			'woocommerce_after_cart_contents',
			'woocommerce_after_cart_table',
			'woocommerce_before_cart_collaterals',
			'woocommerce_cart_collaterals',
			'woocommerce_after_cart',
		) as $hook ) {
			$this->assertStringContainsString( $hook, $this->src, "Missing do_action for {$hook}" );
		}
	}

	public function test_template_preserves_wc_per_item_filters(): void {
		// Per-item filters that 3rd-party plugins (wishlist, addons, etc.) hook into.
		foreach ( array(
			'woocommerce_cart_item_class',
			'woocommerce_cart_item_thumbnail',
			'woocommerce_cart_item_name',
			'woocommerce_cart_item_subtotal',
			'woocommerce_cart_item_quantity',
			'woocommerce_cart_item_remove_link',
			'woocommerce_cart_item_visible',
			'woocommerce_cart_item_permalink',
			'woocommerce_cart_item_product',
			'woocommerce_cart_item_product_id',
		) as $filter ) {
			$this->assertStringContainsString( $filter, $this->src, "Missing apply_filters for {$filter}" );
		}
	}

	public function test_template_uses_wc_get_formatted_cart_item_data_for_meta(): void {
		// Variations + add-on data are surfaced via wc_get_formatted_cart_item_data().
		$this->assertStringContainsString( 'wc_get_formatted_cart_item_data', $this->src );
	}

	public function test_template_uses_woocommerce_quantity_input_for_qty(): void {
		// The quantity stepper must use WC's helper (respects min/max/step config).
		$this->assertStringContainsString( 'woocommerce_quantity_input', $this->src );
	}

	public function test_template_handles_sold_individually_products(): void {
		// Products with sold_individually=true get a hidden qty=1 input, not a stepper.
		$this->assertStringContainsString( 'is_sold_individually', $this->src );
	}

	public function test_template_preserves_coupon_form_when_enabled(): void {
		// Coupon form must still render when wc_coupons_enabled() returns true.
		$this->assertStringContainsString( 'wc_coupons_enabled', $this->src );
		$this->assertStringContainsString( "name=\"coupon_code\"", $this->src );
	}

	public function test_template_preserves_update_cart_button(): void {
		// Update Cart button must still render so users can adjust quantities.
		$this->assertStringContainsString( "name=\"update_cart\"", $this->src );
	}

	public function test_template_preserves_csrf_nonce(): void {
		// woocommerce-cart nonce protects update + remove + coupon actions.
		$this->assertStringContainsString( "wp_nonce_field( 'woocommerce-cart'", $this->src );
	}

	public function test_template_renders_cart_collaterals_block(): void {
		// Cart totals + cross-sells live inside .cart-collaterals.
		$this->assertStringContainsString( 'cart-collaterals', $this->src );
	}

	public function test_template_form_action_points_to_cart_url(): void {
		// The cart form posts back to wc_get_cart_url() for cart updates.
		$this->assertStringContainsString( 'wc_get_cart_url()', $this->src );
	}

	public function test_template_aria_label_on_remove_link(): void {
		// a11y: remove link must announce which product it removes.
		$this->assertMatchesRegularExpression(
			"/aria-label=\"%s\"|aria-label='%s'/",
			$this->src,
			'remove link must include aria-label sprintf %s placeholder for the product name'
		);
	}
}
