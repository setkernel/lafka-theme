<?php
/**
 * Shop / category / tag / search archive on WooCommerce's own hooks.
 *
 * woocommerce/archive-product.php fires the same actions as core's template.
 * This file holds what makes that safe for the design: the loop opens as the
 * design's grid, and lafka_archive_compose_hooks() removes (it never skips) the
 * default callbacks the handoff layout replaces. It is called from the archive
 * template only, so every other WooCommerce screen keeps core's defaults.
 *
 * @package Lafka\WooCommerce
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * The archive grid's opening tag. content-product.php hands items to the menu
 * card while the loop carries the lafka_menu_card flag (set by the archive
 * template); the grid replaces core's `ul.products`.
 */
add_filter(
	'woocommerce_product_loop_start',
	static function ( $html ) {
		return wc_get_loop_prop( 'lafka_menu_card' ) ? '<ul class="lafka-menu__grid" role="list">' : $html;
	}
);

if ( ! function_exists( 'lafka_archive_pagination' ) ) {
	/**
	 * The archive's pager, on core's pagination slot (woocommerce_after_shop_loop,
	 * priority 10). Runs through woocommerce_pagination_args like core's pager.
	 *
	 * @since 7.4.0
	 */
	function lafka_archive_pagination(): void {
		if ( function_exists( 'lafka_menu_pagination_html' ) ) {
			echo wp_kses( lafka_menu_pagination_html(), lafka_allowed_html() );
		}
	}
}

if ( ! function_exists( 'lafka_archive_compose_hooks' ) ) {
	/**
	 * Remove the callbacks core and the classic shop layout attach to the archive
	 * actions that the handoff design replaces; attach the design's pager.
	 *
	 * @since 7.4.0
	 */
	function lafka_archive_compose_hooks(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		// Page frame, header, sidebar and the "no products" notice are the design's.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_taxonomy_archive_description', 1 );
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );

		// Classic shop layout: content holder, price slider, ordering dropdown, wrappers.
		remove_action( 'woocommerce_before_shop_loop', 'lafka_add_content_holder', 5 );
		remove_action( 'woocommerce_before_shop_loop', 'lafka_price_filter', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_shop_loop', 'lafka_wrap_before_shop_loop_after', 60 );
		remove_action( 'woocommerce_after_shop_loop', 'lafka_wrap_after_shop_loop', 5 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
		remove_action( 'woocommerce_after_shop_loop', 'lafka_shop_sidebar', 15 );

		// The design prints the subcategories itself (a nav above the grid); core
		// would prepend them to the loop a second time.
		remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );

		// Loop items: the card prints its own image, title, price and quick-add.
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );

		add_action( 'woocommerce_after_shop_loop', 'lafka_archive_pagination', 10 );
	}
}
