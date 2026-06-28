<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * f087 regression lock — theme-partial side, after the f099 SSOT refactor.
 *
 * f087: the cart-drawer line-item remove (×) control must be a WooCommerce-
 * native remove anchor — href = wc_get_cart_remove_url( key ) so it carries the
 * WC cart nonce + a no-JS fallback URL, class includes remove_from_cart_button
 * so WC core's add-to-cart.js binds the AJAX remove, data-cart_item_key carried
 * for that request, and an <a> (never the old dead <button>). Pre-fix the theme
 * partial rendered <button class="lafka-cart-drawer__remove" data-cart-key="…">×
 * </button>, which no JS anywhere bound and which lacked WC's anchor class — so
 * clicking × did nothing and a customer could not drop an accidental add from
 * the drawer without leaving the funnel for the full cart page.
 *
 * Since f099 (v6.14.0 SSOT) the per-item row — including that remove anchor — is
 * rendered by the plugin callable lafka_cart_drawer_render_item(), so the initial
 * server render (this theme partial) and the woocommerce_add_to_cart_fragments
 * AJAX refresh are byte-identical and can never desync. The anchor markup itself
 * therefore lives, and is locked, in the plugin
 * (lafka-plugin CartDrawerSsotTest::test_remove_control_is_wc_native_anchor,
 * which reads the file that owns the markup).
 *
 * This sibling lock guards the THEME partial's half of the f087 contract:
 *
 *   1. it delegates per-row rendering (and hence the remove control) to the SSOT
 *      callable rather than owning the markup;
 *   2. it never re-inlines a divergent copy of the WC-native remove anchor; and
 *   3. the dead, unbound <button class="lafka-cart-drawer__remove"> never returns.
 *
 * It deliberately reads ONLY the theme partial (never the sibling plugin file)
 * so it stays green in isolated CI — the cross-repo isolation trap.
 */
final class CartDrawerRemoveLinkTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents(
			dirname( __DIR__, 2 ) . '/partials/cart-drawer.php'
		);
	}

	/**
	 * The remove control reaches the drawer through the SSOT callable: the
	 * partial guards on lafka_cart_drawer_render_item(), loops the cart, and
	 * calls it per row, plus the no-arg empty-state call. If any of these go
	 * missing the remove (×) control disappears from the server-rendered drawer.
	 *
	 * @param string $needle Delegation code the partial must contain.
	 */
	#[DataProvider('provide_delegation_markers')]
	public function test_remove_control_delegated_to_ssot_callable( string $needle ): void {
		$this->assertStringContainsString(
			$needle,
			$this->src,
			'The cart-drawer remove (×) control is rendered by the plugin SSOT callable lafka_cart_drawer_render_item(); the theme partial must delegate to it, not own the markup.'
		);
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public static function provide_delegation_markers(): array {
		return array(
			'function_exists guard' => array( "function_exists( 'lafka_cart_drawer_render_item' )" ),
			'per-row call'          => array( 'lafka_cart_drawer_render_item( (string) $lafka_cart_item_key, $lafka_cart_item )' ),
			'empty-state call'      => array( 'lafka_cart_drawer_render_item();' ),
			'cart loop'             => array( 'foreach ( WC()->cart->get_cart() as $lafka_cart_item_key => $lafka_cart_item )' ),
		);
	}

	/**
	 * The partial must NOT re-inline its own copy of the WC-native remove anchor.
	 * That markup is the plugin SSOT's job; a second copy here could silently
	 * desync from the AJAX-refresh copy — the exact failure mode f099 fixed — so
	 * the cart-remove URL, the WC anchor class and the data-cart_item_key attr
	 * must appear only in the plugin, never inline in this partial.
	 *
	 * @param string $needle Inline remove-anchor markup that must live only in the plugin.
	 */
	#[DataProvider('provide_inline_remove_markup')]
	public function test_partial_does_not_inline_remove_anchor( string $needle ): void {
		$this->assertStringNotContainsString(
			$needle,
			$this->src,
			'The WC-native remove anchor markup belongs to the plugin SSOT callable; the theme partial must not carry a divergent inline copy.'
		);
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public static function provide_inline_remove_markup(): array {
		return array(
			'no inline cart-remove url' => array( 'wc_get_cart_remove_url' ),
			'no inline wc anchor class' => array( 'remove_from_cart_button' ),
			'no inline cart-key attr'   => array( 'data-cart_item_key=' ),
		);
	}

	/**
	 * The original f087 regression: the dead, unbound
	 * <button class="lafka-cart-drawer__remove"> that no JS ever bound must never
	 * return to the theme partial.
	 */
	public function test_dead_unbound_button_control_removed(): void {
		$this->assertDoesNotMatchRegularExpression(
			'/<button[^>]*lafka-cart-drawer__remove/',
			$this->src,
			'The dead, unbound <button class="lafka-cart-drawer__remove"> must not return.'
		);
	}
}
