<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductCardLayoutTest extends TestCase {
	private string $tpl;
	private string $wc_fns;

	protected function setUp(): void {
		parent::setUp();
		$this->tpl    = file_get_contents( dirname( __DIR__, 2 ) . '/woocommerce/content-product.php' );
		$this->wc_fns = file_get_contents( dirname( __DIR__, 2 ) . '/incl/woocommerce-functions.php' );
	}

	public function test_template_preserves_wc_ecosystem_hooks(): void {
		// All 4 standard hooks must fire so third-party plugins still work.
		foreach ( array(
			'woocommerce_before_shop_loop_item',
			'woocommerce_before_shop_loop_item_title',
			'woocommerce_after_shop_loop_item_title',
			'woocommerce_after_shop_loop_item',
		) as $hook ) {
			$this->assertStringContainsString( $hook, $this->tpl, "Missing do_action for {$hook}" );
		}
	}

	public function test_loop_add_to_cart_removed_from_after_hook(): void {
		// The loop add-to-cart button is suppressed because the whole card
		// is the tap target → PDP. Operators can re-add via add_action() in
		// a child theme.
		$this->assertMatchesRegularExpression(
			"/remove_action\(\s*['\"]woocommerce_after_shop_loop_item['\"]\s*,\s*['\"]woocommerce_template_loop_add_to_cart['\"]\s*,\s*10/",
			$this->wc_fns,
			'woocommerce_template_loop_add_to_cart must be removed; whole-card is the tap target'
		);
	}

	public function test_legacy_lafka_shop_loop_image_filter_removed(): void {
		// lafka_shop_loop_image emits its own .image > a > img block on
		// woocommerce_before_shop_loop_item. The new content-product.php
		// renders the image directly via lafka_product_card_image_html(),
		// so the legacy callback must not be hooked (double thumbnails).
		$this->assertDoesNotMatchRegularExpression(
			"/add_(?:filter|action)\(\s*['\"]woocommerce_before_shop_loop_item['\"]\s*,\s*['\"]lafka_shop_loop_image['\"]/",
			$this->wc_fns,
			'lafka_shop_loop_image must not be hooked; the card renders the image directly.'
		);
	}
}
