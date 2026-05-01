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

	// ─── header.php assertions ──────────────────────────────────────────────
	//
	// The child-side filter callback (originally tested below in tests 4-7)
	// moved to lafka-plugin v9.7.25 (incl/perf/lcp-preload.php) as part of
	// the v5.16.0/v6.0.0 child→parent split. The plugin's LcpPreloadTest
	// now covers those assertions on the canonical home.

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

}
