<?php
/**
 * P6-PERF-5: critical CSS inline + non-critical stylesheet deferral.
 *
 * Two hooks:
 *
 *  1. lafka_inline_critical_css()  — wp_head priority 1
 *     Reads styles/critical.css, strips comments + extra whitespace, and
 *     emits a <style id="lafka-critical-css"> as the very first thing in
 *     <head>.  This gives the browser layout-critical rules before any
 *     render-blocking network request fires.
 *
 *  2. lafka_defer_non_critical_css()  — style_loader_tag priority 999
 *     Converts every "all"/"screen" stylesheet link tag to the loadCSS
 *     pattern:
 *       <link rel="stylesheet" href="…" media="print"
 *             onload="this.media='all'; this.onload=null;">
 *     The browser fetches the file (no blocking) and applies it on load.
 *     A <noscript> sibling keeps non-JS clients styled.
 *
 * Stylesheets that must remain render-blocking (payment-form CSS, etc.) can
 * opt out via the 'lafka_critical_css_keep_blocking' filter.
 *
 * ROLLBACK: remove the require_once for this file from core-functions.php to
 * disable the entire feature instantly.
 *
 * @package Lafka
 * @since   5.10.0 (W3-T4 P6-PERF-5)
 */

defined( 'ABSPATH' ) || exit;

/* ──────────────────────────────────────────────────────────────────────────
 * 1. INLINE CRITICAL CSS AT wp_head PRIORITY 1
 * ────────────────────────────────────────────────────────────────────────── */

if ( ! function_exists( 'lafka_inline_critical_css' ) ) {
	add_action( 'wp_head', 'lafka_inline_critical_css', 1 );

	/**
	 * Inline the critical CSS bundle as the first <style> tag in <head>.
	 *
	 * The file is read at request time (no object-cache dependency).
	 * Comments and runs of whitespace are stripped to shrink transfer size.
	 *
	 * @return void
	 */
	function lafka_inline_critical_css() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		$path = get_template_directory() . '/styles/critical.css';
		if ( ! file_exists( $path ) ) {
			return;
		}

		$css = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! $css ) {
			return;
		}

		// Strip block comments (/* … */) and collapse whitespace runs.
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = trim( $css );

		echo "\n<style id=\"lafka-critical-css\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/* ──────────────────────────────────────────────────────────────────────────
 * 2. DEFER NON-CRITICAL STYLESHEETS VIA media="print" onload PATTERN
 * ────────────────────────────────────────────────────────────────────────── */

if ( ! function_exists( 'lafka_defer_non_critical_css' ) ) {
	add_filter( 'style_loader_tag', 'lafka_defer_non_critical_css', 999, 4 );

	/**
	 * Convert render-blocking stylesheet links to the loadCSS async pattern.
	 *
	 * The "print" media trick:
	 *   - Browser fetches the file immediately (not render-blocking).
	 *   - onload handler flips media to "all" so styles apply on load.
	 *   - <noscript> sibling ensures non-JS users still get styles.
	 *
	 * Stylesheets may opt out of deferral by returning true from the
	 * 'lafka_critical_css_keep_blocking' filter for their handle.
	 *
	 * @param string $html   The full <link …> tag HTML.
	 * @param string $handle The WP style handle.
	 * @param string $href   The stylesheet URL.
	 * @param string $media  The registered media attribute value.
	 * @return string Modified HTML (possibly with appended <noscript>).
	 */
	function lafka_defer_non_critical_css( $html, $handle, $href, $media ) {
		if ( is_admin() ) {
			return $html;
		}

		// Allow specific handles to remain render-blocking.
		$keep_blocking = apply_filters( 'lafka_critical_css_keep_blocking', false, $handle );
		if ( $keep_blocking ) {
			return $html;
		}

		// Skip if another hook already applied the print-media trick.
		if ( false !== strpos( $html, "media='print'" ) || false !== strpos( $html, 'media="print"' ) ) {
			return $html;
		}

		// Only defer stylesheets whose media targets all browsers.
		// Stylesheets already scoped to "print" or specific media are
		// non-blocking by definition — leave them alone.
		if ( '' !== $media && 'all' !== $media && 'screen' !== $media ) {
			return $html;
		}

		// ── Apply loadCSS pattern ──────────────────────────────────────────
		// Case A: link tag already has an explicit media="…" attribute.
		$deferred = preg_replace(
			"/(<link[^>]*?rel=['\"]stylesheet['\"][^>]*?)media=['\"][^'\"]*['\"]([^>]*?>)/i",
			"$1media=\"print\" onload=\"this.media='all'; this.onload=null;\"$2",
			$html
		);

		// Case B: link tag has no media attribute at all — insert one.
		if ( $deferred === $html ) {
			$deferred = preg_replace(
				"/(<link[^>]*?rel=['\"]stylesheet['\"])([^>]*?>)/i",
				"$1 media=\"print\" onload=\"this.media='all'; this.onload=null;\"$2",
				$html
			);
		}

		// Append a <noscript> block with the original blocking tag so that
		// non-JS visitors still receive the stylesheet normally.
		$deferred .= '<noscript>' . $html . '</noscript>';

		return $deferred;
	}
}

/* ──────────────────────────────────────────────────────────────────────────
 * 3. KEEP-BLOCKING FILTER — PAYMENT & CHECKOUT CSS
 * ────────────────────────────────────────────────────────────────────────── */

add_filter( 'lafka_critical_css_keep_blocking', 'lafka_keep_payment_css_blocking', 10, 2 );
if ( ! function_exists( 'lafka_keep_payment_css_blocking' ) ) {
	/**
	 * Prevent deferral of payment-form CSS.
	 *
	 * These stylesheets are loaded on checkout/account pages where a FOUC of
	 * the payment form would confuse customers. They are typically small
	 * (<10 KB) so the render-blocking penalty is acceptable.
	 *
	 * Add or remove handles here as the payment stack changes.
	 *
	 * @param bool   $keep   Current keep-blocking flag.
	 * @param string $handle WP style handle.
	 * @return bool True to keep blocking, false to defer.
	 */
	function lafka_keep_payment_css_blocking( $keep, $handle ) {
		$always_blocking = array(
			// Authorize.Net CIM – checkout block CSS (rendered synchronously
			// by the block at page load; FOUC here would expose unstyled
			// credit-card fields).
			'wc-authorize-net-cim-credit-card-checkout-block',
			'wc-authorize-net-cim-echeck-checkout-block',
			// SkyVerge payment gateway form base styles (shared across CIM,
			// eCheck; keeps card-form layout intact on checkout render).
			'sv-wc-payment-gateway-payment-form-v6_1_4',
		);

		if ( in_array( $handle, $always_blocking, true ) ) {
			return true;
		}

		return $keep;
	}
}
