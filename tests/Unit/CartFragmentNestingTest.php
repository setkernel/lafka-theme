<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-A11Y-7 follow-up regression lock: WC cart-fragments AJAX must not
 * produce <li><li> nesting in the header cart-module.
 *
 * Background: lafka_cart_link() outputs <li class="lafka-cart-link-item">
 * <a id="lafka_quick_cart_link" class="cart-contents">…</a></li>. Earlier
 * versions registered the WC fragment with key 'a.cart-contents' and
 * captured the full <li>…</li> output via ob_start. WC then injected the
 * <li>…</li> string at the <a class="cart-contents"> position, producing
 * <li><li><a>…</a></li></li> on every cart fragment refresh. The fix keys
 * the fragment on the <li> itself (li.lafka-cart-link-item) so the
 * replacement target matches the wrapper that's actually being emitted.
 */
final class CartFragmentNestingTest extends TestCase {

	private string $woocommerce_functions;

	protected function setUp(): void {
		parent::setUp();
		$this->woocommerce_functions = file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/woocommerce-functions.php'
		);
	}

	public function test_lafka_cart_link_li_has_stable_class(): void {
		// The <li> wrapper must carry the lafka-cart-link-item class so the
		// fragment selector can match it.
		$this->assertMatchesRegularExpression(
			'/<li class="lafka-cart-link-item/',
			$this->woocommerce_functions,
			'lafka_cart_link() must emit <li class="lafka-cart-link-item ..."> for the fragment to target'
		);
	}

	public function test_fragment_keyed_on_li_not_anchor(): void {
		// If the fragment ever drifts back to keying on a.cart-contents,
		// nesting will return on every AJAX fragment refresh.
		$this->assertStringContainsString(
			"\$fragments['li.lafka-cart-link-item']",
			$this->woocommerce_functions,
			'Fragment must be keyed on li.lafka-cart-link-item — not on the inner <a>'
		);
		$this->assertStringNotContainsString(
			"\$fragments['a.cart-contents']",
			$this->woocommerce_functions,
			'Fragment must NOT be keyed on a.cart-contents (causes <li><li> nesting on AJAX refresh)'
		);
	}
}
