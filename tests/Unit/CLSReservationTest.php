<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-2 W3-T1 regression lock: CSS reservations + image-dimension
 * filter must remain in place to prevent CLS regressions.
 */
final class CLSReservationTest extends TestCase {

    public function test_child_css_reserves_owl_carousel_aspect_ratio(): void {
        $css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/style.css' );
        $this->assertMatchesRegularExpression(
            '/\.lafka-owl-carousel:not\(\.owl-loaded\)[^}]*aspect-ratio/s',
            $css,
            'lafka-child/style.css must reserve aspect-ratio on .lafka-owl-carousel pre-mount'
        );
    }

    public function test_child_css_reserves_content_slider_aspect_ratio(): void {
        $css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/style.css' );
        $this->assertMatchesRegularExpression(
            '/\[id\^="lafka_content_slider"\]:not\(\.owl-loaded\)[^}]*aspect-ratio/s',
            $css
        );
    }

    public function test_child_css_reserves_revslider_aspect_ratio(): void {
        $css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/style.css' );
        // Either rs-module-wrap or rev_slider_wrapper variant
        $this->assertMatchesRegularExpression(
            '/(rs-module-wrap|rev_slider_wrapper)[^}]*aspect-ratio/s',
            $css
        );
    }

    // image-dimensions filter + helper moved to lafka-plugin v9.7.25
    // (incl/perf/image-dimensions.php). The plugin's ImageDimensionsTest
    // covers those assertions on the canonical home — no need to test
    // them from this theme test class.

    public function test_rubik_font_display_optional_still_present(): void {
        // Sanity: confirm W1-T10 self-hosted Rubik @font-face still uses font-display: optional.
        // Quotes around 'Rubik' are optional in CSS — regex allows both forms.
        $css = file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
        $occurrences = preg_match_all( '/@font-face[^}]*font-family:\s*[\'"]?Rubik[\'"]?[^}]*font-display:\s*optional/s', $css );
        $this->assertGreaterThanOrEqual( 3, $occurrences,
            'Rubik @font-face blocks must keep font-display: optional (P6-PERF-3 + P6-PERF-2 dependency)' );
    }
}
