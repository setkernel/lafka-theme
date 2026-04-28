<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-SEO-7 regression lock: shop archive must have exactly one <h1>.
 * Filter `woocommerce_show_page_title` must suppress WC core's H1 when
 * lafka's themed <h1> is rendered.
 */
final class ShopArchiveH1Test extends TestCase {
    public function test_woocommerce_show_page_title_filter_in_child(): void {
        $child = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/functions.php' );
        $this->assertMatchesRegularExpression(
            "/add_filter\(\s*['\"]woocommerce_show_page_title['\"]/",
            $child,
            'lafka-child/functions.php must register woocommerce_show_page_title filter — P6-SEO-7'
        );
        $this->assertStringContainsString(
            'is_shop()',
            $child,
            'filter must gate by is_shop() — P6-SEO-7'
        );
    }
}
