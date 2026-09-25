<?php
/**
 * Classic checkout: plain-language inline field errors (O-31).
 *
 * WooCommerce marks a field invalid on blur (red border only) and prints an
 * inline message only after a failed submit. js/lafka-checkout-fields.js
 * prints the same element WooCommerce uses on submit
 * (p.checkout-inline-error-message#{field}_description, wired with
 * aria-describedby) as soon as a field is left invalid, and treats a phone
 * with too few digits as invalid. The server-side phone rule lives in
 * lafka-plugin; this is the client half only.
 *
 * Filters:
 *   lafka_checkout_phone_min_digits (int, default 7) — digits a phone needs.
 *   lafka_checkout_fields_js_config (array)          — the whole client config.
 *
 * @package Lafka\WooCommerce
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_checkout_fields_js_config' ) ) {
	/**
	 * Client configuration for js/lafka-checkout-fields.js.
	 *
	 * @return array{phoneMinDigits:int,i18n:array<string,string>}
	 */
	function lafka_checkout_fields_js_config() {
		/**
		 * Filter how many digits a checkout phone number needs (0 = no check).
		 *
		 * @since 7.3.0
		 * @param int $digits Default 7.
		 */
		$min_digits = max( 0, (int) apply_filters( 'lafka_checkout_phone_min_digits', 7 ) );

		$config = array(
			'phoneMinDigits' => $min_digits,
			'i18n'           => array(
				/* translators: %s: the field's label, e.g. "Phone". */
				'required' => __( '%s is required.', 'lafka' ),
				'email'    => __( 'Enter a valid email address.', 'lafka' ),
				'phone'    => __( 'Enter a valid phone number.', 'lafka' ),
				'invalid'  => __( 'Check this field.', 'lafka' ),
			),
		);

		/**
		 * Filter the classic-checkout inline error configuration.
		 *
		 * @since 7.3.0
		 * @param array $config phoneMinDigits + i18n strings.
		 */
		return (array) apply_filters( 'lafka_checkout_fields_js_config', $config );
	}
}

if ( ! function_exists( 'lafka_is_classic_checkout_form_page' ) ) {
	/**
	 * The classic (shortcode) checkout form page: checkout, not the
	 * order-received / order-pay endpoints, not a block checkout.
	 *
	 * @return bool
	 */
	function lafka_is_classic_checkout_form_page() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return false;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) ) {
			return false;
		}

		return ! ( function_exists( 'lafka_is_block_cart_checkout_page' ) && lafka_is_block_cart_checkout_page() );
	}
}

if ( ! function_exists( 'lafka_enqueue_checkout_fields_script' ) ) {
	/**
	 * Enqueue the inline-error helper on the classic checkout form page. It
	 * depends on WooCommerce's wc-checkout, so it never prints without it.
	 *
	 * @return void
	 */
	function lafka_enqueue_checkout_fields_script() {
		if ( ! lafka_is_classic_checkout_form_page() ) {
			return;
		}
		wp_enqueue_script(
			'lafka-checkout-fields',
			get_template_directory_uri() . '/js/lafka-checkout-fields.js',
			array( 'jquery', 'wc-checkout' ),
			function_exists( 'lafka_asset_version' ) ? lafka_asset_version( '/js/lafka-checkout-fields.js' ) : null,
			true
		);
		wp_localize_script( 'lafka-checkout-fields', 'lafkaCheckoutFields', lafka_checkout_fields_js_config() );
	}
	add_action( 'wp_enqueue_scripts', 'lafka_enqueue_checkout_fields_script', 20 );
}
