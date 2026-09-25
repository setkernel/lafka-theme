<?php
/**
 * GX T-25: front-end asset diet.
 *
 *  - Legacy libraries (Font Awesome + v4 shims, flexslider, owl-carousel +
 *    theme + animate.css, nice-select, imagesloaded, wp-util for the legacy
 *    quick view) load only where a template can render the markup that uses
 *    them. The counter surfaces — counter home, counter menu (/menu/, shop,
 *    product categories), the redesigned PDP, cart and checkout — render none
 *    of it (verified: no fa / owl / flexslider / nice-select markup or icon-font
 *    glyph on those pages), so they skip ~140 KB of CSS + JS. Classic layouts,
 *    the account page (owl login/register slider) and every legacy surface keep
 *    them.
 *  - Content-sniffed assets (flaticon / et-line / typed / legacy-shortcodes)
 *    only look at post content a template actually renders: front-page.php
 *    never renders the front page's post_content, so shortcodes left in it no
 *    longer pull those sheets onto the home page.
 *  - wp-emoji is off on the front end (Customizer → General, default on).
 *
 * Filter surface:
 *   lafka_needs_legacy_libs( bool $needed, array $context )
 *   lafka_front_page_renders_content( bool $renders )
 *   lafka_disable_emoji( bool $disabled )
 *
 * @package Lafka
 * @since   7.3.0 (GX T-25)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_asset_context' ) ) {
	/**
	 * The request facts the asset gates read (one place, so the decisions are
	 * pure functions of this array).
	 *
	 * @return array<string,bool>
	 */
	function lafka_asset_context(): array {
		$layout = static function ( string $surface ): bool {
			return function_exists( 'lafka_layout_is' ) && lafka_layout_is( $surface, 'counter' );
		};

		$menu_page = false;
		if ( function_exists( 'is_page' ) && is_page() ) {
			$queried   = get_queried_object();
			$menu_page = $queried && isset( $queried->post_name ) && 'menu' === $queried->post_name;
		}

		return array(
			'counter'      => function_exists( 'lafka_any_counter_layout' ) && lafka_any_counter_layout(),
			'counter_home' => $layout( 'home' ),
			'counter_menu' => $layout( 'menu' ),
			'front_page'   => function_exists( 'is_front_page' ) && is_front_page(),
			'menu_surface' => $menu_page
				|| ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ),
			'product'      => function_exists( 'is_product' ) && is_product(),
			'pdp_redesign' => ! function_exists( 'lafka_pdp_redesign_enabled' ) || lafka_pdp_redesign_enabled(),
			'cart'         => function_exists( 'is_cart' ) && is_cart(),
			'checkout'     => function_exists( 'is_checkout' ) && is_checkout(),
		);
	}
}

if ( ! function_exists( 'lafka_legacy_libs_needed_for' ) ) {
	/**
	 * Whether a request (described by lafka_asset_context()) can render legacy
	 * library markup. Only counter surfaces opt out; a classic site is unchanged.
	 *
	 * @param array<string,bool> $ctx Request facts.
	 */
	function lafka_legacy_libs_needed_for( array $ctx ): bool {
		if ( empty( $ctx['counter'] ) ) {
			return true;
		}
		if ( ! empty( $ctx['front_page'] ) ) {
			return empty( $ctx['counter_home'] );
		}
		if ( ! empty( $ctx['menu_surface'] ) ) {
			return empty( $ctx['counter_menu'] );
		}
		if ( ! empty( $ctx['product'] ) ) {
			return empty( $ctx['pdp_redesign'] );
		}
		if ( ! empty( $ctx['cart'] ) || ! empty( $ctx['checkout'] ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'lafka_needs_legacy_libs' ) ) {
	/**
	 * Whether this request loads the legacy icon-font / carousel / select libs.
	 */
	function lafka_needs_legacy_libs(): bool {
		$ctx = lafka_asset_context();

		/**
		 * Filter whether the legacy libraries (Font Awesome, flexslider, owl,
		 * animate.css, nice-select, imagesloaded) load on this request.
		 *
		 * @param bool               $needed  Computed decision.
		 * @param array<string,bool> $context Request facts (lafka_asset_context()).
		 */
		return (bool) apply_filters( 'lafka_needs_legacy_libs', lafka_legacy_libs_needed_for( $ctx ), $ctx );
	}
}

if ( ! function_exists( 'lafka_rendered_post_content' ) ) {
	/**
	 * The queried post's content IF the template renders it, else ''. Used to
	 * sniff shortcodes that pull optional assets. The static front page is
	 * rendered by front-page.php, which never outputs the page's post_content.
	 */
	function lafka_rendered_post_content(): string {
		if ( ! is_singular() ) {
			return '';
		}
		$post = $GLOBALS['post'] ?? null;
		if ( ! $post instanceof WP_Post ) {
			return '';
		}
		/**
		 * Filter whether the front page's template renders its post_content
		 * (false for the theme's front-page.php).
		 *
		 * @param bool $renders Default false.
		 */
		if ( function_exists( 'is_front_page' ) && is_front_page() && ! apply_filters( 'lafka_front_page_renders_content', false ) ) {
			return '';
		}
		return (string) $post->post_content;
	}
}

if ( ! function_exists( 'lafka_emoji_disabled' ) ) {
	/**
	 * Whether WordPress's front-end emoji script + styles are removed.
	 */
	function lafka_emoji_disabled(): bool {
		/**
		 * Filter whether the wp-emoji front-end script is removed.
		 *
		 * @param bool $disabled Customizer → General (default true).
		 */
		return (bool) apply_filters( 'lafka_disable_emoji', (bool) get_theme_mod( 'lafka_disable_emoji', true ) );
	}
}

if ( ! function_exists( 'lafka_disable_front_emoji' ) ) {
	/**
	 * Unhook wp-emoji from the front end (the admin keeps it).
	 */
	function lafka_disable_front_emoji(): void {
		if ( is_admin() || ! lafka_emoji_disabled() ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
}
add_action( 'init', 'lafka_disable_front_emoji' );
