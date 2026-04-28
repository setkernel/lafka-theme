<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-A11Y-1 regression lock: Owl Carousel navText must use screen-reader-text
 * labels (not bare FontAwesome icons), and dots must have aria-label, and
 * nav buttons must not have role="presentation".
 */
final class OwlCarouselA11yTest extends TestCase {

    public function test_owl_navtext_uses_screen_reader_text(): void {
        $js = file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-libs-config.js' );
        // At least 3 navText blocks must contain "Previous slide" sr-only label
        $count = preg_match_all( '/screen-reader-text">Previous slide</', $js );
        $this->assertGreaterThanOrEqual( 3, $count, 'Expected ≥3 navText entries with sr labels' );

        $count_next = preg_match_all( '/screen-reader-text">Next slide</', $js );
        $this->assertGreaterThanOrEqual( 3, $count_next );
    }

    public function test_owl_navtext_does_not_emit_bare_icons(): void {
        $js = file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-libs-config.js' );
        // The audit's failing pattern: navText array containing only an <i> tag
        // with fa-angle-left, no surrounding sr-only.
        $this->assertDoesNotMatchRegularExpression(
            '/navText:\s*\[\s*"<i class=\'fas fa-angle-left\'><\/i>"/',
            $js
        );
    }

    public function test_screen_reader_text_helper_defined(): void {
        $child_css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/style.css' );
        $this->assertMatchesRegularExpression(
            '/\.screen-reader-text\s*\{/',
            $child_css,
            'lafka-child/style.css must define .screen-reader-text helper'
        );
    }

    public function test_content_slider_navtext_uses_screen_reader_text(): void {
        $php = file_get_contents( dirname( __DIR__, 2 ) . '/vc_templates/vc_lafka_content_slider.php' );
        $this->assertMatchesRegularExpression(
            '/screen-reader-text.*Previous slide/',
            $php,
            'vc_lafka_content_slider.php navText must use sr-only label for previous'
        );
        $this->assertMatchesRegularExpression(
            '/screen-reader-text.*Next slide/',
            $php,
            'vc_lafka_content_slider.php navText must use sr-only label for next'
        );
    }

    public function test_content_slider_dots_get_aria_labels(): void {
        $php = file_get_contents( dirname( __DIR__, 2 ) . '/vc_templates/vc_lafka_content_slider.php' );
        $this->assertMatchesRegularExpression(
            '/aria-label.*Go to slide/',
            $php,
            'vc_lafka_content_slider.php must add aria-label to owl-dot elements'
        );
    }

    public function test_content_slider_removes_presentation_role(): void {
        $php = file_get_contents( dirname( __DIR__, 2 ) . '/vc_templates/vc_lafka_content_slider.php' );
        $this->assertMatchesRegularExpression(
            '/removeAttr\("role"\)/',
            $php,
            'vc_lafka_content_slider.php must remove role="presentation" from nav buttons'
        );
    }
}
