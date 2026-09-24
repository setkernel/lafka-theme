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
