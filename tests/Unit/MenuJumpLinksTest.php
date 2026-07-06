<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Audit V3 regression lock: the /menu/ page shipped TWO category navigations —
 * the "Jump to" anchor strip (.lafka-menu__toc) AND the filter-pill strip
 * (.lafka-menu__cats). Both are in-page category anchor lists, so they are
 * duplicates.
 *
 * Decision (roadmap NX1-08e): the filter-pill strip is the single canonical
 * category nav. The duplicate jump-to anchor strip must NOT render server-side
 * by default (a conditional, not CSS hiding). An operator can re-enable the
 * extra in-page TOC with:
 *
 *   add_filter( 'lafka_menu_show_jump_links', '__return_true' );
 *
 * This test locks the conditional and the default-off filter on page-menu.php.
 */
final class MenuJumpLinksTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		$this->src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/page-menu.php' );
	}

	public function test_jump_links_gated_behind_filter_default_off(): void {
		$this->assertStringContainsString(
			"apply_filters( 'lafka_menu_show_jump_links', false )",
			$this->src,
			'The jump-to anchor strip must be gated behind the lafka_menu_show_jump_links filter, defaulting OFF (pills are canonical).'
		);
	}

	public function test_gate_precedes_the_jump_to_toc_nav(): void {
		$filter_pos = strpos( $this->src, 'lafka_menu_show_jump_links' );
		$toc_pos    = strpos( $this->src, 'class="lafka-menu__toc"' );
		$this->assertNotFalse( $filter_pos, 'The lafka_menu_show_jump_links gate must be present.' );
		$this->assertNotFalse( $toc_pos, 'The jump-to TOC nav must still exist (behind the filter).' );
		$this->assertLessThan(
			$toc_pos,
			$filter_pos,
			'The lafka_menu_show_jump_links gate must precede (wrap) the jump-to TOC nav.'
		);
	}

	public function test_gate_closes_before_canonical_pill_nav(): void {
		$toc_pos  = strpos( $this->src, 'class="lafka-menu__toc"' );
		$cats_pos = strpos( $this->src, 'class="lafka-menu__cats"' );
		$this->assertNotFalse( $toc_pos );
		$this->assertNotFalse( $cats_pos, 'The canonical filter-pill nav (lafka-menu__cats) must remain.' );
		$this->assertLessThan(
			$cats_pos,
			$toc_pos,
			'The (now-optional) jump-to TOC must sit above the canonical filter-pill nav.'
		);
		$between = substr( $this->src, $toc_pos, $cats_pos - $toc_pos );
		$this->assertStringContainsString(
			'endif;',
			$between,
			'The jump-links gate must close (endif) before the canonical filter-pill nav so the pills ALWAYS render.'
		);
	}

	public function test_canonical_pill_nav_is_not_gated_by_the_filter(): void {
		// The filter must appear exactly once (guarding the TOC only) — the pill
		// strip below it is unconditional.
		$this->assertSame(
			1,
			substr_count( $this->src, "apply_filters( 'lafka_menu_show_jump_links'" ),
			'The lafka_menu_show_jump_links filter must gate ONLY the jump-to TOC, never the canonical pill nav.'
		);
	}
}
