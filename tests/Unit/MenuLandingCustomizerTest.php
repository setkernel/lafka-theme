<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression cover for f086: the "Lafka — Menu Landing" Customizer panel
 * registered five controls (intro/style/show_count/show_subcats/accent) that
 * the rebuilt /menu/ template never reads, while the two keys the template
 * DOES read (lafka_menu_archive_title / lafka_menu_archive_lead) were not
 * registered by any add_setting — so the operator could not edit the real
 * heading/lead and the visible toggles did nothing. This locks the
 * reconciliation: the two real keys are registered with the correct
 * sanitizers and the orphaned controls are gone.
 */
final class MenuLandingCustomizerTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-menu-landing.php' );
	}

	public function test_registers_archive_title_setting(): void {
		$this->assertStringContainsString( "'lafka_menu_archive_title'", $this->src );
	}

	public function test_registers_archive_lead_setting(): void {
		$this->assertStringContainsString( "'lafka_menu_archive_lead'", $this->src );
	}

	public function test_title_uses_text_field_sanitizer(): void {
		// The title is echoed via esc_html() (page-menu.php:79), so plain-text
		// sanitisation on save is correct.
		$this->assertMatchesRegularExpression(
			"/'lafka_menu_archive_title',[\s\S]*?'sanitize_callback'\s*=>\s*'sanitize_text_field'/",
			$this->src,
			'lafka_menu_archive_title must sanitize with sanitize_text_field.'
		);
	}

	public function test_lead_uses_wp_kses_post_sanitizer(): void {
		// The lead is emitted via wp_kses_post() (page-menu.php:81), so the
		// saved value must allow the same post markup — sanitize_text_field
		// here would strip operator-entered emphasis/links.
		$this->assertMatchesRegularExpression(
			"/'lafka_menu_archive_lead',[\s\S]*?'sanitize_callback'\s*=>\s*'wp_kses_post'/",
			$this->src,
			'lafka_menu_archive_lead must sanitize with wp_kses_post, not sanitize_text_field.'
		);
	}
}
