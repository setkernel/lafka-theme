<?php
/**
 * NX1-03 drift gate: theme.json must stay reconciled with the token SSOT.
 *
 * `styles/lafka-tokens.css` (:root block, the 212 --lafka-* custom properties)
 * is the single source of truth for colour, type and spacing. `theme.json`
 * exposes a subset of those to the block editor as presets. Historically the
 * two disagreed (theme.json accent #DD430E vs token accent-500 #dc2626), so the
 * editor and the front end rendered different brand colours.
 *
 * `scripts/build-theme-json.mjs` regenerates theme.json's palette / typography /
 * spacing sections from the tokens. This test is the gate that keeps them in
 * lock-step: it re-parses BOTH files independently and asserts every mapped
 * theme.json entry equals its token value. It fails the moment someone hand-edits
 * a theme.json palette/type/spacing value out of agreement with the tokens
 * (re-run `npm run build:theme-json` to reconcile).
 *
 * The slug -> token maps below mirror the maps in scripts/build-theme-json.mjs;
 * keep the two in sync when adding presets.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeJsonTokenParityTest extends TestCase {

	private const THEME_JSON = __DIR__ . '/../../theme.json';
	private const TOKENS_CSS  = __DIR__ . '/../../styles/lafka-tokens.css';

	/** Palette slug -> --lafka-* colour token (mirror of the build script). */
	private const PALETTE_MAP = array(
		'accent'            => '--lafka-color-accent-500',
		'text-primary'      => '--lafka-color-text-primary',
		'text-secondary'    => '--lafka-color-text-secondary',
		'text-muted'        => '--lafka-color-text-muted',
		'background'        => '--lafka-color-surface-page',
		'background-subtle' => '--lafka-color-surface-sunken',
		'background-input'  => '--lafka-color-surface-sunken',
		'border-light'      => '--lafka-color-border-subtle',
		'border-default'    => '--lafka-color-border-default',
		'border-medium'     => '--lafka-color-border-default',
		'border-dark'       => '--lafka-color-border-strong',
		'status-error'      => '--lafka-color-error-500',
		'status-warning'    => '--lafka-color-warning-500',
		'status-success'    => '--lafka-color-success-500',
		'status-info'       => '--lafka-color-info-500',
	);

	/** Font-size slug -> --lafka-* type token. x-large (28px) has no token. */
	private const FONT_SIZE_MAP = array(
		'small'    => '--lafka-font-size-caption',
		'medium'   => '--lafka-font-size-body',
		'large'    => '--lafka-font-size-h3',
		'xx-large' => '--lafka-font-size-display',
	);

	/** Font-family slug -> --lafka-* family token. */
	private const FONT_FAMILY_MAP = array(
		'body'    => '--lafka-font-family-body',
		'display' => '--lafka-font-family-display',
		'mono'    => '--lafka-font-family-mono',
	);

	/** Spacing slug -> --lafka-* space token. 3xl (30px) / 5xl (50px) have none. */
	private const SPACING_MAP = array(
		'xs'  => '--lafka-space-1',
		'sm'  => '--lafka-space-2',
		'md'  => '--lafka-space-3',
		'lg'  => '--lafka-space-4',
		'xl'  => '--lafka-space-5',
		'2xl' => '--lafka-space-6',
		'4xl' => '--lafka-space-10',
	);

	/**
	 * Parse the base (light-mode) :root { ... } block of lafka-tokens.css into a
	 * name => value map. Block comments are stripped first so prose containing
	 * ':' or ';' cannot corrupt the declaration split. The base block has no
	 * nested braces, so the first '}' closes it.
	 *
	 * @return array<string,string>
	 */
	private static function parse_root_tokens(): array {
		$css = (string) file_get_contents( self::TOKENS_CSS );
		$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );

		if ( ! preg_match( '/:root\s*\{(.*?)\}/s', $css, $m ) ) {
			return array();
		}

		$tokens = array();
		foreach ( explode( ';', $m[1] ) as $decl ) {
			$decl = trim( $decl );
			if ( '' === $decl || strncmp( $decl, '--', 2 ) !== 0 ) {
				continue;
			}
			$colon = strpos( $decl, ':' );
			if ( false === $colon ) {
				continue;
			}
			$name            = trim( substr( $decl, 0, $colon ) );
			$value           = trim( substr( $decl, $colon + 1 ) );
			$value           = (string) preg_replace( '/\s+/', ' ', $value );
			$tokens[ $name ] = $value;
		}

		return $tokens;
	}

	/** @return array<string,array<string,mixed>> */
	private static function theme_json(): array {
		$decoded = json_decode( (string) file_get_contents( self::THEME_JSON ), true );
		self::assertIsArray( $decoded, 'theme.json must parse as an object.' );
		return $decoded;
	}

	/**
	 * Reduce a theme.json array-of-objects section to slug => value.
	 *
	 * @param array<int,array<string,string>> $entries
	 * @return array<string,string>
	 */
	private static function by_slug( array $entries, string $value_key ): array {
		$out = array();
		foreach ( $entries as $entry ) {
			if ( isset( $entry['slug'], $entry[ $value_key ] ) ) {
				$out[ $entry['slug'] ] = $entry[ $value_key ];
			}
		}
		return $out;
	}

	public function test_tokens_are_parsed(): void {
		$tokens = self::parse_root_tokens();
		// Sanity: the SSOT ships ~212 custom properties; guard against a broken parse.
		self::assertGreaterThan( 150, count( $tokens ), 'Expected the token :root block to parse into 150+ properties.' );
		self::assertSame( '#dc2626', $tokens['--lafka-color-accent-500'] ?? null );
	}

	public function test_palette_matches_tokens(): void {
		$tokens  = self::parse_root_tokens();
		$palette = self::by_slug( self::theme_json()['settings']['color']['palette'] ?? array(), 'color' );

		foreach ( self::PALETTE_MAP as $slug => $token ) {
			self::assertArrayHasKey( $slug, $palette, "theme.json palette is missing the '{$slug}' slug." );
			self::assertArrayHasKey( $token, $tokens, "Token {$token} not found in lafka-tokens.css." );
			self::assertSame(
				strtolower( $tokens[ $token ] ),
				strtolower( $palette[ $slug ] ),
				"Palette '{$slug}' ({$palette[ $slug ]}) must equal token {$token} ({$tokens[ $token ]}). Run `npm run build:theme-json`."
			);
		}
	}

	public function test_font_sizes_match_tokens(): void {
		$tokens = self::parse_root_tokens();
		$sizes  = self::by_slug( self::theme_json()['settings']['typography']['fontSizes'] ?? array(), 'size' );

		foreach ( self::FONT_SIZE_MAP as $slug => $token ) {
			self::assertArrayHasKey( $slug, $sizes, "theme.json fontSizes is missing the '{$slug}' slug." );
			self::assertArrayHasKey( $token, $tokens, "Token {$token} not found in lafka-tokens.css." );
			self::assertSame(
				$tokens[ $token ],
				$sizes[ $slug ],
				"fontSize '{$slug}' must equal token {$token}. Run `npm run build:theme-json`."
			);
		}
	}

	public function test_font_families_match_tokens(): void {
		$tokens   = self::parse_root_tokens();
		$families = self::by_slug( self::theme_json()['settings']['typography']['fontFamilies'] ?? array(), 'fontFamily' );

		foreach ( self::FONT_FAMILY_MAP as $slug => $token ) {
			self::assertArrayHasKey( $slug, $families, "theme.json fontFamilies is missing the '{$slug}' slug." );
			self::assertArrayHasKey( $token, $tokens, "Token {$token} not found in lafka-tokens.css." );
			self::assertSame(
				$tokens[ $token ],
				$families[ $slug ],
				"fontFamily '{$slug}' must equal token {$token}. Run `npm run build:theme-json`."
			);
		}
	}

	public function test_spacing_sizes_match_tokens(): void {
		$tokens  = self::parse_root_tokens();
		$spacing = self::by_slug( self::theme_json()['settings']['spacing']['spacingSizes'] ?? array(), 'size' );

		foreach ( self::SPACING_MAP as $slug => $token ) {
			self::assertArrayHasKey( $slug, $spacing, "theme.json spacingSizes is missing the '{$slug}' slug." );
			self::assertArrayHasKey( $token, $tokens, "Token {$token} not found in lafka-tokens.css." );
			self::assertSame(
				$tokens[ $token ],
				$spacing[ $slug ],
				"spacingSize '{$slug}' must equal token {$token}. Run `npm run build:theme-json`."
			);
		}
	}
}
