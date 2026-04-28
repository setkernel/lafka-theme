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

    public function test_child_functions_registers_image_dimensions_filter(): void {
        $fns = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/functions.php' );
        $this->assertStringContainsString( 'lafka_inject_image_dimensions', $fns );
        $this->assertMatchesRegularExpression(
            "/add_filter\(\s*['\"]the_content['\"]\s*,\s*['\"]lafka_inject_image_dimensions['\"]/",
            $fns
        );
    }

    public function test_image_dimensions_helper_uses_local_path_only(): void {
        // Defensive: getimagesize() must not be invoked on remote URLs (would
        // hit the network on every page render).
        $fns = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/functions.php' );
        $this->assertStringContainsString( 'lafka_url_to_local_path', $fns );
        $this->assertStringContainsString( 'wp_get_upload_dir', $fns );
    }

    public function test_rubik_font_display_optional_still_present(): void {
        // Sanity: confirm W1-T10 self-hosted Rubik @font-face still uses font-display: optional
        $css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-theme/style.css' );
        $occurrences = preg_match_all( '/@font-face[^}]*font-family:\s*[\'"]Rubik[\'"][^}]*font-display:\s*optional/s', $css );
        $this->assertGreaterThanOrEqual( 3, $occurrences,
            'Rubik @font-face blocks must keep font-display: optional (P6-PERF-3 + P6-PERF-2 dependency)' );
    }
}
