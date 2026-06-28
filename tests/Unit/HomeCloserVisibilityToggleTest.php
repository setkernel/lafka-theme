<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for audit f089: the home final-CTA "closer" band ignored
 * its own Customizer visibility toggle.
 *
 * incl/customizer-home.php registers lafka_home_closer_visible (default true,
 * label "Show this section"), but partials/home-cta-closer.php never read it,
 * so the band always rendered and the operator could not hide it — unlike the
 * sibling Story section (partials/home-story.php), which honours its toggle.
 *
 * This test asserts the closer partial early-returns on the toggle before it
 * does any data lookups, mirroring the established sibling pattern.
 */
final class HomeCloserVisibilityToggleTest extends TestCase {

	private string $closer;
	private string $customizer;

	protected function setUp(): void {
		$root             = dirname( __DIR__, 2 );
		$this->closer     = file_get_contents( $root . '/partials/home-cta-closer.php' );
		$this->customizer = file_get_contents( $root . '/incl/customizer-home.php' );
	}

	public function test_closer_partial_early_returns_on_visibility_toggle(): void {
		$this->assertMatchesRegularExpression(
			"/if\s*\(\s*!\s*\(bool\)\s*get_theme_mod\(\s*'lafka_home_closer_visible',\s*true\s*\)\s*\)\s*\{\s*return;\s*\}/",
			$this->closer,
			'Closer partial must early-return when lafka_home_closer_visible is off (default true).'
		);
	}

	public function test_visibility_guard_precedes_data_lookups(): void {
		$guard_pos    = strpos( $this->closer, "get_theme_mod( 'lafka_home_closer_visible'" );
		$headline_pos = strpos( $this->closer, "get_theme_mod( 'lafka_home_closer_headline'" );
		$this->assertNotFalse( $guard_pos, 'Closer partial must read the visibility toggle.' );
		$this->assertNotFalse( $headline_pos, 'Closer partial must read its headline setting.' );
		$this->assertLessThan(
			$headline_pos,
			$guard_pos,
			'The visibility guard must run before any closer data lookups (early return).'
		);
	}

	public function test_visibility_toggle_is_registered(): void {
		$this->assertStringContainsString(
			"'lafka_home_closer_visible'",
			$this->customizer,
			'Customizer must register the lafka_home_closer_visible toggle the partial honours.'
		);
	}
}
