<?php
declare(strict_types=1);

/**
 * GX T-25: the counter surfaces skip the legacy libraries (Font Awesome,
 * flexslider, owl + animate, nice-select, imagesloaded) and content sniffing
 * only reads content a template renders.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-asset-diet.php';

	if ( ! class_exists( 'WP_Post' ) ) {
		/** Minimal WP_Post (same constructor shape as core's). */
		final class WP_Post {
			/** @var int */
			public $ID = 0;
			/** @var string */
			public $post_content = '';
			/** @var string */
			public $post_name = '';

			public function __construct( $post ) {
				foreach ( get_object_vars( $post ) as $key => $value ) {
					$this->$key = $value;
				}
			}
		}
	}
	if ( ! function_exists( 'is_singular' ) ) {
		/** $GLOBALS['lafka_test_is_singular']. */
		function is_singular( $post_types = '' ) {
			return (bool) ( $GLOBALS['lafka_test_is_singular'] ?? false );
		}
	}
	if ( ! function_exists( 'is_front_page' ) ) {
		/** $GLOBALS['lafka_test_is_front_page']. */
		function is_front_page() {
			return (bool) ( $GLOBALS['lafka_test_is_front_page'] ?? false );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class AssetDietTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_is_singular']   = false;
			$GLOBALS['lafka_test_is_front_page'] = false;
			unset( $GLOBALS['post'] );
		}

		protected function tearDown(): void {
			unset( $GLOBALS['post'], $GLOBALS['lafka_test_is_singular'], $GLOBALS['lafka_test_is_front_page'] );
		}

		/** @param array<string,bool> $facts */
		private static function ctx( array $facts ): array {
			return array_merge(
				array(
					'counter'      => true,
					'counter_home' => true,
					'counter_menu' => true,
					'front_page'   => false,
					'menu_surface' => false,
					'product'      => false,
					'pdp_redesign' => true,
					'cart'         => false,
					'checkout'     => false,
				),
				$facts
			);
		}

		public function test_counter_surfaces_skip_the_legacy_libraries(): void {
			foreach ( array( 'front_page', 'menu_surface', 'product', 'cart', 'checkout' ) as $surface ) {
				$this->assertFalse( \lafka_legacy_libs_needed_for( self::ctx( array( $surface => true ) ) ), "{$surface} renders no legacy markup" );
			}
		}

		public function test_other_pages_keep_them(): void {
			// e.g. My account (owl login/register slider), blog, legacy pages.
			$this->assertTrue( \lafka_legacy_libs_needed_for( self::ctx( array() ) ) );
		}

		public function test_a_classic_site_is_unchanged(): void {
			foreach ( array( 'front_page', 'menu_surface', 'product', 'cart', 'checkout' ) as $surface ) {
				$this->assertTrue( \lafka_legacy_libs_needed_for( self::ctx( array( 'counter' => false, $surface => true ) ) ), "{$surface} on a classic site" );
			}
		}

		public function test_a_classic_surface_inside_a_counter_site_keeps_them(): void {
			$this->assertTrue( \lafka_legacy_libs_needed_for( self::ctx( array( 'front_page' => true, 'counter_home' => false ) ) ), 'classic home' );
			$this->assertTrue( \lafka_legacy_libs_needed_for( self::ctx( array( 'menu_surface' => true, 'counter_menu' => false ) ) ), 'classic menu' );
			$this->assertTrue( \lafka_legacy_libs_needed_for( self::ctx( array( 'product' => true, 'pdp_redesign' => false ) ) ), 'legacy PDP gallery' );
		}

		public function test_the_decision_is_filterable(): void {
			\add_filter( 'lafka_needs_legacy_libs', '__return_true' );
			$this->assertTrue( \lafka_needs_legacy_libs() );
			\add_filter( 'lafka_needs_legacy_libs', '__return_false', 20 );
			$this->assertFalse( \lafka_needs_legacy_libs() );
		}

		public function test_front_page_post_content_is_not_sniffed(): void {
			$GLOBALS['post']                   = new \WP_Post( (object) array( 'ID' => 7, 'post_content' => '[lafka_icon type="flaticon"]' ) );
			$GLOBALS['lafka_test_is_singular'] = true;
			$this->assertSame( '[lafka_icon type="flaticon"]', \lafka_rendered_post_content(), 'an ordinary page renders its content' );

			$GLOBALS['lafka_test_is_front_page'] = true;
			$this->assertSame( '', \lafka_rendered_post_content(), 'front-page.php never outputs the page content' );

			\add_filter( 'lafka_front_page_renders_content', '__return_true' );
			$this->assertSame( '[lafka_icon type="flaticon"]', \lafka_rendered_post_content(), 'a child front page that renders it says so' );
		}

		public function test_nothing_to_sniff_off_singular_views(): void {
			$GLOBALS['post'] = new \WP_Post( (object) array( 'ID' => 7, 'post_content' => '[lafka_typed]' ) );
			$this->assertSame( '', \lafka_rendered_post_content() );
		}
	}
}
