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
 * Called from woocommerce/content-product.php inside .lafka-product-card
 * __bottom so the pill flows naturally with the existing flex layout.
 * Rendered as a <span role="button"> (not a nested <a> / <button>) because
 * the card's outer wrapper is itself an <a>, and nested interactive
 * elements would be invalid HTML.
 *
 * Pure-vanilla JS at js/lafka-archive-quickadd.js intercepts clicks on
 * the pill (capture phase, stopPropagation) so taps don't bubble up
 * and navigate the card's outer link. For simple products it calls
 * WooCommerce's wc-ajax `add_to_cart` endpoint directly — same path
 * WC's own add-to-cart button uses, so the `added_to_cart` jQuery
 * event still fires and the cart drawer + sticky cart bar refresh.
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

if ( ! function_exists( 'lafka_archive_quickadd_render' ) ) {
	/**
	 * Render the quick-add pill inside a loop product card's __bottom row.
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

		$is_variable    = $product->is_type( 'variable' );
		$is_purchasable = $product->is_purchasable();
		$is_in_stock    = $product->is_in_stock();
		$can_quick_add  = ! $is_variable && $is_purchasable && $is_in_stock;
		$label          = $can_quick_add ? __( 'Add', 'lafka' ) : __( 'Choose', 'lafka' );
		$label          = (string) apply_filters( 'lafka_archive_quickadd_label', $label, $product );

		$aria_label_template = $can_quick_add
			/* translators: %s: product name */
			? __( 'Add %s to cart', 'lafka' )
			/* translators: %s: product name */
			: __( 'Choose options for %s', 'lafka' );
		$aria_label = sprintf( $aria_label_template, wp_strip_all_tags( $product->get_name() ) );

		$action  = $can_quick_add ? 'add' : 'choose';
		$variant = $can_quick_add ? 'lafka-archive-quickadd--add' : 'lafka-archive-quickadd--choose';

		ob_start();
		?>
		<span
			class="lafka-archive-quickadd <?php echo esc_attr( $variant ); ?>"
			role="button"
			tabindex="0"
			data-lafka-quickadd-action="<?php echo esc_attr( $action ); ?>"
			data-lafka-quickadd-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
			data-lafka-quickadd-url="<?php echo esc_url( $can_quick_add ? $product->add_to_cart_url() : $product->get_permalink() ); ?>"
			aria-label="<?php echo esc_attr( $aria_label ); ?>"
		>
			<span class="lafka-archive-quickadd__label"><?php echo esc_html( $label ); ?></span>
		</span>
		<?php
		$html = (string) ob_get_clean();
		echo apply_filters( 'lafka_archive_quickadd_html', $html, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
