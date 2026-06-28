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

	public function test_grouped_menu_uses_design_tokens(): void {
		foreach ( array(
			'var(--lafka-color-text-muted)',
			'var(--lafka-color-text-primary)',
			'var(--lafka-color-accent-text)',
			'var(--lafka-color-surface-muted)',
			'var(--lafka-color-border-subtle)',
		) as $token ) {
			$this->assertStringContainsString(
				$token,
				$this->css,
				"Grouped mobile menu must resolve colours from {$token}, not a hardcoded hex."
			);
		}
	}

	public function test_grouped_menu_drops_the_old_hardcoded_hex(): void {
		// These hexes were the stranded lafka-child values; they must not survive
		// the migration into the tokenised parent baseline.
		foreach ( array( '#1a1a1a', '#f8f4ee', '#e4584b' ) as $hex ) {
			$this->assertStringNotContainsString(
				$hex,
				$this->css,
				"lafka-base.css must not carry the old hardcoded grouped-menu hex {$hex}."
			);
		}
	}
}
