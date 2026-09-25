<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX1-10c asset budget: a ratchet against silent CSS-payload regressions.
 *
 * Sums the SOURCE (un-minified) byte size of the first-party stylesheets that a
 * default install serves on a typical front page and fails if the total exceeds
 * a fixed budget with ~10% headroom. If a change grows the always-on CSS past
 * the budget, this test fails and forces a deliberate decision (trim, split, or
 * re-baseline the budget) rather than letting the front-page payload creep.
 *
 * The list is DERIVED from the enqueue code in
 * incl/system/core-functions.php::lafka_enqueue_scripts_and_styles() — every
 * entry is annotated with the enqueue site and why it is always-on for the
 * front page of a default install. Only stylesheets with no operator toggle and
 * no WooCommerce/page-type gate are counted (plus the two front-page-only home
 * sheets), so the number is stable.
 *
 * NX1-10a: style.css is now INCLUDED — the monolith was torn down (its
 * blog / forum / events / shortcode remnants extracted into conditionally-
 * enqueued styles/legacy-*.css sheets that the six handoff pages never load),
 * shrinking the always-on root sheet from 348,870 → 279,589 bytes (−69,281 B,
 * −19.9%) on EVERY front-page load. With the monolith no longer a swamping
 * outlier, folding it into the ratchet keeps the true front-page floor honest.
 * The modular-only subtotal is asserted separately so modular growth still has
 * a tight signal.
 *
 * Deliberately EXCLUDED:
 *   - Toggle-gated sheets (sticky-cart, archive-quickadd, exit-intent, …) and
 *     WooCommerce-gated sheets (cart-drawer, …) — present on a default front
 *     page but conditional, so they are not part of the fixed always-on floor.
 *   - The legacy-*.css extraction sheets — gated OFF on the handoff pages.
 *   - Vendored/font CSS (font-awesome, owl, et-line, …) — third-party weight,
 *     not something this budget is meant to police.
 *
 * Baselines recorded 2026-09-24 (after the dead pre-handoff header CSS was
 * pruned from style.css and lafka-responsive.css):
 *   - modular-only (12 sheets)     162,160 B  → MODULAR_BUDGET 175,000 (≈7.9%).
 *   - style.css                    223,205 B  → STYLE_CEILING  232,000 (≈3.9%).
 *   - total always-on front page   385,365 B  → BUDGET_BYTES   400,000 (≈3.8%).
 * Pre-NX1-10a the same front page shipped 506,985 B of always-on first-party
 * CSS; 7.0.0 shipped 444,995 B. Re-baseline intentionally (and bump this docblock)
 * when the always-on set legitimately grows.
 *
 * 2026-09-25 (GX QA, sx2): BUDGET_BYTES 400,000 → 402,000 for the counter
 * home fixes (masonry rest-of-menu, rim-label hero captions, row alignment,
 * landscape bar, one "Order online") — the counter front page measured
 * 400,892 B; page-specific fixes went to the menu / PDP sheets instead.
 */
final class AssetBudgetTest extends TestCase {
	private const BUDGET_BYTES   = 402000;
	private const MODULAR_BUDGET = 175000;
	private const STYLE_CEILING  = 232000;

	/**
	 * Always-on front-page first-party modular stylesheets, relative to the theme
	 * root, each tagged with its enqueue site in core-functions.php. style.css is
	 * tracked separately (self::style_css()).
	 *
	 * @return string[]
	 */
	private static function always_on_modular_stylesheets(): array {
		return array(
			// Site-wide, unconditional (no gate) in lafka_enqueue_scripts_and_styles():
			'styles/lafka-tokens.css',        // enqueued first (design tokens).
			'styles/lafka-base.css',          // parent baseline a11y/CLS rules.
			'styles/lafka-components.css',    // shared component primitives, site-wide.
			'styles/lafka-promo-bar.css',     // site-wide promo bar (wp_body_open).
			'styles/lafka-announce-bar.css',  // site-wide announce bar.
			'styles/lafka-header-chrome.css', // rebuilt header chrome.
			'styles/lafka-mobile-nav.css',    // mobile slide-out nav.
			'styles/lafka-footer-chrome.css', // 4-col dark footer.
			'styles/lafka-notices.css',       // tokenized WC notices, site-wide.

			// Front-page only (is_front_page()):
			'styles/lafka-hero.css',    // home hero section.
			'styles/lafka-home-v2.css', // home sections 2-7.

			// Default-on responsive layer (lafka_get_option('is_responsive')):
			'styles/lafka-responsive.css',
		);
	}

	/**
	 * The always-on root sheet — the shrunk NX1-10a monolith remainder, loaded
	 * site-wide on every front-end request.
	 *
	 * @return string[]
	 */
	private static function always_on_stylesheets(): array {
		return array_merge( self::always_on_modular_stylesheets(), array( 'style.css' ) );
	}

	public function test_front_page_stylesheets_stay_within_budget(): void {
		$root  = dirname( __DIR__, 2 );
		$total = 0;
		$sizes = array();

		foreach ( self::always_on_stylesheets() as $relative ) {
			$path = $root . '/' . $relative;
			$this->assertFileExists(
				$path,
				"Budget stylesheet missing (renamed/removed?): {$relative}"
			);
			$bytes           = (int) filesize( $path );
			$sizes[ $relative ] = $bytes;
			$total          += $bytes;
		}

		$this->assertLessThanOrEqual(
			self::BUDGET_BYTES,
			$total,
			sprintf(
				"Always-on front-page CSS is %d bytes, over the %d-byte budget.\n"
				. "Trim the modular sheets, split them behind a gate, or re-baseline the "
				. "budget deliberately (and update this test's docblock). Per-file bytes:\n%s",
				$total,
				self::BUDGET_BYTES,
				self::format_sizes( $sizes )
			)
		);
	}

	/**
	 * GX4: the counter home SWAPS lafka-hero + lafka-home-v2 for the single
	 * lafka-counter.css (core-functions.php skips the two home sheets on the
	 * counter home), so the counter front page must fit the same budget.
	 */
	public function test_counter_front_page_is_a_swap_not_growth(): void {
		$root  = dirname( __DIR__, 2 );
		$files = array_diff( self::always_on_stylesheets(), array( 'styles/lafka-hero.css', 'styles/lafka-home-v2.css' ) );
		$files[] = 'styles/lafka-counter.css';
		$total   = 0;
		foreach ( $files as $relative ) {
			$this->assertFileExists( $root . '/' . $relative );
			$total += (int) filesize( $root . '/' . $relative );
		}
		$this->assertLessThanOrEqual(
			self::BUDGET_BYTES,
			$total,
			sprintf( 'The counter front page ships %d bytes of CSS, over the %d-byte budget.', $total, self::BUDGET_BYTES )
		);
	}

	/**
	 * The modular-only subtotal keeps its own tight ratchet, so growth in the
	 * hand-authored modular sheets is caught even though the (much larger) shrunk
	 * monolith now sits in the combined budget above.
	 */
	public function test_modular_subtotal_within_budget(): void {
		$root  = dirname( __DIR__, 2 );
		$total = 0;
		foreach ( self::always_on_modular_stylesheets() as $relative ) {
			$total += (int) filesize( $root . '/' . $relative );
		}
		$this->assertLessThanOrEqual(
			self::MODULAR_BUDGET,
			$total,
			sprintf( 'Modular always-on CSS is %d bytes, over the %d-byte modular budget.', $total, self::MODULAR_BUDGET )
		);
	}

	/**
	 * NX1-10a shrink ratchet: the always-on root style.css (monolith remainder)
	 * must stay under the recorded ceiling, so the ~73 KB the teardown removed
	 * from every front page can never silently creep back in.
	 */
	public function test_style_css_within_shrink_ceiling(): void {
		$path  = dirname( __DIR__, 2 ) . '/style.css';
		$bytes = (int) filesize( $path );
		$this->assertLessThanOrEqual(
			self::STYLE_CEILING,
			$bytes,
			sprintf(
				'style.css is %d bytes, over the %d-byte ceiling (was 348,870 before NX1-10a, 279,588 in 7.0.0). '
				. 'New legacy surfaces belong in a scoped styles/legacy-*.css, not back in the monolith.',
				$bytes,
				self::STYLE_CEILING
			)
		);
	}

	/**
	 * @param array<string,int> $sizes
	 */
	private static function format_sizes( array $sizes ): string {
		arsort( $sizes );
		$lines = array();
		foreach ( $sizes as $file => $bytes ) {
			$lines[] = sprintf( '  %7d  %s', $bytes, $file );
		}
		return implode( "\n", $lines );
	}
}
