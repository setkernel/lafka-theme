<?php
/**
 * Serve the theme's service worker (js/sw.js) from the site root.
 *
 * A browser only lets a service worker control the pages at or below the
 * folder it is loaded from, so the worker file in /wp-content/themes/ cannot
 * control the site ("/") and registration fails. Loading it through
 * `/?lafka_sw=1` gives it the site root as its folder. Nothing is written to
 * disk and no rewrite rule is needed.
 *
 * @package Lafka
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_service_worker_url' ) ) {
	/**
	 * URL the service worker is registered from.
	 *
	 * @since 7.4.0
	 * @return string
	 */
	function lafka_service_worker_url(): string {
		return home_url( '/?lafka_sw=1' );
	}
}

if ( ! function_exists( 'lafka_serve_service_worker' ) ) {
	/**
	 * Send js/sw.js with the headers a service worker needs, then stop.
	 *
	 * @since 7.4.0
	 * @return void
	 */
	function lafka_serve_service_worker(): void {
		// Public, read-only file: a query flag selects it, nothing is changed.
		if ( '1' !== filter_input( INPUT_GET, 'lafka_sw', FILTER_SANITIZE_NUMBER_INT ) ) {
			return;
		}
		$file = get_template_directory() . '/js/sw.js';
		if ( ! is_readable( $file ) ) {
			status_header( 404 );
			exit;
		}
		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$filesystem = new WP_Filesystem_Direct( null );
		nocache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		echo lafka_service_worker_escape( (string) $filesystem->get_contents( $file ) );
		exit;
	}
	add_action( 'init', 'lafka_serve_service_worker', 1 );
}

if ( ! function_exists( 'lafka_service_worker_escape' ) ) {
	/**
	 * Output filter for the worker source: valid UTF-8 and no control
	 * characters other than tab and line breaks. Registered as an escaping
	 * function in .phpcs.xml.dist (the file is the theme's own, not user input).
	 *
	 * @since 7.4.0
	 * @param string $source Worker source.
	 * @return string
	 */
	function lafka_service_worker_escape( string $source ): string {
		$source = wp_check_invalid_utf8( $source, true );
		return (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $source );
	}
}
