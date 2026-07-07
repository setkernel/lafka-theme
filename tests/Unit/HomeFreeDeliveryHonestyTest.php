<?php
declare(strict_types=1);

/**
 * OSS-integrity regression lock for the home free-delivery claims.
 *
 * Follow-up to audit #5 (fabricated social proof): the hero stat 3 and the
 * how-it-works step 2 defaulted to a literal "$30" free-delivery promise,
 * unlinked from the threshold the announce bar / cart / PDP resolve and the
 * shipping rule enforces (default 0 = OFF). Every fresh install advertised
 * an offer that didn't exist, and an operator setting a real threshold kept
 * the stale figure on the home page forever.
 *
 * The fix: both defaults derive from the free-delivery SSOT helpers
 * (incl/template-helpers/free-delivery.php) — cite the real amount, or drop
 * the claim entirely when the threshold is off.
 *
 * Global-namespace block holds the usual WP/WC shims (theme-suite
 * convention: function_exists-guarded, first-loaded wins process-wide — so
 * behaviour assertions below stay formatting-agnostic).
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return '<span class="amount">$' . number_format( (float) $price, 2 ) . '</span>';
		}
	}
	if ( ! function_exists( 'wp_strip_all_tags' ) ) {
		function wp_strip_all_tags( $text, $remove_breaks = false ) {
			return strip_tags( (string) $text );
		}
	}
	// The resolver the helper prefers; the test varies it via this global.
	if ( ! function_exists( 'lafka_get_free_delivery_threshold' ) ) {
		function lafka_get_free_delivery_threshold() {
			return $GLOBALS['lafka_test_free_delivery_threshold'] ?? 0;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/free-delivery.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class HomeFreeDeliveryHonestyTest extends TestCase {

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_free_delivery_threshold'] );
			parent::tearDown();
		}

		// ── Behaviour ────────────────────────────────────────────────────────

		public function test_threshold_off_yields_no_claim_anywhere(): void {
			$GLOBALS['lafka_test_free_delivery_threshold'] = 0;

			$this->assertSame( '', \lafka_free_delivery_amount_text() );
			$this->assertSame(
				array(
					'value' => '',
					'label' => '',
				),
				\lafka_home_hero_stat3_defaults(),
				'With no threshold the hero stat must default to hidden, not a fictional offer.'
			);
			$this->assertStringNotContainsString(
				'Free delivery',
				\lafka_home_how_step2_body_default(),
				'With no threshold the how-it-works copy must drop the free-delivery claim.'
			);
		}

		public function test_configured_threshold_is_cited_verbatim(): void {
			$GLOBALS['lafka_test_free_delivery_threshold'] = 45;

			$amount = \lafka_free_delivery_amount_text();
			$this->assertStringContainsString( '45', $amount, 'Amount text must cite the real threshold.' );
			$this->assertStringNotContainsString( '<', $amount, 'Amount text must be tag-free plain text.' );

			$defaults = \lafka_home_hero_stat3_defaults();
			$this->assertSame( 'Free', $defaults['value'] );
			$this->assertStringContainsString( '45', $defaults['label'] );
			$this->assertStringNotContainsString( '30', $defaults['label'], 'The retired baked-in figure must never reappear.' );

			$this->assertStringContainsString(
				'45',
				\lafka_home_how_step2_body_default(),
				'The how-it-works copy must cite the REAL threshold, not a baked-in figure.'
			);
		}

		// ── Source locks ─────────────────────────────────────────────────────

		public function test_no_home_surface_hardcodes_the_30_dollar_claim(): void {
			$root = dirname( __DIR__, 2 );
			foreach ( array( 'partials/home-hero.php', 'partials/home-how-it-works.php', 'incl/customizer-home.php' ) as $rel ) {
				$this->assertStringNotContainsString(
					'$30',
					(string) file_get_contents( $root . '/' . $rel ),
					"{$rel} must not hardcode the \$30 free-delivery figure — derive it from the SSOT helper."
				);
			}
		}

		public function test_helper_is_loaded_by_functions_php(): void {
			$this->assertStringContainsString(
				'template-helpers/free-delivery.php',
				(string) file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' ),
				'functions.php must require the free-delivery SSOT helpers.'
			);
		}
	}
}
