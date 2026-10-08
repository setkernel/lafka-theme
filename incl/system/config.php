<?php defined( 'ABSPATH' ) || exit; ?>
<?php

if ( ! defined( 'LAFKA_IMAGES_PATH' ) ) {
	define( 'LAFKA_IMAGES_PATH', get_template_directory_uri() . '/image/' );
}

if ( ! defined( 'LAFKA_IS_BBPRESS' ) ) {
	if ( class_exists( 'bbPress' ) ) {
		define( 'LAFKA_IS_BBPRESS', true );
	} else {
		define( 'LAFKA_IS_BBPRESS', false );
	}
}

// Check if WooCommerce is active (supports regular plugins and MU-plugins)
if ( ! defined( 'LAFKA_IS_WOOCOMMERCE' ) ) {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )
		|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) )
		|| class_exists( 'WooCommerce' ) ) {
		define( 'LAFKA_IS_WOOCOMMERCE', true );
	} else {
		define( 'LAFKA_IS_WOOCOMMERCE', false );
	}
}
if ( LAFKA_IS_WOOCOMMERCE ) {
	require_once get_template_directory() . '/incl/woocommerce-functions.php';
}

if ( class_exists( 'Tribe__Events__Main' ) ) {
	define( 'LAFKA_IS_EVENTS', true );
} else {
	define( 'LAFKA_IS_EVENTS', false );
}

/**
 * Force Visual Composer to initialize as "built into the theme". This will hide certain tabs under the Settings->Visual Composer page
 */
if ( ! function_exists( 'lafka_set_vc_as_theme' ) ) {
	add_action( 'vc_before_init', 'lafka_set_vc_as_theme' );

	function lafka_set_vc_as_theme() {
		vc_set_as_theme( true );
	}

}

add_action( 'init', 'lafka_vc_set_cpt' );
if ( ! function_exists( 'lafka_vc_set_cpt' ) ) {

	/**
	 * Define the post types that will use VC
	 */
	function lafka_vc_set_cpt() {
		if ( class_exists( 'WPBakeryVisualComposerAbstract' ) ) {
			$list = array(
				'post',
				'page',
				'product',
				'product_variation',
				'lafka-foodmenu',
			);
			vc_set_default_editor_post_types( $list );
		}
	}

}

/**
 * Include TGM-Plugin-Activation
 */
require_once get_template_directory() . '/incl/tgm-plugin-activation/class-tgm-plugin-activation.php';

/*
 * Register theme text domain
 */
add_action( 'after_setup_theme', 'lafka_lang_setup' );
if ( ! function_exists( 'lafka_lang_setup' ) ) {

	function lafka_lang_setup() {
		load_theme_textdomain( 'lafka', get_template_directory() . '/languages' );
	}

}

/**
 * Include the dynamic css
 */
require_once get_template_directory() . '/styles/dynamic-css.php';

/**
 * Include the dynamic css for Gutenberg in the admin area
 */
require_once get_template_directory() . '/styles/lafka-gutenberg-dynamic-css.php';
