<?php
/**
 * Storefront search = menu search (GX QA M-04 / O-15 / T-27).
 *
 * On a counter storefront a bare site search (`/?s=pizza`, which is also the
 * JSON-LD SearchAction target and what the header search submits) becomes a
 * WooCommerce product search, so it lands on archive-product.php's search view
 * (menu rows with prices and Add, a count, a real empty state) instead of the
 * legacy blog search template. An explicit `post_type` is left alone.
 *
 * Filter `lafka_search_products_only` (bool) — default: WooCommerce active and
 * the menu surface on the counter layout.
 *
 * @package Lafka\WooCommerce
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_search_products_only' ) ) {
	/** Whether a bare site search should search the menu only. */
	function lafka_search_products_only(): bool {
		$on = function_exists( 'wc_get_page_id' )
			&& function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'menu', 'counter' );
		return (bool) apply_filters( 'lafka_search_products_only', $on );
	}
}

if ( ! function_exists( 'lafka_search_request_vars' ) ) {
	/**
	 * PURE: the request's query vars with a bare search turned into a product
	 * search.
	 *
	 * @param array<string,mixed> $vars    Parsed query vars.
	 * @param bool                $enabled lafka_search_products_only().
	 * @return array<string,mixed>
	 */
	function lafka_search_request_vars( array $vars, bool $enabled ): array {
		if ( $enabled && isset( $vars['s'] ) && '' !== trim( (string) $vars['s'] ) && empty( $vars['post_type'] ) ) {
			$vars['post_type'] = 'product';
		}
		return $vars;
	}
}

if ( ! function_exists( 'lafka_search_request_filter' ) ) {
	/**
	 * `request`: route bare front-end searches to the menu.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	function lafka_search_request_filter( $vars ) {
		if ( ! is_array( $vars ) || is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			return $vars;
		}
		return lafka_search_request_vars( $vars, lafka_search_products_only() );
	}
}
add_filter( 'request', 'lafka_search_request_filter' );
