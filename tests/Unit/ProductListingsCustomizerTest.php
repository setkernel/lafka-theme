<?php
declare(strict_types=1);

/**
 * Product-card fallback image setting (incl/customizer-product-listings.php).
 *
 * WP_Customize_Image_Control saves the image URL, not an attachment ID, so the
 * setting needs a URL → ID sanitizer; absint() would zero every save.
 */

namespace {
	if ( ! function_exists( 'attachment_url_to_postid' ) ) {
		function attachment_url_to_postid( $url ) {
			return 'http://example.test/wp-content/uploads/fallback.jpg' === $url ? 77 : 0;
		}
	}
	require_once dirname( __DIR__, 2 ) . '/incl/customizer-product-listings.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class ProductListingsCustomizerTest extends TestCase {

		public function test_setting_uses_the_url_to_id_sanitizer(): void {
			$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-product-listings.php' );
			$this->assertStringContainsString( "'sanitize_callback' => 'lafka_sanitize_attachment_id_from_url'", $src );
		}

		public function test_sanitizer_resolves_urls_and_passes_ids_through(): void {
			$this->assertSame( 77, \lafka_sanitize_attachment_id_from_url( 'http://example.test/wp-content/uploads/fallback.jpg' ) );
			$this->assertSame( 12, \lafka_sanitize_attachment_id_from_url( '12' ) );
			$this->assertSame( 0, \lafka_sanitize_attachment_id_from_url( 'http://example.test/unknown.jpg' ) );
			$this->assertSame( 0, \lafka_sanitize_attachment_id_from_url( '   ' ) );
		}
	}
}
