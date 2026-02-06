<?php

/**
 * Loop Add to Cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/add-to-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package 	WooCommerce\Templates
 * @version     9.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

global $product;

$aria_describedby = isset( $args['aria-describedby_text'] ) ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) ) : '';

echo '<div class="links">';
echo apply_filters('lafka_links_before_add_to_cart', '');
echo apply_filters('woocommerce_loop_add_to_cart_link', // WPCS: XSS ok.
					sprintf('<a href="%s" %s data-quantity="%s" class="%s" title="%s" %s>%s</a>',
						esc_url($product->add_to_cart_url()),
						$aria_describedby,
						esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
						esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
						esc_attr($product->add_to_cart_text()),
						isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
						esc_html($product->add_to_cart_text())
					), $product, $args);
?>
<?php if ( isset( $args['aria-describedby_text'] ) ) : ?>
    <span id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">
        <?php echo esc_html( $args['aria-describedby_text'] ); ?>
    </span>
<?php endif; ?>
<?php
// Do not show quickview link for composite products as it is too complex for the user
if ( lafka_get_option( 'use_quickview' ) && ! in_array( $product->get_type(), array( 'composite', 'bundle', 'combo' ), true ) ) {
	$classes = array('lafka-quick-view-link');

	if ( lafka_is_product_eligible_for_variation_in_listings( $product ) ) {
		$lafka_quickview_link_label = __( 'More Options', 'lafka' );
		$classes[] = 'lafka-more-options';
	} else {
		$lafka_quickview_link_label = __( 'Order Now', 'lafka' );
	}

	echo '<a href="#" class="'.esc_attr(implode(' ', $classes)).'" data-id="' . esc_attr( $product->get_id() ) . '" title="' . esc_attr( $lafka_quickview_link_label ) . '">' . esc_html( $lafka_quickview_link_label ) . '</a>';
}
// show compare link
if (defined('YITH_WOOCOMPARE')) {
	lafka_add_compare_link();
}

echo '</div>';
