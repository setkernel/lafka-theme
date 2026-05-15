<?php
/**
 * Announce bar — Customizer panel + render hook.
 *
 * Registers a "Lafka — Announce Bar" panel exposing the three operator
 * knobs (enabled toggle, free-delivery threshold, show-delivery toggle)
 * and wires the partial to render at the very top of <body> via
 * wp_body_open. The bar reads hours + city + phone from the plugin's
 * lafka_get_restaurant_info() resolver — no separate operator data entry.
 *
 * Pattern mirrors incl/customizer-promo-bar.php (v5.51.0).
 *
 * @package Lafka
 * @since   5.54.0
 */

defined( 'ABSPATH' ) || exit;

// Load the open-status helper that the partial + REST clients use.
require_once get_template_directory() . '/incl/template-helpers/open-status.php';

if ( ! function_exists( 'lafka_customize_register_announce_bar' ) ) {

	function lafka_customize_register_announce_bar( $wp_customize ) {
		$wp_customize->add_panel(
			'lafka_announce_bar',
			array(
				'title'       => __( 'Lafka — Announce Bar', 'lafka' ),
				'description' => __( 'Site-wide dark strip above the header. Shows live open/closed status, delivery info, and a click-to-call phone link.', 'lafka' ),
				'priority'    => 28,
			)
		);

		$wp_customize->add_section(
			'lafka_announce_bar_section',
			array(
				'title' => __( 'Announce bar', 'lafka' ),
				'panel' => 'lafka_announce_bar',
			)
		);

		$wp_customize->add_setting(
			'lafka_announce_bar_enabled',
			array(
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_announce_bar_enabled',
			array(
				'type'        => 'checkbox',
				'section'     => 'lafka_announce_bar_section',
				'label'       => __( 'Show the announce bar', 'lafka' ),
				'description' => __( 'Renders at the very top of every page. Auto-hides when no hours or phone are configured.', 'lafka' ),
			)
		);

		$wp_customize->add_setting(
			'lafka_announce_bar_show_delivery',
			array(
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_announce_bar_show_delivery',
			array(
				'type'        => 'checkbox',
				'section'     => 'lafka_announce_bar_section',
				'label'       => __( 'Show the delivery line', 'lafka' ),
				'description' => __( 'Adds "Delivery in {city} · Free over ${threshold}" between the status and phone. Hidden below 560px on all viewports.', 'lafka' ),
			)
		);

		$wp_customize->add_setting(
			'lafka_announce_bar_delivery_threshold',
			array(
				'default'           => 30,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_announce_bar_delivery_threshold',
			array(
				'type'        => 'number',
				'section'     => 'lafka_announce_bar_section',
				'label'       => __( 'Free-delivery threshold', 'lafka' ),
				'description' => __( 'Minimum cart subtotal that unlocks free delivery. Displayed in the bar and used by the cart progress meter.', 'lafka' ),
				'input_attrs' => array(
					'min'  => 0,
					'step' => 1,
				),
			)
		);
	}
}
add_action( 'customize_register', 'lafka_customize_register_announce_bar' );

if ( ! function_exists( 'lafka_render_announce_bar' ) ) {
	/**
	 * Render the announce bar via wp_body_open. Priority 5 puts it above
	 * the v5.51.0 promo bar (priority 10) so the dark service strip is
	 * always the topmost item on the page.
	 */
	function lafka_render_announce_bar() {
		get_template_part( 'partials/announce-bar' );
	}
}
add_action( 'wp_body_open', 'lafka_render_announce_bar', 5 );
