<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards against var() fallback drift in lafka-active-promos.css.
 *
 * lafka-tokens.css is the single source of truth and is guaranteed-enqueued
 * first, so these fallbacks never render today. But a wrong fallback is
 * misleading documentation AND would inject a layout shift / off-brand colour
 * the instant the tokens stylesheet fails to load or is overridden — exactly
 * the failure mode fallbacks exist for. Every var(--token, fallback) whose
 * token resolves to a literal value in lafka-tokens.css must match that value.
 */
final class ActivePromosCssFallbackTest extends TestCase {

	private static function tokensPath(): string {
		return dirname( __DIR__, 2 ) . '/styles/lafka-tokens.css';
	}

	private static function activePromosPath(): string {
		return dirname( __DIR__, 2 ) . '/styles/lafka-active-promos.css';
	}

	/**
	 * Build a map of token name => canonical literal value from
	 * lafka-tokens.css. The FIRST definition wins (the :root base value);
	 * tokens whose value is itself a var() chain are skipped because they
	 * have no single literal to compare a fallback against.
	 *
	 * @return array<string, string>
	 */
	private static function literalTokenMap(): array {
		$css = (string) file_get_contents( self::tokensPath() );
		$map = array();
		if ( preg_match_all( '/(--lafka-[a-z0-9-]+)\s*:\s*([^;]+);/i', $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$name  = $match[1];
				$value = trim( $match[2] );
				// Only keep literal values; skip var()-chained tokens.
				if ( '' === $value || 0 === stripos( $value, 'var(' ) ) {
					continue;
				}
				// First definition wins (base :root value, not later overrides).
				if ( ! isset( $map[ $name ] ) ) {
					$map[ $name ] = $value;
				}
			}
		}
		return $map;
	}

	/**
	 * @return array<int, array{0:string,1:string}> token name, declared fallback
	 */
	public static function provideFallbacks(): array {
		$css = (string) file_get_contents( self::activePromosPath() );
		$out = array();
		if ( preg_match_all( '/var\(\s*(--lafka-[a-z0-9-]+)\s*,\s*([^)]+?)\s*\)/i', $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$out[] = array( $match[1], trim( $match[2] ) );
			}
		}
		// Ensure the provider is never empty (which would skip the test silently).
		if ( empty( $out ) ) {
			$out[] = array( '--lafka-__none__', '' );
		}
		return $out;
	}

	#[DataProvider( 'provideFallbacks' )]
	public function test_fallback_matches_token_value( string $token, string $fallback ): void {
		if ( '--lafka-__none__' === $token ) {
			$this->fail( 'No var() fallbacks found in lafka-active-promos.css — provider produced nothing.' );
		}

		$map = self::literalTokenMap();

		// If the token resolves to a var() chain (no literal), there is nothing
		// to compare against — the fallback is documentary only.
		if ( ! isset( $map[ $token ] ) ) {
			$this->assertTrue( true );
			return;
		}

		$this->assertSame(
			$map[ $token ],
			$fallback,
			sprintf(
				'var(%s) fallback "%s" must equal its token value "%s" from lafka-tokens.css',
				$token,
				$fallback,
				$map[ $token ]
			)
		);
	}

	public function test_space_4_fallback_is_16px_not_20px(): void {
		// Regression: the bottom-margin fallback was 20px while --lafka-space-4
		// is 16px — an 8px layout-shift footgun if tokens.css ever drops out.
		$css = (string) file_get_contents( self::activePromosPath() );
		$this->assertStringContainsString( 'var(--lafka-space-4, 16px)', $css );
		$this->assertStringNotContainsString( 'var(--lafka-space-4, 20px)', $css );
	}

	public function test_accent_fallback_is_canonical_brand_red(): void {
		// Regression: keep the accent fallback on the canonical #dc2626 brand
		// red, never the orphaned #ec1d24 third red.
		$css = (string) file_get_contents( self::activePromosPath() );
		$this->assertStringNotContainsString( '#ec1d24', $css );
	}
}
