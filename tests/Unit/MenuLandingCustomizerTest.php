<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

	public function test_customizer_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/incl/customizer-menu-landing.php' );
	}

	public function test_hooks_into_customize_register(): void {
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]customize_register['\"]/",
			$this->src
		);
	}

	public function test_panel_is_preserved(): void {
		$this->assertStringContainsString( "'lafka_menu_landing'", $this->src );
	}

	public function test_registers_archive_title_setting(): void {
		$this->assertStringContainsString( "'lafka_menu_archive_title'", $this->src );
	}

	public function test_registers_archive_lead_setting(): void {
		$this->assertStringContainsString( "'lafka_menu_archive_lead'", $this->src );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function orphaned_keys(): array {
		return array(
			'intro'        => array( 'lafka_menu_landing_intro' ),
			'style'        => array( 'lafka_menu_landing_style' ),
			'show_count'   => array( 'lafka_menu_landing_show_count' ),
			'show_subcats' => array( 'lafka_menu_landing_show_subcats' ),
			'accent'       => array( 'lafka_menu_landing_accent' ),
		);
	}

	#[DataProvider('orphaned_keys')]
	public function test_orphaned_controls_are_removed( string $key ): void {
		$this->assertStringNotContainsString(
			"'" . $key . "'",
			$this->src,
			$key . ' is not consumed anywhere — the dead control must be removed so the UI stops advertising a non-functional setting.'
		);
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

	public function test_title_default_is_empty_string(): void {
		// Default '' keeps the get_the_title()/'The full menu' fallback chain
		// in page-menu.php:33-39 in control.
		$this->assertMatchesRegularExpression(
			"/'lafka_menu_archive_title',[\s\S]*?'default'\s*=>\s*''/",
			$this->src,
			"lafka_menu_archive_title default must be '' so the template fallback chain still applies."
		);
	}

	public function test_lead_default_matches_template_fallback(): void {
		$this->assertStringContainsString(
			'Browse everything we make. Tap a category to jump to it or scroll through the whole menu.',
			$this->src,
			'lead default must mirror the template fallback string at page-menu.php:43.'
		);
	}

	public function test_dead_style_sanitizer_is_removed(): void {
		$this->assertStringNotContainsString(
			'lafka_menu_landing_sanitize_style',
			$this->src,
			'the card-style sanitizer is dead once the style control is removed.'
		);
	}

	public function test_functions_php_requires_customizer(): void {
		$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			"/require(_once)?\s+.*customizer-menu-landing\.php/",
			$src,
			'functions.php must require customizer-menu-landing.php.'
		);
	}
}
