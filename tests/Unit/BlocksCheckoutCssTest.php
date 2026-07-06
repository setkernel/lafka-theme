<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX1-04b: styles/lafka-blocks-checkout.css skins WooCommerce's block Cart +
 * Checkout and the plugin's lafka- block components to the Peppery handoff.
 *
 * Locks the two things that silently rot:
 *  - TOKEN DISCIPLINE — every colour/size value is a --lafka-* token; NO hex
 *    literals (HardcodedColorTokenTest-style), so the sheet can never desync
 *    from lafka-tokens.css / the a11y contrast pairs.
 *  - LOAD-BEARING SELECTORS — the WooCommerce block classes + lafka- component
 *    classes the skin targets must stay present, so a rename doesn't silently
 *    drop the styling.
 */
final class BlocksCheckoutCssTest extends TestCase {

	private string $css;

	protected function setUp(): void {
		parent::setUp();
		$this->css = (string) file_get_contents(
			dirname( __DIR__, 2 ) . '/styles/lafka-blocks-checkout.css'
		);
	}

	public function test_css_file_exists(): void {
		$this->assertFileExists(
			dirname( __DIR__, 2 ) . '/styles/lafka-blocks-checkout.css'
		);
	}

	/**
	 * No hex colour literals — the sheet must read colour exclusively from
	 * --lafka-* tokens. Comments are stripped first so prose can't trip the scan.
	 */
	public function test_uses_only_lafka_tokens_no_hex_literals(): void {
		$without_comments = (string) preg_replace( '#/\*.*?\*/#s', '', $this->css );
		$this->assertDoesNotMatchRegularExpression(
			'/#[0-9a-fA-F]{3,8}\b/',
			$without_comments,
			'lafka-blocks-checkout.css must not contain hex colour literals — use --lafka-* tokens only.'
		);
		// Positive signal: it actually reads from the token namespace.
		$this->assertStringContainsString(
			'var(--lafka-',
			$without_comments,
			'lafka-blocks-checkout.css must consume --lafka-* tokens.'
		);
	}

	/**
	 * The accent CTA (Proceed to Checkout / Place Order) must ride the accent
	 * fill + display type + pill radius, per the handoff (CTAs only).
	 */
	public function test_accent_cta_uses_accent_and_display_tokens(): void {
		foreach ( array(
			'.wc-block-cart__submit-button',
			'.wc-block-components-checkout-place-order-button',
		) as $sel ) {
			$this->assertStringContainsString(
				$sel,
				$this->css,
				"Missing CTA selector {$sel}"
			);
		}
		$this->assertStringContainsString(
			'var(--lafka-color-accent-500)',
			$this->css,
			'CTA fill must use the accent token.'
		);
		$this->assertStringContainsString(
			'var(--lafka-radius-pill)',
			$this->css,
			'CTA must use the pill radius token.'
		);
	}

	/**
	 * The load-bearing WooCommerce block + lafka- component selectors the skin
	 * targets must stay present.
	 */
	public function test_defines_load_bearing_selectors(): void {
		foreach ( array(
			// WooCommerce block structure.
			'.wp-block-woocommerce-cart',
			'.wp-block-woocommerce-checkout',
			'.wc-block-cart-items__row',
			'.wc-block-components-totals-item',
			'.wc-block-components-checkout-step__title',
			'.wc-block-components-text-input',
			'.wc-block-components-radio-control__option',
			// Addon item_data lines (NX1-04c).
			'.wc-block-components-product-details',
			// lafka block components.
			'.lafka-block-free-delivery',
			'.lafka-block-timeslot',
			// lafka order-type select (Additional Checkout Fields API).
			'lafka-order-type',
		) as $sel ) {
			$this->assertStringContainsString(
				$sel,
				$this->css,
				"Missing load-bearing selector {$sel}"
			);
		}
	}

	/**
	 * The block form fields must match .lafka-input's sunken → ink-focus
	 * treatment (surface-sunken rest, shadow-focus ring).
	 */
	public function test_inputs_match_lafka_input_treatment(): void {
		$this->assertStringContainsString(
			'var(--lafka-color-surface-sunken)',
			$this->css,
			'Block inputs must use the sunken field background token.'
		);
		$this->assertStringContainsString(
			'var(--lafka-shadow-focus)',
			$this->css,
			'Block inputs must carry the shared focus ring token.'
		);
	}

	/**
	 * WC's primary block buttons (cart submit, place order) are ANCHOR tags, so
	 * the theme's global link styles (accent-text colour + underline) bleed into
	 * them unless the skin resets anchor inheritance. Caught live 2026-07-06:
	 * the cart's "Proceed to Checkout" rendered dark-red struck-through text on
	 * the accent background.
	 */
	public function test_primary_block_buttons_reset_anchor_inheritance(): void {
		$button_reset = $this->extract_rule_blocks( '.wc-block-components-button' );
		$this->assertStringContainsString(
			'text-decoration: none',
			$button_reset,
			'All WC block buttons must reset the theme link underline.'
		);

		$cta_block = $this->extract_rule_blocks( '.wc-block-cart__submit-button' );
		$this->assertStringContainsString(
			'text-decoration: none',
			$cta_block,
			'The accent CTAs must reset the theme link underline.'
		);
		$this->assertStringContainsString(
			'var(--lafka-color-accent-contrast',
			$cta_block,
			'Accent CTA text must use the on-accent contrast token, not the link colour.'
		);
	}

	/**
	 * All declaration blocks whose selector list mentions the given selector,
	 * concatenated.
	 */
	private function extract_rule_blocks( string $selector ): string {
		$out = '';
		if ( preg_match_all( '/[^{}]*\{[^}]*\}/', $this->css, $matches ) ) {
			foreach ( $matches[0] as $rule ) {
				if ( str_contains( $rule, $selector ) ) {
					$out .= $rule . "\n";
				}
			}
		}
		return $out;
	}
}
