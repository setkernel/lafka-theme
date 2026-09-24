<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use Lafka_Color_Contrast;
use PHPUnit\Framework\TestCase;

/**
 * Small-text colours in the static sheets meet WCAG AA (4.5:1) on white.
 *
 * The preset palettes are covered by PresetContrastTest; these are literal
 * colours the parent sheets set on markup the parent emits.
 */
final class ContrastFixesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-color-contrast.php';
	}

	public function test_foodmenu_ingredients_text_meets_aa_on_white(): void {
		$css   = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-base.css' );
		$color = $this->declared_color( $css, '.foodmenu-unit-info .ingredients' );

		$this->assertGreaterThanOrEqual( 4.5, Lafka_Color_Contrast::ratio( $color, '#ffffff' ), "Ingredients colour {$color} is below 4.5:1 on white." );
	}

	private function declared_color( string $css, string $selector ): string {
		$this->assertSame( 1, preg_match( '/' . preg_quote( $selector, '/' ) . '\s*\{([^}]*)\}/', $css, $rule ), "{$selector} rule not found." );
		$this->assertSame( 1, preg_match( '/(?<![-\w])color\s*:\s*(#[0-9a-f]{3,6})/i', $rule[1], $m ), "{$selector} declares no hex colour." );
		return $m[1];
	}
}
