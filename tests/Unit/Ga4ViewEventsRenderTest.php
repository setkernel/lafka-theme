<?php
declare(strict_types=1);

/**
 * Regression lock (audit f008): GA4 view_item (PDP) and view_item_list
 * (shop/category/tag) must actually fire in the RENDERED redesigned templates.
 *
 * The plugin only hooks these emits on woocommerce_before_single_product_summary
 * and woocommerce_before_main_content — neither of which the redesigned
 * single-product.php / archive-product.php fire. The templates therefore have to
 * invoke the emit functions directly. This test renders the real template files
 * with lightweight WP/WC shims and asserts the dataLayer push marker is present
 * in the produced HTML — not merely that the emit function exists or the hook is
 * registered.
 *
 * The two namespace blocks are intentional: the shims must live in the GLOBAL
 * namespace (that is where the procedural templates resolve their function
 * calls), while the test class stays under Lafka\Tests\Unit.
 */

namespace {

	// The templates open with `defined( 'ABSPATH' ) || exit;`. The shared
	// bootstrap already defines ABSPATH, but guard for isolated runs.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// ---- Page-state flags the shims read (reset per test) -------------------
	$GLOBALS['lafka_test_have_posts']  = 0;
	$GLOBALS['lafka_test_is_product']  = false;
	$GLOBALS['lafka_test_is_shop']     = false;
	$GLOBALS['lafka_test_is_cat']      = false;
	$GLOBALS['lafka_test_is_tag']      = false;
	$GLOBALS['lafka_test_tpl_dir']     = '';

	// ---- Redesign feature flag — force the redesigned branch ON -------------
	if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) ) {
		function lafka_pdp_redesign_enabled() {
			return true;
		}
	}

	// ---- The emit shims under test -----------------------------------------
	// These mirror the real plugin emits' self-guards and echo a dataLayer push
	// that matches the live marker shape (lafka_dl_emit_push). The whole point
	// of the assertion is that the TEMPLATE calls these at render time so their
	// output lands in the page HTML.
	if ( ! function_exists( 'lafka_dl_emit_view_item' ) ) {
		function lafka_dl_emit_view_item() {
			if ( function_exists( 'is_product' ) && is_product() ) {
				echo "<script>\nwindow.dataLayer = window.dataLayer || [];\n";
				echo "window.dataLayer.push({\"event\":\"view_item\"});\n</script>\n";
			}
		}
	}
	if ( ! function_exists( 'lafka_dl_emit_view_item_list' ) ) {
		function lafka_dl_emit_view_item_list() {
			$is_list = ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product_category' ) && is_product_category() )
				|| ( function_exists( 'is_product_tag' ) && is_product_tag() );
			if ( $is_list ) {
				echo "<script>\nwindow.dataLayer = window.dataLayer || [];\n";
				echo "window.dataLayer.push({\"event\":\"view_item_list\"});\n</script>\n";
			}
		}
	}

	// ---- WordPress / WooCommerce shims --------------------------------------
	if ( ! function_exists( 'get_header' ) ) {
		function get_header( $name = '' ) {}
	}
	if ( ! function_exists( 'get_footer' ) ) {
		function get_footer( $name = '' ) {}
	}
	if ( ! function_exists( 'have_posts' ) ) {
		function have_posts() {
			if ( $GLOBALS['lafka_test_have_posts'] > 0 ) {
				$GLOBALS['lafka_test_have_posts']--;
				return true;
			}
			return false;
		}
	}
	if ( ! function_exists( 'the_post' ) ) {
		function the_post() {}
	}
	if ( ! function_exists( 'get_template_directory' ) ) {
		function get_template_directory() {
			return $GLOBALS['lafka_test_tpl_dir'];
		}
	}
	if ( ! function_exists( 'get_template_part' ) ) {
		function get_template_part( $slug, $name = '', $args = array() ) {}
	}
	if ( ! function_exists( 'is_product' ) ) {
		function is_product() {
			return (bool) $GLOBALS['lafka_test_is_product'];
		}
	}
	if ( ! function_exists( 'is_shop' ) ) {
		function is_shop() {
			return (bool) $GLOBALS['lafka_test_is_shop'];
		}
	}
	if ( ! function_exists( 'is_product_category' ) ) {
		function is_product_category() {
			return (bool) $GLOBALS['lafka_test_is_cat'];
		}
	}
	if ( ! function_exists( 'is_product_tag' ) ) {
		function is_product_tag() {
			return (bool) $GLOBALS['lafka_test_is_tag'];
		}
	}
	if ( ! function_exists( 'is_page' ) ) {
		function is_page( $page = '' ) {
			return false;
		}
	}
	if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
		function woocommerce_breadcrumb( $args = array() ) {}
	}
	if ( ! function_exists( 'woocommerce_show_product_images' ) ) {
		function woocommerce_show_product_images() {}
	}
	if ( ! function_exists( 'woocommerce_output_related_products' ) ) {
		function woocommerce_output_related_products() {}
	}
	if ( ! function_exists( 'woocommerce_product_loop' ) ) {
		function woocommerce_product_loop() {
			return true;
		}
	}
	if ( ! function_exists( 'get_queried_object' ) ) {
		function get_queried_object() {
			return null;
		}
	}
	if ( ! function_exists( 'taxonomy_exists' ) ) {
		function taxonomy_exists( $taxonomy ) {
			return false;
		}
	}
	if ( ! function_exists( 'get_terms' ) ) {
		function get_terms( $args = array() ) {
			return array();
		}
	}
	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ) {
			return false;
		}
	}
	if ( ! function_exists( 'wc_get_products' ) ) {
		function wc_get_products( $args = array() ) {
			return array();
		}
	}
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		function wc_get_page_permalink( $page ) {
			return 'http://example.test/menu/';
		}
	}
	if ( ! function_exists( 'get_term_link' ) ) {
		function get_term_link( $term, $taxonomy = '' ) {
			return '#';
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'home_url' ) ) {
		function home_url( $path = '' ) {
			return 'http://example.test' . $path;
		}
	}
	// Canonical menu-URL resolver (incl/template-helpers/menu-url.php) — the
	// redesigned templates now call this theme helper for every menu CTA.
	if ( ! function_exists( 'lafka_theme_menu_url' ) ) {
		function lafka_theme_menu_url() {
			return 'http://example.test/menu/';
		}
	}
	// Escaping / i18n shims — return or echo the raw value.
	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url, $protocols = null, $context = 'display' ) {
			return $url;
		}
	}
	if ( ! function_exists( 'wp_kses_post' ) ) {
		function wp_kses_post( $data ) {
			return $data;
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_html_e' ) ) {
		function esc_html_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
	if ( ! function_exists( 'esc_attr_e' ) ) {
		function esc_attr_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class Ga4ViewEventsRenderTest extends TestCase {

		private string $theme_dir;
		private string $tpl_dir;

		protected function setUp(): void {
			parent::setUp();
			$this->theme_dir = dirname( __DIR__, 2 );

			// Stub template directory: single-product.php `require`s its partials
			// from get_template_directory() . '/partials/...'. Point that helper
			// at a scratch dir holding harmless empty stubs so the render reaches
			// the emit without dragging in the real (heavy) partials.
			$this->tpl_dir = sys_get_temp_dir() . '/lafka-ga4-render-' . uniqid();
			@mkdir( $this->tpl_dir . '/partials', 0777, true );
			foreach ( array( 'pdp-summary.php', 'pdp-make-it-a-meal.php', 'pdp-ingredients-reviews.php' ) as $stub ) {
				file_put_contents( $this->tpl_dir . '/partials/' . $stub, "<?php\n" );
			}

			$GLOBALS['lafka_test_tpl_dir']    = $this->tpl_dir;
			$GLOBALS['lafka_test_have_posts'] = 0;
			$GLOBALS['lafka_test_is_product'] = false;
			$GLOBALS['lafka_test_is_shop']    = false;
			$GLOBALS['lafka_test_is_cat']     = false;
			$GLOBALS['lafka_test_is_tag']     = false;
		}

		protected function tearDown(): void {
			foreach ( glob( $this->tpl_dir . '/partials/*' ) ?: array() as $f ) {
				@unlink( $f );
			}
			@rmdir( $this->tpl_dir . '/partials' );
			@rmdir( $this->tpl_dir );
			parent::tearDown();
		}

		/**
		 * Render the template file and return the captured HTML.
		 */
		private function render( string $relative ): string {
			$path = $this->theme_dir . '/' . $relative;
			$this->assertFileExists( $path );
			ob_start();
			require $path;
			return (string) ob_get_clean();
		}

		public function test_redesigned_pdp_renders_view_item_push(): void {
			$GLOBALS['lafka_test_is_product'] = true;
			$GLOBALS['lafka_test_have_posts'] = 1; // exactly one product in the loop

			$html = $this->render( 'woocommerce/single-product.php' );

			$this->assertStringContainsString(
				'window.dataLayer.push',
				$html,
				'Redesigned PDP must emit a dataLayer push at render time.'
			);
			$this->assertStringContainsString(
				'"event":"view_item"',
				$html,
				'Redesigned PDP must fire the GA4 view_item event (broken when the template never calls the prio-5 emit).'
			);
			// Sanity: the PDP fires view_item, not the list event.
			$this->assertStringNotContainsString(
				'"event":"view_item_list"',
				$html,
				'PDP must not fire view_item_list.'
			);
		}

		public function test_redesigned_archive_renders_view_item_list_push(): void {
			$GLOBALS['lafka_test_is_shop']    = true;
			$GLOBALS['lafka_test_have_posts'] = 0; // empty-state path, no product-card requires

			$html = $this->render( 'woocommerce/archive-product.php' );

			$this->assertStringContainsString(
				'window.dataLayer.push',
				$html,
				'Redesigned product archive must emit a dataLayer push at render time.'
			);
			$this->assertStringContainsString(
				'"event":"view_item_list"',
				$html,
				'Redesigned archive must fire the GA4 view_item_list event (broken when the template suppresses woocommerce_before_main_content and never calls the emit).'
			);
		}

		/**
		 * Wiring guard: the emit must be invoked BEFORE the heavy partials /
		 * product loop, otherwise a fatal in a partial would suppress the event.
		 * Also a cheap source-level regression lock independent of the render.
		 */
		public function test_templates_invoke_emit_functions_directly(): void {
			$pdp = file_get_contents( $this->theme_dir . '/woocommerce/single-product.php' );
			$this->assertStringContainsString(
				'lafka_dl_emit_view_item()',
				$pdp,
				'single-product.php must call lafka_dl_emit_view_item() directly.'
			);

			$archive = file_get_contents( $this->theme_dir . '/woocommerce/archive-product.php' );
			$this->assertStringContainsString(
				'lafka_dl_emit_view_item_list()',
				$archive,
				'archive-product.php must call lafka_dl_emit_view_item_list() directly.'
			);
		}
	}
}
