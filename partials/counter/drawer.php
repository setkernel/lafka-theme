<?php
/**
 * Counter layout: the order drawer (board C-Mobile-Order). Included by
 * partials/cart-drawer.php, so it inherits $lafka_cart_count / $lafka_cart_empty
 * and keeps the SAME shell: <aside class="lafka-cart-drawer" … data-lafka-cart-drawer>
 * (js/cart-drawer.js focus trap / inert / open-close are unchanged), the
 * plugin fragment targets (ul.lafka-cart-drawer__items, the upsell wrapper,
 * div.lafka-cart-drawer__total) and the payment trust line.
 *
 *   "Your order" · "3 items · Pickup" · Close
 *   rows (plugin; quantity stepper while the theme declares lafka-drawer-stepper)
 *   "Add a little extra?" (plugin upsell, headed/worded through its filters)
 *   Pickup or delivery? (large radio cards + the pickup address)
 *   Subtotal · "Taxes are added at checkout." · Go to checkout — $42.94 ·
 *   Prefer to call? · trust line
 *
 * < 600 px it is a bottom sheet; ≥ 600 px the right-hand panel.
 * "When? ASAP / Choose a time" is deferred (GX4c) — checkout keeps the
 * timeslot picker.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_drw_nap     = lafka_counter_nap();
$lafka_drw_modes   = lafka_counter_fulfilment_modes();
$lafka_drw_current = lafka_counter_fulfilment_current();
?>
<aside
	class="lafka-cart-drawer lafka-cart-drawer--counter"
	role="dialog"
	aria-modal="true"
	aria-hidden="true"
	aria-labelledby="lafka-cart-drawer-title"
	tabindex="-1"
	data-lafka-cart-drawer
	data-open="false"
>
	<div class="lafka-cart-drawer__scrim" data-lafka-cart-close></div>

	<div class="lafka-cart-drawer__panel">
		<div class="lafka-drawer__handle" aria-hidden="true"></div>

		<header class="lafka-cart-drawer__header lafka-drawer__header">
			<div>
				<h2 id="lafka-cart-drawer-title" class="lafka-drawer__title"><?php esc_html_e( 'Your order', 'lafka' ); ?></h2>
				<p class="lafka-drawer__sub">
					<?php echo lafka_counter_drawer_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html in lafka_counter_drawer_summary(). ?>
					<?php if ( $lafka_drw_current ) : ?>
						<span aria-hidden="true">·</span>
						<span
							data-lafka-fulfilment-text
							data-lafka-fulfilment-pickup="<?php echo esc_attr( lafka_counter_fulfilment_label( 'pickup' ) ); ?>"
							data-lafka-fulfilment-delivery="<?php echo esc_attr( lafka_counter_fulfilment_label( 'delivery' ) ); ?>"
						><?php echo esc_html( lafka_counter_fulfilment_label( $lafka_drw_current ) ); ?></span>
					<?php endif; ?>
				</p>
			</div>
			<button type="button" class="lafka-cart-drawer__close lafka-drawer__close lafka-counter-btn" data-lafka-cart-close>
				<?php echo lafka_counter_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span><?php esc_html_e( 'Close', 'lafka' ); ?></span>
			</button>
		</header>

		<div class="lafka-cart-drawer__body">
			<ul class="lafka-cart-drawer__items">
				<?php
				if ( function_exists( 'lafka_cart_drawer_render_item' ) ) {
					if ( $lafka_cart_empty ) {
						lafka_cart_drawer_render_item();
					} else {
						foreach ( WC()->cart->get_cart() as $lafka_cart_item_key => $lafka_cart_item ) {
							lafka_cart_drawer_render_item( (string) $lafka_cart_item_key, $lafka_cart_item );
						}
					}
				}
				?>
			</ul>

			<?php
			if ( function_exists( 'lafka_cart_drawer_render_upsell' ) ) {
				lafka_cart_drawer_render_upsell();
			}
			?>

			<?php if ( count( $lafka_drw_modes ) > 1 ) : ?>
				<div class="lafka-drawer__fulfilment">
					<?php lafka_counter_render_fulfilment( 'drawer', __( 'Pickup or delivery?', 'lafka' ) ); ?>
					<?php if ( '' !== $lafka_drw_nap['address_short'] ) : ?>
						<p class="lafka-drawer__note" data-lafka-fulfilment-note="pickup"<?php echo 'pickup' === $lafka_drw_current ? '' : ' hidden'; ?>>
							<?php echo lafka_counter_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span>
							<?php
							/* translators: %s: pickup address */
							echo esc_html( sprintf( __( 'Pick up at %s', 'lafka' ), $lafka_drw_nap['address_short'] ) );
							?>
							</span>
						</p>
					<?php endif; ?>
					<?php
					// O-24: what Delivery means before checkout — the minimum order and
					// free-delivery threshold the plugin enforces, and when the fee shows.
					$lafka_drw_money   = static function ( float $amount ): string {
						return function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $amount ) ) : number_format_i18n( $amount, 2 );
					};
					$lafka_drw_min     = function_exists( 'lafka_delivery_minimum' ) ? (float) lafka_delivery_minimum() : 0.0;
					$lafka_drw_free    = function_exists( 'lafka_get_free_delivery_threshold' ) ? (float) lafka_get_free_delivery_threshold() : 0.0;
					$lafka_drw_deliver = array();
					if ( $lafka_drw_min > 0 ) {
						/* translators: %s: minimum order amount for delivery */
						$lafka_drw_deliver[] = sprintf( __( 'Delivery on orders over %s.', 'lafka' ), $lafka_drw_money( $lafka_drw_min ) );
					}
					if ( $lafka_drw_free > 0 ) {
						/* translators: %s: order amount above which delivery is free */
						$lafka_drw_deliver[] = sprintf( __( 'Free delivery over %s.', 'lafka' ), $lafka_drw_money( $lafka_drw_free ) );
					}
					$lafka_drw_deliver[] = __( 'The delivery fee shows at checkout once you enter your address.', 'lafka' );
					$lafka_drw_deliver   = (string) apply_filters( 'lafka_counter_drawer_delivery_note', implode( ' ', $lafka_drw_deliver ), $lafka_drw_min, $lafka_drw_free );
					if ( '' !== $lafka_drw_deliver ) :
						?>
						<p class="lafka-drawer__note" data-lafka-fulfilment-note="delivery"<?php echo 'delivery' === $lafka_drw_current ? '' : ' hidden'; ?>>
							<span><?php echo esc_html( $lafka_drw_deliver ); ?></span>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<footer class="lafka-cart-drawer__footer lafka-drawer__footer">
			<?php
			if ( function_exists( 'lafka_cart_drawer_render_total' ) ) {
				lafka_cart_drawer_render_total();
			}
			?>
			<p class="lafka-drawer__tax"><?php echo esc_html( (string) apply_filters( 'lafka_counter_drawer_tax_note', __( 'Taxes are added at checkout.', 'lafka' ) ) ); ?></p>
			<a class="lafka-cart-drawer__checkout lafka-counter-btn lafka-counter-btn--primary lafka-counter-btn--lg" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
				<?php echo lafka_counter_drawer_checkout_label(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html in lafka_counter_drawer_checkout_label(). ?>
			</a>
			<?php if ( '' !== $lafka_drw_nap['phone'] ) : ?>
				<p class="lafka-drawer__call">
					<?php esc_html_e( 'Prefer to call?', 'lafka' ); ?>
					<a href="<?php echo esc_attr( 'tel:' . $lafka_drw_nap['tel'] ); ?>" data-lafka-channel="phone"><?php echo esc_html( $lafka_drw_nap['phone'] ); ?></a>
				</p>
			<?php endif; ?>
			<?php
			if ( function_exists( 'lafka_payment_trust_render' ) ) {
				lafka_payment_trust_render( 'lafka-cart-drawer__trust' );
			}
			?>
		</footer>
	</div>
</aside>
