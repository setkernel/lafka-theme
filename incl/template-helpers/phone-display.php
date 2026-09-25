<?php
/**
 * Visible phone text — never a raw E.164 string.
 *
 * lafka_get_restaurant_info() carries phone_e164 (for `tel:` links) and
 * phone_display (what customers read). When the display value is empty, or is
 * itself an unformatted number ("+19025550100"), the visible text goes through
 * lafka-plugin's lafka_format_phone_display() ("(902) 555-0100") when the
 * plugin provides it; without the plugin the value is returned unchanged.
 * `tel:` hrefs keep using phone_e164.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_theme_phone_display' ) ) {
	/**
	 * Human-readable phone text for a template.
	 *
	 * @param string $display Operator display value (may be empty).
	 * @param string $e164    E.164 number, used when $display is empty.
	 * @return string '' when there is no number at all.
	 */
	function lafka_theme_phone_display( $display, $e164 = '' ): string {
		$display = trim( (string) $display );
		$raw     = '' !== $display ? $display : trim( (string) $e164 );
		if ( '' === $raw ) {
			return '';
		}

		// Only a bare number (optional +, digits only) is reformatted — an
		// operator-typed display value ("902-555-0100 ext. 2") is kept verbatim.
		if ( preg_match( '/^\+?\d{7,15}$/', $raw ) && function_exists( 'lafka_format_phone_display' ) ) {
			$formatted = trim( (string) lafka_format_phone_display( $raw ) );
			if ( '' !== $formatted ) {
				return $formatted;
			}
		}

		return $raw;
	}
}
