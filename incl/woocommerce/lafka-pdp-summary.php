<?php
/**
 * Redesigned product page — the summary column on WooCommerce's own hooks.
 *
 * The redesigned PDP (woocommerce/single-product.php) fires WooCommerce's
 * product-page actions like core does, so extensions work on it unchanged.
 * Each part of the Lafka summary is a callback on
 * `woocommerce_single_product_summary`, at a priority that keeps the design's
 * order and sits next to the core slot it replaces:
 *
 *   1 eyebrow · 2 title · 3 short description · 4 age notice · 5 price
 *   (6-12: social proof, weight, nutrition, countdown, promo tooltips, popup
 *   link — the plugin's and the theme's own callbacks, untouched)
 *   25 last-order card · 30 buy box (core's add-to-cart slot) · 40 meta hooks ·
 *   50 share hooks · 60 trust line + assurances.
 *
 * The core callbacks the design replaces are removed (not skipped) by
 * lafka_pdp_compose_summary(), which runs once, from the template only: the
 * classic flow, quick view and every other theme path keep core's defaults.
 *
 * @package Lafka\WooCommerce
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_pdp_summary_context' ) ) {
	/**
	 * What the summary parts share for one product, computed once per product.
	 *
	 * @since 7.4.0
	 *
	 * @param WC_Product $product The product.
	 * @return array{is_variable:bool,form_action:string,cart_disabled:bool,initial:array,price_html:string}
	 */
	function lafka_pdp_summary_context( WC_Product $product ): array {
		static $cache = array();
		$id           = $product->get_id();
		if ( isset( $cache[ $id ] ) ) {
			return $cache[ $id ];
		}

		$is_variable = $product->is_type( 'variable' );

		// Store-closed gate: when the store is closed AND the operator opted into
		// lafka_order_hours_disable_add_to_cart, the closed-store card replaces the
		// add-to-cart form (no ordering ahead via date/time slots).
		$cart_disabled = lafka_add_to_cart_blocked();

		// GX M-10: before a choice the price line never shows a variation nobody
		// picked — the resolved default (or single-option) variation's price,
		// else "From $min". JS swaps in the chosen variation's price and restores
		// this line when the selection is incomplete again.
		$initial = array(
			'selection' => array(),
			'variation' => null,
		);
		if ( $is_variable && function_exists( 'lafka_pdp_initial_selection' ) ) {
			$initial = lafka_pdp_initial_selection( $product );
		}
		$price_html = $is_variable && function_exists( 'lafka_pdp_price_html' )
			? lafka_pdp_price_html( $product, $initial )
			: wc_price( $product->get_price() );

		$cache[ $id ] = array(
			'is_variable'   => $is_variable,
			'form_action'   => (string) apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ),
			'cart_disabled' => $cart_disabled,
			'initial'       => $initial,
			'price_html'    => $price_html,
		);

		return $cache[ $id ];
	}
}

if ( ! function_exists( 'lafka_pdp_summary_eyebrow' ) ) {
	/**
	 * Best-seller eyebrow above the title.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_eyebrow(): void {
		global $product;
		if ( $product instanceof WC_Product && function_exists( 'lafka_pdp_render_bestseller_eyebrow' ) ) {
			lafka_pdp_render_bestseller_eyebrow( $product->get_id() );
		}
	}
}

if ( ! function_exists( 'lafka_pdp_summary_title' ) ) {
	/**
	 * The page's h1.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_title(): void {
		global $product;
		if ( $product instanceof WC_Product ) {
			echo '<h1 class="lafka-pdp-summary__title">' . esc_html( $product->get_name() ) . '</h1>';
		}
	}
}

if ( ! function_exists( 'lafka_pdp_summary_short' ) ) {
	/**
	 * Short description under the title.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_short(): void {
		global $product;
		if ( $product instanceof WC_Product ) {
			echo '<div class="lafka-pdp-summary__short">' . wp_kses_post( $product->get_short_description() ) . '</div>';
		}
	}
}

if ( ! function_exists( 'lafka_pdp_summary_age_notice' ) ) {
	/**
	 * GX M-25: operator-chosen age-restricted categories (empty by default).
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_age_notice(): void {
		global $product;
		if ( $product instanceof WC_Product && function_exists( 'lafka_product_age_notice_html' ) ) {
			echo wp_kses( lafka_product_age_notice_html( $product ), lafka_allowed_html() );
		}
	}
}

if ( ! function_exists( 'lafka_pdp_summary_price' ) ) {
	/**
	 * Live price line. Currency symbol/position come from WooCommerce settings
	 * (wc_price() honours woocommerce_currency_pos + currency code); the script
	 * replaces this textContent on size change with the formatter localised in
	 * functions.php — same currency settings, same output shape.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_price(): void {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$ctx = lafka_pdp_summary_context( $product );
		echo '<div class="lafka-pdp-summary__price"><span data-lafka-live-price>' . wp_kses_post( $ctx['price_html'] ) . '</span></div>';
	}
}

if ( ! function_exists( 'lafka_pdp_summary_last_order' ) ) {
	/**
	 * "Your last order" card (plugin-rendered).
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_last_order(): void {
		require get_template_directory() . '/partials/pdp-last-order-card.php';
	}
}

if ( ! function_exists( 'lafka_pdp_summary_buybox' ) ) {
	/**
	 * Quantity + add-to-cart (or the closed-store card, or the deal builder).
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_buybox(): void {
		require get_template_directory() . '/partials/pdp-buybox.php';
	}
}

if ( ! function_exists( 'lafka_pdp_summary_meta' ) ) {
	/**
	 * woocommerce_product_meta_start / _end for extensions (SKU, brand, GTIN and
	 * similar plugins print there). The design shows no category/SKU line, so the
	 * wrapper is emitted only when a callback is attached.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_meta(): void {
		if ( ! has_action( 'woocommerce_product_meta_start' ) && ! has_action( 'woocommerce_product_meta_end' ) ) {
			return;
		}
		echo '<div class="product_meta lafka-pdp-summary__meta">';
		do_action( 'woocommerce_product_meta_start' );
		do_action( 'woocommerce_product_meta_end' );
		echo '</div>';
	}
}

if ( ! function_exists( 'lafka_pdp_summary_share' ) ) {
	/**
	 * woocommerce_share for share-button extensions; wrapper only when used.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_share(): void {
		if ( ! has_action( 'woocommerce_share' ) ) {
			return;
		}
		echo '<div class="lafka-pdp-summary__share">';
		do_action( 'woocommerce_share' );
		echo '</div>';
	}
}

if ( ! function_exists( 'lafka_pdp_summary_assurances' ) ) {
	/**
	 * Prep-time trust line + the assurances row under the buy box.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_summary_assurances(): void {
		require get_template_directory() . '/partials/pdp-assurances.php';
	}
}

if ( ! function_exists( 'lafka_pdp_compose_summary' ) ) {
	/**
	 * Put the redesigned PDP on WooCommerce's hooks: remove the core callbacks the
	 * design replaces, attach the Lafka parts. Called once, from
	 * woocommerce/single-product.php, before any of the product hooks fire.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_compose_summary(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		// Wrappers: the redesign supplies its own page frame, breadcrumb and sidebar.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		// Gallery column: the sale flash and the share links of the classic page are
		// not part of the design.
		remove_action( 'woocommerce_before_single_product_summary', 'lafka_add_this_share', 99 );

		// Summary column: core (and the classic theme's re-ordered) callbacks that
		// the Lafka parts replace.
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_show_product_sale_flash', 1 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 6 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 8 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		remove_action( 'woocommerce_before_add_to_cart_form', 'lafka_add_to_cart_separator', 99 );

		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_eyebrow', 1 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_title', 2 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_short', 3 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_age_notice', 4 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_price', 5 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_last_order', 25 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_buybox', 30 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_meta', 40 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_share', 50 );
		add_action( 'woocommerce_single_product_summary', 'lafka_pdp_summary_assurances', 60 );

		// Below the summary: the design's meal builder and ingredients/reviews cards
		// take the place of core's tabs; upsells and related products stay on core's
		// own callbacks.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		add_action( 'woocommerce_after_single_product_summary', 'lafka_pdp_make_it_a_meal', 5 );
		add_action( 'woocommerce_after_single_product_summary', 'lafka_pdp_ingredients_reviews', 8 );
	}
}

if ( ! function_exists( 'lafka_pdp_make_it_a_meal' ) ) {
	/**
	 * "Make it a meal" cross-sell strip.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_make_it_a_meal(): void {
		global $product; // The partial reads the loop's product.
		require get_template_directory() . '/partials/pdp-make-it-a-meal.php';
	}
}

if ( ! function_exists( 'lafka_pdp_ingredients_reviews' ) ) {
	/**
	 * Ingredients + reviews 2-card grid (handoff), in place of the WC tabs.
	 *
	 * @since 7.4.0
	 */
	function lafka_pdp_ingredients_reviews(): void {
		global $product; // The partial reads the loop's product.
		require get_template_directory() . '/partials/pdp-ingredients-reviews.php';
	}
}
