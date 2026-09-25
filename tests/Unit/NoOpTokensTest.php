<?php
declare(strict_types=1);

/**
 * GX4 A3: the base tokens the counter design needs are declared with values
 * that reproduce today's rendering (no pixel moves for any preset), are
 * preset-settable (whitelisted), and never form a var() cycle.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class NoOpTokensTest extends TestCase {

		private const NEW_TOKENS = array(
			'--lafka-font-size-body-desk',
			'--lafka-radius-button',
			'--lafka-motif-check-a',
			'--lafka-motif-check-b',
			'--lafka-motif-check-size',
			'--lafka-motif-check-h',
			'--lafka-dish-shadow',
			'--lafka-dish-contact',
		);

		/** @return array<string,string> the first :root{} block's declarations. */
		private static function root_tokens(): array {
			$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-tokens.css' );
			$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
			preg_match( '/(?<![\w\]-]):root\s*\{([^}]*)\}/', $css, $m );
			$out = array();
			preg_match_all( '/(--[a-z0-9-]+)\s*:\s*([^;]+);/', $m[1] ?? '', $decls, PREG_SET_ORDER );
			foreach ( $decls as $d ) {
				$out[ $d[1] ] = trim( $d[2] );
			}
			return $out;
		}

		public function test_each_new_token_is_declared_and_whitelisted(): void {
			$tokens = self::root_tokens();
			foreach ( self::NEW_TOKENS as $token ) {
				$this->assertArrayHasKey( $token, $tokens, "{$token} must be declared in the base :root" );
				$this->assertContains( $token, LAFKA_PRESET_TOKEN_WHITELIST, "{$token} must be preset-settable" );
			}
		}

		public function test_base_values_are_no_ops(): void {
			$tokens = self::root_tokens();
			$this->assertSame( 'var(--lafka-radius-pill)', $tokens['--lafka-radius-button'], 'buttons keep the pill radius by default' );
			$this->assertSame( 'var(--lafka-font-size-body)', $tokens['--lafka-font-size-body-desk'] );
			$this->assertSame( 'none', $tokens['--lafka-dish-shadow'] );
			$this->assertSame( 'transparent', $tokens['--lafka-dish-contact'] );
			$this->assertSame( 'var(--lafka-color-surface-page)', $tokens['--lafka-motif-check-b'] );
		}

		public function test_btn_primitive_reads_the_button_radius(): void {
			$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-components.css' );
			$this->assertMatchesRegularExpression( '/\.lafka-btn\s*\{[^}]*border-radius:\s*var\(--lafka-radius-button\)/', $css );
		}

		public function test_no_base_rule_repoints_the_body_size(): void {
			$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-tokens.css' );
			$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
			preg_match_all( '/--lafka-font-size-body\s*:\s*([^;]+);/', $css, $m );
			$this->assertCount( 1, $m[1], '--lafka-font-size-body is declared exactly once (no desktop re-point: that would move every preset)' );
		}

		public function test_no_token_references_itself_transitively(): void {
			$tokens = self::root_tokens();
			foreach ( self::NEW_TOKENS as $start ) {
				$seen  = array();
				$queue = array( $start );
				while ( $queue ) {
					$name = array_shift( $queue );
					if ( ! isset( $tokens[ $name ] ) ) {
						continue;
					}
					preg_match_all( '/var\((--[a-z0-9-]+)/', $tokens[ $name ], $refs );
					foreach ( $refs[1] as $ref ) {
						$this->assertNotSame( $start, $ref, "{$start} references itself via {$name}" );
						if ( ! isset( $seen[ $ref ] ) ) {
							$seen[ $ref ] = true;
							$queue[]      = $ref;
						}
					}
				}
			}
		}
	}
}
