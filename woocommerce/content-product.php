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
	<?php
	// v5.84.0: a11y — dropped redundant aria-label="<product name>". The
	// link's visible content (h3 title + description + price) already
	// provides the accessible name; an aria-label override stripped the
	// description/price out of the SR announcement and triggered WCAG
	// 2.5.3 (Label in Name) since the aria-label was shorter than the
	// visible text. The default computed accessible name is correct.
	?>
	<a class="lafka-product-card__link" href="<?php the_permalink(); ?>">
		<div class="lafka-product-card__img-wrap">
			<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lafka_product_card_image_html() returns img markup with all attributes pre-escaped (see incl/template-helpers/product-card-image.php).
			echo lafka_product_card_image_html( $product );
			?>
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
				<?php
				// v5.28.0: quick-add pill rendered as a span[role=button]
				// inside the existing flex `__bottom` row. A real <a> or
				// <button> here would be nested inside the card's outer
				// <a class="lafka-product-card__link"> which is invalid
				// HTML. The JS in js/lafka-archive-quickadd.js intercepts
				// pill clicks (capture phase, stopPropagation) so taps on
				// the pill don't bubble up and navigate the parent link.
				if ( function_exists( 'lafka_archive_quickadd_render' ) ) {
					lafka_archive_quickadd_render();
				}
				?>
			</div>
		</div>
	</a>
	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>
