<?php
/**
 * GX T-25: front-end asset diet.
 *
 *  - Legacy libraries (imagesloaded for the classic shop's infinite scroll,
 *    wp-util for the legacy quick view) load only where a template can render the markup
 *    that uses them. The counter surfaces — counter home, counter menu (/menu/,
 *    shop, product categories), the redesigned PDP, cart and checkout — render
 *    none of it, so they skip that CSS + JS. Classic layouts and every legacy
 *    surface keep them.
 *  - Pages whose template never outputs the page's post_content —
 *    page-menu.php, template-contact.php and the editorial templates
 *    (lafka_templates_without_content()) — build their own modern markup, so
 *    they skip the legacy libraries too, whatever old page-builder content is
 *    stored on the page (e.g. /menu/ and /contact-us/ on sites migrated from
 *    the page-builder era).
 *  - (wp-emoji on counter storefronts: lafka_counter_disable_emoji() in
 *    incl/template-helpers/counter-chrome.php, Customizer → Layouts.)
 *
 * Filter surface:
 *   lafka_needs_legacy_libs( bool $needed, array $context )
 *   lafka_front_page_renders_content( bool $renders )
 *   lafka_templates_without_content( string[] $templates )
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
		if ( is_page() ) {
			$queried   = get_queried_object();
			$menu_page = $queried && isset( $queried->post_name ) && 'menu' === $queried->post_name;
		}

		return array(
			'counter'      => function_exists( 'lafka_any_counter_layout' ) && lafka_any_counter_layout(),
			'counter_home' => $layout( 'home' ),
			'counter_menu' => $layout( 'menu' ),
			'front_page'   => is_front_page(),
			'menu_surface' => $menu_page
				|| ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ),
			'product'      => function_exists( 'is_product' ) && is_product(),
			'pdp_redesign' => ! function_exists( 'lafka_pdp_redesign_enabled' ) || lafka_pdp_redesign_enabled(),
			'cart'         => function_exists( 'is_cart' ) && is_cart(),
			'checkout'     => function_exists( 'is_checkout' ) && is_checkout(),
			'own_markup'   => is_singular() && ! lafka_template_renders_content(),
		);
	}
}

if ( ! function_exists( 'lafka_legacy_libs_needed_for' ) ) {
	/**
	 * Whether a request (described by lafka_asset_context()) can render legacy
	 * library markup: not the templates that build their own markup, not the
	 * counter surfaces; everything else on a classic site keeps them.
	 *
	 * @param array<string,bool> $ctx Request facts.
	 */
	function lafka_legacy_libs_needed_for( array $ctx ): bool {
		if ( ! empty( $ctx['own_markup'] ) && empty( $ctx['front_page'] ) ) {
			return false;
		}
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
	 * Whether this request loads the legacy libraries (imagesloaded, wp-util).
	 */
	function lafka_needs_legacy_libs(): bool {
		$ctx = lafka_asset_context();

		/**
		 * Filter whether the legacy libraries (imagesloaded, wp-util) load on
		 * this request.
		 *
		 * @param bool               $needed  Computed decision.
		 * @param array<string,bool> $context Request facts (lafka_asset_context()).
		 */
		return (bool) apply_filters( 'lafka_needs_legacy_libs', lafka_legacy_libs_needed_for( $ctx ), $ctx );
	}
}

if ( ! function_exists( 'lafka_templates_without_content' ) ) {
	/**
	 * Templates that never output the queried page's post_content (their
	 * layout comes from settings and live data instead), as paths relative to
	 * the theme (or child theme) directory. Old page-builder content stored on
	 * these pages — /menu/, /contact-us/, the home page — is never shown, so it
	 * must not pull assets in either.
	 *
	 * @return list<string>
	 */
	function lafka_templates_without_content(): array {
		/**
		 * Filter the templates that do not render the page's post_content.
		 *
		 * @param list<string> $templates Paths relative to the theme directory.
		 */
		return array_values(
			array_map(
				'strval',
				(array) apply_filters(
					'lafka_templates_without_content',
					array(
						'front-page.php',
						'page-menu.php',
						'template-contact.php',
						'page_templates/template-editorial-home.php',
						'page_templates/template-editorial-contact.php',
					)
				)
			)
		);
	}
}

if ( ! function_exists( 'lafka_template_renders_content' ) ) {
	/**
	 * Whether the template chosen for this request outputs the queried post's
	 * content. Reads the template WordPress resolved (after template_include),
	 * so slug templates (page-menu.php) and the auto-applied contact template
	 * count as well as assigned page templates.
	 *
	 * @return bool
	 */
	function lafka_template_renders_content(): bool {
		$template = isset( $GLOBALS['template'] ) ? wp_normalize_path( (string) $GLOBALS['template'] ) : '';
		if ( '' === $template ) {
			return true;
		}
		$relative = $template;
		foreach ( array( get_stylesheet_directory(), get_template_directory() ) as $dir ) {
			$dir = trailingslashit( wp_normalize_path( $dir ) );
			if ( 0 === strpos( $template, $dir ) ) {
				$relative = substr( $template, strlen( $dir ) );
				break;
			}
		}
		if ( 'front-page.php' === $relative ) {
			/**
			 * Filter whether the front page's template renders its post_content
			 * (false for the theme's front-page.php).
			 *
			 * @param bool $renders Default false.
			 */
			return (bool) apply_filters( 'lafka_front_page_renders_content', false );
		}

		return ! in_array( $relative, lafka_templates_without_content(), true );
	}
}
