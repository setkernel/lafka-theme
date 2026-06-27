<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * style.css integrity guard.
 *
 * After the PurgeCSS-based legacy purge (v6.17.0, -109 KB), this locks the bits
 * that MUST survive any future purge: the WordPress theme header (WP reads the
 * version/metadata from it), the self-hosted @font-face set, the design-token
 * custom properties (consumed cross-file by styles/*.css), and a baseline size
 * (catches an accidental truncation like the 2026-04-29 sed incident).
 */
final class StyleCssIntegrityTest extends TestCase {

	private string $css;

	protected function setUp(): void {
		$this->css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
	}

	public function test_theme_header_present(): void {
		$this->assertStringContainsString( 'Theme Name: Lafka', $this->css );
		$this->assertMatchesRegularExpression( '/^\s*Version:\s*\d+\.\d+\.\d+/m', $this->css,
			'WP reads the theme version from the style.css header — it must survive purges.' );
		$this->assertStringContainsString( 'Text Domain: lafka', $this->css );
	}

	public function test_font_faces_preserved(): void {
		$this->assertSame( 5, substr_count( $this->css, '@font-face' ),
			'All self-hosted @font-face rules must be kept (purge must not drop fonts).' );
	}

	public function test_design_tokens_preserved(): void {
		// Custom properties are referenced via var() by styles/*.css — purging
		// them out of style.css would break the modular design system.
		$vars = preg_match_all( '/--lafka-[a-z0-9-]+\s*:/i', $this->css );
		$this->assertGreaterThanOrEqual( 20, $vars, 'design-token custom properties must be retained' );
	}

	public function test_not_truncated(): void {
		// Post-purge baseline ~384 KB; guard against accidental truncation.
		$this->assertGreaterThan( 200000, strlen( $this->css ), 'style.css looks truncated' );
		$this->assertSame(
			substr_count( $this->css, '{' ),
			substr_count( $this->css, '}' ),
			'unbalanced braces — CSS is malformed'
		);
	}
}
