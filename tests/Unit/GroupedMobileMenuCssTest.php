<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Audit 2026-06-28 (f022) regression lock: the grouped-mobile-menu feature is a
 * first-class product feature — the Customizer toggle lives in the parent theme
 * and the .lafka-mobile-menu-group* markup is emitted by the lafka-plugin
 * walker. Its CSS used to live ONLY in the private lafka-child, so the OSS
 * bundle (parent + plugin) rendered bare <h4>/<ul> when an operator turned
 * grouping on. The plugin emits markup; the theme owns appearance — so the
 * styling must ship in the PARENT baseline, tokenised from --lafka-*.
 */
final class GroupedMobileMenuCssTest extends TestCase {
	private string $css;

	protected function setUp(): void {
		parent::setUp();
		$this->css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-base.css' );
	}

	public function test_parent_baseline_styles_grouped_menu_selectors(): void {
		foreach ( array(
			'.lafka-mobile-menu-group',
			'.lafka-mobile-menu-group-label',
			'.lafka-mobile-menu-group-items',
		) as $selector ) {
			$this->assertStringContainsString(
				$selector . ' {',
				$this->css,
				"lafka-theme/styles/lafka-base.css must style {$selector} (plugin emits the markup, theme owns appearance)."
			);
		}
	}
}
