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

	public function test_template_preserves_csrf_nonce(): void {
		// woocommerce-cart nonce protects update + remove + coupon actions.
		$this->assertStringContainsString( "wp_nonce_field( 'woocommerce-cart'", $this->src );
	}

	public function test_template_aria_label_on_remove_link(): void {
		// a11y: remove link must announce which product it removes.
		$this->assertMatchesRegularExpression(
			"/aria-label=\"%s\"|aria-label='%s'/",
			$this->src,
			'remove link must include aria-label sprintf %s placeholder for the product name'
		);
	}

	public function test_template_fires_after_cart_item_name_action(): void {
		// Per-item action hooked by Subscriptions, WC Deposits, Product Add-Ons,
		// gift-wrap plugins. Must fire inside the body, after the title.
		$this->assertStringContainsString( 'woocommerce_after_cart_item_name', $this->src );
	}

	public function test_template_renders_backorder_notification(): void {
		// Backorder availability message must surface for backorderable products.
		// Filter contract preserved for plugins that customize the message.
		$this->assertStringContainsString( 'woocommerce_cart_item_backorder_notification', $this->src );
		$this->assertStringContainsString( 'backorders_require_notification', $this->src );
	}

	public function test_template_applies_cart_item_price_filter(): void {
		// Per-unit price filter for plugins that customize unit-price display.
		$this->assertStringContainsString( 'woocommerce_cart_item_price', $this->src );
	}

	public function test_template_remove_link_has_role_button(): void {
		// a11y: remove link triggers a state change (cart removal via GET);
		// role="button" signals action vs navigation to screen readers.
		$this->assertStringContainsString( 'role="button"', $this->src );
	}

	public function test_template_aria_label_uses_filtered_product_name(): void {
		// The aria-label on the remove link must use the filtered product name
		// (woocommerce_cart_item_name) so plugins that customize names are
		// announced to screen readers.
		$this->assertMatchesRegularExpression(
			"/wp_strip_all_tags\(\s*\\\$product_name\s*\)/",
			$this->src,
			'aria-label must use $product_name (filtered) not $_product->get_name() (raw)'
		);
	}
}
