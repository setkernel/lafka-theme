<?php
/**
 * Sticky cart bar — fixed-bottom CTA that appears once items are in the cart.
 *
 * The audit's #1 conversion friction point was the cart icon scrolling off
 * screen on long product/category pages. This partial renders a 56 px bar
 * pinned to the viewport bottom showing item count + running subtotal + a
 * "View cart" pill. JS in js/lafka-sticky-cart.js listens to WC's
 * `added_to_cart` jQuery event and updates this markup in place.
 *
 * Hidden on `/cart/` and `/checkout/` to avoid double-CTA noise.
 *
 * Operators can disable the bar entirely via the "Lafka — Order Flow"
 * Customizer panel.
 *
 * @package Lafka
 * @since   5.26.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_sticky_cart_render' ) ) {
	/**
	 * Output the sticky cart bar in wp_footer.
	 */
	function lafka_sticky_cart_render() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		if ( is_cart() || is_checkout() ) {
			return;
		}
		if ( ! (bool) get_theme_mod( 'lafka_sticky_cart_enabled', true ) ) {
			return;
		}

		$cart           = WC()->cart;
		$count          = $cart ? (int) $cart->get_cart_contents_count() : 0;
		$subtotal_html  = $cart ? wc_price( $cart->get_subtotal() ) : '';
		$cart_url       = wc_get_cart_url();
		$has_items      = $count > 0;
		$hidden_attr    = $has_items ? '' : ' hidden';

		// Templates render even when empty so JS can show the bar after
		// add-to-cart without needing a page refresh — `[hidden]` is just
		// the initial state.
		?>
		<aside
			class="lafka-sticky-cart"
			data-lafka-sticky-cart
			data-lafka-empty-text="<?php esc_attr_e( 'Your cart is empty', 'lafka' ); ?>"
			aria-label="<?php esc_attr_e( 'Cart summary', 'lafka' ); ?>"
			<?php echo $hidden_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<a class="lafka-sticky-cart__link" href="<?php echo esc_url( $cart_url ); ?>">
				<span class="lafka-sticky-cart__summary">
					<span class="lafka-sticky-cart__count" data-lafka-cart-count><?php echo esc_html( $count ); ?></span>
					<span class="lafka-sticky-cart__count-label">
						<?php
						/* translators: %s: number of items in the cart */
						echo esc_html(
							sprintf(
								_n( 'item', 'items', $count, 'lafka' ),
								$count
							)
						);
						?>
					</span>
					<span class="lafka-sticky-cart__separator" aria-hidden="true">·</span>
					<span class="lafka-sticky-cart__subtotal" data-lafka-cart-subtotal>
						<?php echo wp_kses_post( $subtotal_html ); ?>
					</span>
				</span>
				<span class="lafka-sticky-cart__cta">
					<?php esc_html_e( 'View cart', 'lafka' ); ?>
					<svg class="lafka-sticky-cart__arrow" width="14" height="14" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<path d="M7 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</span>
			</a>
		</aside>
		<?php
	}
}

add_action( 'wp_footer', 'lafka_sticky_cart_render', 5 );
