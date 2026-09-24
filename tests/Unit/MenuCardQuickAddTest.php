<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Conversion: menu/archive card one-tap quick-add + select_item tracking.
 *
 * The /menu/ page and the WC archive both render woocommerce/loop/
 * lafka-product-card.php (NOT content-product.php), so the quick-add pill +
 * GA4 select_item contract must live there, and the quick-add assets must load
 * on /menu/ (the highest-traffic surface).
 */
final class MenuCardQuickAddTest extends TestCase {
	private string $card;
	private string $core;

	protected function setUp(): void {
		$root       = dirname( __DIR__, 2 );
		$this->card = file_get_contents( $root . '/woocommerce/loop/lafka-product-card.php' );
		$this->core = file_get_contents( $root . '/incl/system/core-functions.php' );
	}

	public function test_card_emits_select_item_attrs(): void {
		foreach ( array(
			'data-lafka-item-id',
			'data-lafka-item-name',
			'data-lafka-item-category',
			'data-lafka-item-price',
			'data-lafka-list-name',
		) as $attr ) {
			$this->assertStringContainsString( $attr, $this->card, "card link must emit $attr for select_item." );
		}
	}
}
