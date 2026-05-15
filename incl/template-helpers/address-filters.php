<?php
/**
 * Address-display filter — strip country code from the resolved
 * `address_display` field when it appears on its own line.
 *
 * Default address_display from lafka_get_restaurant_info() is:
 *   "Street\nCity, Region Postal\nCountry"
 * For a local restaurant site the country code on the last line is
 * redundant noise. Strip it for display while leaving the underlying
 * `country` field intact for schema/JSON-LD use.
 *
 * Operators in international markets where country matters can disable
 * this via filter:
 *   add_filter( 'lafka_strip_country_from_address', '__return_false' );
 *
 * @package Lafka
 * @since   5.63.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_strip_country_from_restaurant_info' ) ) {

	function lafka_strip_country_from_restaurant_info( $info ) {
		if ( ! is_array( $info ) ) {
			return $info;
		}
		if ( ! (bool) apply_filters( 'lafka_strip_country_from_address', true ) ) {
			return $info;
		}
		if ( ! empty( $info['address_display'] ) ) {
			$lines = explode( "\n", (string) $info['address_display'] );
			if ( count( $lines ) > 1 ) {
				$last = trim( end( $lines ) );
				$country = isset( $info['country'] ) ? trim( (string) $info['country'] ) : '';
				// Drop the last line if it equals the country (case-insensitive).
				if ( '' !== $country && 0 === strcasecmp( $last, $country ) ) {
					array_pop( $lines );
					$info['address_display'] = implode( "\n", $lines );
				}
			}
		}
		return $info;
	}
}
add_filter( 'lafka_restaurant_info', 'lafka_strip_country_from_restaurant_info', 5 );
