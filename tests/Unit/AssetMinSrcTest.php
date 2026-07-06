<?php
declare(strict_types=1);

/**
 * NX1-10b regression lock: the production asset-minification switch.
 *
 * A minify-only dist step (scripts/build-assets.mjs, run in release.yml) emits a
 * `.min.css` / `.min.js` sibling next to every first-party file in styles/ and
 * js/. lafka_maybe_min_src() (incl/system/asset-min.php), hooked on
 * style_loader_src + script_loader_src, rewrites an enqueued theme asset URL to
 * that sibling — but ONLY when minified assets are in play AND the sibling
 * actually exists on disk. Everything else is a strict no-op:
 *
 *   - SCRIPT_DEBUG on / the lafka_use_min_assets filter forced off,
 *   - a non-string / empty src,
 *   - an already-minified src,
 *   - a src that does not point into this theme's styles/ or js/,
 *   - a theme asset whose .min sibling is absent (dev checkouts before build).
 *
 * The version query arg is preserved verbatim so cache-busting never regresses.
 *
 * The WP shims live in the GLOBAL namespace (the helper resolves its calls
 * there) and are driven by $GLOBALS so a per-test temp theme dir can stand in
 * for the real one. Each test runs in its own process so these shims win
 * regardless of the shims other test files define for the same function names
 * (and so a per-test SCRIPT_DEBUG define stays isolated).
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// Override slot for the lafka_use_min_assets decision: null = use the
	// helper's own SCRIPT_DEBUG-derived default; bool = force it.
	if ( ! array_key_exists( 'lafka_test_use_min', $GLOBALS ) ) {
		$GLOBALS['lafka_test_use_min'] = null;
	}

	if ( ! function_exists( 'get_template_directory' ) ) {
		function get_template_directory() {
			return $GLOBALS['lafka_test_tpl_dir'];
		}
	}
	if ( ! function_exists( 'get_template_directory_uri' ) ) {
		function get_template_directory_uri() {
			return $GLOBALS['lafka_test_tpl_uri'];
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $tag, $value = null ) {
			if ( 'lafka_use_min_assets' === $tag
				&& array_key_exists( 'lafka_test_use_min', $GLOBALS )
				&& null !== $GLOBALS['lafka_test_use_min'] ) {
				return $GLOBALS['lafka_test_use_min'];
			}
			return $value;
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			return true;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/system/asset-min.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class AssetMinSrcTest extends TestCase {

		private string $tpl_dir;
		private string $tpl_uri = 'http://example.test/wp-content/themes/lafka';

		protected function setUp(): void {
			parent::setUp();
			$this->tpl_dir = sys_get_temp_dir() . '/lafka-asset-min-' . uniqid( '', true );
			mkdir( $this->tpl_dir . '/styles', 0777, true );
			mkdir( $this->tpl_dir . '/js', 0777, true );
			$GLOBALS['lafka_test_tpl_dir'] = $this->tpl_dir;
			$GLOBALS['lafka_test_tpl_uri'] = $this->tpl_uri;
			$GLOBALS['lafka_test_use_min'] = null;
		}

		protected function tearDown(): void {
			$this->rrmdir( $this->tpl_dir );
			parent::tearDown();
		}

		private function rrmdir( string $dir ): void {
			if ( ! is_dir( $dir ) ) {
				return;
			}
			foreach ( scandir( $dir ) as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}
				$path = $dir . '/' . $entry;
				is_dir( $path ) ? $this->rrmdir( $path ) : unlink( $path );
			}
			rmdir( $dir );
		}

		private function write( string $relative, string $body = '/* x */' ): void {
			file_put_contents( $this->tpl_dir . '/' . ltrim( $relative, '/' ), $body );
		}

		public function test_rewrites_css_when_min_exists_and_min_assets_on(): void {
			$this->write( 'styles/lafka-base.css' );
			$this->write( 'styles/lafka-base.min.css' );

			$src = $this->tpl_uri . '/styles/lafka-base.css?ver=6.19.0';
			$this->assertSame(
				$this->tpl_uri . '/styles/lafka-base.min.css?ver=6.19.0',
				\lafka_maybe_min_src( $src, 'lafka-base' )
			);
		}

		public function test_rewrites_js_when_min_exists(): void {
			$this->write( 'js/cart-drawer.js' );
			$this->write( 'js/cart-drawer.min.js' );

			$src = $this->tpl_uri . '/js/cart-drawer.js?ver=123';
			$this->assertSame(
				$this->tpl_uri . '/js/cart-drawer.min.js?ver=123',
				\lafka_maybe_min_src( $src, 'lafka-cart-drawer' )
			);
		}

		public function test_preserves_absent_query(): void {
			$this->write( 'styles/lafka-base.css' );
			$this->write( 'styles/lafka-base.min.css' );

			$src = $this->tpl_uri . '/styles/lafka-base.css';
			$this->assertSame(
				$this->tpl_uri . '/styles/lafka-base.min.css',
				\lafka_maybe_min_src( $src, 'lafka-base' )
			);
		}

		public function test_no_op_when_min_sibling_absent(): void {
			// Dev checkout before `npm run build`: source present, no .min.
			$this->write( 'styles/lafka-base.css' );

			$src = $this->tpl_uri . '/styles/lafka-base.css?ver=6.19.0';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'lafka-base' ) );
		}

		public function test_no_op_on_already_minified_src(): void {
			$this->write( 'styles/lafka-base.min.css' );

			$src = $this->tpl_uri . '/styles/lafka-base.min.css?ver=6.19.0';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'lafka-base' ) );
		}

		public function test_no_op_on_non_theme_src(): void {
			$src = 'https://cdn.example.com/vendor/foo.css?ver=1';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'foo' ) );
		}

		public function test_no_op_outside_styles_and_js_dirs(): void {
			// A theme file, but not under styles/ or js/ (e.g. the root style.css).
			$this->write( 'style.css' );
			$this->write( 'style.min.css' );

			$src = $this->tpl_uri . '/style.css?ver=6.19.0';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'lafka-style' ) );
		}

		public function test_no_op_on_non_string_src(): void {
			$this->assertFalse( \lafka_maybe_min_src( false, 'x' ) );
		}

		public function test_no_op_when_use_min_filter_forces_raw(): void {
			$this->write( 'styles/lafka-base.css' );
			$this->write( 'styles/lafka-base.min.css' );
			$GLOBALS['lafka_test_use_min'] = false; // e.g. an operator debugging on prod.

			$src = $this->tpl_uri . '/styles/lafka-base.css?ver=6.19.0';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'lafka-base' ) );
		}

		public function test_no_op_when_script_debug_on(): void {
			if ( ! defined( 'SCRIPT_DEBUG' ) ) {
				define( 'SCRIPT_DEBUG', true );
			}
			$this->write( 'styles/lafka-base.css' );
			$this->write( 'styles/lafka-base.min.css' );

			$src = $this->tpl_uri . '/styles/lafka-base.css?ver=6.19.0';
			$this->assertSame( $src, \lafka_maybe_min_src( $src, 'lafka-base' ) );
		}

		public function test_hooks_both_loader_src_filters(): void {
			$source = (string) file_get_contents(
				dirname( __DIR__, 2 ) . '/incl/system/asset-min.php'
			);
			$this->assertMatchesRegularExpression(
				"/add_filter\(\s*'style_loader_src',\s*'lafka_maybe_min_src'/",
				$source,
				'asset-min.php must hook style_loader_src.'
			);
			$this->assertMatchesRegularExpression(
				"/add_filter\(\s*'script_loader_src',\s*'lafka_maybe_min_src'/",
				$source,
				'asset-min.php must hook script_loader_src.'
			);
		}
	}
}
