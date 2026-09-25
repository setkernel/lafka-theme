<?php
/**
 * Customizer: GX4 "counter" design controls.
 *
 *  1. Lafka Settings → Page layouts — one select per surface
 *     (lafka_{header,home,menu,footer,drawer}_layout ∈ classic|counter) plus the
 *     decorative motif (lafka_motif ∈ none|check). Each DEFAULT is the active
 *     preset's variant (lafka_preset_variant()), so operator > preset > classic.
 *  2. Lafka — Home Page → Counter sections — co-star categories, hero dishes,
 *     deals category + featured deal + copy, per-section limits, the rest of
 *     the menu, jump links, find-us. Visible while the counter home is active;
 *     one selective-refresh partial re-renders .lafka-counter-home.
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
			'lafka_counter_brand_short',
			array(
				'default'           => '',
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_counter_brand_short',
			array(
				'label'       => __( 'Short name on phones (counter header)', 'lafka' ),
				'description' => __( 'Optional, e.g. the first word of your business name. Shown next to the logo below 600 px; empty keeps the full name, sized to fit one line.', 'lafka' ),
				'section'     => 'lafka_layouts',
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			'lafka_counter_mobile_bar',
			array(
				'default'           => true,
				'type'              => 'theme_mod',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lafka_counter_mobile_bar',
			array(
				'label'       => __( 'Show the Call / Order bar on phones and tablets (counter header)', 'lafka' ),
				'description' => __( 'Hidden on the cart, checkout and product pages. Replaces the classic sticky cart bar.', 'lafka' ),
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

if ( ! function_exists( 'lafka_counter_sanitize_limit_12' ) ) {
	/**
	 * 1–12 (deals / co-star rows); anything else -> the setting default.
	 *
	 * @param mixed  $value   Value.
	 * @param object $setting WP_Customize_Setting.
	 */
	function lafka_counter_sanitize_limit_12( $value, $setting = null ): int {
		$value = absint( $value );
		return $value >= 1 && $value <= 12 ? $value : (int) ( is_object( $setting ) && isset( $setting->default ) ? $setting->default : 6 );
	}
}

if ( ! function_exists( 'lafka_counter_sanitize_limit_24' ) ) {
	/**
	 * 1–24 (rows per "more from our menu" section).
	 *
	 * @param mixed  $value   Value.
	 * @param object $setting WP_Customize_Setting.
	 */
	function lafka_counter_sanitize_limit_24( $value, $setting = null ): int {
		$value = absint( $value );
		return $value >= 1 && $value <= 24 ? $value : (int) ( is_object( $setting ) && isset( $setting->default ) ? $setting->default : 6 );
	}
}

if ( ! function_exists( 'lafka_counter_sanitize_menu_style' ) ) {
	/**
	 * compact | photo.
	 *
	 * @param mixed $value Value.
	 */
	function lafka_counter_sanitize_menu_style( $value ): string {
		return 'photo' === $value ? 'photo' : 'compact';
	}
}

if ( ! function_exists( 'lafka_counter_home_is_active' ) ) {
	/** Customizer active_callback: the counter homepage is showing. */
	function lafka_counter_home_is_active(): bool {
		return function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'home', 'counter' );
	}
}

if ( ! function_exists( 'lafka_counter_category_choices' ) ) {
	/**
	 * Top-level product categories for a select, "Automatic" first.
	 *
	 * @return array<int,string>
	 */
	function lafka_counter_category_choices(): array {
		$choices = array( 0 => __( 'Automatic', 'lafka' ) );
		$terms   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'menu_order',
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$choices[ (int) $term->term_id ] = (string) $term->name;
			}
		}
		return $choices;
	}
}

if ( ! function_exists( 'lafka_counter_product_choices' ) ) {
	/**
	 * Published products for a select, "Automatic" first (title order, capped).
	 *
	 * @return array<int,string>
	 */
	function lafka_counter_product_choices(): array {
		$choices = array( 0 => __( 'Automatic', 'lafka' ) );
		if ( ! function_exists( 'wc_get_products' ) ) {
			return $choices;
		}
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => (int) apply_filters( 'lafka_counter_product_choices_limit', 300 ),
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
		foreach ( (array) $products as $product ) {
			$choices[ (int) $product->get_id() ] = wp_strip_all_tags( (string) $product->get_name() );
		}
		return $choices;
	}
}

if ( ! function_exists( 'lafka_counter_customize_register_home' ) ) {
	/**
	 * Register "Lafka — Home Page → Counter sections".
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 */
	function lafka_counter_customize_register_home( $wp_customize ): void {
		$wp_customize->add_section(
			'lafka_home_counter',
			array(
				'title'           => __( 'Counter sections', 'lafka' ),
				'description'     => __( 'What the counter homepage shows. "Automatic" follows your category order (Products → Categories, drag to sort) and featured products.', 'lafka' ),
				'panel'           => 'lafka_home',
				'priority'        => 5,
				'active_callback' => 'lafka_counter_home_is_active',
			)
		);

		$categories = lafka_counter_category_choices();
		$products   = lafka_counter_product_choices();

		$fields = array(
			'lafka_counter_costar_a'       => array( 0, 'absint', 'select', __( 'First featured category', 'lafka' ), $categories, __( 'Shown beside the second one under the deals. Automatic = the first two categories in your order.', 'lafka' ) ),
			'lafka_counter_costar_b'       => array( 0, 'absint', 'select', __( 'Second featured category', 'lafka' ), $categories, '' ),
			'lafka_counter_hero_product_a' => array( 0, 'absint', 'select', __( 'Hero dish (front)', 'lafka' ), $products, __( 'Automatic = the first product with a photo in the first featured category.', 'lafka' ) ),
			'lafka_counter_hero_product_b' => array( 0, 'absint', 'select', __( 'Hero dish (back)', 'lafka' ), $products, '' ),
			'lafka_counter_deals_cat'      => array( 0, 'absint', 'select', __( 'Deals category', 'lafka' ), $categories, __( 'Automatic = a category named deals, combos or specials.', 'lafka' ) ),
			'lafka_counter_featured_deal'  => array( 0, 'absint', 'select', __( 'Featured deal', 'lafka' ), $products, __( 'Automatic = a featured product in the deals category, else the first one.', 'lafka' ) ),
			'lafka_counter_deals_heading'  => array( __( "Today's deals", 'lafka' ), 'sanitize_text_field', 'text', __( 'Deals heading', 'lafka' ), array(), '' ),
			'lafka_counter_deals_lead'     => array( '', 'sanitize_text_field', 'text', __( 'Deals line', 'lafka' ), array(), __( 'Optional. Only claim what is true (for example that ordering here costs less than the delivery apps).', 'lafka' ) ),
			'lafka_counter_deals_limit'    => array( 6, 'lafka_counter_sanitize_limit_12', 'number', __( 'Deals shown', 'lafka' ), array(), __( '1 to 12.', 'lafka' ) ),
			'lafka_counter_costar_limit'   => array( 3, 'lafka_counter_sanitize_limit_12', 'number', __( 'Dishes per featured category', 'lafka' ), array(), __( '1 to 12.', 'lafka' ) ),
			'lafka_counter_menu_heading'   => array( __( 'More from our menu', 'lafka' ), 'sanitize_text_field', 'text', __( 'Heading above the rest of the menu', 'lafka' ), array(), '' ),
			'lafka_counter_menu_limit'     => array( 3, 'lafka_counter_sanitize_limit_24', 'number', __( 'Dishes per category (rest of the menu)', 'lafka' ), array(), __( '1 to 24. Longer categories get a "See all" link.', 'lafka' ) ),
			'lafka_counter_menu_style'     => array(
				'compact',
				'lafka_counter_sanitize_menu_style',
				'select',
				__( 'Rest of the menu: row style', 'lafka' ),
				array(
					'compact' => __( 'Compact', 'lafka' ),
					'photo'   => __( 'Large photos', 'lafka' ),
				),
				'',
			),
			'lafka_counter_menu_thumbs'    => array( true, 'rest_sanitize_boolean', 'checkbox', __( 'Show small photos in compact rows', 'lafka' ), array(), '' ),
			'lafka_counter_jump_links'     => array( true, 'rest_sanitize_boolean', 'checkbox', __( 'Show the list of category links', 'lafka' ), array(), '' ),
			'lafka_counter_show_find_us'   => array( true, 'rest_sanitize_boolean', 'checkbox', __( 'Show "Find us" (address, hours, phone)', 'lafka' ), array(), '' ),
		);

		foreach ( $fields as $id => $field ) {
			list( $default, $sanitize, $type, $label, $choices, $description ) = $field;
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $default,
					'type'              => 'theme_mod',
					'sanitize_callback' => $sanitize,
					'transport'         => 'postMessage',
				)
			);
			$control = array(
				'label'       => $label,
				'description' => $description,
				'section'     => 'lafka_home_counter',
				'type'        => $type,
			);
			if ( $choices ) {
				$control['choices'] = $choices;
			}
			if ( 'number' === $type ) {
				$control['input_attrs'] = array(
					'min' => 1,
					'max' => 'lafka_counter_sanitize_limit_24' === $sanitize ? 24 : 12,
				);
			}
			$wp_customize->add_control( $id, $control );
		}

		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'lafka_counter_home',
				array(
					'selector'            => '.lafka-counter-home',
					'settings'            => array_keys( $fields ),
					'container_inclusive' => true,
					'render_callback'     => 'lafka_counter_render_home_partial',
				)
			);
		}
	}
	add_action( 'customize_register', 'lafka_counter_customize_register_home', 20 );
}

if ( ! function_exists( 'lafka_counter_render_home_partial' ) ) {
	/** Selective-refresh render of the counter homepage body. */
	function lafka_counter_render_home_partial(): void {
		get_template_part( 'partials/counter/home' );
	}
}
