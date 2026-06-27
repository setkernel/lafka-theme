<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-SEO-9 + P6-A11Y-5 regression lock: subtitle text in WC archive headers
 * must not be wrapped in <h6> (heading-order violation). Use <p class="section-subtitle">.
 */
final class SectionSubtitleTest extends TestCase {
    public function test_wrapper_start_uses_section_subtitle(): void {
        $tpl = file_get_contents( dirname( __DIR__, 2 ) . '/woocommerce/global/wrapper-start.php' );
        $this->assertStringNotContainsString(
            '<h6><?php echo esc_html( $shop_subtitle',
            $tpl,
            '$shop_subtitle must not be wrapped in <h6>'
        );
        $this->assertStringContainsString(
            '<p class="section-subtitle"><?php echo esc_html( $shop_subtitle',
            $tpl
        );
        $this->assertStringNotContainsString(
            '<h6><?php echo esc_html( $lafka_prod_category_subtitle',
            $tpl
        );
        $this->assertStringContainsString(
            '<p class="section-subtitle"><?php echo esc_html( $lafka_prod_category_subtitle',
            $tpl
        );
    }

    public function test_foodmenu_category_uses_section_subtitle(): void {
        $tpl = file_get_contents( dirname( __DIR__, 2 ) . '/partials/content-lafka_foodmenu_category.php' );
        $this->assertStringNotContainsString(
            '<h6><?php echo esc_html( $lafka_subtitle',
            $tpl
        );
        $this->assertStringContainsString(
            '<p class="section-subtitle"><?php echo esc_html( $lafka_subtitle',
            $tpl
        );
    }

    public function test_section_subtitle_css_class_defined(): void {
        // Audit 2026-06-27 #6: the PARENT emits .section-subtitle, so the PARENT
        // must style it — this assertion used to reach into the sibling child
        // repo and skipped in isolated CI, leaving the a11y guarantee untested.
        $base_css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-base.css' );
        $this->assertMatchesRegularExpression(
            '/\.section-subtitle\s*\{/',
            $base_css,
            'lafka-theme/styles/lafka-base.css must define .section-subtitle (parent emits the markup).'
        );
    }
}
