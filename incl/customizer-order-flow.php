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

	// ---------------------------------------------------------------------
	// Section: Product page (PDP) — v5.27.0
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_pdp',
		array(
			'title'       => __( 'Product page', 'lafka' ),
			'description' => __( 'Conversion-tuned defaults for the single-product page: auto-selected default variation, sticky bottom CTA with live total.', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 20,
		)
	);

	$wp_customize->add_setting(
		'lafka_pdp_sticky_cta_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_pdp_sticky_cta_enabled',
		array(
			'label'       => __( 'Show sticky CTA on product pages', 'lafka' ),
			'description' => __( 'Bottom-anchored "Add — $X.XX" button that stays visible while customers customise their order. Replaces the buried Add-to-Cart button.', 'lafka' ),
			'section'     => 'lafka_order_flow_pdp',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_pdp_default_variation_strategy',
		array(
			'default'           => 'median',
			'sanitize_callback' => 'lafka_pdp_sanitize_default_strategy',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_pdp_default_variation_strategy',
		array(
			'label'       => __( 'Default variation auto-select', 'lafka' ),
			'description' => __( 'Which variation should be pre-selected so customers don\'t have to pick before they see a price.', 'lafka' ),
			'section'     => 'lafka_order_flow_pdp',
			'type'        => 'radio',
			'choices'     => array(
				'median'  => __( 'Median price (recommended)', 'lafka' ),
				'lowest'  => __( 'Lowest price', 'lafka' ),
				'highest' => __( 'Highest price', 'lafka' ),
				'none'    => __( 'No auto-select (customer must choose)', 'lafka' ),
			),
		)
	);
}

if ( ! function_exists( 'lafka_pdp_sanitize_default_strategy' ) ) {
	/**
	 * Sanitize the PDP default-variation strategy radio.
	 *
	 * @param string $value Submitted value.
	 * @return string One of: median, lowest, highest, none.
	 */
	function lafka_pdp_sanitize_default_strategy( $value ) {
		$allowed = array( 'median', 'lowest', 'highest', 'none' );
		return in_array( $value, $allowed, true ) ? $value : 'median';
	}
}
