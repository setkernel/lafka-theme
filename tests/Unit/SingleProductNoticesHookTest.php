<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock (audit f052): the redesigned PDP must fire
 * woocommerce_before_single_product so WC core's woocommerce_output_all_notices
 * (priority 10) prints add-to-cart validation errors, stock/coupon/login
 * messages and the 'redirect to product after add' success notice — and
 * woocommerce_after_single_product so third-party integrations on that hook run.
 *
 * Before the fix the redesign branch called get_header('shop') and rendered the
 * layout directly without ever firing either hook, so every PDP notice was
 * silently swallowed. The before hook must sit INSIDE .lafka-pdp__main above the
 * breadcrumb, otherwise notices print outside the styled .lafka-pdp wrapper.
 *
 * This is a source lock against woocommerce/single-product.php's redesign
 * branch (everything from the <div class="lafka-pdp"> marker onward).
 */
final class SingleProductNoticesHookTest extends TestCase {

	private string $src;

	private string $redesign;

	protected function setUp(): void {
		parent::setUp();
		$this->src = (string) file_get_contents(
			dirname( __DIR__, 2 ) . '/woocommerce/single-product.php'
		);

		// Isolate the redesign branch: everything from the .lafka-pdp wrapper on.
		// The legacy/flag-OFF branch returns before this marker, so anything
		// found here belongs to the default redesign path.
		$pos = strpos( $this->src, '<div class="lafka-pdp">' );
		$this->assertNotFalse(
			$pos,
			'single-product.php must contain the .lafka-pdp redesign wrapper.'
		);
		$this->redesign = substr( $this->src, $pos );
	}

	public function test_redesign_fires_before_single_product(): void {
		$this->assertStringContainsString(
			"do_action( 'woocommerce_before_single_product' )",
			$this->redesign,
			'Redesign branch must fire woocommerce_before_single_product so woocommerce_output_all_notices runs.'
		);
	}

	public function test_redesign_fires_after_single_product(): void {
		$this->assertStringContainsString(
			"do_action( 'woocommerce_after_single_product' )",
			$this->redesign,
			'Redesign branch must fire woocommerce_after_single_product so integrations on that hook run.'
		);
	}

	public function test_before_hook_is_inside_main_above_breadcrumb(): void {
		$main       = strpos( $this->redesign, 'lafka-pdp__main' );
		$before     = strpos( $this->redesign, "do_action( 'woocommerce_before_single_product' )" );
		$breadcrumb = strpos( $this->redesign, 'lafka-pdp__breadcrumb' );

		$this->assertNotFalse( $main );
		$this->assertNotFalse( $before );
		$this->assertNotFalse( $breadcrumb );

		// Inside the main wrapper, and above the breadcrumb, so notices render
		// within the styled .lafka-pdp layout rather than unstyled above it.
		$this->assertGreaterThan(
			$main,
			$before,
			'woocommerce_before_single_product must fire inside .lafka-pdp__main, not before the wrapper.'
		);
		$this->assertLessThan(
			$breadcrumb,
			$before,
			'woocommerce_before_single_product must fire above the breadcrumb so notices sit atop the layout.'
		);
	}

	public function test_after_hook_fires_before_get_footer(): void {
		$after  = strpos( $this->redesign, "do_action( 'woocommerce_after_single_product' )" );
		$footer = strpos( $this->redesign, "get_footer( 'shop' )" );

		$this->assertNotFalse( $after );
		$this->assertNotFalse( $footer );
		$this->assertLessThan(
			$footer,
			$after,
			'woocommerce_after_single_product must fire before get_footer( \'shop\' ).'
		);
	}
}
