<?php
/**
 * Exclude WC's default "Uncategorized" product category from all
 * customer-facing category listings (shop subcategories, /order/
 * carousels, widgets that query product_cat).
 *
 * Uncategorized is a system bucket for orphan products; surfacing it
 * to customers looks like a real menu category and creates noise.
 * The /menu/ template already excluded it via page-menu.php; this
 * module extends the exclusion to:
 *   - WC's shop subcategory display
 *     (`woocommerce_product_subcategories_args` filter)
 *   - Any `get_terms('product_cat')` query on the front-end
 *     (`get_terms_args` filter)
 *
 * Operators wanting the bucket visible can disable via the
 * `lafka_exclude_uncategorized` filter or the matching Customizer
 * toggle.
 *
 * @package Lafka\WooCommerce
 * @since   5.37.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
	/**
	 * Resolve the term IDs to exclude. Caches the result for the
	 * current request to avoid repeated DB hits.
	 *
	 * @return int[]
	 */
	function lafka_uncategorized_excluded_ids() {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		$ids = array();
		$default_cat = (int) get_option( 'default_product_cat', 0 );
		if ( $default_cat ) {
			$ids[] = $default_cat;
		}
		$uncategorized = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		if ( $uncategorized && ! in_array( (int) $uncategorized->term_id, $ids, true ) ) {
			$ids[] = (int) $uncategorized->term_id;
		}
		$cached = (array) apply_filters( 'lafka_uncategorized_term_ids', $ids );
		return $cached;
	}
}

if ( ! function_exists( 'lafka_filter_subcategories_exclude_uncategorized' ) ) {
	/**
	 * Strip Uncategorized from WC's shop subcategory display args.
	 *
	 * @param array $args get_terms() args.
	 * @return array
	 */
	function lafka_filter_subcategories_exclude_uncategorized( $args ) {
		if ( is_admin() ) {
			return $args;
		}
		if ( ! (bool) apply_filters( 'lafka_exclude_uncategorized', true ) ) {
			return $args;
		}
		$excluded = lafka_uncategorized_excluded_ids();
		if ( empty( $excluded ) ) {
			return $args;
		}
		$existing = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();
		$args['exclude'] = array_values( array_unique( array_merge( $existing, $excluded ) ) );
		return $args;
	}
}
add_filter( 'woocommerce_product_subcategories_args', 'lafka_filter_subcategories_exclude_uncategorized' );

if ( ! function_exists( 'lafka_filter_get_terms_exclude_uncategorized' ) ) {
	/**
	 * Front-end get_terms('product_cat') queries also strip Uncategorized.
	 * Admin queries are left untouched so the term remains manageable.
	 *
	 * @param array $args     get_terms() args.
	 * @param array $taxonomies Taxonomy slugs.
	 * @return array
	 */
	function lafka_filter_get_terms_exclude_uncategorized( $args, $taxonomies ) {
		if ( is_admin() ) {
			return $args;
		}
		if ( ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
			return $args;
		}
		if ( ! (bool) apply_filters( 'lafka_exclude_uncategorized', true ) ) {
			return $args;
		}
		$excluded = lafka_uncategorized_excluded_ids();
		if ( empty( $excluded ) ) {
			return $args;
		}
		$existing = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();
		$args['exclude'] = array_values( array_unique( array_merge( $existing, $excluded ) ) );
		return $args;
	}
}
add_filter( 'get_terms_args', 'lafka_filter_get_terms_exclude_uncategorized', 10, 2 );
