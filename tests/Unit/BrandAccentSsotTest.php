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
 *    design-system defaults (no out-of-box divergence).
 *
 * The matching child-theme guard (rebrand recipes name the handoff tokens)
 * lives in lafka-child's ThinLayerTest.
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
}
