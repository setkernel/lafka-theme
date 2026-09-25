<?php
/**
 * Order-path glue for the counter drawer (GX order-path QA).
 *
 *  - O-02: the counter layout's add-to-cart opens the order drawer. With
 *    WooCommerce's "Redirect to the cart page after successful addition" on,
 *    every drawer upsell "Add" and PDP add jumped to /cart/ instead, where
 *    "added to your cart" notices stacked up. While the counter drawer is the
 *    active drawer layout the option reads "no" on the front end (the admin
 *    settings screen still shows the stored value). Filter
 *    `lafka_counter_force_ajax_add` (bool, default true) to keep the redirect.
 *  - O-07: the shipping method ids that count as pickup reach the fulfilment
 *    script (window.lafkaCfg.pickupMethods), so a Pickup/Delivery choice on the
 *    header, drawer or cart tabs selects the matching shipping rate on the
 *    classic cart and checkout (and a rate picked there updates the choice).
 *
 * @package Lafka
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_counter_cart_redirect_after_add' ) ) {
	/**
	 * pre_option_woocommerce_cart_redirect_after_add: "no" under the counter
	 * drawer (front end only).
	 *
	 * @param mixed $pre Short-circuit value (false = read the option).
	 * @return mixed
	 */
	function lafka_counter_cart_redirect_after_add( $pre ) {
		if ( is_admin() && ! ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			return $pre;
		}
		if ( ! function_exists( 'lafka_layout_is' ) || ! lafka_layout_is( 'drawer', 'counter' ) ) {
			return $pre;
		}

		/**
		 * Filter whether the counter drawer forces WooCommerce's "redirect to
		 * the cart after add" off (so adds open the drawer instead).
		 *
		 * @since 7.3.0
		 * @param bool $force Default true.
		 */
		return apply_filters( 'lafka_counter_force_ajax_add', true ) ? 'no' : $pre;
	}
	add_filter( 'pre_option_woocommerce_cart_redirect_after_add', 'lafka_counter_cart_redirect_after_add' );
}

if ( ! function_exists( 'lafka_order_path_fulfilment_cfg' ) ) {
	/**
	 * lafka_fulfilment_js_config: add the pickup shipping method ids.
	 *
	 * @param mixed $cfg window.lafkaCfg.
	 * @return mixed
	 */
	function lafka_order_path_fulfilment_cfg( $cfg ) {
		if ( ! is_array( $cfg ) ) {
			return $cfg;
		}
		// Same list (and filter) lafka-plugin uses to tell pickup from delivery.
		$cfg['pickupMethods'] = array_values( array_map( 'strval', (array) apply_filters( 'lafka_pickup_shipping_method_ids', array( 'local_pickup', 'pickup_location' ) ) ) );

		return $cfg;
	}
	add_filter( 'lafka_fulfilment_js_config', 'lafka_order_path_fulfilment_cfg' );
}
