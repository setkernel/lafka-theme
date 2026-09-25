<?php
declare(strict_types=1);

/**
 * Editor content styles reach WordPress 7.1's iframed editor canvas: they ride
 * enqueue_block_assets (the only action whose styles are copied into the
 * iframe), carry the Customizer typography CSS in the same call, and stay off
 * the front end, where enqueue_block_assets fires too.
 */

namespace {
	if ( ! function_exists( 'wp_enqueue_style' ) ) {
		function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
			$GLOBALS['lafka_test_enqueued_styles'][ $handle ] = array( 'src' => $src, 'media' => $media );
		}
	}
	if ( ! function_exists( 'lafka_asset_version' ) ) {
		function lafka_asset_version( $relative_path ) {
			return '1';
		}
	}
	if ( ! function_exists( 'lafka_add_custom_gutenberg_css' ) ) {
		function lafka_add_custom_gutenberg_css() {
			$GLOBALS['lafka_test_editor_calls'][] = 'dynamic-css';
		}
	}
	if ( ! function_exists( 'lafka_typography_enqueue_google_font' ) ) {
		function lafka_typography_enqueue_google_font() {
			$GLOBALS['lafka_test_editor_calls'][] = 'google-font';
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-editor-styles.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class EditorStylesTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_enqueued_styles'] = array();
			$GLOBALS['lafka_test_editor_calls']    = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_enqueued_styles'], $GLOBALS['lafka_test_editor_calls'] );
		}

		public function test_hooks_the_iframe_aware_action_not_the_outer_document_one(): void {
			$this->assertSame( 10, \has_action( 'enqueue_block_assets', 'lafka_enqueue_gutenberg_styles' ) );
			$this->assertFalse( \has_action( 'enqueue_block_editor_assets', 'lafka_enqueue_gutenberg_styles' ) );
		}

		public function test_editor_gets_the_stylesheet_then_its_dynamic_css(): void {
			$GLOBALS['lafka_test_is_admin'] = true;

			\do_action( 'enqueue_block_assets' );

			$this->assertStringEndsWith(
				'/styles/lafka-gutenberg-styles.css',
				$GLOBALS['lafka_test_enqueued_styles']['lafka_block_editor_assets']['src'] ?? ''
			);
			// Inline CSS is attached after the handle exists, in the same (iframe) pass.
			$this->assertSame( array( 'dynamic-css', 'google-font' ), $GLOBALS['lafka_test_editor_calls'] );
		}

		public function test_front_end_loads_no_editor_css(): void {
			$GLOBALS['lafka_test_is_admin'] = false;

			\do_action( 'enqueue_block_assets' );

			$this->assertSame( array(), $GLOBALS['lafka_test_enqueued_styles'] );
			$this->assertSame( array(), $GLOBALS['lafka_test_editor_calls'] );
		}
	}
}
