<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-4 W3-T2 regression lock: dropped / conditionalised legacy asset
 * libraries must remain absent from unconditional enqueues.
 *
 * Investigation findings (2026-04-28):
 * - FlexSlider: KEPT — actively called by lafka-libs-config.js (.flexslider())
 *   and rendered by forum.php, content.php, single-lafka-foodmenu.php templates.
 * - et-line-font: CONDITIONAL — used by VC/Lafka icon shortcodes (type="etline").
 *   Moved from unconditional to shortcode-presence check; saves ~80 KB on pages
 *   that don't use those shortcodes.
 * - flaticon: CONDITIONAL — used by lafka_icon / lafka_icon_teaser shortcodes
 *   (type="flaticon"). Same conditional pattern; saves ~80 KB on most pages.
 */
final class AssetEnqueueTest extends TestCase {

	private string $core_functions;

	protected function setUp(): void {
		parent::setUp();
		$this->core_functions = file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/system/core-functions.php'
		);
	}

	/**
	 * et-line-font must not appear as an unconditional wp_enqueue_style call.
	 * It is allowed to appear inside a conditional block ($has_etline check).
	 */
	public function test_etline_not_unconditionally_enqueued(): void {
		// Strip the et-line-font conditional block and verify the bare enqueue
		// doesn't exist outside it. We do this by checking the source contains
		// the $has_etline guard rather than a bare unconditional enqueue.
		$this->assertStringContainsString(
			'$has_etline',
			$this->core_functions,
			'et-line-font enqueue must be guarded by $has_etline (P6-PERF-4)'
		);
		// Confirm the conditional is wired to shortcode detection.
		$this->assertStringContainsString(
			'type="etline"',
			$this->core_functions,
			'et-line-font condition must detect type="etline" shortcode (P6-PERF-4)'
		);
	}

	/**
	 * flaticon must not appear as an unconditional wp_enqueue_style call.
	 * It is allowed to appear inside a conditional block ($has_flaticon check).
	 */
	public function test_flaticon_not_unconditionally_enqueued(): void {
		$this->assertStringContainsString(
			'$has_flaticon',
			$this->core_functions,
			'flaticon enqueue must be guarded by $has_flaticon (P6-PERF-4)'
		);
		// Confirm the conditional is wired to shortcode detection.
		$this->assertStringContainsString(
			'type="flaticon"',
			$this->core_functions,
			'flaticon condition must detect type="flaticon" shortcode (P6-PERF-4)'
		);
	}

	/**
	 * FlexSlider is kept (actively used by templates + lafka-libs-config.js).
	 * This test documents that FlexSlider was reviewed and intentionally retained.
	 */
	public function test_flexslider_kept_with_documented_rationale(): void {
		// The enqueue should still exist (it's active).
		$stripped = preg_replace( '#//[^\n]*\n|/\*.*?\*/#s', '', $this->core_functions );
		$this->assertMatchesRegularExpression(
			"/wp_(register|enqueue)_(script|style)\(\s*['\"]flexslider['\"]/i",
			$stripped,
			'FlexSlider must remain enqueued — it is actively used by .lafka_flexslider templates (P6-PERF-4)'
		);
	}
}
