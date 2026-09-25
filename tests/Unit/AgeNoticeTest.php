<?php
declare(strict_types=1);

/**
 * M-25: an operator-chosen age / ID notice — nothing by default, the notice on
 * products (and menu sections) of the listed categories, operator copy wins.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/age-notice.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class AgeNoticeTest extends TestCase {

		private function beer(): \WC_Product {
			$GLOBALS['lafka_test_post_terms'][501] = array(
				'product_cat' => array( new \WP_Term( array( 'term_id' => 40, 'slug' => 'beer-and-coolers', 'name' => 'Beer' ) ) ),
			);
			return new \WC_Product(
				array(
					'id'   => 501,
					'name' => 'Example Lager',
				)
			);
		}

		public function test_nothing_renders_by_default(): void {
			$this->assertSame( array(), \lafka_age_notice_slugs() );
			$this->assertSame( '', \lafka_product_age_notice_html( $this->beer() ) );
			$this->assertSame( '', \lafka_age_notice_html( array( 'beer-and-coolers' ), 'menu' ) );
		}

		public function test_listed_category_products_and_sections_carry_the_notice(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_age_notice_categories'] = 'Wine, beer-and-coolers';
			$html = \lafka_product_age_notice_html( $this->beer() );

			$this->assertStringContainsString( 'class="lafka-age-notice lafka-age-notice--pdp"', $html );
			$this->assertStringContainsString( 'Valid photo ID is required', $html );
			$this->assertStringContainsString( 'lafka-age-notice--menu', \lafka_age_notice_html( array( new \WP_Term( array( 'slug' => 'wine' ) ) ), 'menu' ) );
			$this->assertSame( '', \lafka_age_notice_html( array( new \WP_Term( array( 'slug' => 'pizza' ) ) ), 'menu' ) );
		}

		public function test_operator_text_wins(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_age_notice_categories'] = 'beer-and-coolers';
			$GLOBALS['lafka_test_theme_mods']['lafka_age_notice_text']       = '19+ only. Bring ID.';
			$this->assertStringContainsString( '19+ only. Bring ID.', \lafka_product_age_notice_html( $this->beer() ) );
		}
	}
}
