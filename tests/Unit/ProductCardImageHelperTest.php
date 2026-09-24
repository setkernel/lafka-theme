<?php
declare(strict_types=1);

/**
 * lafka_product_card_image_html() (incl/template-helpers/product-card-image.php):
 * product image, then the Customizer fallback image, then the bundled SVG —
 * always lazy, async-decoded and labelled with the product name.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/product-card-image.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ProductCardImageHelperTest extends TestCase {

		private const SVG = 'http://example.test/wp-content/themes/lafka/assets/images/product-card-fallback.svg';

		public function test_product_image_wins(): void {
			$GLOBALS['lafka_test_attachments'] = array( 5 => 'product.jpg', 9 => 'fallback.jpg' );
			$GLOBALS['lafka_test_theme_mods']['lafka_product_card_fallback_image_id'] = 9;

			$html = \lafka_product_card_image_html( new \WC_Product( array( 'image_id' => 5 ) ) );

			$this->assertStringContainsString( 'src="product.jpg"', $html );
			$this->assertStringContainsString( 'loading="lazy"', $html );
			$this->assertStringContainsString( 'decoding="async"', $html );
			$this->assertStringContainsString( 'alt="Margherita Pizza"', $html );
		}

		public function test_customizer_fallback_image_when_the_product_has_none(): void {
			$GLOBALS['lafka_test_attachments'] = array( 9 => 'fallback.jpg' );
			$GLOBALS['lafka_test_theme_mods']['lafka_product_card_fallback_image_id'] = 9;

			$this->assertStringContainsString( 'src="fallback.jpg"', \lafka_product_card_image_html( new \WC_Product() ) );
		}

		public function test_bundled_svg_when_no_image_is_configured(): void {
			$html = \lafka_product_card_image_html( new \WC_Product() );

			$this->assertStringContainsString( 'src="' . self::SVG . '"', $html );
			$this->assertStringContainsString( 'alt="Margherita Pizza"', $html );
			$this->assertFileExists( dirname( __DIR__, 2 ) . '/assets/images/product-card-fallback.svg' );
		}

		public function test_non_product_input_gets_the_decorative_svg(): void {
			$html = \lafka_product_card_image_html( null );

			$this->assertStringContainsString( 'src="' . self::SVG . '"', $html );
			$this->assertStringContainsString( 'alt=""', $html );
		}
	}
}
