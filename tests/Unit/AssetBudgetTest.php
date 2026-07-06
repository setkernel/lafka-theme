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
 * Deliberately EXCLUDED:
 *   - style.css — the 349 KB legacy monolith is a separate teardown effort
 *     (NX1-10a, out of scope for this wave); it would swamp the ratchet and
 *     make the ~10% headroom useless for catching modular-CSS growth.
 *   - Toggle-gated sheets (sticky-cart, archive-quickadd, exit-intent, …) and
 *     WooCommerce-gated sheets (cart-drawer, …) — present on a default front
 *     page but conditional, so they are not part of the fixed always-on floor.
 *   - Vendored/font CSS (font-awesome, owl, et-line, …) — third-party weight,
 *     not something this budget is meant to police.
 *
 * Baseline recorded 2026-07-06 (theme 6.19.0): the 12 sheets below total
 * 158,115 bytes. Budget = 175,000 bytes (≈10.7% headroom). Re-baseline
 * intentionally (and bump this docblock) when the always-on set legitimately
 * grows — e.g. once NX1-10a folds monolith remnants into scoped modular files.
 */
final class AssetBudgetTest extends TestCase {

	private const BUDGET_BYTES = 175000;

	/**
	 * Always-on front-page first-party stylesheets, relative to the theme root,
	 * each tagged with its enqueue site in core-functions.php.
	 *
	 * @return string[]
	 */
	private static function always_on_stylesheets(): array {
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
	 * The recorded baseline must keep meaningful headroom under the budget — if
	 * the two ever meet, the ratchet has stopped ratcheting and needs a reset.
	 */
	public function test_budget_keeps_headroom_over_current_size(): void {
		$root  = dirname( __DIR__, 2 );
		$total = 0;
		foreach ( self::always_on_stylesheets() as $relative ) {
			$total += (int) filesize( $root . '/' . $relative );
		}

		$this->assertGreaterThan(
			$total,
			self::BUDGET_BYTES,
			'Budget must sit above the current always-on total (headroom exhausted).'
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
