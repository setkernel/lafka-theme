<?php
/**
 * Customizer panel: Lafka — Home Page
 *
 * Operator-facing controls for the v5.46.0 native home page. Every
 * setting has a smart default from site identity + WC, so the OSS
 * bundle ships with a working home without any operator configuration.
 *
 * Panel: "Lafka — Home Page"
 * Sections:
 *   1. Hero (eyebrow, headline, subhead, CTAs, image)
 *   2. Category Showcase (eyebrow, headline, limit, ordering)
 *   3. Featured Products (eyebrow, headline, limit)
 *
 * @package Lafka\Customizer
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_customize_register_home' ) ) {

	/**
	 * Register the home-page customizer panel + sections + controls.
	 *
	 * @param WP_Customize_Manager $wp_customize WP Customizer instance.
	 * @return void
	 */
	function lafka_customize_register_home( $wp_customize ) {

		$wp_customize->add_panel(
			'lafka_home',
			array(
				'title'       => __( 'Lafka — Home Page', 'lafka' ),
				'description' => __( 'Customize the native home page hero, category showcase, and featured products. All sections have smart defaults from your site identity and store settings.', 'lafka' ),
				'priority'    => 35,
			)
		);

		// -----------------------------------------------------------------
		// 1. Hero section
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_hero',
			array(
				'title'    => __( 'Hero', 'lafka' ),
				'panel'    => 'lafka_home',
				'priority' => 10,
			)
		);

		$lafka_home_hero_fields = array(
			'lafka_home_hero_eyebrow'             => array(
				'label'   => __( 'Eyebrow (small uppercase label above headline)', 'lafka' ),
				'default' => __( 'Order online', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_hero_headline'            => array(
				'label'   => __( 'Headline', 'lafka' ),
				'default' => get_bloginfo( 'name' ),
				'type'    => 'text',
			),
			'lafka_home_hero_subhead'             => array(
				'label'   => __( 'Sub-headline', 'lafka' ),
				'default' => get_bloginfo( 'description' ),
				'type'    => 'textarea',
			),
			'lafka_home_hero_primary_cta_label'   => array(
				'label'   => __( 'Primary CTA label', 'lafka' ),
				'default' => __( 'Order Now', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_hero_primary_cta_url'     => array(
				'label'   => __( 'Primary CTA URL', 'lafka' ),
				'default' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'type'    => 'url',
			),
			'lafka_home_hero_secondary_cta_label' => array(
				'label'   => __( 'Secondary CTA label (leave blank to hide)', 'lafka' ),
				'default' => __( 'View Menu', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_hero_secondary_cta_url'   => array(
				'label'   => __( 'Secondary CTA URL', 'lafka' ),
				'default' => '',
				'type'    => 'url',
			),
		);

		foreach ( $lafka_home_hero_fields as $lafka_setting_id => $lafka_field ) {
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
					'label'   => $lafka_field['label'],
					'section' => 'lafka_home_hero',
					'type'    => $lafka_field['type'],
				)
			);
		}

		$wp_customize->add_setting(
			'lafka_home_hero_image_id',
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'lafka_home_hero_image_id',
				array(
					'label'     => __( 'Hero background image (optional)', 'lafka' ),
					'section'   => 'lafka_home_hero',
					'mime_type' => 'image',
				)
			)
		);

		// -----------------------------------------------------------------
		// 2. Category showcase
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_categories',
			array(
				'title'    => __( 'Category Showcase', 'lafka' ),
				'panel'    => 'lafka_home',
				'priority' => 20,
			)
		);

		$lafka_home_cat_fields = array(
			'lafka_home_categories_eyebrow'  => array(
				'label'   => __( 'Eyebrow', 'lafka' ),
				'default' => __( 'Browse the menu', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_categories_headline' => array(
				'label'   => __( 'Headline', 'lafka' ),
				'default' => __( 'What are you craving?', 'lafka' ),
				'type'    => 'text',
			),
		);

		foreach ( $lafka_home_cat_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$lafka_setting_id,
				array(
					'label'   => $lafka_field['label'],
					'section' => 'lafka_home_categories',
					'type'    => $lafka_field['type'],
				)
			);
		}

		$wp_customize->add_setting(
			'lafka_home_categories_limit',
			array(
				'default'           => 6,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_categories_limit',
			array(
				'label'       => __( 'How many categories to show', 'lafka' ),
				'section'     => 'lafka_home_categories',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 12,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'lafka_home_categories_orderby',
			array(
				'default'           => 'count',
				'sanitize_callback' => 'sanitize_key',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_categories_orderby',
			array(
				'label'   => __( 'Order categories by', 'lafka' ),
				'section' => 'lafka_home_categories',
				'type'    => 'select',
				'choices' => array(
					'count'      => __( 'Most popular (item count)', 'lafka' ),
					'name'       => __( 'Alphabetical', 'lafka' ),
					'menu_order' => __( 'Manual order (drag-and-drop in Products → Categories)', 'lafka' ),
				),
			)
		);

		// -----------------------------------------------------------------
		// 3. Featured products
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_featured',
			array(
				'title'       => __( 'Featured Products', 'lafka' ),
				'description' => __( 'Showcases products marked as "Featured" in Products → All Products.', 'lafka' ),
				'panel'       => 'lafka_home',
				'priority'    => 30,
			)
		);

		$lafka_home_feat_fields = array(
			'lafka_home_featured_eyebrow'  => array(
				'label'   => __( 'Eyebrow', 'lafka' ),
				'default' => __( "Tonight's specials", 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_featured_headline' => array(
				'label'   => __( 'Headline', 'lafka' ),
				'default' => __( 'Top picks', 'lafka' ),
				'type'    => 'text',
			),
		);

		foreach ( $lafka_home_feat_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$lafka_setting_id,
				array(
					'label'   => $lafka_field['label'],
					'section' => 'lafka_home_featured',
					'type'    => $lafka_field['type'],
				)
			);
		}

		$wp_customize->add_setting(
			'lafka_home_featured_limit',
			array(
				'default'           => 8,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_featured_limit',
			array(
				'label'       => __( 'How many featured products to show', 'lafka' ),
				'section'     => 'lafka_home_featured',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 12,
					'step' => 1,
				),
			)
		);
	}
}
add_action( 'customize_register', 'lafka_customize_register_home' );
