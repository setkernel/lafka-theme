<?php
/**
 * The template for displaying product content within loops.
 *
 * Lafka v5.17.0 — list-row layout: image-left thumb (92px mobile, 120px desktop),
 * body-right with title, short description, price. Whole card wrapped in a
 * single <a> linking to PDP (no inline add-to-cart button).
 *
 * Hooks preserved for WooCommerce ecosystem compatibility:
 * - woocommerce_before_shop_loop_item       (link_open already removed by lafka)
 * - woocommerce_before_shop_loop_item_title (sale flash) — fires inside image wrap
 * - woocommerce_after_shop_loop_item_title  (rating)     — fires in body, above bottom row
 * - woocommerce_after_shop_loop_item        (link_close + add_to_cart removed)
 *
 * Operators wanting the loop add-to-cart back can re-add via:
 *   add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Lafka\WooCommerce
 * @version 5.17.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'lafka-product-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<a class="lafka-product-card__link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
		<div class="lafka-product-card__img-wrap">
			<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
			<?php echo lafka_product_card_image_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — helper returns escaped HTML ?>
		</div>
		<div class="lafka-product-card__body">
			<div class="lafka-product-card__head">
				<h3 class="lafka-product-card__title"><?php echo esc_html( $product->get_name() ); ?></h3>
				<?php if ( $product->get_short_description() ) : ?>
					<p class="lafka-product-card__desc"><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ) ); ?></p>
				<?php endif; ?>
				<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
			</div>
			<div class="lafka-product-card__bottom">
				<?php if ( ! lafka_is_product_eligible_for_variation_in_listings( $product ) ) : ?>
					<span class="lafka-product-card__price"><?php woocommerce_template_loop_price(); ?></span>
				<?php endif; ?>
				<?php lafka_shop_sale_countdown(); ?>
			</div>
		</div>
	</a>
	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>
