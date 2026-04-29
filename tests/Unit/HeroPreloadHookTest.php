<?php
/**
 * Source-grep test for P6-PERF-1: LCP hero preload hook in header.php.
 *
 * Verifies that:
 *   1. header.php calls apply_filters( 'lafka_lcp_image_url', '' )
 *   2. header.php emits the <link rel="preload" as="image" fetchpriority="high"> tag
 *      conditionally (guarded by the filter return value check).
 *   3. The child-theme filter callback wires up lafka_lcp_image_url and the
 *      wp_get_attachment_image_attributes filter.
 *
 * No WP runtime is needed — source-grep gives us 90% of the lock value at 0%
 * of the WP-bootstrap cost (same pattern as ThemeRegistrationsTest).
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HeroPreloadHookTest extends TestCase {

	private const HEADER_PHP     = __DIR__ . '/../../header.php';
	private const CHILD_FUNCS    = __DIR__ . '/../../../lafka-child/functions.php';

	// ─── header.php assertions ──────────────────────────────────────────────

	public function test_header_applies_lcp_image_filter(): void {
		$src = file_get_contents( self::HEADER_PHP );
		self::assertNotFalse( $src, 'header.php unreadable' );
		self::assertStringContainsString(
			"apply_filters( 'lafka_lcp_image_url', '' )",
			$src,
			'header.php must call apply_filters( \'lafka_lcp_image_url\', \'\' ) — P6-PERF-1'
		);
	}

	public function test_header_emits_preload_link(): void {
		$src = file_get_contents( self::HEADER_PHP );
		self::assertNotFalse( $src, 'header.php unreadable' );
		self::assertStringContainsString(
			'rel="preload" as="image" fetchpriority="high"',
			$src,
			'header.php must contain the preload <link> with fetchpriority="high" — P6-PERF-1'
		);
	}

	public function test_header_guards_preload_on_filter_return(): void {
		$src = file_get_contents( self::HEADER_PHP );
		self::assertNotFalse( $src, 'header.php unreadable' );
		// The if-block must exist so empty-string returns are a no-op.
		self::assertMatchesRegularExpression(
			'/if\s*\(\s*\$lafka_lcp_image\s*\)/',
			$src,
			'header.php must guard the preload link with if ( $lafka_lcp_image ) — P6-PERF-1'
		);
	}

	// ─── child theme assertions ──────────────────────────────────────────────

	public function test_child_registers_lcp_image_filter(): void {
		$src = $this->read_child_functions();
		self::assertStringContainsString(
			"add_filter( 'lafka_lcp_image_url'",
			$src,
			'lafka-child/functions.php must register the lafka_lcp_image_url filter — P6-PERF-1'
		);
	}

	public function test_child_returns_hero_url_on_front_page(): void {
		$src = $this->read_child_functions();
		self::assertStringContainsString(
			'is_front_page()',
			$src,
			'lafka-child/functions.php must gate the hero URL on is_front_page() — P6-PERF-1'
		);
		// W2-T1: hero URL now comes from Customizer (lafka_homepage_hero_image),
		// not a hardcoded literal. Verify the filter pulls from get_theme_mod().
		self::assertStringContainsString(
			"get_theme_mod( 'lafka_homepage_hero_image'",
			$src,
			'lafka-child/functions.php must read the hero image from the lafka_homepage_hero_image Customizer setting — P6-PERF-1 / W2-T1'
		);
	}

	public function test_child_registers_attachment_attributes_filter(): void {
		$src = $this->read_child_functions();
		self::assertStringContainsString(
			"add_filter( 'wp_get_attachment_image_attributes'",
			$src,
			'lafka-child/functions.php must register wp_get_attachment_image_attributes filter — P6-PERF-1'
		);
	}

	public function test_child_sets_fetchpriority_high_on_hero(): void {
		$src = $this->read_child_functions();
		// Match the actual array-assignment syntax: $attr['fetchpriority'] = 'high'
		self::assertMatchesRegularExpression(
			"/\\\$attr\\['fetchpriority'\\]\\s*=\\s*'high'/",
			$src,
			"lafka-child/functions.php must set \$attr['fetchpriority'] = 'high' — P6-PERF-1"
		);
	}

	// ─── helpers ────────────────────────────────────────────────────────────

	private function read_child_functions(): string {
		if ( ! file_exists( self::CHILD_FUNCS ) ) {
			self::markTestSkipped( 'lafka-child/functions.php not found — run from monorepo root.' );
		}
		$src = file_get_contents( self::CHILD_FUNCS );
		self::assertNotFalse( $src, 'lafka-child/functions.php unreadable' );
		return $src;
	}
}
