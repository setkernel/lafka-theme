<?php
/**
 * Public "Reach us" email resolver — footer / contact surfaces (audit V4).
 *
 * The footer's Reach-us column previously fell straight back to the resolved
 * business email, which on a dev/staging install is the auto-generated
 * admin_email derived from the site URL — e.g. "info@localhost:8080". That
 * leaked the port into the rendered mailto: and on-page text.
 *
 * Resolution order here:
 *   1. Operator-configured business email — lafka_get_restaurant_info()['email']
 *      (the plugin's canonical resolver) then the lafka_business_email theme_mod
 *      as a plugin-absent fallback.
 *   2. A host-derived "info@<host>" address as a last resort.
 *
 * The host is always taken with wp_parse_url( home_url(), PHP_URL_HOST ) — HOST
 * ONLY, never the port — so no address ever carries a ":8080". A configured
 * value that itself carries a ":" (the admin_email default on a ported dev
 * install) is rejected in favour of that clean host-only derivation.
 *
 * @package Lafka
 * @since   6.19.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_theme_reach_email' ) ) {
	/**
	 * Resolve the public contact email, never leaking a host port.
	 *
	 * @return string A display email address, or '' when the host is unresolvable.
	 */
	function lafka_theme_reach_email() {
		$email = '';

		// 1a. Operator-configured business email (plugin resolver).
		if ( function_exists( 'lafka_get_restaurant_info' ) ) {
			$info = lafka_get_restaurant_info();
			if ( is_array( $info ) && isset( $info['email'] ) ) {
				$email = (string) $info['email'];
			}
		}

		// 1b. Theme_mod fallback when the plugin resolver isn't loaded.
		if ( '' === $email && function_exists( 'get_theme_mod' ) ) {
			$email = (string) get_theme_mod( 'lafka_business_email', '' );
		}

		// 2. Reject an empty OR port-leaking address (e.g. the admin_email
		// default "info@localhost:8080" on a ported dev install) and derive a
		// clean host-only "info@<host>" instead. wp_parse_url(..., PHP_URL_HOST)
		// returns the host WITHOUT the port.
		if ( '' === $email || false !== strpos( $email, ':' ) ) {
			$host = '';
			if ( function_exists( 'wp_parse_url' ) && function_exists( 'home_url' ) ) {
				$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
			}
			$email = '' !== $host ? 'info@' . $host : '';
		}

		return $email;
	}
}
