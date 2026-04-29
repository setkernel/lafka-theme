<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * C-A11Y-Audit-2026-04-29: WCAG 1.4.3 contrast regression lock.
 *
 * Asserts that specific failing color values (#999/#888 on light backgrounds)
 * have been replaced with the compliant value (#5e5e5e, 4.6:1 on white) in
 * both lafka-theme/style.css and lafka-child/style.css.
 *
 * Negative assertions: forbidden values must NOT appear in rule declarations.
 * Positive assertions: compliant replacements must be present.
 *
 * Note: #999 is still allowed in:
 *   - text-shadow declarations (shadow colors, not foreground text)
 *   - rgba() values
 *   - comments
 *   - .video_controlls a:hover (dark background, exempted with comment)
 *   - background-color declarations
 * The regex patterns below are scoped to `color:` declarations to avoid
 * false-positives on shadow/rgba/background uses.
 */
final class ContrastFixesTest extends TestCase {

	private string $theme_css;
	private string $child_css;

	protected function setUp(): void {
		parent::setUp();
		$this->theme_css = file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
		$this->child_css = file_get_contents(
			dirname( __DIR__, 3 ) . '/lafka-child/style.css'
		);
	}

	// ------------------------------------------------------------------
	// Child theme: .foodmenu-unit-info .ingredients
	// ------------------------------------------------------------------

	/** .ingredients must not use #999 for its text color. */
	public function test_ingredients_color_not_999_in_child(): void {
		// Match only the ingredients rule block to avoid false positives.
		preg_match(
			'/\.foodmenu-unit-info\s+\.ingredients\s*\{([^}]+)\}/s',
			$this->child_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';
		$this->assertNotEmpty( $ruleBlock, '.foodmenu-unit-info .ingredients rule not found in child style.css' );

		$this->assertDoesNotMatchRegularExpression(
			'/color\s*:\s*#999\b/',
			$ruleBlock,
			'.ingredients color must not be #999 (fails WCAG 4.5:1) — C-A11Y-Audit-2026-04-29'
		);
	}

	/** .ingredients must use #5e5e5e for its text color. */
	public function test_ingredients_color_is_5e5e5e_in_child(): void {
		preg_match(
			'/\.foodmenu-unit-info\s+\.ingredients\s*\{([^}]+)\}/s',
			$this->child_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';

		$this->assertMatchesRegularExpression(
			'/color\s*:\s*#5e5e5e\b/',
			$ruleBlock,
			'.ingredients color must be #5e5e5e (4.6:1 WCAG AA) — C-A11Y-Audit-2026-04-29'
		);
	}

	/** Child style.css must carry the audit provenance comment. */
	public function test_child_css_audit_comment_present(): void {
		$this->assertStringContainsString(
			'C-A11Y-Audit-2026-04-29',
			$this->child_css,
			'lafka-child/style.css must carry C-A11Y-Audit-2026-04-29 provenance comment'
		);
	}

	// ------------------------------------------------------------------
	// Theme: sub-menu icon color
	// ------------------------------------------------------------------

	/** #main-menu sub-menu icon color must not be #999. */
	public function test_submenu_icon_color_not_999_in_theme(): void {
		preg_match(
			'/#main-menu\s+li\s+ul\.sub-menu\s+li\s+a\s+i\s*,\s*#main-menu\s+li\s+ul\.sub-menu\s+li\s+a\s+i::before\s*\{([^}]+)\}/s',
			$this->theme_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';
		$this->assertNotEmpty( $ruleBlock, 'Sub-menu icon rule not found in theme style.css' );

		$this->assertDoesNotMatchRegularExpression(
			'/color\s*:\s*#999\b/',
			$ruleBlock,
			'Sub-menu icon color must not be #999 (fails WCAG 4.5:1) — C-A11Y-Audit-2026-04-29'
		);
	}

	/** #main-menu sub-menu icon color must be #5e5e5e. */
	public function test_submenu_icon_color_is_5e5e5e_in_theme(): void {
		preg_match(
			'/#main-menu\s+li\s+ul\.sub-menu\s+li\s+a\s+i\s*,\s*#main-menu\s+li\s+ul\.sub-menu\s+li\s+a\s+i::before\s*\{([^}]+)\}/s',
			$this->theme_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';

		$this->assertMatchesRegularExpression(
			'/color\s*:\s*#5e5e5e\b/',
			$ruleBlock,
			'Sub-menu icon color must be #5e5e5e (4.6:1 WCAG AA) — C-A11Y-Audit-2026-04-29'
		);
	}

	// ------------------------------------------------------------------
	// Theme: lafka-foodmenu-light weight badge
	// ------------------------------------------------------------------

	/** lafka-foodmenu-light weight badge must not use #999. */
	public function test_foodmenu_light_weight_badge_not_999(): void {
		preg_match(
			'/\.lafka-foodmenu-light\s+\.foodmenu-unit-info\s+h4\s*>\s*span\.lafka-item-weight-list\s*\{([^}]+)\}/s',
			$this->theme_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';
		$this->assertNotEmpty( $ruleBlock, 'lafka-foodmenu-light weight badge rule not found in theme style.css' );

		$this->assertDoesNotMatchRegularExpression(
			'/color\s*:\s*#999\b/',
			$ruleBlock,
			'lafka-foodmenu-light weight badge color must not be #999 — C-A11Y-Audit-2026-04-29'
		);
	}

	/** lafka-foodmenu-light weight badge must use #5e5e5e. */
	public function test_foodmenu_light_weight_badge_is_5e5e5e(): void {
		preg_match(
			'/\.lafka-foodmenu-light\s+\.foodmenu-unit-info\s+h4\s*>\s*span\.lafka-item-weight-list\s*\{([^}]+)\}/s',
			$this->theme_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';

		$this->assertMatchesRegularExpression(
			'/color\s*:\s*#5e5e5e\b/',
			$ruleBlock,
			'lafka-foodmenu-light weight badge color must be #5e5e5e — C-A11Y-Audit-2026-04-29'
		);
	}

	// ------------------------------------------------------------------
	// Theme: audit provenance
	// ------------------------------------------------------------------

	/** Theme style.css must carry audit provenance comments on changed rules. */
	public function test_theme_css_audit_comment_present(): void {
		$this->assertStringContainsString(
			'C-A11Y-Audit-2026-04-29',
			$this->theme_css,
			'lafka-theme/style.css must carry C-A11Y-Audit-2026-04-29 provenance comments'
		);
	}

	// ------------------------------------------------------------------
	// Theme: video_controlls hover — exempted (dark bg)
	// ------------------------------------------------------------------

	/** .video_controlls a:hover must carry the large-text / dark-bg exemption comment. */
	public function test_video_controlls_hover_has_exemption_comment(): void {
		preg_match(
			'/\.video_controlls\s+a:hover\s*\{([^}]+)\}/s',
			$this->theme_css,
			$m
		);
		$ruleBlock = $m[1] ?? '';
		$this->assertNotEmpty( $ruleBlock, '.video_controlls a:hover rule not found in theme style.css' );

		$this->assertStringContainsString(
			'C-A11Y-Audit-2026-04-29',
			$ruleBlock,
			'.video_controlls a:hover must carry the audit exemption comment'
		);
	}
}
