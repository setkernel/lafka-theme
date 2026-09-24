<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-2 W3-T1 regression lock: CSS reservations + image-dimension
 * filter must remain in place to prevent CLS regressions.
 */
final class CLSReservationTest extends TestCase {

    /** Read the sibling child stylesheet, or skip if it isn't checked out. */
    private function child_css(): string {
        $child_css = dirname( __DIR__, 3 ) . '/lafka-child/style.css';
        if ( ! file_exists( $child_css ) ) {
            $this->markTestSkipped( 'Sibling lafka-child repo not checked out (isolated CI); local dev only.' );
        }
        return file_get_contents( $child_css );
    }

    public function test_owl_carousel_aspect_ratio_reserved_in_parent(): void {
        // Audit 2026-06-27 #6: the .lafka-owl-carousel reservation moved from
        // lafka-child into the PARENT (styles/lafka-base.css) — the parent emits
        // the carousels. Assert against the parent's own CSS (always present, so
        // this no longer skips in isolated CI).
        $css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-base.css' );
        $this->assertMatchesRegularExpression(
            '/\.lafka-owl-carousel:not\(\.owl-loaded\)[^}]*aspect-ratio/s',
            $css,
            'styles/lafka-base.css must reserve aspect-ratio on .lafka-owl-carousel pre-mount'
        );
    }

    // image-dimensions filter + helper moved to lafka-plugin v9.7.25
    // (incl/perf/image-dimensions.php). The plugin's ImageDimensionsTest
    // covers those assertions on the canonical home — no need to test
    // them from this theme test class.
}
