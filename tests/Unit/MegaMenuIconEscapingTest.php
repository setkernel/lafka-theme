<?php
/**
 * C-6 Site 2: Privileged stored XSS via mega-menu icon attribute.
 *
 * The mega-menu walker writes user-supplied meta into a `class=""` attribute:
 *
 *     $item_output .= '<i class="' . $font_awesome_icon . '"></i> ';
 *
 * If a privileged user can be tricked into posting (or saves) a payload like
 * `"></i><script>…`, the unescaped concatenation breaks out of the attribute
 * and runs script in the public menu. Two defenses must be in place:
 *
 *   1. on save: sanitize + allowlist `[a-z0-9 -]` (FA convention)
 *   2. on output: wrap with `esc_attr()`
 *
 * Source-grep lock for both.
 *
 * @package Lafka\Theme\Tests\Unit
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MegaMenuIconEscapingTest extends TestCase {

	private function source(): string {
		$path = __DIR__ . '/../../incl/LafkaMegaMenu.php';
		$this->assertFileExists( $path );

		return file_get_contents( $path );
	}

	public function test_icon_output_uses_esc_attr(): void {
		$src = $this->source();

		$this->assertStringContainsString(
			'\'<i class="\' . esc_attr( $font_awesome_icon ) . \'"></i>',
			$src,
			'Icon class attribute must be wrapped with esc_attr()'
		);
	}

	public function test_no_raw_concat_of_icon_into_class_attribute(): void {
		$src = $this->source();

		// The bare-concat shape (no esc_*) must not return.
		$this->assertDoesNotMatchRegularExpression(
			'/\'<i class="\'\s*\.\s*\$font_awesome_icon\s*\.\s*\'"><\/i>/',
			$src,
			'Raw concatenation of $font_awesome_icon into class="" attribute is forbidden'
		);
	}

	public function test_save_callback_sanitizes_and_allowlists_icon(): void {
		$src = $this->source();

		// sanitize_text_field + wp_unslash defense
		$this->assertStringContainsString( 'sanitize_text_field(', $src );
		$this->assertStringContainsString( 'wp_unslash(', $src );

		// FA-class allowlist regex (a-z0-9 hyphen and space).
		$this->assertMatchesRegularExpression(
			"#preg_match\(\s*'/\^\[a-z0-9 \\\\-\]\*\\\$/i'#",
			$src,
			'Save callback must validate icon against FA-style allowlist'
		);
	}
}
