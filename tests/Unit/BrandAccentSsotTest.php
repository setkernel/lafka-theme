<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * f074 regression lock: brand/accent single-source-of-truth across the
 * theme's three color naming systems (handoff / legacy / editorial).
 *
 * Guards the drift fixed in cluster f074:
 *  - editorial.css must follow the design-system accent token instead of the
 *    old hardcoded #E4584B / #C93827 brand literals;
 *  - dynamic-css.php must bridge the Customizer brand_color into the handoff
 *    brand ramp anchor (--lafka-color-brand-500) at the pepper-yellow default;
 *  - critical.css must name the handoff accent/brand tokens above-fold at the
 *    design-system defaults (no out-of-box divergence);
 *  - the child theme docs must point future rebrands at the handoff token
 *    names, not only the legacy --lafka-accent-color.
 */
final class BrandAccentSsotTest extends TestCase {

	private function theme_file( string $rel ): string {
		$path = dirname( __DIR__, 2 ) . $rel;
		$this->assertFileExists( $path );
		return (string) file_get_contents( $path );
	}

	public function test_editorial_brand_tokens_follow_accent_ssot(): void {
		$css = $this->theme_file( '/styles/editorial.css' );
		$this->assertMatchesRegularExpression(
			'/--brand:\s+var\(\s*--lafka-color-accent-500\b/',
			$css,
			'editorial --brand must read the accent SSOT token so editorial pages follow the operator accent.'
		);
		$this->assertMatchesRegularExpression(
			'/--brand-deep:\s+var\(\s*--lafka-color-accent-600\b/',
			$css,
			'editorial --brand-deep base value must read the accent SSOT token (with a design-system fallback).'
		);
	}

	public function test_editorial_brand_deep_tracks_accent_via_color_mix(): void {
		$css = $this->theme_file( '/styles/editorial.css' );
		$this->assertStringContainsString(
			'@supports (color: color-mix(in srgb, red 50%, white))',
			$css,
			'editorial.css must progressively enhance --brand-deep so the CTA hover tracks the live accent.'
		);
		$this->assertMatchesRegularExpression(
			'/--brand-deep:\s*color-mix\(in srgb,\s*var\(\s*--lafka-color-accent-500/',
			$css,
			'--brand-deep must be derived from the live accent in the color-mix @supports block.'
		);
	}

	public function test_editorial_has_no_hardcoded_brand_literals(): void {
		$css = strtolower( $this->theme_file( '/styles/editorial.css' ) );
		$this->assertStringNotContainsString(
			'#e4584b',
			$css,
			'editorial.css must not bake the brand literal #E4584B into the OSS repo.'
		);
		$this->assertStringNotContainsString(
			'#c93827',
			$css,
			'editorial.css must not bake the brand literal #C93827 into the OSS repo.'
		);
	}

	public function test_dynamic_css_bridges_brand_color_to_handoff_token(): void {
		$php = $this->theme_file( '/styles/dynamic-css.php' );
		// NX1-02.logos-brand-pilot: brand_color migrated legacy option ->
		// lafka_brand_color theme_mod; the pepper-yellow default is preserved.
		$this->assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_brand_color'\s*,\s*'#f59e0b'\s*\)/",
			$php,
			'dynamic-css.php must read the lafka_brand_color theme_mod with the pepper-yellow default.'
		);
		$this->assertStringContainsString(
			"--lafka-color-brand-500:' . \$brand_color",
			$php,
			'dynamic-css.php must emit --lafka-color-brand-500 from the operator brand color.'
		);
	}

	public function test_critical_css_names_handoff_tokens_at_design_defaults(): void {
		$css = $this->theme_file( '/styles/critical.css' );
		$this->assertMatchesRegularExpression(
			'/--lafka-color-accent-500:\s*#dc2626;/',
			$css,
			'critical.css above-fold subset must name --lafka-color-accent-500 at the design default.'
		);
		$this->assertMatchesRegularExpression(
			'/--lafka-color-brand-500:\s*#f59e0b;/',
			$css,
			'critical.css above-fold subset must name --lafka-color-brand-500 at the design default.'
		);
	}

	public function test_child_theme_docs_reference_handoff_tokens(): void {
		// Cross-repo: lafka-child is a sibling repo, absent in isolated CI.
		$child = dirname( __DIR__, 3 ) . '/lafka-child/style.css';
		if ( ! file_exists( $child ) ) {
			$this->markTestSkipped( 'Sibling lafka-child repo not checked out (isolated CI); local dev only.' );
		}
		$css = (string) file_get_contents( $child );
		$this->assertStringContainsString(
			'--lafka-color-accent-500',
			$css,
			'Child theme docs must steer future rebrands at the handoff accent token, not only the legacy name.'
		);
		$this->assertStringContainsString(
			'--lafka-color-brand-500',
			$css,
			'Child theme docs must mention the handoff brand ramp token.'
		);
	}
}
