<?php
/**
 * Customizer panel: Lafka — Promo Bar (v5.51.0)
 *
 * Site-wide red deal bar above the header. Defaults OFF so the OSS
 * bundle ships clean — operator opts in via the toggle.
 *
 * @package Lafka\Customizer
 * @since   5.51.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_customize_register_promo_bar' ) ) {

	function lafka_customize_register_promo_bar( $wp_customize ) {

		$wp_customize->add_section(
			'lafka_promo_bar',
			array(
				'title'       => __( 'Lafka — Promo Bar', 'lafka' ),
				'description' => __( 'Site-wide red deal bar above the header. Customers can dismiss; dismissal resets when you change the deal text or dates.', 'lafka' ),
				'priority'    => 36,
			)
		);

		$wp_customize->add_setting(
			'lafka_promo_bar_enabled',
			array(
				'default'           => false,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_promo_bar_enabled',
			array(
				'label'   => __( 'Show promo bar', 'lafka' ),
				'section' => 'lafka_promo_bar',
				'type'    => 'checkbox',
			)
		);

		$lafka_promo_fields = array(
			'lafka_promo_bar_text'       => array(
				'label'       => __( 'Deal text', 'lafka' ),
				'description' => __( 'e.g. "Tuesdays: $5 off any large pizza with code PIZZATUESDAY"', 'lafka' ),
				'default'     => '',
				'type'        => 'text',
			),
			'lafka_promo_bar_link_url'   => array(
				'label'   => __( 'CTA link URL (optional)', 'lafka' ),
				'default' => '',
				'type'    => 'url',
			),
			'lafka_promo_bar_link_label' => array(
				'label'   => __( 'CTA link label', 'lafka' ),
				'default' => __( 'Order now →', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_promo_bar_icon'       => array(
				'label'       => __( 'Icon (emoji)', 'lafka' ),
				'description' => __( 'Single emoji shown before the deal text. Use any emoji or leave blank.', 'lafka' ),
				'default'     => '🍕',
				'type'        => 'text',
			),
			'lafka_promo_bar_start'      => array(
				'label'       => __( 'Start date (YYYY-MM-DD, optional)', 'lafka' ),
				'description' => __( 'Bar hidden before this date. Leave blank to show immediately.', 'lafka' ),
				'default'     => '',
				'type'        => 'text',
			),
			'lafka_promo_bar_end'        => array(
				'label'       => __( 'End date (YYYY-MM-DD, optional)', 'lafka' ),
				'description' => __( 'Bar hidden after this date. Leave blank for no end.', 'lafka' ),
				'default'     => '',
				'type'        => 'text',
			),
		);

		foreach ( $lafka_promo_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'url' === $lafka_field['type'] ? 'esc_url_raw' : 'sanitize_text_field',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$lafka_setting_id,
				array(
					'label'       => $lafka_field['label'],
					'description' => isset( $lafka_field['description'] ) ? $lafka_field['description'] : '',
					'section'     => 'lafka_promo_bar',
					'type'        => $lafka_field['type'],
				)
			);
		}
	}
}
add_action( 'customize_register', 'lafka_customize_register_promo_bar' );

if ( ! function_exists( 'lafka_render_promo_bar' ) ) {
	/**
	 * Render the promo bar via wp_body_open — the universal "top of body"
	 * action fires from header.php before the visible #header markup.
	 */
	function lafka_render_promo_bar() {
		get_template_part( 'partials/promo-bar' );
	}
}
add_action( 'wp_body_open', 'lafka_render_promo_bar', 10 );
