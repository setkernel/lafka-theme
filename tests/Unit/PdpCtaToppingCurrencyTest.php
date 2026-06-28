<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock (audit f073): the sticky PDP CTA live total must not derive
 * topping prices by scraping the formatted label text with a hardcoded '$'
 * regex.
 *
 * Before the fix, getToppingPrice() did
 *   label.textContent.match(/\$(\d+(?:\.\d+)?)/)
 * The dollar sign is hardcoded, but CONFIG.currencySymbol can be '€', 'kr',
 * '£', … (read from WooCommerce). On any non-USD shop the regex never matched
 * and the code fell back to a stale data-price, so the sticky CTA under-counted
 * toppings and showed a total lower than the real cart price (sticker shock at
 * checkout).
 *
 * The fix drives the topping total off the plugin's authoritative
 * #product-addons-total (its data-addons-price, already computed by addons.js
 * with the shop's currency, separators and per-attribute pricing), recomputing
 * on the plugin's `updated_addons` event, and — as a fallback — reads each
 * checked topping's numeric data-price / data-attribute-prices rather than the
 * formatted label.
 *
 * This test locks the behaviour against the JS source.
 */
final class PdpCtaToppingCurrencyTest extends TestCase {

	private string $cta;

	protected function setUp(): void {
		parent::setUp();
		$this->cta = (string) file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-pdp-cta.js' );
	}

	public function test_no_hardcoded_dollar_sign_price_regex(): void {
		// The hardcoded "$" currency regex must be gone — it silently fails on
		// every non-USD shop.
		$this->assertDoesNotMatchRegularExpression(
			'/match\(\s*\/\\\\\$\(/',
			$this->cta,
			'lafka-pdp-cta.js must not scrape topping prices with a hardcoded "$" regex.'
		);
	}

	public function test_does_not_scrape_label_text_for_price(): void {
		// No reading of the formatted label text to recover a price.
		$this->assertStringNotContainsString(
			'label.textContent.match',
			$this->cta,
			'lafka-pdp-cta.js must not parse the formatted topping label text for the price.'
		);
	}

	public function test_reads_authoritative_addons_total(): void {
		// Primary source of truth: the plugin's #product-addons-total element
		// and its data-addons-price (read through jQuery's data cache).
		$this->assertStringContainsString(
			"window.jQuery('#product-addons-total')",
			$this->cta,
			'lafka-pdp-cta.js must read the plugin-computed #product-addons-total.'
		);
		$this->assertStringContainsString(
			"\$totals.data('addons-price')",
			$this->cta,
			'lafka-pdp-cta.js must read the authoritative data-addons-price total.'
		);
	}

	public function test_recomputes_on_plugin_updated_addons_event(): void {
		// Listening for `updated_addons` (fired after addons.js' 300ms debounce)
		// both refreshes the total with correct per-attribute pricing and avoids
		// racing the debounce.
		$this->assertStringContainsString(
			'updated_addons',
			$this->cta,
			'lafka-pdp-cta.js must recompute on the plugin\'s updated_addons event.'
		);
	}

	public function test_fallback_reads_numeric_data_price_attribute(): void {
		// The fallback path reads the numeric data-price attribute rather than
		// formatted text, and prefers the per-attribute price matrix.
		$this->assertStringContainsString(
			"checkbox.getAttribute('data-price')",
			$this->cta,
			'lafka-pdp-cta.js fallback must read the numeric data-price attribute.'
		);
		$this->assertStringContainsString(
			'data-attribute-prices',
			$this->cta,
			'lafka-pdp-cta.js fallback must honour the per-attribute price matrix.'
		);
	}
}
