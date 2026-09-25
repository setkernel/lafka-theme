<?php
declare(strict_types=1);

/**
 * GX T-10: a legacy /product-category/… URL 301s to the real term archive
 * when the store's category base is something else (e.g. /menu/).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-fix-category-canonical-redirect.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class LegacyProductCatRedirectTest extends TestCase {

		private static function links(): callable {
			$terms = array(
				'pizza'           => 'http://example.test/menu/pizza/',
				'new-york-style'  => 'http://example.test/menu/pizza/new-york-style/',
			);
			return static function ( string $slug ) use ( $terms ): string {
				return $terms[ $slug ] ?? '';
			};
		}

		public function test_legacy_archive_goes_to_the_term_link(): void {
			$this->assertSame( 'http://example.test/menu/pizza/', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/', 'menu', self::links() ) );
			$this->assertSame( 'http://example.test/menu/pizza/', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza', 'menu', self::links() ), 'no trailing slash' );
		}

		public function test_pagination_and_nesting_are_kept(): void {
			$this->assertSame( 'http://example.test/menu/pizza/page/3/', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/page/3/', 'menu', self::links() ) );
			$this->assertSame( 'http://example.test/menu/pizza/new-york-style/', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/new-york-style/', 'menu', self::links() ) );
			$this->assertSame( 'http://example.test/menu/pizza/', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/page/1/', 'menu', self::links() ), 'page 1 is the archive itself' );
		}

		public function test_nothing_happens_when_the_base_is_product_category(): void {
			$this->assertSame( '', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/', 'product-category', self::links() ) );
			$this->assertSame( '', \lafka_legacy_product_cat_redirect_url( '/product-category/pizza/', '', self::links() ) );
		}

		public function test_unknown_terms_and_other_paths_are_left_alone(): void {
			$this->assertSame( '', \lafka_legacy_product_cat_redirect_url( '/product-category/nope/', 'menu', self::links() ), 'no redirect into a 404' );
			$this->assertSame( '', \lafka_legacy_product_cat_redirect_url( '/menu/pizza/', 'menu', self::links() ) );
			$this->assertSame( '', \lafka_legacy_product_cat_redirect_url( '/product-category/', 'menu', self::links() ) );
		}

		public function test_redirect_runs_before_redirect_canonical(): void {
			$this->assertContains( 'lafka_redirect_legacy_product_cat', $GLOBALS['lafka_test_filters']['template_redirect'][1] ?? array() );
		}
	}
}
