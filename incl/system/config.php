<?php defined( 'ABSPATH' ) || exit; ?>
<?php

if ( ! defined( 'LAFKA_IMAGES_PATH' ) ) {
	define( 'LAFKA_IMAGES_PATH', get_template_directory_uri() . '/image/' );
}

// Whether WooCommerce is loaded. The theme loads after every plugin, so the
// class is the source of truth; the active-plugins list is not, because
// requests that skip plugins (wp-activate.php sets WP_INSTALLING) still list
// it and then fatal on the first WooCommerce call.
if ( ! defined( 'LAFKA_IS_WOOCOMMERCE' ) ) {
	define( 'LAFKA_IS_WOOCOMMERCE', class_exists( 'WooCommerce' ) );
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
