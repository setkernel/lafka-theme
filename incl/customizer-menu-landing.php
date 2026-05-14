<?php
/**
 * Customizer panel for the /menu/ landing template.
 *
 * Exposes operator-facing settings for page-menu.php so the OSS theme
 * bundle works out-of-box and can be tailored per install without
 * code edits. Pattern matches incl/customizer-editorial.php.
 *
 * All settings are namespaced `lafka_menu_landing_*` and accessed via
 * `get_theme_mod( 'lafka_menu_landing_<key>', <default> )`.
 *
 * @package Lafka
 * @since   5.25.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_menu_landing_customizer_register' );

/**
 * Register the Menu Landing Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function lafka_menu_landing_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_panel(
		'lafka_menu_landing',
		array(
			'title'       => __( 'Lafka — Menu Landing', 'lafka' ),
			'description' => __( 'Settings for the auto-generated /menu/ landing template (page-menu.php).', 'lafka' ),
			'priority'    => 165,
		)
	);

	// ---------------------------------------------------------------------
	// Section: Content
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_menu_landing_content',
		array(
			'title'    => __( 'Content', 'lafka' ),
			'panel'    => 'lafka_menu_landing',
			'priority' => 10,
		)
	);

	$wp_customize->add_setting(
		'lafka_menu_landing_intro',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_landing_intro',
		array(
			'label'       => __( 'Intro text', 'lafka' ),
			'description' => __( 'Short tagline shown above the category grid. Leave blank for the default.', 'lafka' ),
			'section'     => 'lafka_menu_landing_content',
			'type'        => 'textarea',
		)
	);

	// ---------------------------------------------------------------------
	// Section: Layout
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_menu_landing_layout',
		array(
			'title'    => __( 'Layout', 'lafka' ),
			'panel'    => 'lafka_menu_landing',
			'priority' => 20,
		)
	);

	$wp_customize->add_setting(
		'lafka_menu_landing_style',
		array(
			'default'           => 'text',
			'sanitize_callback' => 'lafka_menu_landing_sanitize_style',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_landing_style',
		array(
			'label'       => __( 'Card style', 'lafka' ),
			'description' => __( 'Text-first is dense and scannable; image cards add a hero photo per category.', 'lafka' ),
			'section'     => 'lafka_menu_landing_layout',
			'type'        => 'radio',
			'choices'     => array(
				'text'  => __( 'Text only (recommended)', 'lafka' ),
				'image' => __( 'With category images', 'lafka' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lafka_menu_landing_show_count',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_landing_show_count',
		array(
			'label'   => __( 'Show item counts', 'lafka' ),
			'section' => 'lafka_menu_landing_layout',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_menu_landing_show_subcats',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_landing_show_subcats',
		array(
			'label'       => __( 'Show subcategories inline', 'lafka' ),
			'description' => __( 'When a category has children, surface them as inline chips on the card.', 'lafka' ),
			'section'     => 'lafka_menu_landing_layout',
			'type'        => 'checkbox',
		)
	);

	// v5.26.0: default flipped from #fccc4c (Peppery yellow, 1.47:1 contrast,
	// fails WCAG AA) to the design-system accent #dc2626 (4.83:1 AA pass).
	// Existing operator overrides are unaffected.
	$wp_customize->add_setting(
		'lafka_menu_landing_accent',
		array(
			'default'           => '#dc2626',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lafka_menu_landing_accent',
			array(
				'label'   => __( 'Accent colour', 'lafka' ),
				'section' => 'lafka_menu_landing_layout',
			)
		)
	);
}

if ( ! function_exists( 'lafka_menu_landing_sanitize_style' ) ) {
	/**
	 * Sanitize the card-style radio.
	 *
	 * @param string $value Submitted value.
	 * @return string `text` or `image`.
	 */
	function lafka_menu_landing_sanitize_style( $value ) {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, array( 'text', 'image' ), true ) ? $value : 'text';
	}
}
