<?php
/**
 * Customizer section "Lafka — Site text": the appearance copy of the footer and
 * the contact page, so the operator can edit what the templates print. (The
 * contact-page FAQ is business data: it lives in the plugin's settings,
 * WooCommerce → Settings → Restaurant → Contact FAQ.)
 *
 * Defaults mirror the template fallbacks, so the preview matches the page.
 *
 * @package Lafka\Customizer
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_customize_register_site_copy' ) ) {

	/**
	 * Register the section, settings and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize WP Customizer instance.
	 * @return void
	 */
	function lafka_customize_register_site_copy( $wp_customize ) {
		$wp_customize->add_section(
			'lafka_site_copy',
			array(
				'title'       => __( 'Lafka — Site text', 'lafka' ),
				'description' => __( 'Short pieces of text on the footer and the contact page.', 'lafka' ),
				'priority'    => 36,
			)
		);

		$wp_customize->add_setting(
			'lafka_footer_about',
			array(
				'default'           => __( 'Fresh food, made to order from scratch in our kitchen. Order online for pickup or delivery.', 'lafka' ),
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_footer_about',
			array(
				'label'       => __( 'Footer: about text', 'lafka' ),
				'description' => __( 'Two short sentences under the logo in the footer. Leave empty to hide.', 'lafka' ),
				'section'     => 'lafka_site_copy',
				'type'        => 'textarea',
			)
		);

		$wp_customize->add_setting(
			'lafka_contact_hours_note',
			array(
				'default'           => __( 'Last orders 15 min before close. Statutory holidays may differ — call ahead.', 'lafka' ),
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_contact_hours_note',
			array(
				'label'       => __( 'Contact page: note under the opening hours', 'lafka' ),
				'description' => __( 'Say only what is true for your restaurant. Leave empty to hide.', 'lafka' ),
				'section'     => 'lafka_site_copy',
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			'lafka_contact_photo_id',
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'lafka_contact_photo_id',
				array(
					'label'     => __( 'Contact page: photo', 'lafka' ),
					'section'   => 'lafka_site_copy',
					'mime_type' => 'image',
				)
			)
		);
	}
	add_action( 'customize_register', 'lafka_customize_register_site_copy' );
}
