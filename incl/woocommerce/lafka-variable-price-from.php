<?php
/**
 * Variable-product price formatting — "from $X.XX" instead of "$min – $max".
 *
 * Conversion audit recommendation: customers can't scan a price range
 * fast on mobile. A single anchor price ("from $12.50") sets a clear
 * baseline expectation and lets the variation selector / PDP do the
 * work of revealing the full price grid.
 *
 * Applies to every WC surface that calls $product->get_price_html()
 * for a variable product: archive cards, home showcase cards, PDPs,
 * cart line items where applicable, etc.
 *
 * Filter surface:
 *   lafka_use_from_pricing(bool, $product) — operator opt-out per product
 *
 * @package Lafka\WooCommerce
 * @since   5.38.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_variable_price_from' ) ) {
	/**
	 * Replace WC's "$min – $max" variable price HTML with "from $min".
	 *
	 * @param string                $price   Existing HTML.
	 * @param WC_Product_Variable   $product Product object.
	 * @return string
	 */
	function lafka_variable_price_from( $price, $product ) {
		if ( ! $product || ! is_a( $product, 'WC_Product_Variable' ) ) {
			return $price;
		}
		if ( ! (bool) apply_filters( 'lafka_use_from_pricing', true, $product ) ) {
			return $price;
		}

		$min_price = $product->get_variation_price( 'min', true );
		$max_price = $product->get_variation_price( 'max', true );

		// Single-variation or zero-variation fallback: just show the price.
		if ( '' === $min_price || $min_price === $max_price ) {
			return wc_price( $min_price );
		}

		return sprintf(
			'<span class="lafka-price-from"><span class="lafka-price-from__label">%s</span> %s</span>',
			esc_html__( 'from', 'lafka' ),
			wc_price( $min_price )
		);
	}
}
add_filter( 'woocommerce_variable_price_html', 'lafka_variable_price_from', 10, 2 );
add_filter( 'woocommerce_variable_sale_price_html', 'lafka_variable_price_from', 10, 2 );
