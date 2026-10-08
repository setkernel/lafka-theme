<?php
/**
 * Theme → Lafka logging bridge (GX1 · Diagnostics).
 *
 * The theme never depends on the Lafka plugin. It logs by firing the
 * `lafka_log` action; the plugin's Lafka_Log facade listens and writes the
 * record to WooCommerce → Status → Logs (source `lafka-theme`), scrubbed of
 * personal data, with warnings and errors listed on Lafka → Diagnostics.
 *
 * Without a listener (plugin inactive) nothing is written, unless the
 * fallback is on — WP_DEBUG by default, filter `lafka_theme_log_fallback` —
 * in which case one line goes to the WooCommerce log (message only; context is
 * never written there because the theme has no scrubber).
 *
 * The child theme uses the same action directly:
 *   do_action( 'lafka_log', 'warning', 'child', 'Message', array( 'code' => 'x' ) );
 *
 * @package Lafka
 * @since   7.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_theme_log' ) ) {
	/**
	 * Log a theme message through the Lafka plugin (if active).
	 *
	 * @param string $level   debug|info|notice|warning|error|critical.
	 * @param string $message Message (the plugin scrubs personal data).
	 * @param array  $context Structured context; `code` is a stable machine code.
	 * @param string $channel Channel — 'theme' (default) or 'child'.
	 * @return bool Whether the message went anywhere.
	 */
	function lafka_theme_log( $level, $message, $context = array(), $channel = 'theme' ) {
		$level   = is_string( $level ) && '' !== $level ? strtolower( $level ) : 'info';
		$channel = is_string( $channel ) && '' !== $channel ? $channel : 'theme';
		$context = is_array( $context ) ? $context : array( 'value' => $context );
		$message = is_scalar( $message ) ? (string) $message : '';

		if ( has_action( 'lafka_log' ) ) {
			do_action( 'lafka_log', $level, $channel, $message, $context );
			return true;
		}

		$fallback = (bool) apply_filters( 'lafka_theme_log_fallback', defined( 'WP_DEBUG' ) && WP_DEBUG, $level, $channel );
		if ( ! $fallback || ! function_exists( 'wc_get_logger' ) ) {
			return false;
		}
		$known_levels = array( 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' );
		wc_get_logger()->log( in_array( $level, $known_levels, true ) ? $level : 'info', $message, array( 'source' => 'lafka-' . $channel ) );
		return true;
	}
}
