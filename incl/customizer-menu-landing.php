<?php
/**
 * Customizer panel for the /menu/ landing template.
 *
 * Exposes operator-facing settings for page-menu.php (and the matching
 * shop view in woocommerce/archive-product.php) so the OSS theme bundle
 * works out-of-box and can be tailored per install without code edits.
 * Pattern matches incl/customizer-editorial.php.
 *
 * The two settings registered here are the keys the rebuilt /menu/
 * template actually reads:
 *   - `lafka_menu_archive_title` — page-menu.php:33-39, archive-product.php:50
 *   - `lafka_menu_archive_lead`  — page-menu.php:41-44, archive-product.php:51-54
 *
 * Both are accessed via `get_theme_mod( '<key>', <default> )`.
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
			'description' => __( 'Settings for the auto-generated /menu/ landing template (page-menu.php) and the shop archive.', 'lafka' ),
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

	// Heading shown at the top of /menu/ and the shop archive. Default ''
	// so the get_the_title() / 'The full menu' fallback chain in
	// page-menu.php:33-39 (and archive-product.php:50) stays in control when
	// the operator has not set an explicit heading.
	$wp_customize->add_setting(
		'lafka_menu_archive_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_archive_title',
		array(
			'label'       => __( 'Menu heading', 'lafka' ),
			'description' => __( 'Headline at the top of the menu. Leave blank to use the page title (falls back to "The full menu").', 'lafka' ),
			'section'     => 'lafka_menu_landing_content',
			'type'        => 'text',
		)
	);

	// Lead paragraph beneath the heading. Sanitized with wp_kses_post (NOT
	// sanitize_text_field) because the templates emit it via wp_kses_post
	// (page-menu.php:81, archive-product.php:97), so inline markup must
	// survive the save. Default mirrors the template fallback at
	// page-menu.php:43 / archive-product.php:52-53.
	$wp_customize->add_setting(
		'lafka_menu_archive_lead',
		array(
			'default'           => __( 'Browse everything we make. Tap a category to jump to it or scroll through the whole menu.', 'lafka' ),
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_menu_archive_lead',
		array(
			'label'       => __( 'Menu lead copy', 'lafka' ),
			'description' => __( 'Intro paragraph shown beneath the heading. Leave blank to hide it.', 'lafka' ),
			'section'     => 'lafka_menu_landing_content',
			'type'        => 'textarea',
		)
	);
}
