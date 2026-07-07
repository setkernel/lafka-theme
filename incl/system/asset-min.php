<?php
/**
 * Production asset-minification switch (NX1-10b).
 *
 * A minify-only dist step — scripts/build-assets.mjs, run by `npm run build`
 * and wired into release.yml before the theme is zipped — emits a `.min.css` /
 * `.min.js` sibling next to every first-party file in styles/ and js/. Those
 * siblings are BUILD ARTEFACTS: git-ignored, never committed, present only in a
 * packaged/built theme.
 *
 * This helper, hooked on style_loader_src + script_loader_src, rewrites an
 * enqueued theme asset URL to its minified sibling when minified assets are in
 * play AND the sibling actually exists on disk. It is a strict no-op otherwise,
 * so:
 *   - dev checkouts (no .min on disk until the build runs) are unaffected;
 *   - SCRIPT_DEBUG on serves the raw, readable source;
 *   - non-theme assets, already-minified srcs, and non-css/js srcs pass through.
 *
 * The version query arg is preserved verbatim so cache-busting never regresses.
 *
 * Note: a few first-party scripts (lafka-front, lafka-dialog, lafka-libs-config,
 * lafka-price-slider) are enqueued through their own `$suffix` `.min` switch and
 * ship a committed, hand-tuned `.min` sibling. This filter leaves those alone —
 * their src already ends in `.min` when SCRIPT_DEBUG is off, which trips the
 * already-minified guard below — so the two mechanisms never double up.
 *
 * @package Lafka
 * @since   6.20.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_use_min_assets' ) ) {
	/**
	 * Whether minified theme assets should be served.
	 *
	 * Defaults to true unless SCRIPT_DEBUG is on. Exposed through the
	 * `lafka_use_min_assets` filter so CI or an operator debugging in
	 * production can force raw assets regardless of the constant.
	 *
	 * @return bool
	 */
	function lafka_use_min_assets() {
		$use_min = ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

		return (bool) apply_filters( 'lafka_use_min_assets', $use_min );
	}
}

if ( ! function_exists( 'lafka_maybe_min_src' ) ) {
	/**
	 * Rewrite a theme styles//js asset URL to its `.min` sibling when available.
	 *
	 * @param string $src    Registered asset URL (may carry a ?ver query).
	 * @param string $handle Asset handle (part of the filter signature; unused).
	 * @return string The `.min` URL, or the original src when no swap applies.
	 */
	function lafka_maybe_min_src( $src, $handle = '' ) {
		unset( $handle );

		if ( ! is_string( $src ) || '' === $src ) {
			return $src;
		}

		if ( ! lafka_use_min_assets() ) {
			return $src;
		}

		// Peel the fragment then the query off, so only the path is inspected
		// and both are re-attached verbatim to the rewritten URL.
		$hash = '';
		$path = $src;
		$pos  = strpos( $path, '#' );
		if ( false !== $pos ) {
			$hash = substr( $path, $pos );
			$path = substr( $path, 0, $pos );
		}
		$query = '';
		$pos   = strpos( $path, '?' );
		if ( false !== $pos ) {
			$query = substr( $path, $pos );
			$path  = substr( $path, 0, $pos );
		}

		// Only stylesheets and scripts, and never re-minify a `.min` file.
		if ( ! preg_match( '/\.(css|js)$/i', $path ) ) {
			return $src;
		}
		if ( preg_match( '/\.min\.(css|js)$/i', $path ) ) {
			return $src;
		}

		// The asset must live under THIS theme's styles/ or js/ directory.
		// Compare scheme-agnostically so an http/https mismatch never defeats it.
		$strip     = static function ( $url ) {
			return preg_replace( '#^https?:#i', '', (string) $url );
		};
		$theme_uri = rtrim( (string) get_template_directory_uri(), '/' );
		$uri_key   = $strip( $theme_uri );
		$path_key  = $strip( $path );
		if ( 0 !== strpos( $path_key, $uri_key . '/styles/' )
			&& 0 !== strpos( $path_key, $uri_key . '/js/' ) ) {
			return $src;
		}

		$relative = substr( $path_key, strlen( $uri_key ) ); // e.g. /styles/lafka-base.css
		$min_rel  = preg_replace( '/\.(css|js)$/i', '.min.$1', $relative );
		$min_file = rtrim( (string) get_template_directory(), '/\\' ) . $min_rel;
		if ( ! is_file( $min_file ) ) {
			return $src;
		}

		$min_path = preg_replace( '/\.(css|js)$/i', '.min.$1', $path );

		return $min_path . $query . $hash;
	}
}

add_filter( 'style_loader_src', 'lafka_maybe_min_src', 10, 2 );
add_filter( 'script_loader_src', 'lafka_maybe_min_src', 10, 2 );
