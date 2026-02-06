<?php defined( 'ABSPATH' ) || exit; ?>
<?php

if ( ! defined( 'LAFKA_IMAGES_PATH' ) ) {
	define( 'LAFKA_IMAGES_PATH', get_template_directory_uri() . '/image/' );
}

if ( ! defined( 'LAFKA_BACKGROUNDS_PATH' ) ) {
	define( 'LAFKA_BACKGROUNDS_PATH', LAFKA_IMAGES_PATH . 'backgrounds/' );
}

if ( class_exists( 'bbPress' ) ) {
	define( 'LAFKA_IS_BBPRESS', true );
} else {
	define( 'LAFKA_IS_BBPRESS', false );
}

// Check if WooCommerce is active (supports regular plugins and MU-plugins)
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
	|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) )
	|| class_exists( 'WooCommerce' ) ) {
	define( 'LAFKA_IS_WOOCOMMERCE', true );
	require_once get_template_directory() . '/incl/woocommerce-functions.php';
} else {
	define( 'LAFKA_IS_WOOCOMMERCE', false );
}

if ( class_exists( 'Tribe__Events__Main' ) ) {
	define( 'LAFKA_IS_EVENTS', true );
} else {
	define( 'LAFKA_IS_EVENTS', false );
}

if ( class_exists( 'YITH_WCWL' ) ) {
	define( 'LAFKA_IS_WISHLIST', true );
} else {
	define( 'LAFKA_IS_WISHLIST', false );
}

if ( class_exists( 'RevSliderBase' ) ) {
	define( 'LAFKA_IS_REVOLUTION', true );
} else {
	define( 'LAFKA_IS_REVOLUTION', false );
}

// Check if WC Marketplace is active
if ( class_exists( 'WCMp' ) || function_exists( 'wcmp_plugin_init' ) ) {
	define( 'LAFKA_IS_WC_MARKETPLACE', true );
} else {
	define( 'LAFKA_IS_WC_MARKETPLACE', false );
}

// Check if WC Vendors is active
if ( class_exists( 'WC_Vendors' ) || function_exists( 'wcvendors_activate' ) ) {
	define( 'LAFKA_IS_WC_VENDORS', true );
} else {
	define( 'LAFKA_IS_WC_VENDORS', false );
}

// Check if WC Vendors Pro is active
if ( class_exists( 'WCVendors_Pro' ) || function_exists( 'activate_wcvendors_pro' ) ) {
	define( 'LAFKA_IS_WC_VENDORS_PRO', true );
} else {
	define( 'LAFKA_IS_WC_VENDORS_PRO', false );
}

if ( class_exists( 'Vc_Manager' ) ) {
	define( 'LAFKA_IS_VC', true );
} else {
	define( 'LAFKA_IS_VC', false );
}

if ( class_exists( 'Envato_Market' ) ) {
	define( 'LAFKA_IS_ENVATO_MARKET', true );
} else {
	define( 'LAFKA_IS_ENVATO_MARKET', false );
}

// Is blank page template
global $lafka_is_blank;
$lafka_is_blank = false;

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
 * Include Lafka_Font_Awesome
 */
require_once get_template_directory() . '/incl/Lafka_Font_Awesome.php';

/**
 * Include TGM-Plugin-Activation
 */
require_once get_template_directory() . '/incl/tgm-plugin-activation/class-tgm-plugin-activation.php';

/**
 * Include Lafka_Transfer_Content
 */
require_once get_template_directory() . '/incl/LafkaTransferContent.class.php';

/**
 * Include Mega Menu functionality
 */
require_once get_template_directory() . '/incl/LafkaMegaMenu.php';

/**
 * Include LafkaMobileMenuWalker
 */
require_once get_template_directory() . '/incl/LafkaMobileMenuWalker.php';

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
