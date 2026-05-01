<?php
/**
 * P6-PERF-3 regression lock: Rubik must remain self-hosted, not pulled from Google CDN.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SelfHostedFontsTest extends TestCase {

	public function test_style_css_has_rubik_font_face_for_three_weights(): void {
		// Quotes around 'Rubik' are optional in CSS — both forms are valid.
		// Regex allows unquoted (current state) or single/double quoted.
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
		$this->assertMatchesRegularExpression( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*font-weight:\s*400/s', $css );
		$this->assertMatchesRegularExpression( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*font-weight:\s*600/s', $css );
		$this->assertMatchesRegularExpression( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*font-weight:\s*700/s', $css );
	}

	public function test_style_css_uses_font_display_optional_for_rubik(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
		preg_match_all( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*\}/s', $css, $matches );
		$this->assertCount( 3, $matches[0], 'Expected exactly 3 @font-face for Rubik' );
		foreach ( $matches[0] as $face ) {
			$this->assertMatchesRegularExpression( '/font-display:\s*optional/', $face );
		}
	}

	public function test_woff2_files_exist_in_assets(): void {
		$dir = dirname( __DIR__, 2 ) . '/assets/fonts/rubik/';
		foreach ( [ '400', '600', '700' ] as $weight ) {
			$this->assertFileExists( $dir . "Rubik-{$weight}.woff2" );
		}
	}

	public function test_typography_function_skips_google_font_for_rubik(): void {
		$core = file_get_contents( dirname( __DIR__, 2 ) . '/incl/system/core-functions.php' );
		// The short-circuit must reference 'Rubik' literally and short-circuit (return)
		$this->assertStringContainsString( "'Rubik'", $core, 'core-functions.php should reference Rubik literal for the short-circuit' );
		// The short-circuit should be inside lafka_typography_enqueue_google_font
		preg_match( '/function\s+lafka_typography_enqueue_google_font[^{]*\{(.*?)\n\s*\}/s', $core, $m );
		$this->assertNotEmpty( $m, 'lafka_typography_enqueue_google_font function not found' );
		$this->assertStringContainsString( 'Rubik', $m[1], 'short-circuit not inside lafka_typography_enqueue_google_font' );
	}
}
