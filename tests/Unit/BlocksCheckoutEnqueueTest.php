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
}
