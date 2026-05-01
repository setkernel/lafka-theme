<?php
/**
 * Product card image with fallback chain.
 *
 * Priority: product's featured image -> operator Customizer override
 * -> bundled SVG placeholder.
 *
 * @package Lafka\TemplateHelpers
 * @since   5.17.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_product_card_image_html' ) ) {
	/**
	 * Render the <img> tag for a product card with the fallback chain.
	 *
	 * @param WC_Product $product The product to render the image for.
	 * @param string     $size    Registered image size (default: woocommerce_thumbnail).
	 * @return string             HTML of the <img> tag (already escaped).
	 */
	function lafka_product_card_image_html( $product, $size = 'woocommerce_thumbnail' ) {
		$alt = esc_attr( $product->get_name() );
		$attr = array(
			'class'   => 'lafka-product-card__img',
			'loading' => 'lazy',
			'alt'     => $alt,
		);

		// 1. Try the product's own featured image.
		$product_image_id = (int) $product->get_image_id();
		if ( $product_image_id ) {
			$html = wp_get_attachment_image( $product_image_id, $size, false, $attr );
			if ( $html ) {
				return $html;
			}
		}

		// 2. Try the operator's Customizer override.
		$fallback_id = (int) get_theme_mod( 'lafka_product_card_fallback_image_id', 0 );
		if ( $fallback_id ) {
			$html = wp_get_attachment_image( $fallback_id, $size, false, $attr );
			if ( $html ) {
				return $html;
			}
		}

		// 3. Bundled SVG placeholder.
		return sprintf(
			'<img class="lafka-product-card__img lafka-product-card__img--fallback" src="%s" loading="lazy" alt="%s" width="200" height="200">',
			esc_url( get_template_directory_uri() . '/assets/images/product-card-fallback.svg' ),
			$alt
		);
	}
}
