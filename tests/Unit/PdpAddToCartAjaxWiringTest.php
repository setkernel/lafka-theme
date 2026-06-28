<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock (audit f044): the redesigned PDP Add-to-Cart CTAs must route
 * through the AJAX add path so the cart drawer auto-opens and the
 * `added_to_cart` event fires (sticky cart + trackers update).
 *
 * The redesigned CTAs are
 *   <button data-lafka-add-to-cart class="lafka-pdp-summary__cta">  (desktop)
 *   <button data-lafka-add-to-cart class="lafka-pdp-mobile-cta__btn"> (mobile)
 * and intentionally DROP the single_add_to_cart_button class (so
 * lafka-libs-config.js doesn't fight pdp-pickers.js over disabled state).
 * Before the fix:
 *   - lafka-front.js delegated AJAX add-to-cart ONLY off
 *     `.single_add_to_cart_button`, so the redesigned CTAs fell back to a
 *     native form POST (full reload, no drawer).
 *   - lafka-pdp-cta.js forwarded the sticky-footer tap to the now-absent
 *     `.single_add_to_cart_button`, hitting its native `form.submit()`
 *     fallback (again no AJAX, no drawer).
 *
 * This test locks both wirings against the JS source.
 */
final class PdpAddToCartAjaxWiringTest extends TestCase {

	private string $front;
	private string $cta;

	protected function setUp(): void {
		parent::setUp();
		$this->front = (string) file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-front.js' );
		$this->cta   = (string) file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-pdp-cta.js' );
	}

	public function test_front_delegated_handler_targets_redesigned_cta_attribute(): void {
		// The single delegated click handler must include the redesign's
		// [data-lafka-add-to-cart] selector alongside WC's stock class.
		$this->assertMatchesRegularExpression(
			'/\$\(document\)\.on\(\s*[\'"]click[\'"]\s*,\s*[\'"][^\'"]*\.single_add_to_cart_button[^\'"]*\[data-lafka-add-to-cart\][^\'"]*[\'"]/',
			$this->front,
			'lafka-front.js must delegate the AJAX add-to-cart click off both .single_add_to_cart_button AND [data-lafka-add-to-cart].'
		);
	}

	public function test_front_handler_still_serializes_the_cart_form(): void {
		// The AJAX request must post the whole form (variation_id, attribute_*,
		// quantity, addons, hidden add-to-cart) — i.e. it still serializes.
		$this->assertStringContainsString(
			'$add_to_cart_form.serialize()',
			$this->front,
			'lafka-front.js must serialize form.cart so the redesigned CTA posts all add-to-cart fields.'
		);
	}

	public function test_sticky_footer_forwards_to_redesigned_cta(): void {
		// lafka-pdp-cta.js must forward the sticky-footer tap to the redesign's
		// in-form CTA, not the absent stock button.
		$this->assertStringContainsString(
			"form.querySelector('[data-lafka-add-to-cart]')",
			$this->cta,
			'lafka-pdp-cta.js must forward the sticky-footer click to [data-lafka-add-to-cart].'
		);
	}

	public function test_sticky_footer_no_longer_targets_absent_stock_button(): void {
		// Guard against reintroducing the dead .single_add_to_cart_button
		// lookup, which silently fell through to the native form.submit().
		$this->assertStringNotContainsString(
			"form.querySelector('.single_add_to_cart_button')",
			$this->cta,
			'lafka-pdp-cta.js must not query the (now absent) .single_add_to_cart_button.'
		);
	}
}
