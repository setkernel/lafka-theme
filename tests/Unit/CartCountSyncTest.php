<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * f011 regression lock: the header cart badge, sticky-cart count and drawer
 * count pill must refresh after an AJAX (quick-)add — not only on full reload.
 *
 * The plugin's woocommerce_add_to_cart_fragments filter only refreshes the
 * drawer item list + totals (ul.lafka-cart-drawer__items /
 * div.lafka-cart-drawer__total); it never touches the count nodes. So
 * js/cart-drawer.js owns keeping every [data-lafka-cart-count] /
 * [data-lafka-cart-count-pill] node in step with the live cart by recomputing
 * the count from the authoritative drawer item fragment on every cart change.
 */
final class CartCountSyncTest extends TestCase {

	private string $drawer_js;

	protected function setUp(): void {
		parent::setUp();
		$this->drawer_js = file_get_contents(
			dirname( __DIR__, 2 ) . '/js/cart-drawer.js'
		);
	}

	/**
	 * The sync handler must fire on the add/remove events AND on WC's fragment
	 * lifecycle events — the latter covers cross-page / session-restored carts.
	 *
	 * @return array<string,array{0:string}>
	 */
	public static function provide_cart_events(): array {
		return array(
			'added_to_cart'        => array( 'added_to_cart' ),
			'removed_from_cart'    => array( 'removed_from_cart' ),
			'wc_fragments_refreshed' => array( 'wc_fragments_refreshed' ),
			'wc_fragments_loaded'  => array( 'wc_fragments_loaded' ),
		);
	}

	#[DataProvider( 'provide_cart_events' )]
	public function test_sync_binds_to_cart_event( string $event ): void {
		$this->assertStringContainsString(
			$event,
			$this->drawer_js,
			"cart-drawer.js must refresh the cart count on the '$event' event."
		);
	}

	/**
	 * Every count node selector used by the three templates must be written to.
	 *
	 * @return array<string,array{0:string}>
	 */
	public static function provide_count_selectors(): array {
		return array(
			'header + sticky count' => array( 'data-lafka-cart-count' ),
			'drawer count pill'     => array( 'data-lafka-cart-count-pill' ),
		);
	}

	#[DataProvider( 'provide_count_selectors' )]
	public function test_sync_targets_count_node( string $selector ): void {
		$this->assertStringContainsString(
			$selector,
			$this->drawer_js,
			"cart-drawer.js must update [$selector] nodes so the count reflects the live cart."
		);
	}

	public function test_count_derived_from_drawer_item_fragment(): void {
		// The authoritative, always-present fragment is the drawer item list.
		$this->assertStringContainsString(
			'ul.lafka-cart-drawer__items',
			$this->drawer_js,
			'cart-drawer.js must read the live count from the ul.lafka-cart-drawer__items fragment.'
		);
		$this->assertStringContainsString(
			'lafka-cart-drawer__qty',
			$this->drawer_js,
			'cart-drawer.js must sum the per-item .lafka-cart-drawer__qty values.'
		);
	}

	/**
	 * Cross-check: the three templates still emit the data hooks the JS targets.
	 * If a template drops its hook, the sync silently no-ops on that surface.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function provide_template_hooks(): array {
		return array(
			'header badge'  => array( '/header.php', 'data-lafka-cart-count' ),
			'sticky count'  => array( '/template-parts/lafka-sticky-cart.php', 'data-lafka-cart-count' ),
			'drawer pill'   => array( '/partials/cart-drawer.php', 'data-lafka-cart-count-pill' ),
			'drawer items'  => array( '/partials/cart-drawer.php', 'lafka-cart-drawer__items' ),
		);
	}

	#[DataProvider( 'provide_template_hooks' )]
	public function test_template_emits_data_hook( string $relative_path, string $hook ): void {
		$contents = file_get_contents( dirname( __DIR__, 2 ) . $relative_path );
		$this->assertStringContainsString(
			$hook,
			$contents,
			"$relative_path must emit the $hook hook the cart-count sync targets."
		);
	}
}
