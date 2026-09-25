<?php
declare(strict_types=1);

/**
 * woocommerce/single-product/product-image.php (reconciled with core 11.1.0).
 *
 * The main gallery slot and core's product-thumbnails.php (not overridden)
 * split one media ordering between them: on WooCommerce 11.1+ core renders
 * "every item after the first" of ProductMediaGallery's display ordering, so
 * the theme must render exactly item 0 — which can be a gallery image when the
 * product has no featured image, or a native gallery video. On older
 * WooCommerce the featured image (or the placeholder) fills the main slot.
 *
 * The 11.1 scenarios run in their own process: they alias a stub onto core's
 * class name, which must never leak into the legacy tests.
 */

namespace Automattic\WooCommerce\Enums {
	if ( ! class_exists( ProductType::class ) ) {
		final class ProductType {
			public const SIMPLE   = 'simple';
			public const VARIABLE = 'variable';
		}
	}
}

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-wc-template-compat.php';

	if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
		function wc_get_gallery_image_html( $attachment_id, $main_image = false, $image_index = -1 ) {
			return '<div class="woocommerce-product-gallery__image" data-media="image-' . (int) $attachment_id . '" data-main="' . ( $main_image ? '1' : '0' ) . '"></div>';
		}
	}
	if ( ! function_exists( 'wc_placeholder_img_src' ) ) {
		function wc_placeholder_img_src( $size = 'woocommerce_thumbnail' ) {
			return 'http://example.test/placeholder.png';
		}
	}
	if ( ! function_exists( 'sanitize_html_class' ) ) {
		function sanitize_html_class( $classname, $fallback = '' ) {
			return preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', (string) $classname );
		}
	}
	if ( ! function_exists( 'get_post_meta' ) ) {
		function get_post_meta( $post_id, $key = '', $single = false ) {
			return $GLOBALS['lafka_test_post_meta'][ (int) $post_id ][ $key ] ?? '';
		}
	}

	/** Stand-in for WooCommerce 11.1's ProductMediaGallery (aliased onto the core name per test). */
	final class Lafka_Test_Product_Media_Gallery {
		/** @var array<int, array<string, mixed>> */
		public static array $items = array();

		public static function get_product_media_gallery_items_for_display( $product ): array {
			return self::$items;
		}

		public static function get_gallery_video_html( array $media_item, bool $main_video = false ): string {
			return '<div class="woocommerce-product-gallery__image wc-video" data-media="video-' . (int) $media_item['id'] . '" data-main="' . ( $main_video ? '1' : '0' ) . '"></div>';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;
	use PHPUnit\Framework\TestCase;

	final class ProductImageTemplateTest extends TestCase {

		private const TEMPLATE = __DIR__ . '/../../woocommerce/single-product/product-image.php';

		protected function setUp(): void {
			$GLOBALS['lafka_test_post_meta'] = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['product'], $GLOBALS['lafka_test_post_meta'] );
		}

		/** Switch the process onto the "WooCommerce 11.1+" path with the given ordering. */
		private function use_media_gallery( array $items ): void {
			\Lafka_Test_Product_Media_Gallery::$items = $items;
			if ( ! class_exists( LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS ) ) {
				class_alias( \Lafka_Test_Product_Media_Gallery::class, LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS );
			}
			// Core 11.1 product-thumbnails.php: every item after the first, never the main slot.
			\add_action(
				'woocommerce_product_thumbnails',
				static function () use ( $items ) {
					foreach ( array_slice( $items, 1 ) as $key => $item ) {
						if ( 'video' === $item['media_type'] ) {
							echo \Lafka_Test_Product_Media_Gallery::get_gallery_video_html( $item, false ); // phpcs:ignore
						} else {
							echo \wc_get_gallery_image_html( $item['id'], false, $key ); // phpcs:ignore
						}
					}
				}
			);
		}

		private static function image( int $id ): array {
			return array( 'media_type' => 'image', 'source_type' => 'attachment', 'id' => $id );
		}

		private function render( array $product_data = array() ): string {
			$GLOBALS['product'] = new \WC_Product( $product_data );
			ob_start();
			require self::TEMPLATE;
			return (string) ob_get_clean();
		}

		/** @return string[] data-media values in document order, prefixed with * for the main slot. */
		private static function media( string $html ): array {
			preg_match_all( '/data-media="([^"]+)" data-main="([01])"/', $html, $m, PREG_SET_ORDER );
			return array_map( static fn( $row ) => ( '1' === $row[2] ? '*' : '' ) . $row[1], $m );
		}

		// ------------------------------------------------ WooCommerce < 11.1 ----

		public function test_legacy_wc_renders_the_featured_image_in_the_main_slot(): void {
			$this->assertFalse( \lafka_wc_has_product_media_gallery(), 'legacy tests must run without the 11.1 helper' );

			$html = $this->render( array( 'image_id' => 10 ) );

			$this->assertSame( array( '*image-10' ), self::media( $html ) );
			$this->assertStringContainsString( 'woocommerce-product-gallery--with-images', $html );
		}

		public function test_legacy_wc_without_featured_image_renders_the_placeholder(): void {
			$html = $this->render( array( 'image_id' => 0 ) );

			$this->assertSame( array(), self::media( $html ) );
			$this->assertStringContainsString( 'woocommerce-product-gallery__image--placeholder', $html );
			$this->assertStringContainsString( 'woocommerce-product-gallery--without-images', $html );
		}

		public function test_legacy_video_url_meta_renders_the_play_trigger(): void {
			$GLOBALS['lafka_test_post_meta'][42]['lafka_product_video_url'] = 'https://video.example.test/clip';

			$html = $this->render( array( 'image_id' => 10 ) );

			$this->assertStringContainsString( 'class="lafka_product_video_trigger" href="https://video.example.test/clip"', $html );
		}

		public function test_video_trigger_url_is_filterable(): void {
			$GLOBALS['lafka_test_post_meta'][42]['lafka_product_video_url'] = 'https://video.example.test/clip';
			\add_filter( 'lafka_product_video_trigger_url', static fn() => '' );

			$this->assertStringNotContainsString( 'lafka_product_video_trigger', $this->render( array( 'image_id' => 10 ) ) );
		}

		// ------------------------------------------------ WooCommerce 11.1+ ----

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_every_media_item_renders_exactly_once_with_featured_image(): void {
			$this->use_media_gallery( array( self::image( 10 ), self::image( 11 ), self::image( 12 ) ) );

			$this->assertSame( array( '*image-10', 'image-11', 'image-12' ), self::media( $this->render( array( 'image_id' => 10 ) ) ) );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_first_gallery_image_fills_the_main_slot_when_there_is_no_featured_image(): void {
			// Core orders [gallery...] and its thumbnails skip item 0: rendering the
			// placeholder here (the pre-11.1 template) would drop image 11 entirely.
			$this->use_media_gallery( array( self::image( 11 ), self::image( 12 ) ) );

			$html = $this->render( array( 'image_id' => 0 ) );

			$this->assertSame( array( '*image-11', 'image-12' ), self::media( $html ) );
			$this->assertStringNotContainsString( '--placeholder', $html );
			$this->assertStringContainsString( 'woocommerce-product-gallery--with-images', $html );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_a_leading_gallery_video_renders_in_the_main_slot_through_the_video_filter(): void {
			$this->use_media_gallery(
				array(
					array( 'media_type' => 'video', 'source_type' => 'attachment', 'id' => 30 ),
					self::image( 10 ),
				)
			);
			$seen = array();
			\add_filter(
				'woocommerce_single_product_video_thumbnail_html',
				static function ( $html, $id ) use ( &$seen ) {
					$seen[] = $id;
					return $html;
				}
			);

			$html = $this->render( array( 'image_id' => 10 ) );

			$this->assertSame( array( '*video-30', 'image-10' ), self::media( $html ) );
			$this->assertSame( array( 30 ), $seen );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_native_gallery_video_suppresses_the_legacy_play_trigger(): void {
			$GLOBALS['lafka_test_post_meta'][42]['lafka_product_video_url'] = 'https://video.example.test/clip';
			$this->use_media_gallery(
				array(
					self::image( 10 ),
					array( 'media_type' => 'video', 'source_type' => 'attachment', 'id' => 30 ),
				)
			);

			$html = $this->render( array( 'image_id' => 10 ) );

			$this->assertStringNotContainsString( 'lafka_product_video_trigger', $html );
			$this->assertSame( array( '*image-10', 'video-30' ), self::media( $html ) );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_legacy_play_trigger_stays_when_the_gallery_has_no_native_video(): void {
			$GLOBALS['lafka_test_post_meta'][42]['lafka_product_video_url'] = 'https://video.example.test/clip';
			$this->use_media_gallery( array( self::image( 10 ) ) );

			$this->assertStringContainsString( 'lafka_product_video_trigger', $this->render( array( 'image_id' => 10 ) ) );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_placeholder_item_renders_the_placeholder(): void {
			$this->use_media_gallery( array( array( 'media_type' => 'image', 'source_type' => 'placeholder', 'id' => 0 ) ) );

			$html = $this->render( array( 'image_id' => 0 ) );

			$this->assertSame( array(), self::media( $html ) );
			$this->assertStringContainsString( 'woocommerce-product-gallery--without-images', $html );
		}
	}
}
