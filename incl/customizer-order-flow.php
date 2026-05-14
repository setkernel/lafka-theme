<?php
/**
 * Customizer panel — "Lafka — Order Flow"
 *
 * Operator-facing toggles for the order/conversion features added in v5.26+.
 * Lives alongside the Editorial / Product Listings / Menu Landing panels.
 *
 * @package Lafka
 * @since   5.26.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_order_flow_customizer_register' );

/**
 * Register the Order Flow Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function lafka_order_flow_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_panel(
		'lafka_order_flow',
		array(
			'title'       => __( 'Lafka — Order Flow', 'lafka' ),
			'description' => __( 'Conversion-focused features around the cart and ordering flow.', 'lafka' ),
			'priority'    => 170,
		)
	);

	// ---------------------------------------------------------------------
	// Section: Sticky cart bar
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_sticky_cart',
		array(
			'title'       => __( 'Sticky cart bar', 'lafka' ),
			'description' => __( 'Fixed-bottom bar that appears once items are in the cart, showing a live subtotal and "View cart" CTA. Hidden on the cart and checkout pages.', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 10,
		)
	);

	$wp_customize->add_setting(
		'lafka_sticky_cart_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_sticky_cart_enabled',
		array(
			'label'   => __( 'Show sticky cart bar', 'lafka' ),
			'section' => 'lafka_order_flow_sticky_cart',
			'type'    => 'checkbox',
		)
	);
}
