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
 *   1. Hero (headline, sub-headline, primary CTA, image, stat row)
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

		// Setting IDs mirror the keys the rebuilt hero partial actually
		// reads (partials/home-hero.php), and the textarea default mirrors
		// that partial's get_theme_mod() fallback so the Customizer preview
		// matches the rendered page.
		$lafka_home_hero_fields = array(
			'lafka_home_hero_headline'          => array(
				'label'   => __( 'Headline', 'lafka' ),
				'default' => get_bloginfo( 'name' ),
				'type'    => 'text',
			),
			'lafka_home_hero_lead'              => array(
				'label'   => __( 'Sub-headline', 'lafka' ),
				'default' => __( 'Fresh dough, locally-sourced toppings, and recipes refined over years of serving our neighbors. Ready in about 25 minutes.', 'lafka' ),
				'type'    => 'textarea',
			),
			'lafka_home_hero_primary_cta_label' => array(
				'label'   => __( 'Primary CTA label', 'lafka' ),
				'default' => __( 'Order Now', 'lafka' ),
				'type'    => 'text',
			),
			'lafka_home_hero_primary_cta_url'   => array(
				'label'   => __( 'Primary CTA URL', 'lafka' ),
				'default' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'type'    => 'url',
			),
		);

		// NX2-04: these four copy fields are the highest-traffic hero edits;
		// they ride postMessage so the operator sees the change live without a
		// full preview reload (the lafka_home_hero selective-refresh partial
		// below re-renders the real template part). This loop holds ONLY the
		// four copy fields, so the transport is set directly here.
		foreach ( $lafka_home_hero_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'url' === $lafka_field['type'] ? 'esc_url_raw' : ( 'textarea' === $lafka_field['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
					'transport'         => 'postMessage',
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

		// v5.49.0: text-URL fallback for hero bg — lets operators preview
		// the OSS bundle look without uploading. Default applied via
		// `lafka_home_hero_default_bg_url` filter so child themes can
		// inject a brand-specific fallback without overriding operator's
		// Customizer choice. On pepperypizzapoutine.com the operator's
		// existing yellow-texture upload (uploaded 2021-06 for WPBakery
		// hero) is the right default.
		$lafka_home_hero_bg_default = (string) apply_filters(
			'lafka_home_hero_default_bg_url',
			''
		);
		$wp_customize->add_setting(
			'lafka_home_hero_bg_url',
			array(
				'default'           => $lafka_home_hero_bg_default,
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_hero_bg_url',
			array(
				'label'       => __( 'Hero background image URL (used when no media uploaded above)', 'lafka' ),
				'section'     => 'lafka_home_hero',
				'type'        => 'url',
				'description' => __( 'For OSS bundle defaults. Operators with their own images should use the media picker above.', 'lafka' ),
			)
		);

		$wp_customize->add_setting(
			'lafka_home_hero_overlay',
			array(
				'default'           => false,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_hero_overlay',
			array(
				'label'       => __( 'Darken background image with overlay (for photo-style heroes)', 'lafka' ),
				'section'     => 'lafka_home_hero',
				'type'        => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'lafka_home_hero_show_status',
			array(
				'default'           => true,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_hero_show_status',
			array(
				'label'       => __( 'Show open/closed status pill above headline', 'lafka' ),
				'section'     => 'lafka_home_hero',
				'type'        => 'checkbox',
				'description' => __( 'Pulls live data from Service ETA plugin if installed.', 'lafka' ),
			)
		);

		// Hero stat row (rating / pickup time / delivery). Defaults mirror
		// partials/home-hero.php so preview == render. Stat 1 (the rating
		// slot) ships EMPTY on purpose — seeding a number here would publish
		// fabricated social proof on every fresh install (see
		// HomeSocialProofHonestyTest). Each stat renders only when its value
		// is non-empty, so leaving a value blank hides that stat.
		$lafka_home_hero_stat_fields = array(
			'lafka_home_hero_stat_1_value' => array(
				'label'   => __( 'Stat 1 — value (e.g. 4.9 ★) — leave blank to hide', 'lafka' ),
				'default' => '',
			),
			'lafka_home_hero_stat_1_label' => array(
				'label'   => __( 'Stat 1 — caption', 'lafka' ),
				'default' => '',
			),
			'lafka_home_hero_stat_2_value' => array(
				'label'   => __( 'Stat 2 — value', 'lafka' ),
				'default' => __( '25 min', 'lafka' ),
			),
			'lafka_home_hero_stat_2_label' => array(
				'label'   => __( 'Stat 2 — caption', 'lafka' ),
				'default' => __( 'avg. pickup', 'lafka' ),
			),
			'lafka_home_hero_stat_3_value' => array(
				'label'   => __( 'Stat 3 — value', 'lafka' ),
				// Derived from the free-delivery threshold SSOT so the
				// Customizer preview matches the front end (empty = off).
				'default' => function_exists( 'lafka_home_hero_stat3_defaults' ) ? lafka_home_hero_stat3_defaults()['value'] : '',
			),
			'lafka_home_hero_stat_3_label' => array(
				'label'   => __( 'Stat 3 — caption', 'lafka' ),
				'default' => function_exists( 'lafka_home_hero_stat3_defaults' ) ? lafka_home_hero_stat3_defaults()['label'] : '',
			),
		);
		foreach ( $lafka_home_hero_stat_fields as $lafka_setting_id => $lafka_field ) {
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
					'section' => 'lafka_home_hero',
					'type'    => 'text',
				)
			);
		}

		// NX2-04: hero copy edits re-render just the hero via selective
		// refresh instead of a full preview reload.
		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'lafka_home_hero',
				array(
					'selector'            => '.lafka-hero',
					'settings'            => array(
						'lafka_home_hero_headline',
						'lafka_home_hero_lead',
						'lafka_home_hero_primary_cta_label',
						'lafka_home_hero_primary_cta_url',
					),
					'container_inclusive' => true,
					'fallback_refresh'    => true,
					'render_callback'     => static function () {
						get_template_part( 'partials/home-hero' );
					},
				)
			);
		}

		// -----------------------------------------------------------------
		// 4. Story split
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_story',
			array(
				'title'       => __( 'Story (Made Here)', 'lafka' ),
				'description' => __( 'Local-brand differentiation block — kitchen photo + short story. Hide entirely via the toggle below if you prefer.', 'lafka' ),
				'panel'       => 'lafka_home',
				'priority'    => 40,
			)
		);

		$wp_customize->add_setting(
			'lafka_home_story_visible',
			array(
				'default'           => true,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_story_visible',
			array(
				'label'   => __( 'Show this section', 'lafka' ),
				'section' => 'lafka_home_story',
				'type'    => 'checkbox',
			)
		);

		$lafka_home_story_fields = array(
			'lafka_home_story_eyebrow'   => array(
				'label' => __( 'Eyebrow', 'lafka' ),
				'default' => __( 'Made here', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_story_headline'  => array(
				'label' => __( 'Headline', 'lafka' ),
				'default' => '',
				'type' => 'text',
			),
			'lafka_home_story_body'      => array(
				'label' => __( 'Body paragraph', 'lafka' ),
				'default' => '',
				'type' => 'textarea',
			),
			'lafka_home_story_cta_label' => array(
				'label' => __( 'CTA label', 'lafka' ),
				'default' => __( 'Visit us', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_story_cta_url'   => array(
				'label' => __( 'CTA URL (leave blank to hide CTA)', 'lafka' ),
				'default' => '',
				'type' => 'url',
			),
		);
		foreach ( $lafka_home_story_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'url' === $lafka_field['type'] ? 'esc_url_raw' : ( 'textarea' === $lafka_field['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$lafka_setting_id,
				array(
					'label'   => $lafka_field['label'],
					'section' => 'lafka_home_story',
					'type'    => $lafka_field['type'],
				)
			);
		}

		$wp_customize->add_setting(
			'lafka_home_story_image_id',
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'lafka_home_story_image_id',
				array(
					'label'     => __( 'Story image (kitchen, owner portrait, etc.)', 'lafka' ),
					'section'   => 'lafka_home_story',
					'mime_type' => 'image',
				)
			)
		);

		// -----------------------------------------------------------------
		// 5. Reviews wall
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_reviews',
			array(
				'title'       => __( 'Reviews', 'lafka' ),
				'description' => __( 'Up to 3 customer review cards plus an optional aggregate rating callout. Section is hidden automatically when no reviews entered.', 'lafka' ),
				'panel'       => 'lafka_home',
				'priority'    => 50,
			)
		);

		$wp_customize->add_setting(
			'lafka_home_reviews_visible',
			array(
				'default'           => true,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_reviews_visible',
			array(
				'label'   => __( 'Show this section', 'lafka' ),
				'section' => 'lafka_home_reviews',
				'type'    => 'checkbox',
			)
		);

		$lafka_home_reviews_fields = array(
			'lafka_home_reviews_eyebrow'  => array(
				'label' => __( 'Eyebrow', 'lafka' ),
				'default' => __( 'Loved locally', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_reviews_headline' => array(
				'label' => __( 'Headline', 'lafka' ),
				'default' => __( 'What our neighbors say', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_reviews_rating'   => array(
				'label' => __( 'Aggregate rating (e.g. 4.9)', 'lafka' ),
				'default' => '',
				'type' => 'text',
			),
			'lafka_home_reviews_count'    => array(
				'label' => __( 'Number of reviews (e.g. 230)', 'lafka' ),
				'default' => '',
				'type' => 'text',
			),
			'lafka_home_reviews_source'   => array(
				'label' => __( 'Review source (e.g. Google, Yelp)', 'lafka' ),
				'default' => 'Google',
				'type' => 'text',
			),
		);
		foreach ( $lafka_home_reviews_fields as $lafka_setting_id => $lafka_field ) {
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
					'section' => 'lafka_home_reviews',
					'type'    => $lafka_field['type'],
				)
			);
		}

		// Three review cards (quote + name + source + stars).
		for ( $lafka_r = 1; $lafka_r <= 3; $lafka_r++ ) {
			$lafka_review_card_fields = array(
				"lafka_home_reviews_{$lafka_r}_quote"  => array(
					'label' => sprintf( __( 'Review %d — quote', 'lafka' ), $lafka_r ),
					'default' => '',
					'type' => 'textarea',
				),
				"lafka_home_reviews_{$lafka_r}_name"   => array(
					'label' => sprintf( __( 'Review %d — reviewer name', 'lafka' ), $lafka_r ),
					'default' => '',
					'type' => 'text',
				),
				"lafka_home_reviews_{$lafka_r}_source" => array(
					'label' => sprintf( __( 'Review %d — source (Google/Yelp/etc.)', 'lafka' ), $lafka_r ),
					'default' => '',
					'type' => 'text',
				),
			);
			foreach ( $lafka_review_card_fields as $lafka_setting_id => $lafka_field ) {
				$wp_customize->add_setting(
					$lafka_setting_id,
					array(
						'default'           => $lafka_field['default'],
						'sanitize_callback' => 'textarea' === $lafka_field['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);
				$wp_customize->add_control(
					$lafka_setting_id,
					array(
						'label'   => $lafka_field['label'],
						'section' => 'lafka_home_reviews',
						'type'    => $lafka_field['type'],
					)
				);
			}

			$wp_customize->add_setting(
				"lafka_home_reviews_{$lafka_r}_stars",
				array(
					'default'           => 5,
					'sanitize_callback' => 'absint',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				"lafka_home_reviews_{$lafka_r}_stars",
				array(
					'label'       => sprintf( __( 'Review %d — star rating (1–5)', 'lafka' ), $lafka_r ),
					'section'     => 'lafka_home_reviews',
					'type'        => 'number',
					'input_attrs' => array(
						'min' => 1,
						'max' => 5,
						'step' => 1,
					),
				)
			);
		}

		// -----------------------------------------------------------------
		// 6. CTA closer band
		// -----------------------------------------------------------------
		$wp_customize->add_section(
			'lafka_home_closer',
			array(
				'title'       => __( 'CTA Closer Band', 'lafka' ),
				'description' => __( 'Full-bleed brand-yellow band at the bottom of the home — catches scrollers who didn\'t tap a CTA above.', 'lafka' ),
				'panel'       => 'lafka_home',
				'priority'    => 60,
			)
		);

		$wp_customize->add_setting(
			'lafka_home_closer_visible',
			array(
				'default'           => true,
				'sanitize_callback' => 'wp_validate_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_home_closer_visible',
			array(
				'label'   => __( 'Show this section', 'lafka' ),
				'section' => 'lafka_home_closer',
				'type'    => 'checkbox',
			)
		);

		// Setting IDs mirror the keys the rebuilt closer partial actually
		// reads (partials/home-cta-closer.php); the textarea default mirrors
		// that partial's get_theme_mod() fallback so preview == render.
		$lafka_home_closer_fields = array(
			'lafka_home_closer_headline'  => array(
				'label' => __( 'Headline', 'lafka' ),
				'default' => __( 'Hungry?', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_closer_lead'      => array(
				'label' => __( 'Sub-headline', 'lafka' ),
				'default' => __( 'Pickup or delivery. Ready in about 25 minutes.', 'lafka' ),
				'type' => 'textarea',
			),
			'lafka_home_closer_cta_label' => array(
				'label' => __( 'CTA label', 'lafka' ),
				'default' => __( 'Order Now', 'lafka' ),
				'type' => 'text',
			),
			'lafka_home_closer_cta_url'   => array(
				'label' => __( 'CTA URL', 'lafka' ),
				'default' => '',
				'type' => 'url',
			),
		);
		foreach ( $lafka_home_closer_fields as $lafka_setting_id => $lafka_field ) {
			$wp_customize->add_setting(
				$lafka_setting_id,
				array(
					'default'           => $lafka_field['default'],
					'sanitize_callback' => 'url' === $lafka_field['type'] ? 'esc_url_raw' : ( 'textarea' === $lafka_field['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$lafka_setting_id,
				array(
					'label'   => $lafka_field['label'],
					'section' => 'lafka_home_closer',
					'type'    => $lafka_field['type'],
				)
			);
		}

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
