<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ShopDoubleH1Test extends TestCase {
	public function test_remove_action_for_taxonomy_archive_header_is_registered(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			"/remove_action\(\s*['\"]woocommerce_shop_loop_header['\"]\s*,\s*['\"]woocommerce_product_taxonomy_archive_header['\"]/",
			$src,
			'Parent theme must remove WC core taxonomy archive header to avoid double <h1>.'
		);
	}

	public function test_fix_is_gated_to_shop_and_product_taxonomy(): void {
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
		$this->assertMatchesRegularExpression(
			'/is_shop\(\)\s*\|\|\s*is_product_taxonomy\(\)/',
			$src,
			'Fix must be gated to is_shop() || is_product_taxonomy() — never run elsewhere.'
		);
	}
}
