<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * lafka-plugin prints a plain-text "enter your street address to see the
 * delivery cost" notice in the cart/checkout totals (classic:
 * tr.lafka-delivery-quote-notice > td > p.lafka-delivery-quote-notice__text;
 * block: p.lafka-block-delivery-quote-notice). Plugin = markup, theme = look:
 * the theme styles it as a muted info note in the sheets that load on those
 * pages, from preset-aware --lafka-* tokens only (readable in dark presets).
 */
final class DeliveryQuoteNoticeCssTest extends TestCase {

	/**
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function provide_notices(): array {
		return array(
			// Loads on cart + checkout + order-received (classic).
			'classic totals row' => array( 'styles/lafka-checkout-handoff.css', '.lafka-delivery-quote-notice__text' ),
			// Loads on block cart/checkout pages.
			'block notice'       => array( 'styles/lafka-blocks-checkout.css', '.lafka-block-delivery-quote-notice' ),
		);
	}

	#[DataProvider( 'provide_notices' )]
	public function test_notice_is_styled_from_tokens( string $file, string $selector ): void {
		$css   = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $file ) );
		$block = '';
		if ( preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $rule ) {
				if ( in_array( $selector, array_map( 'trim', explode( ',', $rule[1] ) ), true ) ) {
					$block .= $rule[2];
				}
			}
		}

		$this->assertNotSame( '', $block, "{$file} must style {$selector}." );
		$this->assertMatchesRegularExpression( '/(?:^|;)\s*color:\s*var\(--lafka-/', $block, 'Text colour must come from a --lafka-* token.' );
		$this->assertMatchesRegularExpression( '/background(?:-color)?:\s*var\(--lafka-/', $block, 'Background must come from a --lafka-* token.' );
		$this->assertDoesNotMatchRegularExpression( '/#[0-9a-fA-F]{3,8}\b/', $block, 'No hex literals — preset-aware tokens only.' );
		$this->assertStringContainsString( 'overflow-wrap', $block, 'Must wrap cleanly at 375px.' );
	}
}
