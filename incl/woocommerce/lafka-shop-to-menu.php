<?php
/**
 * One menu URL: send the WooCommerce shop page (and a stray /shop/) to /menu/.
 *
 * The theme renders the full menu on the /menu/ page (page-menu.php). The
 * WooCommerce shop page (often "Order" or "Shop") renders the same grouped
 * menu from archive-product.php, so a store ends up with two indexable copies
 * of its menu, and a PDP breadcrumb that points at the second one (GX QA
 * M-14 / T-08). This module:
 *
 *   - 301-redirects the shop archive (NOT a product search, which WooCommerce
 *     also reports as is_shop(), and never product or category URLs — those
 *     keep their /<shop-base>/… permalinks) to the canonical menu URL;
 *   - 301-redirects a 404 on a legacy shop path (default: /shop/) there too;
 *   - drops the redirected shop page from the core XML sitemap.
 *
 * It only acts when the menu URL is a real, different page on this site
 * (never a loop, never a redirect into a 404). Customizer → Lafka — Menu
 * Landing → Behaviour → "Send the shop page to the menu page" (default on);
 * filters `lafka_shop_to_menu_redirect` (bool) and `lafka_shop_legacy_paths`
 * (list of paths).
 *
 * @package Lafka\WooCommerce
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_shop_to_menu_enabled' ) ) {
	/** Whether the shop page should hand off to the menu page. */
	function lafka_shop_to_menu_enabled(): bool {
		return (bool) apply_filters( 'lafka_shop_to_menu_redirect', (bool) get_theme_mod( 'lafka_shop_to_menu_redirect', true ) );
	}
}

if ( ! function_exists( 'lafka_shop_to_menu_same_url' ) ) {
	/**
	 * PURE: two URLs point at the same place (scheme and trailing slash ignored).
	 *
	 * @param string $a URL.
	 * @param string $b URL.
	 */
	function lafka_shop_to_menu_same_url( string $a, string $b ): bool {
		$norm = static fn( string $url ): string => strtolower( rtrim( (string) preg_replace( '#^https?://#i', '', trim( $url ) ), '/' ) );
		return '' !== $norm( $a ) && $norm( $a ) === $norm( $b );
	}
}

if ( ! function_exists( 'lafka_shop_to_menu_target' ) ) {
	/**
	 * PURE: where (if anywhere) this request should be redirected.
	 *
	 * @param array<string,mixed> $ctx {
	 *     @type bool     $enabled       Module on.
	 *     @type bool     $is_shop       is_shop().
	 *     @type bool     $is_search     is_search().
	 *     @type bool     $is_404        is_404().
	 *     @type string   $path          Request path, no query, no slashes at the ends.
	 *     @type string   $menu_url      Canonical menu URL.
	 *     @type string   $shop_url      Shop page URL ('' when none).
	 *     @type bool     $menu_is_page  The menu URL resolves to a published page.
	 *     @type string[] $legacy_paths  Paths that should reach the menu when they 404.
	 * }
	 * @return string Target URL, or '' to stay.
	 */
	function lafka_shop_to_menu_target( array $ctx ): string {
		$menu = (string) ( $ctx['menu_url'] ?? '' );
		if ( empty( $ctx['enabled'] ) || '' === $menu || empty( $ctx['menu_is_page'] ) ) {
			return '';
		}
		$shop = (string) ( $ctx['shop_url'] ?? '' );
		if ( '' !== $shop && lafka_shop_to_menu_same_url( $shop, $menu ) ) {
			return ''; // The menu IS the shop page: nothing to hand off.
		}
		if ( ! empty( $ctx['is_shop'] ) && empty( $ctx['is_search'] ) ) {
			return $menu;
		}
		$path   = trim( strtolower( (string) ( $ctx['path'] ?? '' ) ), '/' );
		$legacy = array_map( static fn( $p ) => trim( strtolower( (string) $p ), '/' ), (array) ( $ctx['legacy_paths'] ?? array() ) );
		if ( ! empty( $ctx['is_404'] ) && '' !== $path && in_array( $path, $legacy, true ) ) {
			return $menu;
		}
		return '';
	}
}

if ( ! function_exists( 'lafka_shop_to_menu_request_path' ) ) {
	/** The request path relative to the site root, without query or edge slashes. */
	function lafka_shop_to_menu_request_path(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $base && '/' !== $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		return trim( $path, '/' );
	}
}

if ( ! function_exists( 'lafka_shop_to_menu_redirect' ) ) {
	/** template_redirect: hand the shop page (or a legacy /shop/ 404) to /menu/. */
	function lafka_shop_to_menu_redirect(): void {
		if ( is_admin() || is_customize_preview() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || is_feed() ) {
			return;
		}
		$is_shop = function_exists( 'is_shop' ) && is_shop();
		$is_404  = is_404();
		if ( ! $is_shop && ! $is_404 ) {
			return;
		}
		$menu_url = lafka_theme_menu_url();
		$target   = lafka_shop_to_menu_target(
			array(
				'enabled'      => lafka_shop_to_menu_enabled(),
				'is_shop'      => $is_shop,
				'is_search'    => is_search(),
				'is_404'       => $is_404,
				'path'         => lafka_shop_to_menu_request_path(),
				'menu_url'     => $menu_url,
				'shop_url'     => function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '',
				'menu_is_page' => function_exists( 'url_to_postid' ) && url_to_postid( $menu_url ) > 0,
				'legacy_paths' => (array) apply_filters( 'lafka_shop_legacy_paths', array( 'shop' ) ),
			)
		);
		if ( '' === $target ) {
			return;
		}
		if ( wp_safe_redirect( $target, 301, 'Lafka' ) ) {
			exit;
		}
	}
}
add_action( 'template_redirect', 'lafka_shop_to_menu_redirect', 5 );

if ( ! function_exists( 'lafka_shop_to_menu_sitemap_args' ) ) {
	/**
	 * Keep the redirected shop page out of the core XML sitemap.
	 *
	 * @param array<string,mixed> $args      WP_Query args.
	 * @param string              $post_type Post type of this sitemap.
	 * @return array<string,mixed>
	 */
	function lafka_shop_to_menu_sitemap_args( $args, $post_type = '' ) {
		if ( 'page' !== $post_type || ! is_array( $args ) || ! lafka_shop_to_menu_enabled() || ! function_exists( 'wc_get_page_id' ) ) {
			return $args;
		}
		$shop_id = (int) wc_get_page_id( 'shop' );
		$menu_id = function_exists( 'url_to_postid' ) ? (int) url_to_postid( lafka_theme_menu_url() ) : 0;
		if ( $shop_id > 0 && $menu_id > 0 && $shop_id !== $menu_id ) {
			$args['post__not_in'] = array_values( array_unique( array_merge( array_map( 'intval', (array) ( $args['post__not_in'] ?? array() ) ), array( $shop_id ) ) ) );
		}
		return $args;
	}
}
add_filter( 'wp_sitemaps_posts_query_args', 'lafka_shop_to_menu_sitemap_args', 30, 2 );

if ( ! function_exists( 'lafka_breadcrumb_menu_crumbs' ) ) {
	/**
	 * PURE: make the WooCommerce breadcrumb say "Menu" → /menu/ where it would
	 * say the shop page's title ("Order", "Shop") → the shop page, and make sure
	 * menu pages (product, category, tag, product search) carry that crumb —
	 * the same trail the JSON-LD BreadcrumbList and the archive template emit.
	 *
	 * @param array<int, array{0:string, 1?:string}> $crumbs WC crumbs ([ label, url ]).
	 * @param array<string,mixed>                    $ctx {
	 *     @type string $shop_url     Shop page URL ('' when none).
	 *     @type string $menu_url     Canonical menu URL.
	 *     @type string $menu_label   "Menu" (translated).
	 *     @type bool   $menu_context The page belongs under the menu.
	 * }
	 * @return array<int, array{0:string, 1?:string}>
	 */
	function lafka_breadcrumb_menu_crumbs( array $crumbs, array $ctx ): array {
		$menu_url = (string) ( $ctx['menu_url'] ?? '' );
		if ( '' === $menu_url ) {
			return $crumbs;
		}
		$label    = (string) ( $ctx['menu_label'] ?? 'Menu' );
		$shop_url = (string) ( $ctx['shop_url'] ?? '' );
		$out      = array();
		$has_menu = false;
		foreach ( array_values( $crumbs ) as $crumb ) {
			$url = isset( $crumb[1] ) ? (string) $crumb[1] : '';
			if ( '' !== $url && ( lafka_shop_to_menu_same_url( $url, $menu_url ) || ( '' !== $shop_url && lafka_shop_to_menu_same_url( $url, $shop_url ) ) ) ) {
				if ( $has_menu ) {
					continue; // Never two "Menu" crumbs.
				}
				$crumb    = array( $label, $menu_url );
				$has_menu = true;
			}
			$out[] = $crumb;
		}
		if ( ! $has_menu && ! empty( $ctx['menu_context'] ) && count( $out ) >= 1 ) {
			array_splice( $out, 1, 0, array( array( $label, $menu_url ) ) );
		}
		return $out;
	}
}

if ( ! function_exists( 'lafka_filter_wc_breadcrumb' ) ) {
	/**
	 * woocommerce_get_breadcrumb: the "Menu" crumb (see lafka_breadcrumb_menu_crumbs()).
	 *
	 * @param array $crumbs WC crumbs.
	 * @return array
	 */
	function lafka_filter_wc_breadcrumb( $crumbs ) {
		if ( ! is_array( $crumbs ) ) {
			return $crumbs;
		}
		$menu_context = ( function_exists( 'is_product' ) && is_product() )
			|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			|| ( function_exists( 'is_shop' ) && is_shop() );
		return lafka_breadcrumb_menu_crumbs(
			$crumbs,
			array(
				'shop_url'     => function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '',
				'menu_url'     => lafka_theme_menu_url(),
				'menu_label'   => __( 'Menu', 'lafka' ),
				'menu_context' => $menu_context,
			)
		);
	}
}
add_filter( 'woocommerce_get_breadcrumb', 'lafka_filter_wc_breadcrumb', 20 );
