<?php
/**
 * Canonical "browse the menu" URL — theme-side resolver (audit #97).
 *
 * SINGLE guard point for the plugin's lafka_get_menu_url() source of truth.
 * Every theme template that links an Order / Browse / Full-menu CTA calls this
 * one helper so the `function_exists( 'lafka_get_menu_url' )` guard — and the
 * bare home_url( '/menu/' ) fallback literal — live in exactly ONE place instead
 * of being duplicated at ~10 call sites.
 *
 * When the plugin is active the shared resolver wins (and is filterable via
 * `lafka_header_cta_url`, so repointing the header "Order now" button moves
 * every menu CTA in lockstep). When the plugin is not loaded we fall back to the
 * /menu/ page URL, mirroring the resolver's own fallback (including the
 * `lafka_header_cta_url` filter) so the operator override still works.
 *
 * @package Lafka
 * @since   6.19.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_theme_menu_url' ) ) {
	/**
	 * Resolve the canonical menu URL for a theme template CTA.
	 *
	 * @return string Absolute, trailing-slashed menu URL.
	 */
	function lafka_theme_menu_url() {
		if ( function_exists( 'lafka_get_menu_url' ) ) {
			return (string) lafka_get_menu_url();
		}

		$menu_url = trailingslashit( home_url( '/menu/' ) );
		if ( function_exists( 'apply_filters' ) ) {
			$menu_url = (string) apply_filters( 'lafka_header_cta_url', $menu_url );
		}
		return $menu_url;
	}
}
