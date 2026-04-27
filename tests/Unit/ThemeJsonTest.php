<?php
/**
 * Smoke test for theme.json (P3-07).
 *
 * Locks the file's presence + schema version + that the Lafka brand color
 * palette is exposed to the block editor. Catches drift if someone deletes
 * the file or removes the accent color slug.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeJsonTest extends TestCase {

	private const THEME_JSON = __DIR__ . '/../../theme.json';

	public function test_theme_json_exists(): void {
		self::assertFileExists( self::THEME_JSON );
	}

	public function test_theme_json_parses(): void {
		$decoded = json_decode( file_get_contents( self::THEME_JSON ), true );
		self::assertIsArray( $decoded, 'theme.json must parse as an object.' );
		self::assertSame( 3, $decoded['version'], 'theme.json schema version must be 3 (WP 6.6+).' );
	}

	public function test_brand_palette_exposed(): void {
		$decoded = json_decode( file_get_contents( self::THEME_JSON ), true );
		$slugs   = array_column( $decoded['settings']['color']['palette'] ?? array(), 'slug' );

		self::assertContains( 'accent', $slugs, 'accent color is the Lafka brand color and must stay in the palette.' );
		self::assertContains( 'text-primary', $slugs );
		self::assertContains( 'background', $slugs );
	}

	public function test_default_palette_disabled(): void {
		// We expose only Lafka-curated colors; WP's defaults would clutter the picker.
		$decoded = json_decode( file_get_contents( self::THEME_JSON ), true );
		self::assertFalse( $decoded['settings']['color']['defaultPalette'] ?? true );
	}
}
