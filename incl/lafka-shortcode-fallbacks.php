<?php
/**
 * Lafka shortcode fallbacks
 *
 * Some operators embed shortcodes from optional/3rd-party plugins
 * directly in widget content (MC4WP newsletter forms, etc.). When the
 * source plugin is deactivated or missing, WordPress falls through to
 * rendering the raw `[shortcode_name id="..."]` as literal text on the
 * page — visibly broken to customers.
 *
 * We register no-op fallback handlers ONLY for shortcodes that:
 *   1. Have no Lafka-native equivalent
 *   2. Are commonly embedded in widget areas via copy-paste from older
 *      operator documentation
 *
 * The fallbacks register at priority 999 on `init`, so if the source
 * plugin loads first it wins (WP `add_shortcode` overwrites). When the
 * source plugin is absent, our handler renders an empty string instead
 * of the orphan literal text.
 *
 * Operators can disable any fallback by returning false from the
 * `lafka_register_shortcode_fallback_{$tag}` filter, or all of them
 * via `lafka_register_shortcode_fallbacks`.
 *
 * @package Lafka\Shortcodes
 * @since   5.40.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_register_shortcode_fallbacks' ) ) {
	/**
	 * Register no-op handlers for known-orphan shortcode tags.
	 *
	 * Only registers when the real handler is NOT already present, so an
	 * activated plugin always wins. Re-runs each request (cheap — just
	 * `shortcode_exists()` checks).
	 *
	 * @return void
	 */
	function lafka_register_shortcode_fallbacks() {
		if ( ! (bool) apply_filters( 'lafka_register_shortcode_fallbacks', true ) ) {
			return;
		}

		$fallbacks = (array) apply_filters(
			'lafka_shortcode_fallback_tags',
			array(
				'mc4wp_form',    // MailChimp for WordPress newsletter form.
				'mc4wp-form',    // Alternative hyphenated tag.
				'contact-form-7', // CF7 form embed.
			)
		);

		foreach ( $fallbacks as $tag ) {
			$tag = (string) $tag;
			if ( '' === $tag ) {
				continue;
			}
			if ( shortcode_exists( $tag ) ) {
				continue;
			}
			if ( ! (bool) apply_filters( "lafka_register_shortcode_fallback_{$tag}", true ) ) {
				continue;
			}
			add_shortcode( $tag, 'lafka_shortcode_fallback_noop' );
		}
	}
}

if ( ! function_exists( 'lafka_shortcode_fallback_noop' ) ) {
	/**
	 * Render nothing. Used as the universal handler for missing-plugin
	 * shortcodes so the literal bracket fragment doesn't leak to the page.
	 *
	 * @return string Empty string.
	 */
	function lafka_shortcode_fallback_noop() {
		return '';
	}
}

// Priority 999 so legitimate plugins (which typically register on `init`
// at default priority 10) win the race. We only ever fill the gap.
add_action( 'init', 'lafka_register_shortcode_fallbacks', 999 );
