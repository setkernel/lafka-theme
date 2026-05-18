<?php
/**
 * Customizer panel — "Lafka — Order Flow"
 *
 * Operator-facing toggles for the order/conversion features added in v5.26+.
 * Lives alongside the Editorial / Product Listings / Menu Landing panels.
 *
 * @package Lafka
 * @since   5.26.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'lafka_order_flow_customizer_register' );

/**
 * Register the Order Flow Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function lafka_order_flow_customizer_register( WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_panel(
		'lafka_order_flow',
		array(
			'title'       => __( 'Lafka — Order Flow', 'lafka' ),
			'description' => __( 'Conversion-focused features around the cart and ordering flow.', 'lafka' ),
			'priority'    => 170,
		)
	);

	// ---------------------------------------------------------------------
	// Section: Sticky cart bar
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_sticky_cart',
		array(
			'title'       => __( 'Sticky cart bar', 'lafka' ),
			'description' => __( 'Fixed-bottom bar that appears once items are in the cart, showing a live subtotal and "View cart" CTA. Hidden on the cart and checkout pages.', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 10,
		)
	);

	$wp_customize->add_setting(
		'lafka_sticky_cart_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_sticky_cart_enabled',
		array(
			'label'   => __( 'Show sticky cart bar', 'lafka' ),
			'section' => 'lafka_order_flow_sticky_cart',
			'type'    => 'checkbox',
		)
	);

	// ---------------------------------------------------------------------
	// Section: Product page (PDP) — v5.27.0
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_pdp',
		array(
			'title'       => __( 'Product page', 'lafka' ),
			'description' => __( 'Conversion-tuned defaults for the single-product page: auto-selected default variation, sticky bottom CTA with live total.', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 20,
		)
	);

	$wp_customize->add_setting(
		'lafka_pdp_sticky_cta_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_pdp_sticky_cta_enabled',
		array(
			'label'       => __( 'Show sticky CTA on product pages', 'lafka' ),
			'description' => __( 'Bottom-anchored "Add — $X.XX" button that stays visible while customers customise their order. Replaces the buried Add-to-Cart button.', 'lafka' ),
			'section'     => 'lafka_order_flow_pdp',
			'type'        => 'checkbox',
		)
	);

	// ---------------------------------------------------------------------
	// Section: Archive cards (category & shop loops) — v5.28.0
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_archive',
		array(
			'title'       => __( 'Archive cards', 'lafka' ),
			'description' => __( 'Quick-add CTA pill on product loop cards (category archives, shop, related products).', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 30,
		)
	);

	$wp_customize->add_setting(
		'lafka_cart_empty_popular_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_cart_empty_popular_enabled',
		array(
			'label'       => __( 'Show popular products on empty cart', 'lafka' ),
			'description' => __( 'When a customer lands on /cart/ with nothing in it, surface the top-selling products instead of a dead-end message.', 'lafka' ),
			'section'     => 'lafka_order_flow_sticky_cart',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_archive_quickadd_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_archive_quickadd_enabled',
		array(
			'label'       => __( 'Show quick-add pill on cards', 'lafka' ),
			'description' => __( 'Simple products: one-tap add to cart. Variable products: a "Choose" link that opens the product page.', 'lafka' ),
			'section'     => 'lafka_order_flow_archive',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_pdp_topping_chips',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_pdp_topping_chips',
		array(
			'label'       => __( 'Use chip toggles for toppings', 'lafka' ),
			'description' => __( 'Render WooCommerce Product Add-Ons checkbox groups as a 2-column grid of chip-style toggles instead of a single column of native checkboxes. Audit #9 — replaces the 34-checkbox wall on pizza PDPs with a scannable chip grid.', 'lafka' ),
			'section'     => 'lafka_order_flow_pdp',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_pdp_default_variation_strategy',
		array(
			'default'           => 'median',
			'sanitize_callback' => 'lafka_pdp_sanitize_default_strategy',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_pdp_default_variation_strategy',
		array(
			'label'       => __( 'Default variation auto-select', 'lafka' ),
			'description' => __( 'Which variation should be pre-selected so customers don\'t have to pick before they see a price.', 'lafka' ),
			'section'     => 'lafka_order_flow_pdp',
			'type'        => 'radio',
			'choices'     => array(
				'median'  => __( 'Median price (recommended)', 'lafka' ),
				'lowest'  => __( 'Lowest price', 'lafka' ),
				'highest' => __( 'Highest price', 'lafka' ),
				'none'    => __( 'No auto-select (customer must choose)', 'lafka' ),
			),
		)
	);

	// ---------------------------------------------------------------------
	// Section: Exit-intent reminder — v6.10.0 (Pillar 3C).
	//
	// A polite, dismissible toast that surfaces when the customer appears
	// about to leave the site with items still in their cart. Defaults OFF
	// because exit-intent is opinionated UX — operator should consciously
	// enable it after confirming the copy reads right for their brand.
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'lafka_order_flow_exit_intent',
		array(
			'title'       => __( 'Exit-intent reminder', 'lafka' ),
			'description' => __( 'Show a small dismissible toast when a customer appears about to leave the site with items in their cart. Fires once per session, never on /cart/ or /checkout/, and respects a 30-second grace period after page load.', 'lafka' ),
			'panel'       => 'lafka_order_flow',
			'priority'    => 40,
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_enabled',
		array(
			'label'       => __( 'Enable exit-intent toast', 'lafka' ),
			'description' => __( 'Default OFF. Enable to surface a "Resume checkout" reminder when a customer hovers toward the browser tab bar (desktop) or rapidly scrolls back to the top (mobile).', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_grace_seconds',
		array(
			'default'           => 30,
			'sanitize_callback' => 'lafka_exit_intent_sanitize_grace_seconds',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_grace_seconds',
		array(
			'label'       => __( 'Grace period (seconds)', 'lafka' ),
			'description' => __( 'How long after page load before the toast can fire. 10–300 seconds. Default 30 — avoids surprising visitors who just landed.', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 10,
				'max'  => 300,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_headline_below',
		array(
			'default'           => __( 'Add {amount} more for free delivery', 'lafka' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_headline_below',
		array(
			'label'       => __( 'Headline (below free-delivery threshold)', 'lafka' ),
			'description' => __( 'Shown when the cart subtotal is below the free-delivery threshold. Use the token {amount} — it is replaced at runtime with the remaining amount, formatted as currency.', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_headline_reached',
		array(
			'default'           => __( 'Your cart is ready — checkout in 30 seconds', 'lafka' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_headline_reached',
		array(
			'label'       => __( 'Headline (threshold reached)', 'lafka' ),
			'description' => __( 'Shown when the cart subtotal has already crossed the free-delivery threshold (or no threshold is configured).', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_body',
		array(
			'default'           => __( 'Tap below to pick up where you left off.', 'lafka' ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_body',
		array(
			'label'   => __( 'Body copy', 'lafka' ),
			'section' => 'lafka_order_flow_exit_intent',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_cta_label',
		array(
			'default'           => __( 'Resume checkout', 'lafka' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_cta_label',
		array(
			'label'       => __( 'Primary CTA label', 'lafka' ),
			'description' => __( 'Red filled button that links to the cart page.', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'lafka_exit_intent_dismiss_label',
		array(
			'default'           => __( 'Maybe later', 'lafka' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'lafka_exit_intent_dismiss_label',
		array(
			'label'       => __( 'Dismiss link label', 'lafka' ),
			'description' => __( 'Ghost text link that closes the toast and suppresses it for the rest of the session.', 'lafka' ),
			'section'     => 'lafka_order_flow_exit_intent',
			'type'        => 'text',
		)
	);
}

if ( ! function_exists( 'lafka_pdp_sanitize_default_strategy' ) ) {
	/**
	 * Sanitize the PDP default-variation strategy radio.
	 *
	 * @param string $value Submitted value.
	 * @return string One of: median, lowest, highest, none.
	 */
	function lafka_pdp_sanitize_default_strategy( $value ) {
		$allowed = array( 'median', 'lowest', 'highest', 'none' );
		return in_array( $value, $allowed, true ) ? $value : 'median';
	}
}

if ( ! function_exists( 'lafka_exit_intent_sanitize_grace_seconds' ) ) {
	/**
	 * Clamp the exit-intent grace period to 10–300 seconds.
	 *
	 * @param mixed $value Raw Customizer value.
	 * @return int Sanitized seconds in [10, 300]; falls back to 30.
	 */
	function lafka_exit_intent_sanitize_grace_seconds( $value ) {
		$int = (int) $value;
		if ( $int < 10 ) {
			return 30;
		}
		if ( $int > 300 ) {
			return 300;
		}
		return $int;
	}
}
