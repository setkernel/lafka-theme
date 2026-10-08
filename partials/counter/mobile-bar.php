<?php
/**
 * Counter layout: the sticky mobile bar (below 1024 px) — "Call" + "Order
 * online →", or "View order · 3 · $42.94" (opens the drawer) once the cart has
 * items. The order control is a WooCommerce cart fragment
 * (`a.lafka-counter-bar__order`), so it updates on every add/remove without
 * extra JS. Not rendered on cart, checkout or product pages (their own CTAs
 * take over). Sits above the consent banner (var(--lafka-consent-banner-h)).
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_bar_nap = lafka_counter_nap();
?>
<div class="lafka-counter-bar<?php echo '' === $lafka_bar_nap['phone'] ? ' lafka-counter-bar--no-phone' : ''; ?>" role="region" aria-label="<?php esc_attr_e( 'Quick order', 'lafka' ); ?>" data-lafka-counter-bar>
	<?php if ( '' !== $lafka_bar_nap['phone'] ) : ?>
		<a class="lafka-counter-bar__call lafka-btn" href="<?php echo esc_attr( 'tel:' . $lafka_bar_nap['tel'] ); ?>" data-lafka-channel="phone">
			<?php echo wp_kses( lafka_counter_icon( 'phone' ), lafka_allowed_html() ); ?>
			<span><?php esc_html_e( 'Call', 'lafka' ); ?></span>
			<span class="lafka-counter-bar__number"><?php echo esc_html( $lafka_bar_nap['phone'] ); ?></span>
		</a>
	<?php endif; ?>
	<?php echo wp_kses( lafka_counter_bar_order_html(), lafka_allowed_html() ); ?>
</div>
