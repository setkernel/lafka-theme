<?php
/**
 * Customizer panel — "Lafka — Service ETA"
 *
 * Operator-facing pickup / delivery time-estimate bands. Defaults are
 * empty so the strip is invisible until the operator sets values
 * (zero-config OSS-friendly).
 *
 * Conversion audit #7 — loss aversion / ambiguity is a key abandonment
 * driver on food-ordering sites; surfacing "Ready in 20-30 min" /
 * "Delivery in 35-50 min" closes the anxiety gap.
 *
 * @package Lafka
 * @since   5.30.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_service_eta_customizer_register' );

/**
 * Register the Service ETA Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function lafka_service_eta_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_panel(
		'lafka_service_eta',
		array(
			'title'       => __( 'Lafka — Service ETA', 'lafka' ),
			'description' => __( 'Pickup and delivery time estimates surfaced on the header strip, cart, and (optionally) the checkout submit button. Leave fields blank to hide the strip entirely.', 'lafka' ),
			'priority'    => 180,
		)
	);

	$wp_customize->add_section(
		'lafka_service_eta_times',
		array(
			'title'    => __( 'Times', 'lafka' ),
			'panel'    => 'lafka_service_eta',
			'priority' => 10,
		)
	);

	$wp_customize->add_setting(
		'lafka_service_eta_pickup',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_service_eta_pickup',
		array(
			'label'       => __( 'Pickup ETA', 'lafka' ),
			'description' => __( 'Free-text time estimate. Example: 20-30 min. Leave blank to hide.', 'lafka' ),
			'section'     => 'lafka_service_eta_times',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'lafka_service_eta_delivery',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_service_eta_delivery',
		array(
			'label'       => __( 'Delivery ETA', 'lafka' ),
			'description' => __( 'Free-text time estimate. Example: 35-50 min. Leave blank to hide.', 'lafka' ),
			'section'     => 'lafka_service_eta_times',
			'type'        => 'text',
		)
	);

	// ---------------------------------------------------------------------
	// Section: Visibility
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_service_eta_visibility',
		array(
			'title'    => __( 'Visibility', 'lafka' ),
			'panel'    => 'lafka_service_eta',
			'priority' => 20,
		)
	);

	$wp_customize->add_setting(
		'lafka_service_eta_show_header',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_service_eta_show_header',
		array(
			'label'       => __( 'Show on header strip', 'lafka' ),
			'description' => __( 'Renders next to the existing "Open until …" info bar.', 'lafka' ),
			'section'     => 'lafka_service_eta_visibility',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_service_eta_show_cart',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_service_eta_show_cart',
		array(
			'label'       => __( 'Show on cart and checkout', 'lafka' ),
			'section'     => 'lafka_service_eta_visibility',
			'type'        => 'checkbox',
		)
	);
}
