<?php
/**
 * Lafka home page — defaults filter scaffolding
 *
 * Centralizes the filter hooks that brand-align the Phase B home page
 * for a specific install. The PARENT theme registers the filter
 * callbacks but returns empty / generic defaults — actual site-
 * specific values belong in a CHILD theme so the OSS bundle stays
 * free of operator-specific URLs and copy.
 *
 * Hooks exposed:
 *  - `lafka_home_hero_default_bg_url`  (string) hero bg image URL
 *
 * Example override (in a child theme's functions.php):
 *
 *   add_filter( 'lafka_home_hero_default_bg_url', function( $url ) {
 *       return $url ?: 'https://example.com/wp-content/uploads/hero.jpg';
 *   } );
 *
 * @package Lafka
 * @since   5.49.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_home_default_hero_bg' ) ) {
	/**
	 * Default hero background image URL for fresh installs.
	 * Returns empty so OSS bundle has no operator-specific URLs baked in.
	 * Child themes override via the same filter at higher priority.
	 *
	 * @param string $url Current value.
	 * @return string
	 */
	function lafka_home_default_hero_bg( $url ) {
		return $url;
	}
}
add_filter( 'lafka_home_hero_default_bg_url', 'lafka_home_default_hero_bg' );
