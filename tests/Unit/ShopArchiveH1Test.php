<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-SEO-7 regression lock: shop archive must have exactly one <h1>.
 *
 * WC core's `woocommerce_shop_loop_header` action fires
 * `woocommerce_product_taxonomy_archive_header()` which outputs a second
 * <h1> alongside lafka's own in wrapper-start.php. The fix removes that
 * action on `is_shop()` inside a `wp` action hook.
 */
final class ShopArchiveH1Test extends TestCase {
    public function test_wc_shop_loop_header_removed_on_shop_archive(): void {
        $child = file_get_contents( dirname( __DIR__, 3 ) . '/lafka-child/functions.php' );
        $this->assertStringContainsString(
            'remove_action',
            $child,
            'lafka-child/functions.php must call remove_action to suppress WC core H1 — P6-SEO-7'
        );
        $this->assertStringContainsString(
            'woocommerce_shop_loop_header',
            $child,
            'remove_action must target woocommerce_shop_loop_header — P6-SEO-7'
        );
        $this->assertStringContainsString(
            'is_shop()',
            $child,
            'suppression must be gated by is_shop() — P6-SEO-7'
        );
    }
}
