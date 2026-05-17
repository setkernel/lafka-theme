<?php
/**
 * Force /product-category/{slug}/ requests to render the WC term archive,
 * even when an attachment or post shares the slug.
 *
 * Root cause: WordPress's URL resolver matches the slug against any
 * eligible post (attachment, page, single product) before it considers
 * the taxonomy archive. On pepperypizzapoutine.com:
 *   - /product-category/pizza/ resolves to attachment 7587 ("pizza.png")
 *   - /product-category/donair/ resolves to product "donair"
 *   - /product-category/nachos/ resolves to product "nachos"
 *   - /product-category/garlic-fingers/ resolves to product "garlic-fingers"
 *   - /product-category/sauces/ resolves to product "sauces"
 * In each case WP then 301-redirects to the resolved post's canonical
 * URL — the PNG file or the product permalink — so the customer never
 * sees a category archive.
 *
 * Fix (primary): hook the `request` filter at priority 1. If the URL
 * matches /product-category/{slug}/, look up the term, and replace the
 * parsed query vars with a clean tax-archive query. WP then loads the
 * archive template and ignores the same-slug post.
 *
 * Fix (belt + suspenders): keep the wp_redirect / redirect_canonical
 * filters from v6.7.1 so that if the request filter ever misses (custom
 * rewrites, future WP changes), a stray redirect to /wp-content/uploads/
 * is still cancelled.
 *
 * @package Lafka\WooCommerce
 * @since   6.7.1  Initial wp_redirect / redirect_canonical filters.
 * @since   6.7.2  Added `request` filter — root-cause fix that prevents
 *                 WP from matching the same-slug attachment / product in
 *                 the first place, instead of just cancelling the
 *                 downstream redirect.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_force_product_cat_archive_query' ) ) {
	/**
	 * Rewrite the parsed query so /product-category/{slug}/ always loads
	 * the WC term archive, never a same-slug attachment or product.
	 *
	 * Only fires for one-segment /product-category/{slug}/ URLs. Pagination
	 * and feed sub-segments are left alone so WP's normal rewrites still
	 * handle /product-category/pizza/page/2/ etc.
	 *
	 * @param array $vars Parsed query vars from WP::parse_request().
	 * @return array
	 */
	function lafka_force_product_cat_archive_query( $vars ) {
		if ( ! is_array( $vars ) ) {
			return $vars;
		}
		$req = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		// Strip query string for pattern matching.
		$path = strtok( $req, '?' );
		if ( ! is_string( $path ) || '' === $path ) {
			return $vars;
		}
		// Match exactly /product-category/{slug}/ — refuse deeper paths so we
		// don't break pagination, feeds, or other tail segments.
		if ( ! preg_match( '#^/product-category/([^/]+)/?$#', $path, $m ) ) {
			return $vars;
		}
		$slug = $m[1];
		if ( ! function_exists( 'term_exists' ) ) {
			return $vars;
		}
		// term_exists returns array|null|0; we only want a real match.
		$term = term_exists( $slug, 'product_cat' );
		if ( ! $term ) {
			return $vars;
		}
		// Replace the entire query so attachment/post lookups don't run.
		return array(
			'product_cat' => $slug,
			'taxonomy'    => 'product_cat',
			'term'        => $slug,
		);
	}
}

add_filter( 'request', 'lafka_force_product_cat_archive_query', 1 );

if ( ! function_exists( 'lafka_block_category_to_attachment_redirect' ) ) {
	/**
	 * Secondary safety net — cancel any redirect from /product-category/*
	 * to /wp-content/uploads/* in case the request filter is bypassed.
	 *
	 * @param string $location Proposed redirect URL.
	 * @return string|false
	 */
	function lafka_block_category_to_attachment_redirect( $location ) {
		if ( ! is_string( $location ) || '' === $location ) {
			return $location;
		}
		$req = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $req, '/product-category/' ) === false ) {
			return $location;
		}
		if ( strpos( $location, '/wp-content/uploads/' ) === false ) {
			return $location;
		}
		return false;
	}
}

add_filter( 'wp_redirect', 'lafka_block_category_to_attachment_redirect', 1 );
add_filter( 'redirect_canonical', 'lafka_block_category_to_attachment_redirect', 1 );
