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

if ( ! function_exists( 'lafka_counter_drawer_delivery_note' ) ) {
	/**
	 * O-24: what Delivery means, shown under the drawer's Pickup/Delivery
	 * cards while Delivery is chosen — the minimum order and free-delivery
	 * threshold the plugin enforces (nothing when not configured) and when
	 * the fee appears. Filter `lafka_counter_drawer_delivery_note`.
	 *
	 * @return string Plain text ('' = no note).
	 */
	function lafka_counter_drawer_delivery_note(): string {
		$money = static function ( float $amount ): string {
			return function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) : number_format_i18n( $amount, 2 );
		};
		$min   = function_exists( 'lafka_delivery_minimum' ) ? (float) lafka_delivery_minimum() : 0.0;
		$free  = function_exists( 'lafka_get_free_delivery_threshold' ) ? (float) lafka_get_free_delivery_threshold() : 0.0;
		$parts = array();
		if ( $min > 0 ) {
			/* translators: %s: minimum order amount for delivery */
			$parts[] = sprintf( __( 'Delivery on orders over %s.', 'lafka' ), $money( $min ) );
		}
		if ( $free > 0 ) {
			/* translators: %s: order amount above which delivery is free */
			$parts[] = sprintf( __( 'Free delivery over %s.', 'lafka' ), $money( $free ) );
		}
		$parts[] = __( 'The delivery fee shows at checkout once you enter your address.', 'lafka' );

		/**
		 * Filter the drawer's Delivery note (plain text; '' hides it).
		 *
		 * @since 7.3.0
		 * @param string $note    Note.
		 * @param float  $minimum Delivery minimum (0 = none).
		 * @param float  $free    Free-delivery threshold (0 = none).
		 */
		return (string) apply_filters( 'lafka_counter_drawer_delivery_note', implode( ' ', $parts ), $min, $free );
	}
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
