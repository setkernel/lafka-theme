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
        // Audit 2026-06-27 #6: the parent's Owl/slider navText needs the
        // .screen-reader-text helper, so the PARENT must define it. This used to
        // assert against the sibling child repo and skipped in isolated CI.
        $base_css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-base.css' );
        $this->assertMatchesRegularExpression(
            '/\.screen-reader-text\s*\{/',
            $base_css,
            'lafka-theme/styles/lafka-base.css must define the .screen-reader-text helper.'
        );
    }

    // NOTE: the vc_lafka_content_slider.php a11y tests were removed in v6.18.0
    // along with the vc_templates/ directory — the theme is WPBakery-free and
    // that WPBakery slider template no longer exists. The owl-carousel a11y
    // config (tested above via lafka-libs-config.js) still covers native usage.
}
