<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use PHPUnit\Framework\Attributes\DataProvider;

	/**
	 * NX2-07 SURFACE-TOKENISATION gate.
	 *
	 * PresetContrastTest resolves each preset's PALETTE in isolation, so it is
	 * blind to a component that paints a core SURFACE with a hardcoded light
	 * literal (or a page-relative text token) instead of a --lafka-color-surface-*
	 * token — exactly the class of bug that left a dark preset rendering
	 * light-text-on-white before NX2-07. This test is the static scan that closes
	 * that gap:
	 *
	 *   1. No core surface stylesheet may declare an OPAQUE LIGHT-LITERAL
	 *      background (#fff / #fafafa / white / …). A future component that hard-
	 *      codes a white panel background fails here and must tokenise it. (Image
	 *      wells like #f5f5f5, translucent rgba() overlays, and var(token, #fff)
	 *      FALLBACKS are intentionally NOT flagged — only opaque literal fills.)
	 *
	 *   2. The load-bearing core surfaces stay WIRED to their surface token, so
	 *      deleting the tokenisation (regressing to a literal or to the ink text
	 *      token) trips this test.
	 *
	 *   3. Every core surface token carries a :root[data-theme="dark"] value in
	 *      lafka-tokens.css, so a newly-tokenised surface can never ship without a
	 *      dark value (which would reintroduce a light island under a dark preset).
	 *
	 * @package Lafka\Tests
	 */
	final class SurfaceTokenizationTest extends TestCase {

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		private static function css( string $rel ): string {
			return (string) file_get_contents( self::root() . '/styles/' . $rel );
		}

		/**
		 * Core surface stylesheets that must never carry an opaque light-literal
		 * background — the theme's page / section / card / chrome surfaces.
		 *
		 * @return array<string,array{0:string}>
		 */
		public static function coreSurfaceFileProvider(): array {
			return array(
				'critical.css'            => array( 'critical.css' ),
				'product-card.css'        => array( 'product-card.css' ),
				'cart-item.css'           => array( 'cart-item.css' ),
				'pdp-redesign.css'        => array( 'pdp-redesign.css' ),
				'lafka-header-chrome.css' => array( 'lafka-header-chrome.css' ),
				'lafka-menu-archive.css'  => array( 'lafka-menu-archive.css' ),
				'lafka-footer-chrome.css' => array( 'lafka-footer-chrome.css' ),
				'lafka-home-v2.css'       => array( 'lafka-home-v2.css' ),
				'lafka-hero.css'          => array( 'lafka-hero.css' ),
				'lafka-base.css'          => array( 'lafka-base.css' ),
				'lafka-page.css'          => array( 'lafka-page.css' ),
			);
		}

		/**
		 * An OPAQUE light-literal background declaration. The trailing \b keeps
		 * `#fff` from matching inside `#fff7ed` (brand-50) and `white` from
		 * matching `whitesmoke`; requiring the literal to sit directly after
		 * `background:` skips `var(--token, #fff)` fallbacks (the literal there is
		 * the fallback, not the value).
		 */
		private const LIGHT_LITERAL_BG = '/background(?:-color)?\s*:\s*(?:#ffffff|#fff|#fafafa|#f8f8f8|#f9f9f9|#f4f4f5|#fcfcfc|white)\b/i';

		#[DataProvider( 'coreSurfaceFileProvider' )]
		public function test_no_opaque_light_literal_background( string $file ): void {
			$css = self::css( $file );
			$this->assertSame(
				0,
				preg_match_all( self::LIGHT_LITERAL_BG, $css, $m ),
				"styles/{$file} declares an opaque light-literal background: "
					. implode( ', ', $m[0] ?? array() )
					. '. Wire it to a --lafka-color-surface-* token so a dark preset '
					. 'renders it dark (use `var(--lafka-color-surface-*, #fff)` if a '
					. 'first-paint fallback is needed).'
			);
		}

		/**
		 * Load-bearing surface → token wirings that must not regress.
		 *
		 * @return array<string,array{0:string,1:string}> label => [file, needle]
		 */
		public static function surfaceWiringProvider(): array {
			return array(
				'body page surface'        => array( 'critical.css', 'var(--lafka-color-surface-page' ),
				'product card raised'      => array( 'product-card.css', 'var(--lafka-color-surface-raised' ),
				'cart item raised'         => array( 'cart-item.css', 'var(--lafka-color-surface-raised' ),
				'pdp surface alias'        => array( 'pdp-redesign.css', '--lafka-pdp-surface: var(--lafka-color-surface-raised)' ),
				'pdp cards use alias'      => array( 'pdp-redesign.css', 'var(--lafka-pdp-surface)' ),
				'header frosted glass'     => array( 'lafka-header-chrome.css', 'var(--lafka-color-surface-glass' ),
				'menu strip frosted glass' => array( 'lafka-menu-archive.css', 'var(--lafka-color-surface-glass' ),
				'footer band surface'      => array( 'lafka-footer-chrome.css', 'var(--lafka-color-surface-footer)' ),
			);
		}

		#[DataProvider( 'surfaceWiringProvider' )]
		public function test_core_surface_wired_to_token( string $file, string $needle ): void {
			$this->assertStringContainsString(
				$needle,
				self::css( $file ),
				"styles/{$file} must keep wiring its core surface to '{$needle}' "
					. '(NX2-07 surface tokenisation).'
			);
		}

		/** The body-background specificity guard that beats WP global-styles. */
		public function test_html_body_background_guard_present(): void {
			$css = self::css( 'lafka-tokens.css' );
			$this->assertMatchesRegularExpression(
				'/html body\s*\{\s*background-color:\s*var\(--lafka-color-surface-page\)/',
				$css,
				'lafka-tokens.css must keep the `html body { background-color: '
					. 'var(--lafka-color-surface-page) }` guard so WordPress\'s '
					. 'global-styles `body { background: var(--wp--preset--color--background) }` '
					. 'cannot pin the page to white under a dark preset.'
			);
		}

		/**
		 * Every core surface token must carry a dark value in the
		 * :root[data-theme="dark"] scaffold, so a tokenised surface can never ship
		 * without a dark value (which would be a light island under a dark preset).
		 *
		 * @return array<string,array{0:string}>
		 */
		public static function darkScaffoldTokenProvider(): array {
			return array(
				'surface-page'   => array( '--lafka-color-surface-page' ),
				'surface-raised' => array( '--lafka-color-surface-raised' ),
				'surface-sunken' => array( '--lafka-color-surface-sunken' ),
				'surface-muted'  => array( '--lafka-color-surface-muted' ),
				'surface-glass'  => array( '--lafka-color-surface-glass' ),
				'surface-footer' => array( '--lafka-color-surface-footer' ),
				'accent-50'      => array( '--lafka-color-accent-50' ),
			);
		}

		#[DataProvider( 'darkScaffoldTokenProvider' )]
		public function test_dark_scaffold_defines_surface_token( string $token ): void {
			$css = self::css( 'lafka-tokens.css' );
			$this->assertMatchesRegularExpression(
				'/:root\[data-theme="dark"\]\s*\{/',
				$css,
				'the dark scaffold block must exist in lafka-tokens.css.'
			);
			// Isolate the dark scaffold block and assert the token is declared in it.
			$start = strpos( $css, ':root[data-theme="dark"]' );
			$this->assertNotFalse( $start, 'dark scaffold selector missing.' );
			$block = substr( $css, $start, (int) strpos( $css, '}', $start ) - $start );
			$this->assertStringContainsString(
				$token . ':',
				$block,
				"the :root[data-theme=\"dark\"] scaffold must give {$token} a dark "
					. 'value (NX2-07 dark completion), else it stays a light island '
					. 'under a dark preset.'
			);
		}
	}
}
