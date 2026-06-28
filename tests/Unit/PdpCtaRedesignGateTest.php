<?php
declare(strict_types=1);

/**
 * Regression lock (audit f020): the legacy v5.27 sticky CTA ("System A" —
 * styles/js lafka-pdp-cta.* + template-parts/lafka-pdp-cta.php) must stand
 * down whenever the PDP redesign owns the page.
 *
 * Before the fix, lafka-pdp-cta.js was enqueued and the .lafka-pdp-cta aside
 * was rendered on every single-product page that had the sticky-CTA toggle on,
 * with NO check on lafka_pdp_redesign_enabled(). With the redesign ON (the
 * default), pdp-pickers.js + pdp-summary's .lafka-pdp-mobile-cta already own
 * the sticky CTA, default-variation auto-select and live total, so System A
 * shipped a dead second bar plus a competing, uncoordinated auto-selector
 * firing duplicate WC variation events against the same form.variations_form.
 *
 * Two independent entry points are gated and both are locked here:
 *   1. the wp_enqueue_scripts enqueue in incl/system/core-functions.php
 *   2. the wp_footer render in template-parts/lafka-pdp-cta.php
 *
 * The global-namespace block holds the WP shims the procedural partial resolves
 * its calls against; the test class stays under Lafka\Tests\Unit.
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// Page-state flag the is_product() shim reads (shared with other render tests).
	if ( ! isset( $GLOBALS['lafka_test_is_product'] ) ) {
		$GLOBALS['lafka_test_is_product'] = false;
	}

	// Force the redesign branch ON — the state under test. Matches the shim in
	// Ga4ViewEventsRenderTest so the two coexist in a shared-process run.
	if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) ) {
		function lafka_pdp_redesign_enabled() {
			return true;
		}
	}

	// The partial registers itself on wp_footer at include time; swallow it.
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			return true;
		}
	}

	if ( ! function_exists( 'is_product' ) ) {
		function is_product() {
			return (bool) $GLOBALS['lafka_test_is_product'];
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $tag, $value = null ) {
			return $value;
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

	final class PdpCtaRedesignGateTest extends TestCase {

		private string $theme_dir;

		protected function setUp(): void {
			parent::setUp();
			$this->theme_dir                  = dirname( __DIR__, 2 );
			$GLOBALS['lafka_test_is_product'] = false;
		}

		protected function tearDown(): void {
			$GLOBALS['lafka_test_is_product'] = false;
			parent::tearDown();
		}

		/**
		 * Runtime: with the redesign ON, lafka_pdp_cta_render() must emit
		 * nothing — no second .lafka-pdp-cta aside on the redesigned PDP.
		 */
		public function test_legacy_sticky_cta_aside_not_rendered_when_redesign_enabled(): void {
			$path = $this->theme_dir . '/template-parts/lafka-pdp-cta.php';
			$this->assertFileExists( $path );

			// On a single-product page (the only surface the partial guards for).
			$GLOBALS['lafka_test_is_product'] = true;

			require_once $path;
			$this->assertTrue(
				function_exists( 'lafka_pdp_cta_render' ),
				'The partial must define lafka_pdp_cta_render().'
			);

			ob_start();
			lafka_pdp_cta_render();
			$html = (string) ob_get_clean();

			$this->assertSame(
				'',
				$html,
				'Legacy System A aside must not render when the PDP redesign owns the page.'
			);
			$this->assertStringNotContainsString(
				'lafka-pdp-cta',
				$html,
				'No .lafka-pdp-cta markup may be emitted while lafka_pdp_redesign_enabled() is true.'
			);
		}

		/**
		 * Source lock: the partial guards on lafka_pdp_redesign_enabled() before
		 * emitting any markup, and the legacy aside markup it guards still exists.
		 */
		public function test_render_partial_is_guarded_before_markup(): void {
			$src = file_get_contents( $this->theme_dir . '/template-parts/lafka-pdp-cta.php' );

			$this->assertStringContainsString(
				'lafka_pdp_redesign_enabled()',
				$src,
				'lafka_pdp_cta_render() must check lafka_pdp_redesign_enabled().'
			);
			$this->assertMatchesRegularExpression(
				'/lafka_pdp_redesign_enabled\(\).*?lafka-pdp-cta__btn/s',
				$src,
				'The redesign guard must precede the legacy aside markup so it can suppress it.'
			);
		}

		/**
		 * Source lock: the enqueue in core-functions.php ties the redesign guard
		 * to $lafka_pdp_cta_active, so neither lafka-pdp-cta.js nor
		 * lafka-pdp-cta.css load when the redesign owns the PDP.
		 */
		public function test_enqueue_gate_tied_to_redesign_flag(): void {
			$src = file_get_contents( $this->theme_dir . '/incl/system/core-functions.php' );

			$this->assertMatchesRegularExpression(
				'/\$lafka_pdp_cta_active\s*=.*?lafka_pdp_redesign_enabled\s*\(\s*\)/s',
				$src,
				'$lafka_pdp_cta_active must be gated by lafka_pdp_redesign_enabled() so System A assets are not enqueued under the redesign.'
			);
			$this->assertStringContainsString(
				"! function_exists( 'lafka_pdp_redesign_enabled' )",
				$src,
				'The enqueue gate must fall back to loading System A when the redesign helper is unavailable (plugin inactive).'
			);
		}
	}
}
