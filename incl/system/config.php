<?php defined( 'ABSPATH' ) || exit; ?>
<?php

if ( ! defined( 'LAFKA_IMAGES_PATH' ) ) {
	define( 'LAFKA_IMAGES_PATH', get_template_directory_uri() . '/image/' );
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
