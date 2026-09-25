<?php
/**
 * GX T-19: add-on group headings as real disclosure buttons.
 *
 * The theme declares add_theme_support( 'lafka-addon-group-toggle' )
 * (lafka_register_theme_features()), so lafka-plugin renders each add-on
 * group heading as <h3 class="addon-name"><button class="lafka-addon-toggle"
 * aria-expanded aria-controls> around a .lafka-addon-body region instead of
 * the old <h3 role="button"> that js/pdp-addons.js used to fake.
 * pdp-redesign.css styles that button exactly like the old heading text and
 * keeps the options wrapper out of the 2-column grid (display: contents).
 *
 * @package Lafka\WooCommerce
 * @since   7.3.0 (GX T-19)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_addon_group_toggle_where_styled' ) ) {
	/**
	 * GX T-19: only render the add-on disclosure button where the redesigned
	 * PDP styles it (pdp-redesign.css + js/pdp-addons.js); add-on forms
	 * rendered elsewhere (legacy PDP, AJAX modals) keep the plain heading.
	 *
	 * @param bool $on Plugin decision (theme support declared).
	 * @return bool
	 */
	function lafka_addon_group_toggle_where_styled( $on ) {
		return (bool) $on
			&& function_exists( 'is_product' ) && is_product()
			&& ( ! function_exists( 'lafka_pdp_redesign_enabled' ) || lafka_pdp_redesign_enabled() );
	}
}
add_filter( 'lafka_addon_group_toggle', 'lafka_addon_group_toggle_where_styled' );
