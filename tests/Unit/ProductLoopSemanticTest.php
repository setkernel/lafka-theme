<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-A11Y-6 W2-T5 regression lock: WC product loop must emit <li> children
 * inside <ul class="products"> for semantic HTML compliance.
 */
final class ProductLoopSemanticTest extends TestCase {

    public function test_content_product_template_uses_li_not_div(): void {
        $tpl = file_get_contents( dirname( __DIR__, 2 ) . '/woocommerce/content-product.php' );

        // Find the outermost wrapping element. WC's wc_product_class() generates
        // the classes; the wrapping tag must be <li>. The first arg may be any
        // string literal (empty or extra classes like 'lafka-product-card').
        $this->assertMatchesRegularExpression(
            '/<li\s[^>]*?<\?php\s+wc_product_class\(\s*[\'"][^\'"]*[\'"]\s*,\s*\$product\s*\)\s*;\s*\?>/s',
            $tpl,
            'content-product.php must wrap product output in <li>, not <div>'
        );
    }

    public function test_content_product_template_does_not_use_div_with_wc_product_class(): void {
        $tpl = file_get_contents( dirname( __DIR__, 2 ) . '/woocommerce/content-product.php' );
        // Negative assertion: the old <div> wrapper must be gone
        $this->assertDoesNotMatchRegularExpression(
            '/<div\s[^>]*?<\?php\s+wc_product_class\(/s',
            $tpl,
            'content-product.php must NOT use <div> as the wc_product_class wrapper'
        );
    }
}
