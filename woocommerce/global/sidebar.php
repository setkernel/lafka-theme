<?php

/**
 * Sidebar
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/sidebar.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

$woocommerce_sidebar = lafka_get_option('woocommerce_sidebar');

if ($woocommerce_sidebar && $woocommerce_sidebar != 'none' ) {
	get_sidebar();
}

//get_sidebar('shop');
//if(is_active_sidebar('shop'))
//{
//    echo '<div class="sidebar">';
//    dynamic_sidebar('shop');
//    echo '</div>';
//}
