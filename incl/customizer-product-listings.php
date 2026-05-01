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
		'sanitize_callback' => 'absint',
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
