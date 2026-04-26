<?php
/**
 * The template for displaying product search form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-searchform.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<form role="search" method="get" class="woocommerce-product-search" action="<?php echo esc_url( lafka_wpml_get_home_url() ); ?>">
	<div>
		<label class="screen-reader-text" for="woocommerce-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>"><?php esc_html_e( 'Search for:', 'lafka' ); ?></label>
		<input type="search" id="woocommerce-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" placeholder="<?php esc_attr_e( 'Search Products', 'lafka' ); ?>" />
		<small class="lafka-search-hint-text"><?php echo esc_html__( 'Type and hit Enter to Search', 'lafka' ); ?></small>
		<button type="submit" value="<?php esc_attr_e( 'Search Products', 'lafka' ); ?>"><?php echo esc_html_x( 'Search', 'submit button', 'lafka' ); ?></button>
		<input type="hidden" name="post_type" value="product" />
	</div>
</form>
