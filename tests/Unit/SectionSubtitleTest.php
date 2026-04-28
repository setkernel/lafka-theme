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
        $child_css = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/style.css' );
        $this->assertMatchesRegularExpression(
            '/\.section-subtitle\s*\{/',
            $child_css,
            'lafka-child/style.css must define .section-subtitle class'
        );
    }
}
