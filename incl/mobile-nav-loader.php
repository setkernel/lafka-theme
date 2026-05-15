<?php
/**
 * Mobile nav drawer loader — registers wp_footer hook to render the partial.
 *
 * The drawer markup must live outside the header DOM (it uses position: fixed
 * inset: 0). wp_footer is the standard hook for late-body fixed-position UI.
 *
 * @package Lafka
 * @since   5.56.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_render_mobile_nav' ) ) {

	function lafka_render_mobile_nav() {
		// Only render on the frontend, and not in the Customizer preview's iframe
		// pre-render phase where late hooks would double-fire.
		if ( is_admin() ) {
			return;
		}
		get_template_part( 'partials/mobile-nav' );
	}
}
add_action( 'wp_footer', 'lafka_render_mobile_nav', 20 );
