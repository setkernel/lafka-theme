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

	public function test_template_uses_lafka_product_card_class(): void {
		$this->assertStringContainsString( 'lafka-product-card', $this->tpl );
	}

	public function test_template_renders_image_via_helper(): void {
		$this->assertStringContainsString( 'lafka_product_card_image_html', $this->tpl );
	}

	public function test_template_wraps_card_in_single_anchor(): void {
		// Whole card is wrapped in one <a> linking to permalink — no nested anchors.
		$this->assertMatchesRegularExpression(
			"/<a\s+class=\"lafka-product-card__link\"\s+href=\"<\?php\s+the_permalink\(\);\s*\?>\"/",
			$this->tpl,
			'card must be wrapped in a single <a class="lafka-product-card__link">'
		);
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

	public function test_template_calls_price_explicitly_for_non_variable(): void {
		// Mirrors existing lafka behavior — variable products surface variation
		// pricing differently; non-variable get a direct price call.
		$this->assertStringContainsString( 'woocommerce_template_loop_price', $this->tpl );
		$this->assertStringContainsString( 'lafka_is_product_eligible_for_variation_in_listings', $this->tpl );
	}

	public function test_template_preserves_sale_countdown(): void {
		$this->assertStringContainsString( 'lafka_shop_sale_countdown', $this->tpl );
	}

	public function test_template_emits_short_description_when_present(): void {
		$this->assertStringContainsString( 'get_short_description', $this->tpl );
	}

	public function test_template_uses_wc_product_class_on_outer_li(): void {
		// wc_product_class adds onsale / out-of-stock / etc. classes critical for CSS.
		$this->assertStringContainsString( 'wc_product_class', $this->tpl );
	}

	public function test_template_aria_label_uses_product_name(): void {
		// a11y: the wrapping <a> must announce what it links to.
		$this->assertStringContainsString( 'aria-label="<?php echo esc_attr( $product->get_name() ); ?>"', $this->tpl );
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
}
