<?php
/**
 * Cart drawer on WooCommerce's mini-cart actions.
 *
 * The drawer (partials/cart-drawer.php and its counter variant) fires the
 * mini-cart actions core's cart/mini-cart.php fires, so extensions that hook
 * them (express-pay buttons, gift-card and coupon fields, closed-store notices,
 * subscription notes) run in it. The two core button callbacks print core's
 * markup; here they are swapped, for the duration of the action, for the
 * drawer's own buttons at the same priorities.
 *
 * @package Lafka\WooCommerce
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_cart_drawer_button_checkout' ) ) {
	/**
	 * The drawer's checkout button (replaces core's proceed-to-checkout, priority 20).
	 *
	 * @since 7.4.0
	 */
	function lafka_cart_drawer_button_checkout(): void {
		if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'drawer', 'counter' ) ) {
			?>
			<a class="lafka-cart-drawer__checkout lafka-counter-btn lafka-counter-btn--primary lafka-counter-btn--lg" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
				<?php echo wp_kses( lafka_counter_drawer_checkout_label(), lafka_allowed_html() ); ?>
			</a>
			<?php
			return;
		}
		?>
		<a class="lafka-cart-drawer__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
			<?php esc_html_e( 'Checkout', 'lafka' ); ?>
			<span class="lafka-cart-drawer__arrow" aria-hidden="true">→</span>
		</a>
		<?php
	}
}

if ( ! function_exists( 'lafka_cart_drawer_button_view_cart' ) ) {
	/**
	 * The classic drawer's "View full cart" link (replaces core's view-cart,
	 * priority 10). The counter drawer has none.
	 *
	 * @since 7.4.0
	 */
	function lafka_cart_drawer_button_view_cart(): void {
		if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'drawer', 'counter' ) ) {
			return;
		}
		?>
		<a class="lafka-cart-drawer__view-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<?php esc_html_e( 'View full cart', 'lafka' ); ?>
		</a>
		<?php
	}
}

if ( ! function_exists( 'lafka_cart_drawer_fire_buttons' ) ) {
	/**
	 * Fire woocommerce_widget_shopping_cart_before_buttons, _buttons and
	 * _after_buttons. Core's two button callbacks are taken off the _buttons
	 * action for the duration and the drawer's own run in their place, checkout
	 * first (priority 5) then the cart link (6), as the classic drawer lists
	 * them; anything an extension adds follows at its own priority. Core's
	 * callbacks are put back afterwards, so other mini-carts keep theirs.
	 *
	 * @since 7.4.0
	 */
	function lafka_cart_drawer_fire_buttons(): void {
		$core = array(
			'woocommerce_widget_shopping_cart_button_view_cart' => 10,
			'woocommerce_widget_shopping_cart_proceed_to_checkout' => 20,
		);
		$had  = array();
		foreach ( $core as $callback => $priority ) {
			$had[ $callback ] = has_action( 'woocommerce_widget_shopping_cart_buttons', $callback );
			if ( false !== $had[ $callback ] ) {
				remove_action( 'woocommerce_widget_shopping_cart_buttons', $callback, (int) $had[ $callback ] );
			}
		}
		add_action( 'woocommerce_widget_shopping_cart_buttons', 'lafka_cart_drawer_button_checkout', 5 );
		add_action( 'woocommerce_widget_shopping_cart_buttons', 'lafka_cart_drawer_button_view_cart', 6 );

		do_action( 'woocommerce_widget_shopping_cart_before_buttons' );
		do_action( 'woocommerce_widget_shopping_cart_buttons' );
		do_action( 'woocommerce_widget_shopping_cart_after_buttons' );

		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'lafka_cart_drawer_button_checkout', 5 );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'lafka_cart_drawer_button_view_cart', 6 );
		foreach ( $had as $callback => $priority ) {
			if ( false !== $priority ) {
				add_action( 'woocommerce_widget_shopping_cart_buttons', $callback, (int) $priority );
			}
		}
	}
}
