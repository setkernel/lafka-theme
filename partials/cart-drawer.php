<?php
/**
 * Cart drawer — right-edge slide-in (v5.57.0 handoff rebuild).
 *
 * Per handoff spec at /design_handoff_peppery_ordering/README.md
 * "Cart Drawer (slide-out, all pages)":
 *
 *   - width: min(420px, 92vw)
 *   - white background, shadow-3
 *   - body scroll lock while open
 *   - auto-opens on added_to_cart (legacy WC event)
 *   - closes on: × button, scrim, ESC, route change
 *   - empty state with browse-menu CTA
 *   - filled state: items list + sticky footer (progress + totals + checkout)
 *
 * Item rows + total block populated/refreshed by
 * lafka-plugin/incl/woocommerce/lafka-cart-drawer-fragments.php (W4-T7),
 * which fires on WC's woocommerce_add_to_cart_fragments filter.
 *
 * @package Lafka
 * @since   5.16.0 (markup originally for PDP redesign)
 * @since   5.57.0 (Ship 2d handoff rebuild — drops the PDP-only gate)
 * @since   6.9.0  (Pillar 3A — rich .lafka-fdp free-delivery progress
 *                  component replaces the plain threshold text)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

$lafka_cart_count = (int) WC()->cart->get_cart_contents_count();
$lafka_cart_empty = 0 === $lafka_cart_count;
?>
<aside
	class="lafka-cart-drawer"
	role="dialog"
	<?php // f092 (a11y): the drawer is only ever shown as a modal — it locks body scroll, traps Tab, and (via js/cart-drawer.js) inerts #header/#content on open. Declare that modality truthfully so AT does not contradict the actual behaviour (WCAG 4.1.2). ?>
	aria-modal="true"
	aria-hidden="true"
	aria-labelledby="lafka-cart-drawer-title"
	tabindex="-1"
	data-lafka-cart-drawer
	data-open="false"
>
	<div class="lafka-cart-drawer__scrim" data-lafka-cart-close></div>

	<div class="lafka-cart-drawer__panel">

		<header class="lafka-cart-drawer__header">
			<h2 id="lafka-cart-drawer-title" class="lafka-cart-drawer__title">
				<?php esc_html_e( 'Your cart', 'lafka' ); ?>
				<span class="lafka-cart-drawer__count-badge" data-lafka-cart-count-pill>
					<?php echo esc_html( (string) $lafka_cart_count ); ?>
				</span>
			</h2>
			<button
				type="button"
				class="lafka-cart-drawer__close"
				data-lafka-cart-close
				aria-label="<?php esc_attr_e( 'Close cart', 'lafka' ); ?>"
			>×</button>
		</header>

		<div class="lafka-cart-drawer__body">

			<?php
			/* ALWAYS render the items <ul> — it is the woocommerce_add_to_cart_
			 * fragments target. v6.14.0: the empty state now lives INSIDE the <ul>
			 * so the fragment refresh can swap empty⇄filled. Previously the empty
			 * branch rendered a <div> instead of the <ul>, so adding the FIRST item
			 * from an empty cart left the drawer body stuck on "empty" (the refresh
			 * had no <ul> to replace) — the single most important add.
			 */
			?>
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

		</div>

		<?php
		/* "Complete your meal" one-tap upsell (plugin-rendered; refreshed by the
		 * woocommerce_add_to_cart_fragments hook on every cart change). Always
		 * emits its wrapper so the AJAX fragment target exists. (v6.14.0) */
		if ( function_exists( 'lafka_cart_drawer_render_upsell' ) ) {
			lafka_cart_drawer_render_upsell();
		}
		?>

		<footer class="lafka-cart-drawer__footer">
			<?php
			/* SSOT (f099): the subtotal + rich .lafka-fdp free-delivery progress
			 * block is rendered by the plugin callable lafka_cart_drawer_render_total()
			 * on initial server load AND refreshed by the SAME callable via
			 * woocommerce_add_to_cart_fragments
			 * (lafka-plugin/incl/woocommerce/lafka-cart-drawer-fragments.php), so the
			 * two paths are byte-identical and the rich component ships even for an
			 * empty initial cart. It emits the full div.lafka-cart-drawer__total
			 * fragment target (mirrors lafka_cart_drawer_render_upsell above).
			 * lafka-fdp-tracker.js keeps only its dataLayer/analytics duties. */
			if ( function_exists( 'lafka_cart_drawer_render_total' ) ) {
				lafka_cart_drawer_render_total();
			}
			?>

			<div class="lafka-cart-drawer__actions">
				<a class="lafka-cart-drawer__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
					<?php esc_html_e( 'Checkout', 'lafka' ); ?>
					<span class="lafka-cart-drawer__arrow" aria-hidden="true">→</span>
				</a>
				<a class="lafka-cart-drawer__view-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php esc_html_e( 'View full cart', 'lafka' ); ?>
				</a>
			</div>

			<p class="lafka-cart-drawer__trust">
				<span aria-hidden="true">🔒</span>
				<?php esc_html_e( 'Secure checkout · Apple Pay · Visa · Mastercard', 'lafka' ); ?>
			</p>
		</footer>

	</div>
</aside>
