<?php
/**
 * Prevent WordPress from redirecting /product-category/{slug}/ URLs
 * to attachment image files (e.g. /wp-content/uploads/.../{slug}.png).
 *
 * Root cause: when a WC product_cat term shares a slug with an existing
 * attachment post (e.g. category "pizza" + an uploaded "pizza.png" in
 * /wp-content/uploads/), WordPress's URL router can resolve to the
 * attachment first and 301-redirect the category URL to the image file.
 * Customers landing on the category page from Google search results
 * then see a tiny PNG instead of the product archive.
 *
 * Observed on pepperypizzapoutine.com 2026-05-17: 5 of 21 WC categories
 * affected (pizza, donair, nachos, garlic-fingers, sauces) — each had
 * a same-slug PNG in /wp-content/uploads/2021/06/.
 *
 * Fix: short-circuit any wp_redirect / redirect_canonical whose source
 * is /product-category/* and destination is /wp-content/uploads/*.
 * Returning false from either filter cancels the redirect; WP then
 * serves the actual archive template.
 *
 * @package Lafka\WooCommerce
 * @since   6.7.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_block_category_to_attachment_redirect' ) ) {
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
