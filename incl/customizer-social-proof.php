<?php
/**
 * Customizer panel — "Lafka — Social Proof"
 *
 * Operator-facing settings for the social-proof / star-widget added in
 * v5.29.0. Manual entry only; defaults are empty so the feature is
 * invisible until the operator opts in. No external API dependencies.
 *
 * Conversion audit cited a 32% PDP lift for visible review counts —
 * the surface this widget targets first.
 *
 * @package Lafka
 * @since   5.29.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_social_proof_customizer_register' );

/**
 * Register the Social Proof Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function lafka_social_proof_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_panel(
		'lafka_social_proof',
		array(
			'title'       => __( 'Lafka — Social Proof', 'lafka' ),
			'description' => __( 'Display a rating / review-count widget. Leave fields blank to hide the widget entirely.', 'lafka' ),
			'priority'    => 175,
		)
	);

	$wp_customize->add_section(
		'lafka_social_proof_main',
		array(
			'title'    => __( 'Rating', 'lafka' ),
			'panel'    => 'lafka_social_proof',
			'priority' => 10,
		)
	);

	$wp_customize->add_setting(
		'lafka_social_proof_rating',
		array(
			'default'           => '',
			'sanitize_callback' => 'lafka_social_proof_sanitize_rating',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_social_proof_rating',
		array(
			'label'       => __( 'Average rating (0–5)', 'lafka' ),
			'description' => __( 'Example: 4.8. Leave blank to hide.', 'lafka' ),
			'section'     => 'lafka_social_proof_main',
			'type'        => 'text',
			'input_attrs' => array(
				'inputmode' => 'decimal',
				'pattern'   => '[0-9]+(\\.[0-9]+)?',
			),
		)
	);

	$wp_customize->add_setting(
		'lafka_social_proof_count',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_social_proof_count',
		array(
			'label'       => __( 'Review count', 'lafka' ),
			'description' => __( 'Example: 312. Leave at 0 to hide the count.', 'lafka' ),
			'section'     => 'lafka_social_proof_main',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lafka_social_proof_provider',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_social_proof_provider',
		array(
			'label'       => __( 'Source name', 'lafka' ),
			'description' => __( 'Where the rating comes from. Example: Google. Optional.', 'lafka' ),
			'section'     => 'lafka_social_proof_main',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'lafka_social_proof_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_social_proof_url',
		array(
			'label'       => __( 'Reviews link (optional)', 'lafka' ),
			'description' => __( 'If provided, the widget links here so customers can read the reviews.', 'lafka' ),
			'section'     => 'lafka_social_proof_main',
			'type'        => 'url',
		)
	);

	// ---------------------------------------------------------------------
	// Section: Visibility
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_social_proof_visibility',
		array(
			'title'    => __( 'Visibility', 'lafka' ),
			'panel'    => 'lafka_social_proof',
			'priority' => 20,
		)
	);

	$wp_customize->add_setting(
		'lafka_social_proof_show_pdp',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_social_proof_show_pdp',
		array(
			'label'   => __( 'Show on product pages', 'lafka' ),
			'section' => 'lafka_social_proof_visibility',
			'type'    => 'checkbox',
		)
	);
}

if ( ! function_exists( 'lafka_social_proof_sanitize_rating' ) ) {
	/**
	 * Sanitize the rating field — empty string or decimal in [0, 5].
	 *
	 * @param mixed $value Submitted value.
	 * @return string Sanitized value or empty string.
	 */
	function lafka_social_proof_sanitize_rating( $value ) {
		if ( '' === $value || null === $value ) {
			return '';
		}
		if ( ! is_numeric( $value ) ) {
			return '';
		}
		$float = (float) $value;
		if ( $float < 0 ) {
			return '0';
		}
		if ( $float > 5 ) {
			return '5';
		}
		return (string) $float;
	}
}
