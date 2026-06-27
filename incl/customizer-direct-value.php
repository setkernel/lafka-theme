<?php
/**
 * "Order Direct — skip the app fees" value component.
 *
 * The core growth lever: most demand currently flows through UberEats / Skip /
 * DoorDash (20–30% commission). This reusable, Customizer-first component tells
 * customers the website is the cheaper, direct way to order, in three contexts
 * (home value-strip, menu badge, cart/checkout reassurance line). The CTAs carry
 * the data-lafka-order-channel="direct" contract so order_channel_click fires
 * (see lafka-plugin/docs/TRACKING.md).
 *
 * All copy is honest + operator-tunable; filter `lafka_direct_value_data`.
 *
 * @package Lafka
 * @since   6.14.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_direct_value_enabled' ) ) {
	/** @return bool */
	function lafka_direct_value_enabled(): bool {
		return (bool) get_theme_mod( 'lafka_direct_value_enabled', true );
	}
}

if ( ! function_exists( 'lafka_direct_value_default_cta_url' ) ) {
	/**
	 * Best "start ordering" URL: the menu page → WC shop → home.
	 *
	 * @return string
	 */
	function lafka_direct_value_default_cta_url(): string {
		$menu = get_page_by_path( 'menu' );
		if ( $menu instanceof WP_Post ) {
			return (string) get_permalink( $menu );
		}
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop = wc_get_page_permalink( 'shop' );
			if ( $shop ) {
				return (string) $shop;
			}
		}
		return home_url( '/' );
	}
}

if ( ! function_exists( 'lafka_direct_value_data' ) ) {
	/**
	 * Component content (honest defaults; operator/filter overridable).
	 *
	 * @return array
	 */
	function lafka_direct_value_data(): array {
		$cta_url = (string) get_theme_mod( 'lafka_direct_value_cta_url', '' );
		if ( '' === $cta_url ) {
			$cta_url = lafka_direct_value_default_cta_url();
		}
		$points = array_values(
            array_filter(
                array(
					(string) get_theme_mod( 'lafka_direct_value_point_1', __( 'Our real menu prices — no delivery-app markup', 'lafka' ) ),
					(string) get_theme_mod( 'lafka_direct_value_point_2', __( 'Straight to our kitchen, made fresh', 'lafka' ) ),
					(string) get_theme_mod( 'lafka_direct_value_point_3', __( 'Direct-only deals you won’t find on the apps', 'lafka' ) ),
                ) 
            ) 
        );

		return (array) apply_filters(
			'lafka_direct_value_data',
			array(
				'enabled'    => lafka_direct_value_enabled(),
				'heading'    => (string) get_theme_mod( 'lafka_direct_value_heading', __( 'Order direct & save', 'lafka' ) ),
				'subheading' => (string) get_theme_mod( 'lafka_direct_value_subheading', __( 'Same kitchen, same food — without the delivery-app fees.', 'lafka' ) ),
				'points'     => $points,
				'line'       => (string) get_theme_mod( 'lafka_direct_value_line', __( 'Ordering direct — these are our real prices, no app markup.', 'lafka' ) ),
				'cta_label'  => (string) get_theme_mod( 'lafka_direct_value_cta_label', __( 'Start your order', 'lafka' ) ),
				'cta_url'    => $cta_url,
			)
		);
	}
}

if ( ! function_exists( 'lafka_render_direct_value' ) ) {
	/**
	 * Output the component for a context: home | menu | cart | checkout.
	 *
	 * @param string $context
	 * @return void
	 */
	function lafka_render_direct_value( string $context = 'home' ): void {
		if ( ! lafka_direct_value_enabled() ) {
			return;
		}
		$GLOBALS['lafka_direct_value_context'] = $context;
		get_template_part( 'partials/direct-value' );
		unset( $GLOBALS['lafka_direct_value_context'] );
	}
}

// Enqueue the component CSS when it can appear (home, menu, cart, checkout).
if ( ! function_exists( 'lafka_direct_value_enqueue' ) ) {
	add_action( 'wp_enqueue_scripts', 'lafka_direct_value_enqueue', 21 );
	function lafka_direct_value_enqueue(): void {
		if ( ! lafka_direct_value_enabled() ) {
			return;
		}
		$show = is_front_page()
			|| ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| is_page_template( 'page-menu.php' )
			|| ( function_exists( 'is_shop' ) && is_shop() );
		// Cart drawer can appear anywhere, so also load site-wide-lite via the
		// small CSS only where the component renders; the drawer line reuses
		// existing drawer styles, so this is enough.
		if ( ! $show ) {
			return;
		}
		wp_enqueue_style(
			'lafka-direct-value',
			get_template_directory_uri() . '/styles/lafka-direct-value.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-direct-value.css' )
		);
	}
}

// Cart + checkout reassurance line (theme-agnostic hooks — no template override).
add_action( 'woocommerce_checkout_before_customer_details', 'lafka_direct_value_checkout_line', 4 );
if ( ! function_exists( 'lafka_direct_value_checkout_line' ) ) {
	function lafka_direct_value_checkout_line(): void {
		lafka_render_direct_value( 'checkout' );
	}
}
add_action( 'woocommerce_before_cart', 'lafka_direct_value_cart_line', 4 );
if ( ! function_exists( 'lafka_direct_value_cart_line' ) ) {
	function lafka_direct_value_cart_line(): void {
		lafka_render_direct_value( 'cart' );
	}
}

// Customizer controls.
if ( ! function_exists( 'lafka_customize_register_direct_value' ) ) {
	add_action( 'customize_register', 'lafka_customize_register_direct_value' );
	function lafka_customize_register_direct_value( $wp_customize ): void {
		$wp_customize->add_section(
			'lafka_direct_value',
			array(
				'title'       => esc_html__( 'Lafka — Order Direct', 'lafka' ),
				'description' => esc_html__( 'The "order direct & save vs the delivery apps" value message shown on the home page, menu, cart and checkout. Keep it honest — only claim what is true for your store.', 'lafka' ),
				'priority'    => 36,
			)
		);
		$fields = array(
			'lafka_direct_value_enabled'    => array( 'checkbox', esc_html__( 'Show the "Order direct & save" message', 'lafka' ), true ),
			'lafka_direct_value_heading'    => array( 'text', esc_html__( 'Heading', 'lafka' ), __( 'Order direct & save', 'lafka' ) ),
			'lafka_direct_value_subheading' => array( 'text', esc_html__( 'Subheading', 'lafka' ), __( 'Same kitchen, same food — without the delivery-app fees.', 'lafka' ) ),
			'lafka_direct_value_point_1'    => array( 'text', esc_html__( 'Bullet 1', 'lafka' ), __( 'Our real menu prices — no delivery-app markup', 'lafka' ) ),
			'lafka_direct_value_point_2'    => array( 'text', esc_html__( 'Bullet 2', 'lafka' ), __( 'Straight to our kitchen, made fresh', 'lafka' ) ),
			'lafka_direct_value_point_3'    => array( 'text', esc_html__( 'Bullet 3', 'lafka' ), __( 'Direct-only deals you won’t find on the apps', 'lafka' ) ),
			'lafka_direct_value_line'       => array( 'text', esc_html__( 'Cart / checkout line', 'lafka' ), __( 'Ordering direct — these are our real prices, no app markup.', 'lafka' ) ),
			'lafka_direct_value_cta_label'  => array( 'text', esc_html__( 'Button label', 'lafka' ), __( 'Start your order', 'lafka' ) ),
			'lafka_direct_value_cta_url'    => array( 'url', esc_html__( 'Button URL (blank = menu page)', 'lafka' ), '' ),
		);
		foreach ( $fields as $key => $cfg ) {
			list( $type, $label, $default ) = $cfg;
			$sanitize = 'checkbox' === $type ? 'wp_validate_boolean' : ( 'url' === $type ? 'esc_url_raw' : 'sanitize_text_field' );
			$wp_customize->add_setting(
                $key,
                array(
					'default' => $default,
					'transport' => 'refresh',
					'sanitize_callback' => $sanitize,
                ) 
            );
			$wp_customize->add_control(
                $key,
                array(
					'label' => $label,
					'section' => 'lafka_direct_value',
					'type' => 'checkbox' === $type ? 'checkbox' : ( 'url' === $type ? 'url' : 'text' ),
                ) 
            );
		}
	}
}
