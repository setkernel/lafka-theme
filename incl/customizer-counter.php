<?php
/**
 * Customizer: GX4 "counter" design controls.
 *
 *  1. Lafka Settings → Page layouts — one select per surface
 *     (lafka_{header,home,menu,footer,drawer}_layout ∈ classic|counter) plus the
 *     decorative motif (lafka_motif ∈ none|check). Each DEFAULT is the active
 *     preset's variant (lafka_preset_variant()), so operator > preset > classic.
 *
 * @package Lafka\Customizer
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_counter_customize_register_layouts' ) ) {
	/**
	 * Register the "Page layouts" section.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 */
	function lafka_counter_customize_register_layouts( $wp_customize ): void {
		$wp_customize->add_section(
			'lafka_layouts',
			array(
				'title'       => __( 'Page layouts', 'lafka' ),
				'description' => __( 'Choose the layout of each part of the storefront. The design preset picks a default for each; your choice here always wins.', 'lafka' ),
				'panel'       => 'lafka_settings',
				'priority'    => 6,
			)
		);

		$labels = array(
			'header' => __( 'Header', 'lafka' ),
			'home'   => __( 'Home page', 'lafka' ),
			'menu'   => __( 'Menu page and category pages', 'lafka' ),
			'footer' => __( 'Footer', 'lafka' ),
			'drawer' => __( 'Cart drawer', 'lafka' ),
		);
		$choices = array(
			'classic' => __( 'Classic', 'lafka' ),
			'counter' => __( 'Counter (calm, big type, size-price rows)', 'lafka' ),
		);

		foreach ( lafka_layout_surfaces() as $surface ) {
			$id = 'lafka_' . $surface . '_layout';
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => lafka_layout_default( $surface ),
					'type'              => 'theme_mod',
					'sanitize_callback' => 'lafka_sanitize_layout',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'label'   => $labels[ $surface ] ?? $surface,
					'section' => 'lafka_layouts',
					'type'    => 'select',
					'choices' => $choices,
				)
			);
		}

		$wp_customize->add_setting(
			'lafka_counter_header_nav',
			array(
				'default'           => true,
				'type'              => 'theme_mod',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_counter_header_nav',
			array(
				'label'       => __( 'Show the header links row (counter header)', 'lafka' ),
				'description' => __( 'Links come from Appearance → Menus → "Header menu (counter layout)"; without a menu: Menu · Deals · Find us. Below 1024 px they move into the menu drawer.', 'lafka' ),
				'section'     => 'lafka_layouts',
				'type'        => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'lafka_motif',
			array(
				'default'           => function_exists( 'lafka_preset_variant' ) ? lafka_sanitize_motif( lafka_preset_variant( 'motif', 'none' ) ) : 'none',
				'type'              => 'theme_mod',
				'sanitize_callback' => 'lafka_sanitize_motif',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_motif',
			array(
				'label'   => __( 'Decorative pattern', 'lafka' ),
				'section' => 'lafka_layouts',
				'type'    => 'select',
				'choices' => array(
					'none'  => __( 'None', 'lafka' ),
					'check' => __( 'Checkered band', 'lafka' ),
				),
			)
		);
	}
	add_action( 'customize_register', 'lafka_counter_customize_register_layouts' );
}
