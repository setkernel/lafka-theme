<?php
/**
 * P6-PERF-5 / GX T-02: critical CSS inline + off-screen stylesheet deferral.
 *
 * Two hooks:
 *
 *  1. lafka_inline_critical_css()  — wp_head priority 1
 *     Reads styles/critical.css (+ the counter slice and the preset's critical
 *     tokens under a counter layout, + the preloader rules when that default-off
 *     feature is on), strips comments + extra whitespace, and emits a
 *     <style id="lafka-critical-css"> as the very first thing in <head>.
 *
 *  2. lafka_defer_non_critical_css()  — style_loader_tag priority 999
 *     GX T-02: stylesheets are render-blocking by default (WordPress's normal
 *     behaviour), so every sheet that styles the first viewport — tokens, base,
 *     components, header chrome, the counter sheet, style.css, and the active
 *     template's menu / PDP / cart / checkout / page sheets — is applied before
 *     first paint and nothing shifts when it arrives (the old "defer
 *     everything" loader measured CLS ≈ 1.0 on desktop). ONLY the handles in
 *     lafka_critical_css_async_handles() — off-screen modules (drawers,
 *     dialogs, overlays, the footer) and vendored icon/carousel libraries —
 *     use the loadCSS pattern:
 *       <link rel="stylesheet" href="…" media="print"
 *             onload="this.media='all'; this.onload=null;">
 *     with a <noscript> sibling for non-JS clients. The critical bundle hides
 *     those off-screen shells until their sheet lands (no FOUC).
 *
 * Filters:
 *   lafka_critical_css_async_handles( string[] $handles ) — the deferred set.
 *   lafka_critical_css_keep_blocking( bool $keep, string $handle ) — force a
 *     handle render-blocking even when it is in the deferred set.
 *
 * ROLLBACK: remove the require_once for this file from core-functions.php to
 * disable the entire feature instantly.
 *
 * @package Lafka
 * @since   5.10.0 (W3-T4 P6-PERF-5); 7.3.0 (GX T-02 blocking-by-default)
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

		$css = lafka_critical_css_minify( $css );

		// GX4: under a counter layout, append the active preset's above-fold
		// tokens + the var()-only counter slice (body face/size/ink, header +
		// hero skeleton). Classic layouts inline exactly the pre-GX4 bundle.
		$preset_block = lafka_critical_preset_css();
		if ( '' !== $preset_block ) {
			$slice_path = get_template_directory() . '/styles/critical-counter.css';
			$slice      = file_exists( $slice_path ) ? (string) file_get_contents( $slice_path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$css       .= ' ' . $preset_block . ' ' . lafka_critical_css_minify( $slice );
		}

		// GX T-01: the (default-off) preloader's rules, printed once, here.
		if ( function_exists( 'lafka_preloader_css' ) ) {
			$preloader = lafka_preloader_css();
			if ( '' !== $preloader ) {
				$css .= ' ' . $preloader;
			}
		}

		// Rewrite relative url() refs to absolute URLs.
		// critical.css lives at <theme>/styles/critical.css, so its base URL is
		// <theme>/styles/. When the CSS is INLINED into <head>, relative paths
		// like url('../assets/...') would otherwise resolve against the page URL
		// (e.g. /menu/pizza/../assets/...) → 404.
		$base_url = trailingslashit( get_template_directory_uri() ) . 'styles/';
		$css      = preg_replace_callback(
			'#url\(\s*([\'"]?)([^\'")\s]+)\1\s*\)#i',
			static function ( $m ) use ( $base_url ) {
				$url = $m[2];
				// Leave absolute URLs (http://, //, /), data: URIs, and #fragments alone.
				if ( preg_match( '#^(?:[a-z]+:|//|/|data:|\#)#i', $url ) ) {
					return $m[0];
				}
				// Resolve url(../foo) and url(./foo) and url(foo) against the stylesheet's base.
				$resolved = $base_url . $url;
				// Collapse "styles/../assets/" → "assets/".
				while ( false !== strpos( $resolved, '/../' ) ) {
					$resolved = preg_replace( '#[^/]+/\.\./#', '', $resolved, 1 );
				}
				return 'url(' . $resolved . ')';
			},
			$css
		);

		echo "\n<style id=\"lafka-critical-css\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'lafka_critical_css_minify' ) ) {
	/**
	 * Strip block comments and collapse whitespace runs.
	 *
	 * @param string $css Raw CSS.
	 */
	function lafka_critical_css_minify( string $css ): string {
		$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
		$css = (string) preg_replace( '/\s+/', ' ', $css );
		return trim( $css );
	}
}

if ( ! function_exists( 'lafka_critical_preset_css' ) ) {
	/**
	 * GX4 (NX2-04.1, minimal): the active preset's LAFKA_PRESET_CRITICAL_KEYS
	 * values as one `:root{}` block, returned only while a counter header or
	 * home layout is active (else '' — classic first paint is untouched).
	 * Values pass the same sanitiser as the Preset-Token Layer.
	 */
	function lafka_critical_preset_css(): string {
		if ( ! function_exists( 'lafka_layout_is' ) || ! function_exists( 'lafka_active_preset' ) ) {
			return '';
		}
		if ( ! lafka_layout_is( 'header', 'counter' ) && ! lafka_layout_is( 'home', 'counter' ) ) {
			return '';
		}
		$keys   = defined( 'LAFKA_PRESET_CRITICAL_KEYS' ) ? LAFKA_PRESET_CRITICAL_KEYS : array();
		$tokens = lafka_active_preset()->tokens();
		$decls  = '';
		foreach ( $keys as $key ) {
			if ( isset( $tokens[ $key ] ) && is_scalar( $tokens[ $key ] ) ) {
				$decls .= $key . ':' . lafka_preset_css_value( (string) $tokens[ $key ] ) . ';';
			}
		}
		// Always non-empty under a counter layout so the slice is appended even
		// for a preset that sets none of the critical keys.
		return ':root{' . $decls . '}';
	}
}

/* ──────────────────────────────────────────────────────────────────────────
 * 2. DEFER ONLY OFF-SCREEN STYLESHEETS VIA media="print" onload PATTERN
 * ────────────────────────────────────────────────────────────────────────── */

if ( ! function_exists( 'lafka_critical_css_async_handles' ) ) {
	/**
	 * GX T-02: the stylesheets that may load asynchronously — nothing they style
	 * is in the first viewport, so applying them after first paint moves nothing.
	 * Every other stylesheet stays render-blocking.
	 *
	 * @return string[] Style handles.
	 */
	function lafka_critical_css_async_handles(): array {
		$is_cart_or_checkout = ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() );

		$handles = array(
			// Off-screen / on-demand UI: fixed drawers, native dialogs and
			// overlays hidden until opened, fixed bottom bars, the footer.
			'lafka-mobile-nav',
			'lafka-cart-drawer',
			'lafka-dialog',
			'lafka-search',
			'lafka-exit-intent',
			'lafka-review-banner',
			'lafka-push-prompt',
			'lafka-sticky-cart',
			'lafka-pdp-cta',
			'lafka-footer-chrome',
			// Vendored icon fonts, carousels and lightboxes (loaded only where
			// legacy markup uses them — see lafka_needs_legacy_libs()).
			'font_awesome_6',
			'font_awesome_6_v4shims',
			'flaticon',
			'et-line-font',
			'lafka-flexslider',
			'owl-carousel',
			'owl-carousel-theme-default',
			'owl-carousel-animate',
			'cloud-zoom',
			'photoswipe',
			'photoswipe-default-skin',
		);

		// The free-delivery progress bar lives in the cart drawer everywhere
		// except the cart/checkout pages, where it sits in the page itself.
		if ( ! $is_cart_or_checkout ) {
			$handles[] = 'lafka-free-delivery-progress';
		}

		// WooCommerce's block styles only paint the first viewport where a
		// block cart/checkout renders.
		if ( ! ( function_exists( 'lafka_is_block_cart_checkout_page' ) && lafka_is_block_cart_checkout_page() ) ) {
			$handles[] = 'wc-blocks-style';
		}

		/**
		 * Filter the stylesheet handles that load asynchronously (print-media
		 * swap + <noscript> fallback). Everything else is render-blocking.
		 *
		 * @param string[] $handles Style handles.
		 */
		return array_values( array_unique( (array) apply_filters( 'lafka_critical_css_async_handles', $handles ) ) );
	}
}

if ( ! function_exists( 'lafka_defer_non_critical_css' ) ) {
	add_filter( 'style_loader_tag', 'lafka_defer_non_critical_css', 999, 4 );

	/**
	 * Convert an off-screen stylesheet link to the loadCSS async pattern; leave
	 * every other stylesheet render-blocking.
	 *
	 * The "print" media trick:
	 *   - Browser fetches the file immediately (not render-blocking).
	 *   - onload handler flips media to "all" so styles apply on load.
	 *   - <noscript> sibling ensures non-JS users still get styles.
	 *
	 * @param string $html   The full <link …> tag HTML.
	 * @param string $handle The WP style handle.
	 * @param string $href   The stylesheet URL.
	 * @param string $media  The registered media attribute value.
	 * @return string Modified HTML (possibly with appended <noscript>).
	 */
	function lafka_defer_non_critical_css( $html, $handle, $href, $media ) {
		unset( $href );
		if ( is_admin() ) {
			return $html;
		}

		// Above-the-fold (i.e. not listed as off-screen) ⇒ render-blocking.
		if ( ! in_array( $handle, lafka_critical_css_async_handles(), true ) ) {
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

/* ──────────────────────────────────────────────────────────────────────────
 * 4. KEEP-BLOCKING FILTER — CANONICAL DESIGN TOKENS
 * ────────────────────────────────────────────────────────────────────────── */

add_filter( 'lafka_critical_css_keep_blocking', 'lafka_keep_tokens_css_blocking', 10, 2 );
if ( ! function_exists( 'lafka_keep_tokens_css_blocking' ) ) {
	/**
	 * Prevent deferral of the design-token stylesheet.
	 *
	 * styles/lafka-tokens.css declares the :root custom properties that every
	 * component, the header chrome, and the hero reference via var(). If it is
	 * deferred along with the component CSS, those var() lookups fall back (or
	 * resolve to nothing) during the print-media window → a colour/spacing FOUC
	 * and text reflow on first paint. The file is small (~24 KB of declarations,
	 * no layout rules), so the render-blocking cost is negligible.
	 *
	 * NOTE: the inlined styles/critical.css still targets pre-rebuild markup and
	 * should be regenerated from the current above-the-fold (header + hero) for
	 * the complete fix; pinning tokens here removes the worst of the FOUC in the
	 * meantime. (Audit 2026-06-27 #7.)
	 *
	 * @param bool   $keep   Current keep-blocking flag.
	 * @param string $handle WP style handle.
	 * @return bool True to keep blocking, false to defer.
	 */
	function lafka_keep_tokens_css_blocking( $keep, $handle ) {
		if ( 'lafka-tokens' === $handle ) {
			return true;
		}

		return $keep;
	}
}
