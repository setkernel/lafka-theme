<?php
/**
 * Archive-card quick-add CTA — "+ Add — $X" pill on every product card
 * in WC product loops (category archives, shop, related products).
 *
 * Implements the conversion audit's QW3: halves tap count for repeat
 * customers on simple products by enabling one-tap add-to-cart from
 * the listing. Variable products get a "Choose — from $X" pill that
 * navigates to the PDP (where the v5.27 sticky CTA + auto-selected
 * default variation take over).
 *
 * Hooks into `woocommerce_after_shop_loop_item` at priority 10 — the
 * standard WC extension point that content-product.php deliberately
 * leaves open for operators to re-add the loop CTA.
 *
 * Quick-add for simple products piggybacks on WC's own AJAX add-to-cart
 * (the `add_to_cart_button ajax_add_to_cart` classes + data-product_id
 * are what WC's built-in JS listens for) — no new server endpoint.
 *
 * Filter surface:
 *   lafka_archive_quickadd_enabled(bool)               — toggle whole feature
 *   lafka_archive_quickadd_label(string, WC_Product)   — replace "Add" / "Choose"
 *   lafka_archive_quickadd_html(string, WC_Product)    — replace rendered HTML
 *
 * @package Lafka\WooCommerce
 * @since   5.28.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_after_shop_loop_item', 'lafka_archive_quickadd_render', 10 );

if ( ! function_exists( 'lafka_archive_quickadd_render' ) ) {
	/**
	 * Render the quick-add pill at the end of a loop product card.
	 */
	function lafka_archive_quickadd_render() {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}
		if ( ! $product->is_visible() ) {
			return;
		}
		$enabled = (bool) apply_filters(
			'lafka_archive_quickadd_enabled',
			(bool) get_theme_mod( 'lafka_archive_quickadd_enabled', true ),
			$product
		);
		if ( ! $enabled ) {
			return;
		}

		$is_variable        = $product->is_type( 'variable' );
		$is_purchasable     = $product->is_purchasable();
		$is_in_stock        = $product->is_in_stock();
		$can_quick_add      = ! $is_variable && $is_purchasable && $is_in_stock;
		$label              = $can_quick_add ? __( 'Add', 'lafka' ) : __( 'Choose', 'lafka' );
		$label              = (string) apply_filters( 'lafka_archive_quickadd_label', $label, $product );
		$price_html         = '';
		if ( $is_variable ) {
			$min_price = $product->get_variation_price( 'min' );
			if ( '' !== $min_price ) {
				/* translators: %s: starting price for a variable product */
				$price_html = sprintf( esc_html__( 'from %s', 'lafka' ), wc_price( $min_price ) );
			}
		} else {
			$price = $product->get_price();
			if ( '' !== $price ) {
				$price_html = wc_price( $price );
			}
		}

		ob_start();
		if ( $can_quick_add ) {
			// WC's built-in AJAX add-to-cart JS triggers on these classes +
			// data-product_id. The added_to_cart event then surfaces in the
			// cart drawer + sticky cart bar.
			?>
			<a
				class="lafka-archive-quickadd lafka-archive-quickadd--add button add_to_cart_button ajax_add_to_cart"
				href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
				data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
				data-product_sku="<?php echo esc_attr( (string) $product->get_sku() ); ?>"
				data-quantity="1"
				rel="nofollow"
				aria-label="
                <?php
					/* translators: %s: product name */
					echo esc_attr( sprintf( __( 'Add %s to cart', 'lafka' ), wp_strip_all_tags( $product->get_name() ) ) );
				?>
                "
			>
				<span class="lafka-archive-quickadd__label"><?php echo esc_html( $label ); ?></span>
				<?php if ( $price_html ) : ?>
					<span class="lafka-archive-quickadd__separator" aria-hidden="true">·</span>
					<span class="lafka-archive-quickadd__price"><?php echo wp_kses_post( $price_html ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		} else {
			// Variable / not-purchasable / out-of-stock: link to PDP. On
			// the PDP, the v5.27 sticky CTA + auto-default variation
			// minimize the extra friction.
			?>
			<a
				class="lafka-archive-quickadd lafka-archive-quickadd--choose"
				href="<?php echo esc_url( $product->get_permalink() ); ?>"
				aria-label="
                <?php
					/* translators: %s: product name */
					echo esc_attr( sprintf( __( 'Choose options for %s', 'lafka' ), wp_strip_all_tags( $product->get_name() ) ) );
				?>
                "
			>
				<span class="lafka-archive-quickadd__label"><?php echo esc_html( $label ); ?></span>
				<?php if ( $price_html ) : ?>
					<span class="lafka-archive-quickadd__separator" aria-hidden="true">·</span>
					<span class="lafka-archive-quickadd__price"><?php echo wp_kses_post( $price_html ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		}
		$html = (string) ob_get_clean();
		echo apply_filters( 'lafka_archive_quickadd_html', $html, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
