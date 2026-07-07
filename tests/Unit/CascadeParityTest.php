<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * NX1-10a cascade-parity structural lock.
 *
 * BACKGROUND. The monolith teardown (NX1-10a) moved per-component rules out of
 * style.css into styles/legacy-*.css. Those sheets enqueue AFTER style.css
 * (dep 'lafka-style', incl/system/core-functions.php), so a moved declaration
 * that was OVERRIDDEN in the monolith by a LATER kept style.css rule (the
 * v5.68.0 "accent-color consolidation" groups, and friends) silently re-won
 * once relocated. The fix (scripts/nx1-10a-prune-dead.mjs) deleted those dead
 * declarations from the legacy sheets, restoring the monolith's effective
 * cascade. The empirical, rerunnable proof is scripts/nx1-10a-cascade-parity.mjs
 * (compares split-vs-monolith winners for every (media,selector,property)).
 *
 * THIS TEST is the committed, CI-visible, pure-PHP lock that stops the
 * dead-declaration class from being reintroduced. It parses style.css + the 4
 * legacy sheets and asserts two invariants:
 *
 *   (A) GENERAL — token-consolidation lock. Every declaration in style.css whose
 *       value is a design token `var(--lafka-*)` is (by the v5.68.0 architecture)
 *       an INTENDED site-wide winner. No (exact-selector, property) pair that a
 *       style.css token rule declares may ALSO be declared in any legacy sheet:
 *       if it were, the later-loading legacy copy would override the token and
 *       re-invert the cascade. This is the sanctioned narrowing of the ideal
 *       "no pair may exist in both a legacy sheet and a LATER kept style.css
 *       rule" invariant — original-source-position tracking needs the recovered
 *       monolith (git) + a full cascade resolver, which is out of scope for a
 *       pure-PHP unit test. The narrowing catches the whole token-consolidation
 *       regression class (10 of the verifier's 11 confirmed flips).
 *
 *   (B) NAMED — the verifier's 11 confirmed flips. Each (selector, property) that
 *       flipped must be ABSENT from the legacy sheets and PRESENT (as the
 *       monolith winner) in style.css. This backstops invariant (A) for the one
 *       flip whose winner is a literal, not a token (`.lafka-related-blog-posts
 *       > h4 { margin-bottom: 80px }`), so all 11 are locked by name regardless
 *       of the narrowing.
 *
 * Selector normalisation mirrors scripts/nx1-10a-css-lib.mjs: whitespace is
 * collapsed and the `>`, `+`, `~` combinators are canonically spaced, so
 * `.a>.b` and `.a > .b` compare equal across sheets.
 */
final class CascadeParityTest extends TestCase {

	/** @var array<string,array{selector:string,property:string,value:string,important:bool}>|null */
	private static ?array $styleRecords = null;

	/** The verifier's 11 confirmed flips: [ normalised-selector, property, winner-value ]. */
	private const CONFIRMED_FLIPS = array(
		array( '.tribe-events-schedule .tribe-events-cost', 'background-color', 'var(--lafka-accent-color)' ),
		array( '.blog-post-meta.post-meta-top .count_comments a', 'background-color', 'var(--lafka-accent-color)' ),
		array( 'div.widget_categories ul li a:hover', 'color', 'var(--lafka-link-color)' ),
		array( 'div.widget_archive ul li a:hover', 'color', 'var(--lafka-link-color)' ),
		array( '.lafka-foodmenu-categories ul li a.is-checked::before', 'background-color', 'var(--lafka-accent-color)' ),
		array( '.lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-next', 'color', 'var(--lafka-accent-color)' ),
		array( '.lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-prev', 'color', 'var(--lafka-accent-color)' ),
		array( '.foodmenu-unit-info a.foodmenu-lightbox-link', 'background-color', 'var(--lafka-accent-color)' ),
		array( '.blog-post-meta span.sticky_post', 'background-color', 'var(--lafka-accent-color)' ),
		array( '.lafka-related-blog-posts > h4', 'margin-bottom', '80px' ),
		array( '.lafka-related-blog-posts div.post.blog-post.lafka-post-no-image .lafka_post_data_holder h2.heading-title::before', 'color', 'var(--lafka-accent-color)' ),
	);

	private const LEGACY_SHEETS = array( 'legacy-blog', 'legacy-shortcodes', 'legacy-forum', 'legacy-events' );

	private static function root(): string {
		return dirname( __DIR__, 2 );
	}

	/**
	 * Parse the `@layer legacy { … }` body of a stylesheet into declaration
	 * records. String-aware brace matching (so content:"…{…}" never miscounts);
	 * @media/@supports blocks recurse one level with a media prefix; @font-face /
	 * @keyframes contribute nothing (no selector/property pairs).
	 *
	 * @return array<int,array{media:string,selector:string,property:string,value:string,important:bool}>
	 */
	private static function parseLegacyLayer( string $css ): array {
		$open = strpos( $css, '@layer legacy {' );
		if ( false === $open ) {
			return array();
		}
		$bodyStart = $open + strlen( '@layer legacy {' );
		$bodyEnd   = strrpos( $css, '}' );
		$body      = substr( $css, $bodyStart, $bodyEnd - $bodyStart );

		$out = array();
		self::walkRules( $body, '', $out );
		return $out;
	}

	/**
	 * @param array<int,array{media:string,selector:string,property:string,value:string,important:bool}> $out
	 */
	private static function walkRules( string $body, string $media, array &$out ): void {
		$i = 0;
		$n = strlen( $body );
		while ( $i < $n ) {
			$c = $body[ $i ];
			// Whitespace.
			if ( ctype_space( $c ) ) {
				$i++;
				continue;
			}
			// Comment.
			if ( '/' === $c && $i + 1 < $n && '*' === $body[ $i + 1 ] ) {
				$end = strpos( $body, '*/', $i + 2 );
				$i   = ( false === $end ) ? $n : $end + 2;
				continue;
			}
			// Read a prelude up to '{' or ';' (string-aware).
			$j   = $i;
			$str = null;
			while ( $j < $n ) {
				$ch = $body[ $j ];
				if ( null !== $str ) {
					if ( '\\' === $ch ) {
						$j += 2;
						continue;
					}
					if ( $ch === $str ) {
						$str = null;
					}
					$j++;
					continue;
				}
				if ( '"' === $ch || "'" === $ch ) {
					$str = $ch;
					$j++;
					continue;
				}
				if ( '{' === $ch || ';' === $ch ) {
					break;
				}
				$j++;
			}
			if ( $j >= $n ) {
				break;
			}
			if ( ';' === $body[ $j ] ) {
				// Statement without a block (stray @import etc.).
				$i = $j + 1;
				continue;
			}
			$prelude = substr( $body, $i, $j - $i );
			// Read the balanced { … } block (string-aware).
			$depth = 0;
			$k     = $j;
			$str   = null;
			for ( ; $k < $n; $k++ ) {
				$ch = $body[ $k ];
				if ( null !== $str ) {
					if ( '\\' === $ch ) {
						$k++;
						continue;
					}
					if ( $ch === $str ) {
						$str = null;
					}
					continue;
				}
				if ( '"' === $ch || "'" === $ch ) {
					$str = $ch;
					continue;
				}
				if ( '{' === $ch ) {
					$depth++;
				} elseif ( '}' === $ch ) {
					$depth--;
					if ( 0 === $depth ) {
						$k++;
						break;
					}
				}
			}
			$blockOpen = strpos( $body, '{', $j );
			$inner     = substr( $body, $blockOpen + 1, ( $k - 1 ) - ( $blockOpen + 1 ) );
			$preludeTrim = trim( $prelude );
			if ( '' !== $preludeTrim && '@' === $preludeTrim[0] ) {
				// At-rule. Recurse into @media/@supports; ignore @font-face/@keyframes.
				if ( 0 === stripos( $preludeTrim, '@media' ) || 0 === stripos( $preludeTrim, '@supports' ) ) {
					self::walkRules( $inner, self::normValue( $preludeTrim ), $out );
				}
			} else {
				$selectors = array_filter( array_map( 'trim', explode( ',', $prelude ) ), 'strlen' );
				$decls     = self::parseDeclarations( $inner );
				foreach ( $selectors as $sel ) {
					foreach ( $decls as $d ) {
						$out[] = array(
							'media'     => $media,
							'selector'  => self::normSelector( $sel ),
							'property'  => $d['property'],
							'value'     => $d['value'],
							'important' => $d['important'],
						);
					}
				}
			}
			$i = $k;
		}
	}

	/**
	 * @return array<int,array{property:string,value:string,important:bool}>
	 */
	private static function parseDeclarations( string $inner ): array {
		$decls = array();
		$n     = strlen( $inner );
		$str   = null;
		$paren = 0;
		$seg   = '';
		$flush = static function ( string $raw ) use ( &$decls ) {
			$raw = trim( $raw );
			if ( '' === $raw ) {
				return;
			}
			$colon = strpos( $raw, ':' );
			if ( false === $colon ) {
				return;
			}
			$property  = strtolower( trim( substr( $raw, 0, $colon ) ) );
			$value     = trim( substr( $raw, $colon + 1 ) );
			$important = (bool) preg_match( '/!\s*important\s*$/i', $value );
			$value     = trim( (string) preg_replace( '/!\s*important\s*$/i', '', $value ) );
			$decls[]   = array(
				'property'  => $property,
				'value'     => self::normValue( $value ),
				'important' => $important,
			);
		};
		for ( $i = 0; $i < $n; $i++ ) {
			$c = $inner[ $i ];
			if ( null !== $str ) {
				$seg .= $c;
				if ( '\\' === $c && $i + 1 < $n ) {
					$seg .= $inner[ ++$i ];
					continue;
				}
				if ( $c === $str ) {
					$str = null;
				}
				continue;
			}
			if ( '"' === $c || "'" === $c ) {
				$str  = $c;
				$seg .= $c;
				continue;
			}
			if ( '(' === $c ) {
				$paren++;
			} elseif ( ')' === $c ) {
				$paren--;
			}
			if ( ';' === $c && 0 === $paren ) {
				$flush( $seg );
				$seg = '';
				continue;
			}
			$seg .= $c;
		}
		$flush( $seg );
		return $decls;
	}

	private static function normSelector( string $sel ): string {
		$sel = (string) preg_replace( '/\s*([>+~])\s*/', ' $1 ', $sel );
		$sel = (string) preg_replace( '/\s+/', ' ', $sel );
		return trim( $sel );
	}

	private static function normValue( string $val ): string {
		$val = (string) preg_replace( '/\s+/', ' ', $val );
		return strtolower( trim( $val ) );
	}

	/** @return array<int,array{media:string,selector:string,property:string,value:string,important:bool}> */
	private static function styleRecords(): array {
		if ( null === self::$styleRecords ) {
			self::$styleRecords = self::parseLegacyLayer(
				(string) file_get_contents( self::root() . '/style.css' )
			);
		}
		return self::$styleRecords;
	}

	/** @return array<string,bool> set of "media|selector|property" keys present in the legacy sheets */
	private static function legacyKeySet(): array {
		$set = array();
		foreach ( self::LEGACY_SHEETS as $sheet ) {
			$css  = (string) file_get_contents( self::root() . '/styles/' . $sheet . '.css' );
			foreach ( self::parseLegacyLayer( $css ) as $r ) {
				$set[ $r['media'] . '|' . $r['selector'] . '|' . $r['property'] ] = true;
			}
		}
		return $set;
	}

	/**
	 * (A) Token-consolidation lock: no (selector,property) pair declared with a
	 * `var(--lafka-*)` token in style.css may also be declared in a legacy sheet.
	 */
	public function test_no_token_consolidation_pair_reappears_in_legacy_sheets(): void {
		$legacy = self::legacyKeySet();
		$hits   = array();
		foreach ( self::styleRecords() as $r ) {
			if ( ! str_starts_with( $r['value'], 'var(--lafka-' ) ) {
				continue;
			}
			$key = $r['media'] . '|' . $r['selector'] . '|' . $r['property'];
			if ( isset( $legacy[ $key ] ) ) {
				$hits[ $key ] = true;
			}
		}
		$this->assertSame(
			array(),
			array_keys( $hits ),
			"A style.css token-consolidation (selector,property) pair reappeared in a legacy sheet — "
			. "the later-loading legacy copy would override the token and re-invert the cascade "
			. "(NX1-10a dead-declaration regression)."
		);
	}

	/**
	 * (B) Each confirmed flip must be ABSENT from every legacy sheet.
	 *
	 * @param string $selector
	 * @param string $property
	 * @param string $winner   monolith winner (unused here; shared provider)
	 */
	#[DataProvider( 'confirmedFlipProvider' )]
	public function test_confirmed_flip_absent_from_legacy_sheets( string $selector, string $property, string $winner ): void {
		$legacy = self::legacyKeySet();
		$this->assertArrayNotHasKey(
			'|' . $selector . '|' . $property,
			$legacy,
			"Dead declaration reintroduced in a legacy sheet: {$selector} { {$property} } — "
			. "it loads after style.css and would re-win over the monolith's intended value."
		);
	}

	/**
	 * (B cont.) Each confirmed flip's monolith winner must still live in style.css
	 * (so pruning the legacy copy did not strand the property).
	 *
	 * @param string $selector
	 * @param string $property
	 * @param string $winner
	 */
	#[DataProvider( 'confirmedFlipProvider' )]
	public function test_confirmed_flip_winner_present_in_style_css( string $selector, string $property, string $winner ): void {
		$found = null;
		foreach ( self::styleRecords() as $r ) {
			if ( $r['selector'] === $selector && $r['property'] === $property ) {
				$found = $r['value'];
				if ( self::normValue( $winner ) === $r['value'] ) {
					break;
				}
			}
		}
		$this->assertNotNull(
			$found,
			"Monolith winner for {$selector} { {$property} } is missing from style.css — pruning stranded it."
		);
		$this->assertSame(
			self::normValue( $winner ),
			$found,
			"style.css winner for {$selector} { {$property} } changed — expected {$winner}."
		);
	}

	/** @return array<string,array{0:string,1:string,2:string}> */
	public static function confirmedFlipProvider(): array {
		$out = array();
		foreach ( self::CONFIRMED_FLIPS as $f ) {
			$out[ $f[0] . ' { ' . $f[1] . ' }' ] = $f;
		}
		return $out;
	}
}
