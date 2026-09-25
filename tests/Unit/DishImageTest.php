<?php
declare(strict_types=1);

/**
 * GX4 polish: cut-out vs opaque dish photos (incl/template-helpers/dish-image.php).
 * Cut-outs get the contact-shadow frame; opaque photos a rounded crop.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/dish-image.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class DishImageTest extends TestCase {

		/** @var string[] */
		private array $files = array();

		protected function tearDown(): void {
			foreach ( $this->files as $f ) {
				@unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			}
		}

		/** A 40×40 PNG: a centred opaque disc, transparent or grey around it. */
		private function png( bool $transparent ): string {
			if ( ! function_exists( 'imagecreatetruecolor' ) ) {
				$this->markTestSkipped( 'GD is not available' );
			}
			$img = imagecreatetruecolor( 40, 40 );
			imagesavealpha( $img, true );
			imagealphablending( $img, false );
			imagefill( $img, 0, 0, $transparent ? imagecolorallocatealpha( $img, 0, 0, 0, 127 ) : imagecolorallocate( $img, 200, 200, 200 ) );
			imagefilledellipse( $img, 20, 20, 30, 30, imagecolorallocate( $img, 190, 90, 30 ) );
			$path = tempnam( sys_get_temp_dir(), 'lafka-dish-' ) . '.png';
			imagepng( $img, $path );
			$this->files[] = $path;
			return $path;
		}

		public function test_mostly_transparent_edges_mean_a_cutout(): void {
			$this->assertTrue( \lafka_image_alpha_is_cutout( array( 127, 127, 127, 127, 127, 127, 0, 0 ) ) );
			$this->assertFalse( \lafka_image_alpha_is_cutout( array( 127, 127, 0, 0, 0, 0, 0, 0 ) ) );
			$this->assertFalse( \lafka_image_alpha_is_cutout( array() ) );
		}

		public function test_detects_a_transparent_png_and_an_opaque_one(): void {
			$this->assertTrue( \lafka_image_detect_cutout( $this->png( true ) ) );
			$this->assertFalse( \lafka_image_detect_cutout( $this->png( false ) ), 'an RGBA file with opaque edges is a photo' );
			$this->assertFalse( \lafka_image_detect_cutout( '/no/such/file.png' ) );
		}

		public function test_answer_is_cached_per_attachment_and_filterable(): void {
			$GLOBALS['lafka_test_attached_files'][70] = $this->png( true );
			$this->assertSame( 'cutout', \lafka_dish_kind( 70 ) );
			$this->assertSame( '1', $GLOBALS['lafka_test_post_meta'][70]['_lafka_is_cutout'] );

			// The cache wins over the file…
			$GLOBALS['lafka_test_post_meta'][70]['_lafka_is_cutout'] = '0';
			$this->assertSame( 'photo', \lafka_dish_kind( 70 ) );

			// …and the filter wins over both.
			\add_filter( 'lafka_image_is_cutout', static fn( $is, $id ) => 70 === $id ? true : $is, 10, 2 );
			$this->assertSame( 'cutout', \lafka_dish_kind( 70 ) );
		}

		public function test_smallest_stored_size_is_the_one_sampled(): void {
			$full  = $this->png( false );
			$small = $this->png( true );
			$GLOBALS['lafka_test_attached_files'][71]  = $full;
			$GLOBALS['lafka_test_attachment_meta'][71] = array(
				'sizes' => array(
					'medium'    => array(
						'file'  => 'missing-300.png',
						'width' => 300,
					),
					'thumbnail' => array(
						'file'  => basename( $small ),
						'width' => 40,
					),
				),
			);
			$this->assertTrue( \lafka_image_is_cutout( 71 ) );
		}

		public function test_rewritten_metadata_forgets_the_cached_answer(): void {
			$GLOBALS['lafka_test_post_meta'][72]['_lafka_is_cutout'] = '1';
			$data = \apply_filters( 'wp_update_attachment_metadata', array( 'file' => 'x.png' ), 72 );
			$this->assertSame( array( 'file' => 'x.png' ), $data );
			$this->assertArrayNotHasKey( '_lafka_is_cutout', $GLOBALS['lafka_test_post_meta'][72] );
		}

		public function test_no_attachment_is_a_photo_without_touching_meta(): void {
			$this->assertSame( 'photo', \lafka_dish_kind( 0 ) );
			$this->assertSame( array(), $GLOBALS['lafka_test_post_meta'] );
		}
	}
}
