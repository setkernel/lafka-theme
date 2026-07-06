<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX1-04b: the block Cart/Checkout skin (styles/lafka-blocks-checkout.css) must
 * be enqueued ONLY on a real WooCommerce block cart/checkout page AND only in
 * Lafka blocks mode — never on the classic shortcode path — and the theme's
 * blanket script `defer` must be suppressed there so the WooCommerce Blocks
 * runtime (wp- / wc- scripts carrying inline data) is not reordered into a wedge.
 *
 * These are source-scan assertions over incl/system/core-functions.php (the same
 * idiom as AssetEnqueueTest / CartItemCssTest) — the theme test harness runs
 * without a WordPress runtime.
 */
final class BlocksCheckoutEnqueueTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = (string) file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/system/core-functions.php'
		);
	}

	public function test_page_helper_exists(): void {
		$this->assertStringContainsString(
			'function lafka_is_block_cart_checkout_page',
			$this->src,
			'A lafka_is_block_cart_checkout_page() helper must gate the block skin + defer bail.'
		);
	}

	/**
	 * The helper must detect BOTH block money pages via has_block().
	 */
	public function test_helper_detects_both_block_pages(): void {
		$this->assertMatchesRegularExpression(
			"/has_block\(\s*'woocommerce\/checkout'\s*\)/",
			$this->src,
			'Helper must detect the woocommerce/checkout block.'
		);
		$this->assertMatchesRegularExpression(
			"/has_block\(\s*'woocommerce\/cart'\s*\)/",
			$this->src,
			'Helper must detect the woocommerce/cart block.'
		);
	}

	/**
	 * The helper must gate on Lafka blocks mode so classic mode (shim / shortcode
	 * pages) is NEVER treated as a block page — the "never in classic mode" half
	 * of the contract.
	 */
	public function test_helper_gates_on_blocks_mode(): void {
		// Isolate the helper body and assert the mode gate lives inside it.
		$start = strpos( $this->src, 'function lafka_is_block_cart_checkout_page' );
		$this->assertNotFalse( $start );
		$body = substr( $this->src, $start, 1200 );

		$this->assertStringContainsString(
			'Lafka_Checkout_Mode',
			$body,
			'Helper must consult the plugin checkout-mode SSOT.'
		);
		$this->assertMatchesRegularExpression(
			'/!\s*Lafka_Checkout_Mode::is_blocks\(\)/',
			$body,
			'Helper must return false when NOT in blocks mode (never style classic).'
		);
		$this->assertMatchesRegularExpression(
			"/class_exists\(\s*'Lafka_Checkout_Mode'\s*\)/",
			$body,
			'Mode gate must be guarded by class_exists so the plugin is optional.'
		);
	}

	/**
	 * The stylesheet enqueue must be gated by the helper and depend on lafka-tokens.
	 */
	public function test_blocks_css_enqueue_is_gated_and_depends_on_tokens(): void {
		$this->assertMatchesRegularExpression(
			"/if\s*\(\s*lafka_is_block_cart_checkout_page\(\)\s*\)\s*\{[\s\S]{0,400}?wp_enqueue_style\(\s*'lafka-blocks-checkout'/",
			$this->src,
			'lafka-blocks-checkout.css must be enqueued only inside the block-page gate.'
		);
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*'lafka-blocks-checkout',[\s\S]*?array\(\s*'lafka-tokens'\s*\)/",
			$this->src,
			'lafka-blocks-checkout.css must depend on lafka-tokens for the cascade.'
		);
		$this->assertStringContainsString(
			'styles/lafka-blocks-checkout.css',
			$this->src,
			'Enqueue must point at styles/lafka-blocks-checkout.css.'
		);
	}

	/**
	 * The blanket-defer filter must bail (return the tag unchanged) on a block
	 * cart/checkout page so the WooCommerce Blocks runtime keeps correct ordering.
	 */
	public function test_defer_filter_bails_on_block_cart_checkout(): void {
		$start = strpos( $this->src, 'function lafka_defer_non_critical_scripts' );
		$this->assertNotFalse( $start );
		$body = substr( $this->src, $start, 1600 );

		$this->assertMatchesRegularExpression(
			'/lafka_is_block_cart_checkout_page\(\)\s*\)\s*\{\s*return\s+\$tag;/',
			$body,
			'lafka_defer_non_critical_scripts must return $tag unchanged on a block cart/checkout page.'
		);
	}

	/**
	 * The block skin is CONDITIONAL: it must NOT be added to the always-on
	 * front-page asset budget (AssetBudgetTest::always_on_stylesheets()).
	 */
	public function test_blocks_css_is_not_in_the_always_on_budget(): void {
		$budget = (string) file_get_contents(
			dirname( __DIR__ ) . '/Unit/AssetBudgetTest.php'
		);
		$this->assertStringNotContainsString(
			'lafka-blocks-checkout.css',
			$budget,
			'The conditional block skin must never enter the always-on asset budget.'
		);
	}
}
