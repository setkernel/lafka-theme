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
	aria-modal="false"
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

			<?php if ( $lafka_cart_empty ) : ?>
				<div class="lafka-cart-drawer__empty" data-lafka-cart-empty>
					<span class="lafka-cart-drawer__empty-icon" aria-hidden="true">🛒</span>
					<h3 class="lafka-cart-drawer__empty-title"><?php esc_html_e( 'Your cart is empty', 'lafka' ); ?></h3>
					<p class="lafka-cart-drawer__empty-hint"><?php esc_html_e( 'Add something delicious to get started.', 'lafka' ); ?></p>
					<a class="lafka-cart-drawer__empty-cta" href="<?php echo esc_url( apply_filters( 'lafka_header_cta_url', home_url( '/menu/' ) ) ); ?>">
						<?php esc_html_e( 'Browse the menu', 'lafka' ); ?>
					</a>
				</div>
			<?php else : ?>
				<ul class="lafka-cart-drawer__items">
					<?php
					/* Server-render line items on initial page load. The plugin's
					 * woocommerce_add_to_cart_fragments hook still replaces this
					 * <ul> on subsequent add-to-cart events via AJAX — keeping
					 * the markup IN SYNC across both render paths (v6.7.4).
					 * Without this, customers who opened the drawer on a fresh
					 * page-load saw an empty body despite the "Your cart / 3"
					 * count badge.
					 */
					foreach ( WC()->cart->get_cart() as $lafka_cart_item_key => $lafka_cart_item ) {
						$lafka_product = $lafka_cart_item['data'] ?? null;
						if ( ! $lafka_product ) {
							continue;
						}
						$lafka_item_name  = apply_filters( 'woocommerce_cart_item_name', $lafka_product->get_name(), $lafka_cart_item, $lafka_cart_item_key );
						$lafka_item_thumb = $lafka_product->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) );
						$lafka_item_price = WC()->cart->get_product_subtotal( $lafka_product, $lafka_cart_item['quantity'] );
						?>
						<li class="lafka-cart-drawer__item" data-cart-key="<?php echo esc_attr( $lafka_cart_item_key ); ?>">
							<span class="lafka-cart-drawer__thumb">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC_Product::get_image() returns trusted WC-core HTML with attributes pre-escaped.
								echo $lafka_item_thumb;
								?>
							</span>
							<span class="lafka-cart-drawer__name"><?php echo wp_kses_post( $lafka_item_name ); ?></span>
							<span class="lafka-cart-drawer__qty">×<?php echo esc_html( (string) (int) $lafka_cart_item['quantity'] ); ?></span>
							<span class="lafka-cart-drawer__price"><?php echo wp_kses_post( $lafka_item_price ); ?></span>
							<button type="button" class="lafka-cart-drawer__remove" data-cart-key="<?php echo esc_attr( $lafka_cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'lafka' ); ?>">×</button>
						</li>
						<?php
					}
					?>
				</ul>
			<?php endif; ?>

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
			<div class="lafka-cart-drawer__total">
				<?php
				/* Server-render the subtotal + free-delivery progress on initial
				 * page load so the drawer footer isn't empty when the customer
				 * opens it without having added anything in this session.
				 *
				 * Fragment replacement on add-to-cart still updates this block
				 * (the plugin's lafka-cart-drawer-fragments.php replaces the
				 * entire .lafka-cart-drawer__total div). After the fragment
				 * HTML lands, lafka-fdp-tracker.js post-processes the plain-
				 * text .lafka-cart-drawer__threshold markup into the rich
				 * .lafka-fdp component — keeping initial-render and AJAX-
				 * refresh visually consistent without modifying the plugin
				 * (v6.9.0, Pillar 3A). */
				if ( ! $lafka_cart_empty ) {
					$lafka_cart_total = (float) WC()->cart->get_cart_contents_total();
					?>
					<div class="lafka-cart-drawer__subtotal">
						<span><?php esc_html_e( 'Subtotal', 'lafka' ); ?></span>
						<strong><?php echo wp_kses_post( wc_price( $lafka_cart_total ) ); ?></strong>
					</div>
					<?php
					/* v6.9.0: rich progress component replaces the v6.7.4 plain-
					 * text threshold notice. Partial silently returns if the
					 * operator hasn't configured a threshold ( <= 0 ). */
					get_template_part(
						'partials/free-delivery-progress',
						null,
						array(
							'context'    => 'drawer',
							'cart_total' => $lafka_cart_total,
						)
					);
				}
				?>
			</div>

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
