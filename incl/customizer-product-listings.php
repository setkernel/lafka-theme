<?php
/**
 * Customizer registration for product list/card settings.
 *
 * v5.17.0 P6-UX-7: registers a single setting allowing operators to
 * override the bundled SVG placeholder used when a product has no
 * featured image. When unset (default 0), the bundled SVG is used.
 *
 * @package Lafka
 * @since   5.17.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_product_listings_customizer_register' );

if ( ! function_exists( 'lafka_sanitize_attachment_id_from_url' ) ) {
	/**
	 * Sanitize a value that may be either an attachment ID (int) or an
	 * attachment URL (string), returning the integer attachment ID.
	 *
	 * WP_Customize_Image_Control stores the URL string of the picked
	 * attachment. To keep the storage type as a clean integer ID (matching
	 * the *_image_id setting name and the consuming helper's expectation),
	 * we resolve URLs through attachment_url_to_postid() at save time.
	 * Returns 0 when input is empty, non-resolvable, or invalid.
	 *
	 * @param mixed $value Numeric ID, URL string, or empty.
	 * @return int Attachment ID, or 0.
	 */
	function lafka_sanitize_attachment_id_from_url( $value ) {
		if ( is_numeric( $value ) ) {
			return absint( $value );
		}
		$url = (string) $value;
		if ( '' === trim( $url ) ) {
			return 0;
		}
		$id = function_exists( 'attachment_url_to_postid' ) ? attachment_url_to_postid( $url ) : 0;
		return $id ? (int) $id : 0;
	}
}

/**
 * Register the product-listings Customizer section + setting.
 *
 * @param WP_Customize_Manager $wp_customize
 */
function lafka_product_listings_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_section( 'lafka_product_listings', array(
		'title'       => __( 'Lafka — Product Listings', 'lafka' ),
		'description' => __( 'Settings for the product card layout used on category archives, the all-products page, and PDP related products.', 'lafka' ),
		'priority'    => 165,
	) );

	$wp_customize->add_setting( 'lafka_product_card_fallback_image_id', array(
		'default' => 0,
		'sanitize_callback' => 'lafka_sanitize_attachment_id_from_url',
		'transport' => 'refresh',
	) );

	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'lafka_product_card_fallback_image_id',
			array(
				'label'       => __( 'Fallback product image', 'lafka' ),
				'description' => __( 'Used when a product has no featured image. Leave empty to use the bundled placeholder.', 'lafka' ),
				'section'     => 'lafka_product_listings',
				'settings'    => 'lafka_product_card_fallback_image_id',
			)
		)
	);
}
