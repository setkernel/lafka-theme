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

	public function test_card_renders_quick_add_pill(): void {
		$this->assertStringContainsString( 'lafka_archive_quickadd_render', $this->card,
			'card must render the one-tap quick-add pill (Add / Choose).' );
	}

	public function test_quickadd_assets_load_on_menu_and_archive(): void {
		// Must include the custom /menu/ page (slug hierarchy) + the WC loops.
		$this->assertMatchesRegularExpression(
			"/is_page\(\s*'menu'\s*\)/",
			$this->core,
			"quick-add must enqueue on the /menu/ page (is_page('menu'))."
		);
		$this->assertStringContainsString( 'is_shop()', $this->core );
	}
}
