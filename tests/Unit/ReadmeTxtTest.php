<?php
/**
 * Locks in the wp.org theme-directory readme.txt.
 *
 * The WordPress theme directory expects a readme.txt whose header floors match
 * the style.css headers, whose license is GPL, and which does NOT require a
 * companion plugin (theme-review rules forbid a plugin dependency). This guard
 * keeps the readme accurate to style.css and free of plugin-requirement wording.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReadmeTxtTest extends TestCase {

	private const ROOT = __DIR__ . '/../..';

	private function readme(): string {
		$path = self::ROOT . '/readme.txt';
		self::assertFileExists( $path, 'wp.org theme readme.txt is missing' );

		return (string) file_get_contents( $path );
	}

	private function style_header(): string {
		return substr( (string) file_get_contents( self::ROOT . '/style.css' ), 0, 8192 );
	}

	private function style_field( string $label ): string {
		$header = $this->style_header();
		self::assertSame( 1, preg_match( '/^\s*' . preg_quote( $label, '/' ) . ':\s*(.+?)\s*$/m', $header, $m ), "no {$label} header in style.css" );

		return trim( $m[1] );
	}

	public function test_readme_starts_with_theme_name_header(): void {
		self::assertMatchesRegularExpression( '/^===\s*Lafka\s*===/', $this->readme() );
	}

	public function test_header_floors_match_style_css(): void {
		$readme = $this->readme();

		foreach ( array( 'Requires at least', 'Tested up to', 'Requires PHP' ) as $label ) {
			$value = $this->style_field( $label );
			self::assertMatchesRegularExpression(
				'/^' . preg_quote( $label, '/' ) . ':\s*' . preg_quote( $value, '/' ) . '\s*$/m',
				$readme,
				"readme.txt {$label} drifted from style.css (expected {$value})"
			);
		}
	}

	public function test_license_is_gpl(): void {
		self::assertMatchesRegularExpression( '/^License:\s*GNU General Public License v2 or later/m', $this->readme() );
		self::assertStringContainsString( 'gnu.org/licenses/gpl-2.0', $this->readme() );
	}

	public function test_required_sections_present(): void {
		$readme = $this->readme();

		foreach ( array( 'Description', 'Installation', 'Frequently Asked Questions', 'Changelog', 'Copyright' ) as $section ) {
			self::assertMatchesRegularExpression(
				'/^==\s*' . preg_quote( $section, '/' ) . '\s*==/m',
				$readme,
				"readme.txt is missing the '{$section}' section"
			);
		}
	}

	public function test_no_plugin_requirement_language(): void {
		$readme = strtolower( $this->readme() );

		$forbidden = array(
			'/requires?\s+the[^.\n]{0,40}\bplugin\b/',
			'/\bplugin\b[^.\n]{0,40}\bis\s+required\b/',
			'/\brequired\s+plugin\b/',
			'/\bplugin\b[^.\n]{0,40}\brequirement\b/',
		);

		foreach ( $forbidden as $pattern ) {
			self::assertDoesNotMatchRegularExpression(
				$pattern,
				$readme,
				'readme.txt must present the companion plugin as a recommendation, never a requirement (theme-directory rule)'
			);
		}
	}

	public function test_companion_plugin_framed_as_recommendation(): void {
		$readme = $this->readme();
		self::assertMatchesRegularExpression( '/recommend/i', $readme, 'companion plugin should be recommended' );
		self::assertStringContainsString( 'lafka-plugin', $readme, 'companion plugin link expected' );
	}

	public function test_copyright_section_credits_verifiable_assets(): void {
		$readme = $this->readme();

		foreach ( array( 'Font Awesome', 'Rubik', 'Fraunces', 'Owl Carousel' ) as $asset ) {
			self::assertStringContainsString(
				$asset,
				$readme,
				"Copyright/credits section should list bundled asset: {$asset}"
			);
		}
	}
}
