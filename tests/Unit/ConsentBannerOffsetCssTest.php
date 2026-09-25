<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Fixed-bottom UI must sit ABOVE the plugin's cookie-consent banner.
 *
 * lafka-plugin's consent banner is fixed to the viewport bottom (z-index
 * 99998). On mobile product pages it covered the theme's sticky "Add to cart"
 * bar and the sticky cart bar — the primary CTA was unreachable until the
 * visitor dealt with the banner.
 *
 * Contract with the plugin: while the banner is visible it sets
 * `--lafka-consent-banner-h` on <html> to the banner's rendered height (e.g.
 * "148px") and adds `html.lafka-consent-open`; on dismiss it resets the
 * property to "0px" and drops the class. Every theme fixed-bottom bar / toast
 * therefore offsets its bottom edge by `var(--lafka-consent-banner-h, 0px)` —
 * the 0px fallback keeps the layout identical with older plugin versions or no
 * banner at all.
 *
 * The browser-level proof (bar's bottom <= banner's top at 375px) is
 * tests/e2e/consent-banner-sticky-bars.spec.js.
 */
final class ConsentBannerOffsetCssTest extends TestCase {

	/**
	 * The banner-height custom property with a zero fallback — `0` for a bare
	 * var() (stylelint length-zero-no-unit), `0px` inside calc() (where a
	 * unitless 0 would invalidate the sum).
	 */
	private const VAR = '/var\(--lafka-consent-banner-h,\s*0(px)?\)/';

	/**
	 * Bottom-anchored fixed rules that deliberately do NOT follow the banner.
	 * "file|selector" => reason.
	 */
	private const EXEMPT = array(
		'style.css|html.no-touch #footer.lafka_do_reveal' => 'desktop-only (no-touch) reveal footer that sits BEHIND page content, not an overlay.',
	);

	/**
	 * Every rule (selector => declarations) in a first-party stylesheet,
	 * innermost blocks only (so rules nested in @media are included).
	 *
	 * @return list<array{selectors:list<string>, decls:array<string,list<string>>}>
	 */
	private static function rules( string $relative ): array {
		$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $relative );
		$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
		preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER );

		$rules = array();
		foreach ( $m as $match ) {
			$decls = array();
			foreach ( explode( ';', $match[2] ) as $decl ) {
				$parts = explode( ':', $decl, 2 );
				if ( 2 === count( $parts ) ) {
					$decls[ strtolower( trim( $parts[0] ) ) ][] = trim( $parts[1] );
				}
			}
			$rules[] = array(
				'selectors' => array_map( 'trim', explode( ',', trim( $match[1] ) ) ),
				'decls'     => $decls,
			);
		}
		return $rules;
	}

	/**
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function provide_bottom_bars(): array {
		return array(
			'sticky cart bar'            => array( 'styles/lafka-sticky-cart.css', '.lafka-sticky-cart' ),
			'PDP sticky CTA (tablet)'    => array( 'styles/lafka-pdp-cta.css', '.lafka-pdp-cta' ),
			'PDP mobile add-to-cart bar' => array( 'styles/pdp-redesign.css', '.lafka-pdp-mobile-cta' ),
			'exit-intent toast'          => array( 'styles/lafka-exit-intent.css', '.lafka-exit-toast' ),
			'review banner'              => array( 'styles/lafka-review-banner.css', '.lafka-review-banner' ),
			'push prompt'                => array( 'styles/lafka-push-prompt.css', '.lafka-push-prompt' ),
			'counter mobile bar (GX4)'   => array( 'styles/lafka-counter.css', '.lafka-counter-bar' ),
		);
	}

	#[DataProvider( 'provide_bottom_bars' )]
	public function test_every_bottom_offset_of_the_bar_follows_the_consent_banner( string $file, string $selector ): void {
		$offsets = array();
		foreach ( self::rules( $file ) as $rule ) {
			if ( ! in_array( $selector, $rule['selectors'], true ) ) {
				continue;
			}
			foreach ( array( 'bottom', 'inset' ) as $prop ) {
				foreach ( $rule['decls'][ $prop ] ?? array() as $value ) {
					$offsets[] = $prop . ': ' . $value;
				}
			}
		}

		$this->assertNotEmpty( $offsets, "{$selector} in {$file} must declare its bottom anchor." );
		foreach ( $offsets as $offset ) {
			$this->assertMatchesRegularExpression(
				self::VAR,
				$offset,
				"{$selector} ({$file}) '{$offset}' must lift above the consent banner via " . self::VAR . '.'
			);
		}
	}

	public function test_bars_keep_the_safe_area_inset(): void {
		foreach ( array(
			array( 'styles/lafka-sticky-cart.css', '.lafka-sticky-cart' ),
			array( 'styles/lafka-pdp-cta.css', '.lafka-pdp-cta' ),
			array( 'styles/pdp-redesign.css', '.lafka-pdp-mobile-cta' ),
			array( 'styles/lafka-counter.css', '.lafka-counter-bar' ),
		) as list( $file, $selector ) ) {
			$padding = '';
			foreach ( self::rules( $file ) as $rule ) {
				if ( in_array( $selector, $rule['selectors'], true ) ) {
					$padding .= implode( ' ', $rule['decls']['padding-bottom'] ?? array() ) . ' ' . implode( ' ', $rule['decls']['padding'] ?? array() );
				}
			}
			$this->assertStringContainsString( 'safe-area-inset-bottom', $padding, "{$selector} must keep its iOS safe-area padding." );
		}
	}

	/**
	 * Any NEW fixed, bottom-anchored rule in a first-party stylesheet must
	 * follow the banner too (or be explicitly exempted above with a reason).
	 */
	public function test_no_fixed_bottom_rule_ignores_the_consent_banner(): void {
		$root  = dirname( __DIR__, 2 );
		$files = array_merge( array( $root . '/style.css' ), glob( $root . '/styles/*.css' ) ?: array() );
		$hits  = array();
		foreach ( $files as $path ) {
			if ( str_ends_with( $path, '.min.css' ) ) {
				continue;
			}
			$relative = ltrim( substr( $path, strlen( $root ) ), '/' );
			foreach ( self::rules( $relative ) as $rule ) {
				$d = $rule['decls'];
				if ( ! in_array( 'fixed', $d['position'] ?? array(), true ) ) {
					continue;
				}
				$bottom = $d['bottom'] ?? array();
				$inset  = $d['inset'] ?? array();
				$top    = array_diff( $d['top'] ?? array(), array( 'auto' ) );
				// Bottom-anchored = a bottom edge with no top edge, or an inset
				// whose top component is `auto`. Full overlays (inset: 0,
				// top + bottom) cover the banner by design.
				$anchored = ( $bottom && ! $top )
					|| ( $inset && str_starts_with( $inset[0], 'auto ' ) );
				if ( ! $anchored ) {
					continue;
				}
				$selector = implode( ', ', $rule['selectors'] );
				if ( isset( self::EXEMPT[ $relative . '|' . $selector ] ) ) {
					continue;
				}
				if ( ! preg_match( self::VAR, implode( ' ', array_merge( $bottom, $inset ) ) ) ) {
					$hits[] = "{$relative}: {$selector}";
				}
			}
		}

		$this->assertSame( array(), $hits, 'Fixed-bottom UI must offset by ' . self::VAR . ' so the consent banner never covers it.' );
	}
}
