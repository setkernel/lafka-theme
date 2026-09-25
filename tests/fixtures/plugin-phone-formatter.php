<?php
/**
 * Stand-in for lafka-plugin's lafka_format_phone_display( $phone, $country ).
 *
 * Loaded ON DEMAND by PhoneDisplayTest (not at file load) so the
 * "plugin absent" case can run in a separate process without it. Records its
 * calls and answers from $GLOBALS['lafka_test_phone_formats'][ $phone ]
 * (default: a NANP-style rendering of +1 numbers, the input otherwise).
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

if ( ! function_exists( 'lafka_format_phone_display' ) ) {
	function lafka_format_phone_display( string $phone, string $country = '' ): string {
		$GLOBALS['lafka_test_phone_format_calls'][] = $phone;
		if ( isset( $GLOBALS['lafka_test_phone_formats'][ $phone ] ) ) {
			return $GLOBALS['lafka_test_phone_formats'][ $phone ];
		}
		$digits = preg_replace( '/\D/', '', $phone );
		if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
			return sprintf( '(%s) %s-%s', substr( $digits, 1, 3 ), substr( $digits, 4, 3 ), substr( $digits, 7 ) );
		}
		return $phone;
	}
}
