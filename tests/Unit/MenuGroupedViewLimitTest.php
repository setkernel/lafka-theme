<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Audit 2026-06-28 (f054) regression lock for the grouped "All" menu view.
 *
 * Two surfaces render the menu grouped by category:
 *   - woocommerce/archive-product.php (the WC shop archive)
 *   - page-menu.php (the /menu/ page route)
 *
 * Both used to query each group with a hardcoded 'limit' => 24, silently
 * hiding any items beyond 24 while the category chip still advertised the
 * full term count — with no pagination and no link to reach the rest. The
 * archive template additionally listed child product_cat terms as their own
 * sections (no 'parent' filter), so a product in a parent + child category
 * rendered twice and the two surfaces showed different category sets.
 *
 * The fix: archive lists top-level terms only ('parent' => 0, matching
 * page-menu.php); the per-group cap is operator-configurable
 * (Customizer mod + filter); the query is paginated so the true total is
 * known; and a "See all N items" link to the full category archive appears
 * whenever the cap truncates a group, keeping every item reachable.
 */
final class MenuGroupedViewLimitTest extends TestCase {

	private static function theme_root(): string {
		return dirname( __DIR__, 2 );
	}

	private function source( string $relative ): string {
		$path = self::theme_root() . '/' . $relative;
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Both grouped-menu surfaces, keyed for readable failure output.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function grouped_templates(): array {
		return array(
			'archive-product' => array( 'woocommerce/archive-product.php' ),
			'page-menu'        => array( 'page-menu.php' ),
		);
	}

	public function test_archive_lists_top_level_categories_only(): void {
		$src = $this->source( 'woocommerce/archive-product.php' );
		$this->assertMatchesRegularExpression(
			"/'parent'\s*=>\s*0/",
			$src,
			"archive-product.php must pass 'parent' => 0 to get_terms() so it lists top-level sections only (mirroring page-menu.php) and does not double-render nested products."
		);
	}

	#[DataProvider('grouped_templates')]
	public function test_per_group_cap_is_not_hardcoded_24( string $relative ): void {
		$src = $this->source( $relative );
		$this->assertDoesNotMatchRegularExpression(
			"/'limit'\s*=>\s*24\b/",
			$src,
			"{$relative} must not hardcode 'limit' => 24 — the per-group cap is operator-configurable."
		);
	}

	#[DataProvider('grouped_templates')]
	public function test_per_group_cap_is_operator_configurable( string $relative ): void {
		$src = $this->source( $relative );
		$this->assertStringContainsString(
			"get_theme_mod( 'lafka_menu_group_limit'",
			$src,
			"{$relative} must read the per-group cap from the lafka_menu_group_limit Customizer mod."
		);
		$this->assertStringContainsString(
			"apply_filters( 'lafka_menu_group_limit'",
			$src,
			"{$relative} must expose the lafka_menu_group_limit filter for per-category overrides."
		);
	}

	#[DataProvider('grouped_templates')]
	public function test_group_query_is_paginated_for_accurate_total( string $relative ): void {
		$src = $this->source( $relative );
		$this->assertStringContainsString(
			"'paginate' => true",
			$src,
			"{$relative} must use a paginated wc_get_products() query so the true category total is known regardless of the cap."
		);
	}

	#[DataProvider('grouped_templates')]
	public function test_truncated_group_links_out_to_full_category( string $relative ): void {
		$src = $this->source( $relative );
		$this->assertStringContainsString(
			'lafka-menu__group-all',
			$src,
			"{$relative} must render a 'See all' link element so items beyond the cap stay reachable."
		);
		$this->assertStringContainsString(
			"esc_html__( 'See all %s items', 'lafka' )",
			$src,
			"{$relative} must use the translatable 'See all %s items' label in the 'lafka' text domain."
		);
		$this->assertStringContainsString(
			'get_term_link(',
			$src,
			"{$relative} 'See all' link must point at the full (paginated) category archive via get_term_link()."
		);
	}

	#[DataProvider('grouped_templates')]
	public function test_see_all_is_gated_on_total_exceeding_rendered( string $relative ): void {
		$src = $this->source( $relative );
		$this->assertMatchesRegularExpression(
			'/\$lafka_(arch|menu)_group_total > count\( \$lafka_(arch|menu)_group_products \)/',
			$src,
			"{$relative} must only render the 'See all' link when the category total exceeds the rendered count."
		);
	}
}
