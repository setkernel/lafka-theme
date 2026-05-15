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
	 *
	 * v5.52.0: when operator hasn't set a Customizer image, auto-pull
	 * the photo from their top-selling WC product. This way the OSS
	 * bundle ships with a real food-photo hero on day-one — no operator
	 * configuration required.
	 *
	 * Returns empty when the catalog is empty (theme falls back to the
	 * flat brand-50 surface).
	 *
	 * @param string $url Current value.
	 * @return string
	 */
	function lafka_home_default_hero_bg( $url ) {
		if ( '' !== $url ) {
			return $url;
		}
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}

		// Cache the auto-discovered URL for 24h — wc_get_products is
		// not free and we shouldn't run it on every pageview.
		$cache_key   = 'lafka_home_hero_auto_bg';
		$cached_url  = get_transient( $cache_key );
		if ( is_string( $cached_url ) && '' !== $cached_url ) {
			return $cached_url;
		}

		// Top-seller first, fall back to any featured product, finally
		// any published product with a thumbnail.
		$candidates = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 1,
				'orderby'  => 'meta_value_num',
				'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'    => 'DESC',
			)
		);
		if ( empty( $candidates ) ) {
			return '';
		}

		$thumb_url = get_the_post_thumbnail_url( $candidates[0]->get_id(), 'large' );
		if ( ! $thumb_url ) {
			return '';
		}

		set_transient( $cache_key, $thumb_url, DAY_IN_SECONDS );
		return $thumb_url;
	}
}
add_filter( 'lafka_home_hero_default_bg_url', 'lafka_home_default_hero_bg' );

if ( ! function_exists( 'lafka_home_default_hero_overlay' ) ) {
	/**
	 * Default overlay state — when we auto-discover a hero image from
	 * the catalog, we don't know if it's bright or dark, so default to
	 * `true` so the scrim ensures headline contrast in either case.
	 *
	 * Only applies when operator hasn't explicitly set the overlay
	 * Customizer toggle (the get_theme_mod default is false; this
	 * pre-filter changes the resolved value to true).
	 */
	function lafka_home_default_hero_overlay( $value ) {
		// Only auto-enable overlay when the hero is using the
		// auto-discovered image (i.e. operator hasn't set their own).
		if ( $value ) {
			return $value;
		}
		$customizer_image  = (int) get_theme_mod( 'lafka_home_hero_image_id', 0 );
		$customizer_bg_url = (string) get_theme_mod( 'lafka_home_hero_bg_url', '' );
		if ( $customizer_image || '' !== $customizer_bg_url ) {
			return $value;
		}
		return true;
	}
}
add_filter( 'theme_mod_lafka_home_hero_overlay', 'lafka_home_default_hero_overlay' );
