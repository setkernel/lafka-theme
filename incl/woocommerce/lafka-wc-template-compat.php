<?php
/**
 * Version bridges for the theme's WooCommerce template overrides.
 *
 * The overrides in woocommerce/ track the newest core templates, but the theme
 * still supports WooCommerce back to its floor (9.5). Each helper here adopts a
 * newer core API when the running WooCommerce has it and falls back to the
 * previous behaviour otherwise, so a template never calls something that does
 * not exist and never disagrees with the core partial it pairs with.
 *
 * Filter surface:
 *   lafka_product_video_trigger_url( string $url, WC_Product $product, array $media_items )
 *     — URL behind the legacy "Play the video" trigger ('' hides it).
 *
 * @package Lafka\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS' ) ) {
	/** WooCommerce 11.1+ media-gallery helper (product images, gallery videos, variation galleries). */
	define( 'LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS', 'Automattic\\WooCommerce\\Internal\\ProductGallery\\ProductMediaGallery' );
}

if ( ! function_exists( 'lafka_wc_has_product_media_gallery' ) ) {
	/**
	 * Whether the running WooCommerce ships the 11.1 media-gallery helper.
	 *
	 * When it does, core's single-product/product-thumbnails.php renders
	 * "every media item after the first" of the helper's ordering, so the main
	 * slot in product-image.php must render exactly item 0 of that ordering.
	 *
	 * @return bool
	 */
	function lafka_wc_has_product_media_gallery() {
		return class_exists( LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS )
			&& method_exists( LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS, 'get_product_media_gallery_items_for_display' )
			&& method_exists( LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS, 'get_gallery_video_html' );
	}
}

if ( ! function_exists( 'lafka_wc_product_media_items' ) ) {
	/**
	 * Ordered media items for the single-product gallery.
	 *
	 * Each item is an array with at least `media_type` ('image'|'video'),
	 * `source_type` ('attachment'|'placeholder') and `id`. On WooCommerce 11.1+
	 * this is core's own display ordering (featured image, gallery images,
	 * positioned gallery videos, de-duplicated, placeholder when empty). On older
	 * WooCommerce, core's product-thumbnails.php renders every gallery image
	 * itself, so the main slot is the featured image alone (or the placeholder).
	 *
	 * @param WC_Product $product Product.
	 * @return array<int, array<string, mixed>>
	 */
	function lafka_wc_product_media_items( $product ) {
		if ( lafka_wc_has_product_media_gallery() ) {
			$class = LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS;
			$items = $class::get_product_media_gallery_items_for_display( $product );
			$items = is_array( $items ) ? array_values( array_filter( $items, 'is_array' ) ) : array();
			if ( $items ) {
				return $items;
			}
		}

		$image_id = absint( $product->get_image_id() );

		return array(
			array(
				'media_type'  => 'image',
				'source_type' => $image_id ? 'attachment' : 'placeholder',
				'id'          => $image_id,
			),
		);
	}
}

if ( ! function_exists( 'lafka_wc_media_items_have_video' ) ) {
	/**
	 * Whether any media item is a (native WooCommerce) gallery video.
	 *
	 * @param array $media_items Items from lafka_wc_product_media_items().
	 * @return bool
	 */
	function lafka_wc_media_items_have_video( array $media_items ) {
		foreach ( $media_items as $item ) {
			if ( is_array( $item ) && 'video' === ( $item['media_type'] ?? '' ) && 'placeholder' !== ( $item['source_type'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'lafka_product_video_trigger_url' ) ) {
	/**
	 * URL for the legacy "Play the video" trigger (the product's Lafka video-URL
	 * meta), or '' when it should not render.
	 *
	 * The trigger is a fallback for products that predate WooCommerce's native
	 * gallery videos: once the product's gallery holds a native video, the
	 * gallery is the single source of video and the trigger is suppressed so the
	 * customer is not offered two video controls.
	 *
	 * @param WC_Product $product     Product.
	 * @param array      $media_items Items from lafka_wc_product_media_items().
	 * @return string
	 */
	function lafka_product_video_trigger_url( $product, array $media_items ) {
		$url = (string) get_post_meta( $product->get_id(), 'lafka_product_video_url', true );
		if ( '' !== $url && lafka_wc_media_items_have_video( $media_items ) ) {
			$url = '';
		}

		/**
		 * Filters the URL behind the legacy "Play the video" trigger.
		 *
		 * @param string     $url         Video URL; '' hides the trigger.
		 * @param WC_Product $product     Product.
		 * @param array      $media_items Ordered gallery media items.
		 */
		return (string) apply_filters( 'lafka_product_video_trigger_url', $url, $product, $media_items );
	}
}

if ( ! function_exists( 'lafka_wc_cart_item_product_name' ) ) {
	/**
	 * Unfiltered display name of a cart line's product.
	 *
	 * WooCommerce 11.2 names a variation from the attributes the customer
	 * actually selected (WC_Cart::get_item_product_name()); older versions use
	 * the product's own name. The result is what core feeds into the
	 * woocommerce_cart_item_name filter.
	 *
	 * @param array      $cart_item Cart line.
	 * @param WC_Product $product   Line product (after woocommerce_cart_item_product).
	 * @return string
	 */
	function lafka_wc_cart_item_product_name( $cart_item, $product ) {
		$cart = function_exists( 'WC' ) ? WC()->cart : null;
		if ( is_object( $cart ) && method_exists( $cart, 'get_item_product_name' ) ) {
			$name = $cart->get_item_product_name( $cart_item, $product );
			if ( is_string( $name ) && '' !== $name ) {
				return $name;
			}
		}
		return (string) $product->get_name();
	}
}

if ( ! function_exists( 'lafka_wc_product_meta_category_orderby' ) ) {
	/**
	 * Category ordering for the single-product meta block.
	 *
	 * Mirrors WooCommerce 11.2's single-product/meta.php: 'breadcrumb' by default,
	 * filterable through core's woocommerce_product_meta_category_orderby, and
	 * limited to the modes wc_get_product_category_list() accepts ('name',
	 * 'breadcrumb', '' for plain term order). Older WooCommerce ignores the
	 * argument and keeps its plain term order.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	function lafka_wc_product_meta_category_orderby( $product ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter, fired exactly as core's meta.php does.
		$orderby = apply_filters( 'woocommerce_product_meta_category_orderby', 'breadcrumb', $product );

		return is_string( $orderby ) && in_array( $orderby, array( 'name', 'breadcrumb', '' ), true ) ? $orderby : '';
	}
}
