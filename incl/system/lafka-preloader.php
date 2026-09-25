<?php
/**
 * GX T-01: the full-screen preloader (`.mask`) is OFF by default.
 *
 * The legacy preloader covered every page with a white mask until
 * `window.load` + ~0.7 s — a multi-second blank screen on mobile (Lighthouse
 * LCP 13 s). It is now:
 *
 *  - OFF for new installs (no stored `lafka_show_preloader` theme_mod ⇒ off);
 *  - forced OFF while any counter layout is active (the counter chrome is
 *    designed to paint immediately), even when an upgraded install still
 *    carries the legacy `1`;
 *  - when an operator does keep it on (classic layouts only), removed by the
 *    deferred front script — i.e. at DOMContentLoaded, no fade delay — with a
 *    CSS fail-safe that hides it after 2 s even if that script never runs, and
 *    its CSS printed ONCE, inside the inlined critical bundle (it used to be a
 *    separate stylesheet plus a <noscript> copy plus a duplicate critical rule).
 *
 * Every existing reader (`get_theme_mod( 'lafka_show_preloader', … )` in
 * header.php, the enqueue/localize code) goes through the
 * `theme_mod_lafka_show_preloader` filter below, so they all agree.
 *
 * Filter surface:
 *   lafka_show_preloader( bool $on ) — last word (e.g. force it back on under a
 *   counter layout).
 *
 * @package Lafka
 * @since   7.3.0 (GX T-01)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_preloader_enabled' ) ) {
	/**
	 * Whether the preloader mask renders on this request.
	 */
	function lafka_preloader_enabled(): bool {
		$mods = function_exists( 'get_theme_mods' ) ? (array) get_theme_mods() : array();
		// No stored value ⇒ the new default: off.
		$on = array_key_exists( 'lafka_show_preloader', $mods ) && (bool) $mods['lafka_show_preloader'];

		if ( $on && function_exists( 'lafka_any_counter_layout' ) && lafka_any_counter_layout() ) {
			$on = false;
		}

		/**
		 * Filter whether the full-screen preloader renders.
		 *
		 * @param bool $on Stored choice (default off), forced off under a counter layout.
		 */
		return (bool) apply_filters( 'lafka_show_preloader', $on );
	}
}

if ( ! function_exists( 'lafka_preloader_theme_mod' ) ) {
	/**
	 * `theme_mod_lafka_show_preloader`: every get_theme_mod() reader sees the
	 * resolved value (so a caller's legacy `true` default no longer turns it on).
	 *
	 * @param mixed $value Stored value or the caller's default.
	 * @return int 1|0.
	 */
	function lafka_preloader_theme_mod( $value ) {
		unset( $value );
		return lafka_preloader_enabled() ? 1 : 0;
	}
}
add_filter( 'theme_mod_lafka_show_preloader', 'lafka_preloader_theme_mod' );

if ( ! function_exists( 'lafka_preloader_css' ) ) {
	/**
	 * The preloader rules for the inlined critical bundle ('' when off), so the
	 * mask is styled before first paint and printed exactly once.
	 */
	function lafka_preloader_css(): string {
		if ( ! lafka_preloader_enabled() ) {
			return '';
		}
		$path = get_template_directory() . '/styles/lafka-preloader.css';
		if ( ! file_exists( $path ) ) {
			return '';
		}
		$css = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return function_exists( 'lafka_critical_css_minify' ) ? lafka_critical_css_minify( $css ) : $css;
	}
}
