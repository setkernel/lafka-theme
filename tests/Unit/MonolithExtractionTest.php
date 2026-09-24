<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * NX1-10a monolith-teardown structural lock.
 *
 * The pixel proof for the teardown is the LOCAL, gitignored visual harness
 * (tests/visual/nx1-10a.spec.js), which does not run in CI. This test is the
 * committed, CI-visible half: it locks the shape of the split so a later change
 * can't silently fold a legacy surface back into the always-on style.css or
 * strand a scoped sheet.
 *
 * It asserts:
 *   - each styles/legacy-*.css exists, is @layer-legacy wrapped, brace-balanced,
 *     and carries a substantial slice of the monolith;
 *   - each namespaced bucket's signature (bbPress, tribe, blog layout) is
 *     present in its scoped sheet (compound rules mixing a legacy selector with
 *     generic ones stay in style.css by design — the extractor only moves rules
 *     whose selectors ALL belong to one bucket, so style.css shrinkage is locked
 *     by AssetBudgetTest, not by absence assertions here);
 *   - comment/review CSS (#reviews, .commentlist) DELIBERATELY stays in
 *     style.css — it also styles WooCommerce product reviews on the handoff PDP,
 *     which never loads legacy-blog.css.
 */
final class MonolithExtractionTest extends TestCase {
	private static function root(): string {
		return dirname( __DIR__, 2 );
	}

	private static function read( string $relative ): string {
		return (string) file_get_contents( self::root() . '/' . $relative );
	}

	/**
	 * @return array<string,array{0:string,1:int}>
	 */
	public static function legacySheetProvider(): array {
		// sheet => minimum expected size (bytes) — a substantial slice moved.
		return array(
			'blog'       => array( 'styles/legacy-blog.css', 30000 ),
			'forum'      => array( 'styles/legacy-forum.css', 1000 ),
			'events'     => array( 'styles/legacy-events.css', 1000 ),
			'shortcodes' => array( 'styles/legacy-shortcodes.css', 10000 ),
		);
	}

	#[DataProvider( 'legacySheetProvider' )]
	public function test_legacy_sheet_is_layer_wrapped_and_balanced( string $relative, int $minBytes ): void {
		$path = self::root() . '/' . $relative;
		$this->assertFileExists( $path, "Extracted sheet missing: {$relative}" );
		$css = self::read( $relative );
		$this->assertStringContainsString(
			'@layer legacy {',
			$css,
			"{$relative} must stay in @layer legacy so it sits below the modular sheets."
		);
		$this->assertSame(
			substr_count( $css, '{' ),
			substr_count( $css, '}' ),
			"{$relative} has unbalanced braces."
		);
		$this->assertGreaterThan(
			$minBytes,
			strlen( $css ),
			"{$relative} is smaller than expected — did the extraction regress?"
		);
	}

	/**
	 * Comment/review styling is cross-cutting: the same monolith rules style
	 * WooCommerce product reviews on the handoff PDP (which never loads
	 * legacy-blog.css), so they must stay in the site-wide style.css.
	 */
	public function test_comment_and_review_css_stays_site_wide(): void {
		$style = self::read( 'style.css' );
		$this->assertStringContainsString( '#reviews', $style, 'Product-review CSS must stay in style.css.' );
		$this->assertStringContainsString( '.commentlist', $style, 'Comment-list CSS must stay in style.css.' );
	}

	/**
	 * Countdown-widget styling is cross-cutting too: the same monolith rules
	 * render on handoff surfaces — the on-sale product "Offer ends in" countdown
	 * (woocommerce-functions.php) on PDP/shop and the store-closed card's
	 * count_holder_small digits on PDP — so it must stay site-wide (NOT in the
	 * shortcode sheet, which those surfaces never load).
	 */
	public function test_countdown_widget_css_stays_site_wide(): void {
		$style      = self::read( 'style.css' );
		$shortcodes = self::read( 'styles/legacy-shortcodes.css' );
		$this->assertStringContainsString( '.count_holder', $style, 'Product/store-closed countdown CSS must stay in style.css.' );
		$this->assertStringNotContainsString( '.count_holder', $shortcodes, 'Countdown CSS must not be gated behind the shortcode sheet.' );
	}
}
