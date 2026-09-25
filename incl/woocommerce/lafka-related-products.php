<?php
/**
 * Related products on the single-product page — count + layout args.
 *
 * The count comes from the `lafka_number_related_products` theme_mod (0 hides
 * the section). Legacy-migrated installs store it as a STRING ("6"), and
 * wc_get_related_products() logs an ERROR ("Invalid limit type … Expected
 * integer, got string") on every product page when handed one — so the value
 * is coerced to a clamped int here before it reaches WooCommerce.
 *
 * Moved out of incl/woocommerce-functions.php so it is unit-testable.
 *
 * @package Lafka\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_related_products_count' ) ) {
	/**
	 * Number of related products to show, as a clamped int.
	 *
	 * Non-numeric / empty values fall back to the default (6); negatives are
	 * made absolute; anything above 24 is capped (one PDP section should never
	 * pull an unbounded product query).
	 *
	 * @return int 0 (hidden) … 24.
	 */
	function lafka_related_products_count(): int {
		$default = 6;
		$value   = get_theme_mod( 'lafka_number_related_products', $default );
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		return min( absint( $value ), 24 );
	}
}

// If related products are set to zero hide them.
if ( 0 === lafka_related_products_count() ) {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}

add_filter( 'woocommerce_output_related_products_args', 'lafka_related_products_args' );
if ( ! function_exists( 'lafka_related_products_args' ) ) {
	/**
	 * Related-products query/layout args for WooCommerce.
	 *
	 * @param array $args WC defaults (posts_per_page, columns, orderby).
	 * @return array
	 */
	function lafka_related_products_args( $args ) {
		$args                   = (array) $args;
		$args['posts_per_page'] = lafka_related_products_count();
		$args['columns']        = 1; // Arranged in 1 column.

		return $args;
	}
}
