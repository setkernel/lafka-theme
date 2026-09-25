<?php
/**
 * Front-end script loading strategy.
 *
 * The theme defers ONLY its own scripts, through WordPress's script-loading
 * strategy API (wp_script_add_data( $handle, 'strategy', 'defer' ), WP 6.3+).
 * Unlike string-injecting `defer` into every <script> tag, the core engine
 * keeps a script blocking whenever deferring it would be unsafe — a blocking
 * dependent, or an `after` inline script — so load order is always right.
 * WooCommerce, payment-gateway, checkout, order-attribution and any other
 * third-party scripts are never touched: they load exactly as their owners
 * registered them.
 *
 * Filter surface:
 *   lafka_deferred_script_handles( string[] $handles ) — the allowlist.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_deferred_script_handles' ) ) {
	/**
	 * Handles of the theme's own front-end scripts that may load deferred.
	 *
	 * @return string[]
	 */
	function lafka_deferred_script_handles() {
		$handles = array(
			// First-party theme scripts.
			'lafka-front',
			'lafka-libs-config',
			'lafka-dialog',
			'lafka-search',
			'lafka-announce-bar',
			'lafka-mobile-nav',
			'lafka-cart-drawer',
			'lafka-sticky-cart',
			'lafka-archive-quickadd',
			'lafka-fdp-tracker',
			'lafka-exit-intent',
			'lafka-review-banner',
			'lafka-push-subscribe',
			'lafka-menu-controls',
			'lafka-cart-controls',
			'lafka-pdp-cta',
			'lafka-order-method',
			'lafka-pdp-pickers',
			'lafka-upsell-modal',
			'lafka-pdp-addons',
			'lafka-price-slider',
			// Vendor libraries the theme bundles and registers itself.
			'lafka-flexslider',
			'owl-carousel',
			'cloud-zoom',
			'jquery-plugin',
			'countdown',
			'jquery-countdown-local',
			'typed',
			'nice-select',
			'isotope',
		);

		/**
		 * Filters the theme script handles that load with the `defer` strategy.
		 *
		 * Add a handle only when it is safe to run after the document is parsed;
		 * WordPress still keeps it blocking when a dependent or an inline `after`
		 * script requires that.
		 *
		 * @param string[] $handles Script handles.
		 */
		$handles = apply_filters( 'lafka_deferred_script_handles', $handles );

		return array_values( array_unique( array_filter( (array) $handles, 'is_string' ) ) );
	}
}

add_action( 'wp_enqueue_scripts', 'lafka_apply_script_defer_strategy', 1000 );
add_action( 'wp_footer', 'lafka_apply_script_defer_strategy', 1 );
if ( ! function_exists( 'lafka_apply_script_defer_strategy' ) ) {
	/**
	 * Give every registered allowlisted handle the `defer` strategy.
	 *
	 * Runs after all theme/plugin enqueues, and once more before the footer
	 * scripts print for handles registered while the page rendered. Handles
	 * that already chose a strategy (e.g. `async`) keep it.
	 */
	function lafka_apply_script_defer_strategy() {
		if ( is_admin() ) {
			return;
		}

		foreach ( lafka_deferred_script_handles() as $handle ) {
			if ( ! wp_script_is( $handle, 'registered' ) ) {
				continue;
			}
			$current = wp_scripts()->get_data( $handle, 'strategy' );
			if ( ! empty( $current ) ) {
				continue;
			}
			wp_script_add_data( $handle, 'strategy', 'defer' );
		}
	}
}
