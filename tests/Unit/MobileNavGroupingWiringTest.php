<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Wiring lock for the grouped mobile categories (P6-UX-6 W3-T10 repair).
 *
 * 2026-07 audit: the plugin's grouped-mobile-menu walker hooked a
 * `lafka_nav_menu_walker` filter the theme never applies and gated on a
 * 'mobile' theme_location the v5.55 header rebuild removed — so the live
 * "Group categories on mobile" Customizer toggle did nothing. The repaired
 * contract: partials/mobile-nav.php consults the toggle and renders
 * LafkaMobileGroupedWalker::group_terms() buckets for its Categories section
 * using the .lafka-mobile-menu-group* classes lafka-base.css already styles
 * (locked by GroupedMobileMenuCssTest).
 */
final class MobileNavGroupingWiringTest extends TestCase {

	private string $partial;

	protected function setUp(): void {
		parent::setUp();
		$this->partial = (string) file_get_contents( dirname( __DIR__, 2 ) . '/partials/mobile-nav.php' );
	}

	public function test_partial_consults_the_customizer_toggle(): void {
		$this->assertStringContainsString(
			"get_theme_mod( 'lafka_mobile_menu_grouping', 'no' )",
			$this->partial,
			'The drawer must read the same toggle the Customizer registers (default no).'
		);
	}

	public function test_partial_delegates_grouping_to_the_plugin_helper(): void {
		$this->assertStringContainsString(
			'LafkaMobileGroupedWalker::group_terms(',
			$this->partial,
			'Grouping heuristics live in the plugin — the theme must delegate, not duplicate.'
		);
		$this->assertStringContainsString(
			"class_exists( 'LafkaMobileGroupedWalker' )",
			$this->partial,
			'The theme must degrade to the flat list when the plugin is absent.'
		);
	}

	public function test_partial_emits_the_styled_group_classes(): void {
		foreach ( array( 'lafka-mobile-menu-group', 'lafka-mobile-menu-group-label', 'lafka-mobile-menu-group-items' ) as $class ) {
			$this->assertStringContainsString(
				$class,
				$this->partial,
				"Grouped markup must use the {$class} class lafka-base.css styles."
			);
		}
	}

	public function test_flat_list_remains_the_default_branch(): void {
		$this->assertGreaterThanOrEqual(
			2,
			substr_count( $this->partial, 'lafka-mobile-nav__count' ),
			'Both the grouped and the default flat branch must render the count pill.'
		);
	}
}
