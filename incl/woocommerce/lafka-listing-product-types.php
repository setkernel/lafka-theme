<?php
/**
 * GX4 polish: keep products with a legacy / unregistered product type listable.
 *
 * wc_get_products() restricts results to the REGISTERED product types
 * (simple, grouped, external, variable, …) by default. Stores migrated from
 * older Lafka versions carry products whose `product_type` term is a type no
 * longer registered (e.g. `combo`): WooCommerce still loads and sells them as
 * simple products (wc_get_product() falls back to WC_Product_Simple), the
 * category archive's main query shows them, but every wc_get_products() list —
 * the /menu/ page groups, the counter homepage's deals — silently drops them.
 *
 * When a query uses the DEFAULT type list, this adds the product_type slugs
 * that are actually in use but unregistered. A query asking for specific types
 * is left alone. Filter: lafka_listing_extra_product_types.
 *
 * @package Lafka
 * @since   7.2.x (GX4 polish)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_listing_extra_product_types' ) ) {
	/**
	 * In-use product_type slugs that WooCommerce does not register.
	 *
	 * @return string[]
	 */
	function lafka_listing_extra_product_types(): array {
		$extra = array();
		if ( function_exists( 'wc_get_product_types' ) && function_exists( 'get_terms' ) ) {
			// get_terms() results are object-cached by WordPress.
			$in_use = get_terms(
				array(
					'taxonomy'   => 'product_type',
					'fields'     => 'slugs',
					'hide_empty' => true,
				)
			);
			if ( is_array( $in_use ) ) {
				$extra = array_values( array_diff( array_map( 'strval', $in_use ), array_keys( wc_get_product_types() ) ) );
			}
		}
		return array_values( array_filter( array_map( 'sanitize_key', (array) apply_filters( 'lafka_listing_extra_product_types', $extra ) ) ) );
	}
}

if ( ! function_exists( 'lafka_listing_product_query_args' ) ) {
	/**
	 * woocommerce_product_object_query_args: widen the DEFAULT type list only.
	 *
	 * @param array<string,mixed> $args Query vars.
	 * @return array<string,mixed>
	 */
	function lafka_listing_product_query_args( $args ) {
		if ( ! is_array( $args ) || ! isset( $args['type'] ) || ! is_array( $args['type'] ) || ! function_exists( 'wc_get_product_types' ) ) {
			return $args;
		}
		$default = array_keys( wc_get_product_types() );
		$given   = $args['type'];
		sort( $default );
		sort( $given );
		if ( $given !== $default ) {
			return $args; // The caller asked for specific types.
		}
		$extra = lafka_listing_extra_product_types();
		if ( $extra ) {
			$args['type'] = array_values( array_unique( array_merge( $args['type'], $extra ) ) );
		}
		return $args;
	}
}
add_filter( 'woocommerce_product_object_query_args', 'lafka_listing_product_query_args' );
