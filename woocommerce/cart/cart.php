<?php
/**
 * Cart Page
 *
 * Lafka v5.19.0 — list-row layout: 80px image-left thumb + body-right
 * with title, meta (variations + add-on data), and bottom row with
 * price + quantity stepper + remove link.
 *
 * Replaces WC's table-based row layout. WC ecosystem hooks preserved
 * for 3rd-party extensions (wishlist plugins, cart-add-on plugins).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Lafka\WooCommerce
 * @version 6.9.0 (Pillar 3A — free-delivery progress component above totals)
 */

defined( 'ABSPATH' ) || exit;

/*
 * Fulfilment localStorage contract (SSOT).
 *
 * The pickup/delivery choice persists under a single brand-neutral key shared
 * by the menu and cart controllers. It is defined ONCE in PHP and handed to the
 * JS via window.lafkaCfg (wp_localize_script); the controllers only fall back to
 * their own literals when this object is missing. `fulfilmentLegacyKey` drives a
 * one-time migration of the pre-rename value so returning customers keep their
 * stored choice. Override all three via the 'lafka_fulfilment_js_config' filter
 * — the single customization point.
 *
 * NOTE: the helper is mirrored in partials/menu-controls.php because the shared
 * enqueue site (incl/system/core-functions.php) is out of scope for this
 * change; consolidate it there when next touching the enqueue.
 */
if ( ! function_exists( 'lafka_localize_fulfilment_cfg' ) ) {
	/**
	 * Attach the brand-neutral fulfilment storage contract (window.lafkaCfg)
	 * to a registered script handle. Idempotent per handle.
	 *
	 * @param string $handle Registered script handle to localize.
	 */
	function lafka_localize_fulfilment_cfg( $handle ) {
		static $done = array();
		if ( isset( $done[ $handle ] ) || ! function_exists( 'wp_localize_script' ) ) {
			return;
		}
		$done[ $handle ] = true;
		wp_localize_script(
			$handle,
			'lafkaCfg',
			apply_filters(
				'lafka_fulfilment_js_config',
				array(
					'fulfilmentKey'       => 'lafka.fulfilment',
					'fulfilmentDefault'   => 'pickup',
					// Pre-rename key, read once for migration only (see JS).
					'fulfilmentLegacyKey' => 'peppery.fulfilment',
				)
			)
		);
	}
}
lafka_localize_fulfilment_cfg( 'lafka-cart-controls' );

do_action( 'woocommerce_before_cart' );
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<?php
	/* v6.7.11: real <h2> heading replaces the v5.67.0 CSS ::before pseudo
	 * (F02-a11y, HTML-MISORDER). Pseudo-elements aren't focusable or
	 * announceable as headings; combined with `.lafka-cart-item__title`
	 * being <h3>, the previous DOM was h1 → h3 → h2 — out of order. The
	 * new sequence is h1 (page) → h2 (Your order) → p (item title styled
	 * as a heading) → h2 (Cart totals from WC core). */
	?>
	<h2 class="lafka-cart-form__title"><?php esc_html_e( 'Your order', 'lafka' ); ?></h2>
	<p class="lafka-cart-form__intro"><?php esc_html_e( 'Review your items, choose pickup or delivery, then check out.', 'lafka' ); ?></p>

	<?php
	/* v5.68.0: handoff pickup/delivery tabs above items list.
	 * State persists to localStorage.lafka.fulfilment via lafka-menu-controls.js
	 * (loaded on menu archive) — cart page uses lafka-cart-controls.js for the same. */
	$lafka_cart_info       = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
	$lafka_cart_addr_short = isset( $lafka_cart_info['address_short'] ) ? (string) $lafka_cart_info['address_short'] : '';
	$lafka_cart_city       = isset( $lafka_cart_info['city'] ) ? (string) $lafka_cart_info['city'] : '';
	$lafka_cart_eta        = function_exists( 'lafka_service_eta_get_data' ) ? lafka_service_eta_get_data() : null;
	$lafka_cart_pickup_eta = $lafka_cart_eta && ! empty( $lafka_cart_eta['pickup'] ) ? (string) $lafka_cart_eta['pickup'] : '';
	/* SSOT: read the same threshold the plugin's free-delivery rule enforces,
	 * so the displayed promise can never diverge from what's charged. When the
	 * plugin isn't loaded, fall back to the single shared theme_mod (0 = off).
	 * 0 means "no free-delivery promise" — matching enforcement on a fresh
	 * (unconfigured) install. */
	$lafka_cart_threshold = function_exists( 'lafka_get_free_delivery_threshold' )
		? (float) lafka_get_free_delivery_threshold()
		: (float) get_theme_mod( 'lafka_announce_bar_delivery_threshold', 0 );
	$lafka_cart_threshold_label = function_exists( 'wc_price' )
		? wp_strip_all_tags( wc_price( $lafka_cart_threshold ) )
		: sprintf( '$%s', number_format_i18n( $lafka_cart_threshold, 0 ) );
	?>
	<div class="lafka-cart-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Fulfilment method', 'lafka' ); ?>" data-lafka-cart-tabs>
		<button type="button" class="lafka-cart-tab is-active" role="tab" aria-selected="true" data-lafka-fulfilment="pickup">
			<span class="lafka-cart-tab__label"><?php esc_html_e( 'Pickup', 'lafka' ); ?></span>
			<span class="lafka-cart-tab__meta">
				<?php
				if ( '' !== $lafka_cart_pickup_eta ) {
					/* translators: %s — pickup ETA, e.g. "~25 min" */
					printf( esc_html__( 'Ready in %s', 'lafka' ), esc_html( $lafka_cart_pickup_eta ) );
					if ( '' !== $lafka_cart_addr_short ) {
						echo ' · ' . esc_html( $lafka_cart_addr_short );
					}
				} elseif ( '' !== $lafka_cart_addr_short ) {
					echo esc_html( $lafka_cart_addr_short );
				}
				?>
			</span>
		</button>
		<button type="button" class="lafka-cart-tab" role="tab" aria-selected="false" data-lafka-fulfilment="delivery">
			<span class="lafka-cart-tab__label"><?php esc_html_e( 'Delivery', 'lafka' ); ?></span>
			<span class="lafka-cart-tab__meta">
				<?php
				if ( $lafka_cart_threshold > 0 ) {
					/* translators: 1: free-delivery threshold; 2: city. */
					printf(
						esc_html__( 'Free over %1$s%2$s', 'lafka' ),
						esc_html( $lafka_cart_threshold_label ),
						'' !== $lafka_cart_city ? ' · ' . esc_html( $lafka_cart_city ) : ''
					);
				} elseif ( '' !== $lafka_cart_city ) {
					echo esc_html( $lafka_cart_city );
				}
				?>
			</span>
		</button>
	</div>

	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<ul class="lafka-cart">
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				?>
				<li class="lafka-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<div class="lafka-cart-item__img-wrap">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

						if ( ! $product_permalink ) {
							echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
					<div class="lafka-cart-item__body">
						<?php
						/* v6.7.11: <p> replaces <h3> (HTML-MISORDER). Cart totals
						 * later in the document is <h2> (WC core), so an h3 here
						 * created an out-of-order h1 → h3 → h2 sequence. Cart-item
						 * titles are list-row labels, not section headings, so a
						 * styled <p> is the correct semantic level. CSS retains
						 * the existing visual weight via `.lafka-cart-item__title`. */
						?>
						<p class="lafka-cart-item__title">
							<?php
							if ( ! $product_permalink ) {
								echo wp_kses_post( $product_name . '&nbsp;' );
							} else {
								echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $product_name ) );
							}
							?>
						</p>
						<?php do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key ); ?>

						<?php
						$item_data = wc_get_formatted_cart_item_data( $cart_item );
						if ( ! empty( $item_data ) ) :
							?>
							<div class="lafka-cart-item__meta"><?php echo wp_kses_post( $item_data ); ?></div>
						<?php endif; ?>

						<?php if ( $cart_item['quantity'] > 1 ) : ?>
							<div class="lafka-cart-item__unit-price">
								<?php
								// translators: %s: per-unit price
								echo wp_kses_post(
                                    sprintf(
                                        __( '%s each', 'lafka' ),
                                        apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key )
                                    ) 
                                );
								?>
							</div>
						<?php endif; ?>

						<?php
						if ( $_product->backorders_require_notification() && $_product->is_in_stock() ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'lafka' ) . '</p>', $product_id ) );
						}
						?>

						<div class="lafka-cart-item__bottom">
							<span class="lafka-cart-item__price">
								<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>

							<div class="lafka-cart-item__qty">
								<?php
								if ( $_product->is_sold_individually() ) {
									$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
								} else {
									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->get_max_purchase_quantity(),
											'min_value'    => '0',
											'product_name' => $_product->get_name(),
										),
										$_product,
										false
									);
								}

								echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</div>

							<?php
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="lafka-cart-item__remove" role="button" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									/* translators: %s: Product name */
									esc_attr( sprintf( __( 'Remove %s from cart', 'lafka' ), wp_strip_all_tags( $product_name ) ) ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() )
								),
								$cart_item_key
							);
							?>
						</div>
					</div>
				</li>
				<?php
			}
		}
		?>

		<?php do_action( 'woocommerce_cart_contents' ); ?>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	</ul>

	<?php
	/* v5.68.0: handoff "+ Add more items" / "Clear order" row.
	 * The first action links to the shop / menu. The second posts to the
	 * cart URL with empty quantities — WC's standard "Update cart" with
	 * zero qty removes lines. */
	// Canonical browse target (f104): the /menu/ page via the shared resolver, so
	// "Add more items" tracks every other menu CTA instead of the WC shop archive.
	$lafka_cart_shop_url = function_exists( 'lafka_get_menu_url' ) ? lafka_get_menu_url() : home_url( '/menu/' );
	?>
	<div class="lafka-cart-bottom-actions">
		<a class="lafka-cart-bottom-actions__add" href="<?php echo esc_url( $lafka_cart_shop_url ); ?>">
			<span aria-hidden="true">+</span>
			<?php esc_html_e( 'Add more items', 'lafka' ); ?>
		</a>
		<button
			type="button"
			class="lafka-cart-bottom-actions__clear"
			data-lafka-cart-clear
		>
			<span aria-hidden="true">🗑</span>
			<?php esc_html_e( 'Clear order', 'lafka' ); ?>
		</button>
	</div>

	<div class="lafka-cart__actions">
		<?php if ( wc_coupons_enabled() ) { ?>
			<div class="coupon">
				<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'lafka' ); ?></label>
				<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'lafka' ); ?>" />
				<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'lafka' ); ?>"><?php esc_html_e( 'Apply coupon', 'lafka' ); ?></button>
				<?php do_action( 'woocommerce_cart_coupon' ); ?>
			</div>
		<?php } ?>

		<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'lafka' ); ?>"><?php esc_html_e( 'Update cart', 'lafka' ); ?></button>

		<?php do_action( 'woocommerce_cart_actions' ); ?>

		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<?php
/* v6.9.0 (Pillar 3A): prominent free-delivery progress component, emitted
 * above the cart totals card. At desktop the totals card sits to the side
 * (handoff layout), so the FDP appears directly above it; at mobile the
 * totals card stacks below the items + bottom-actions, which naturally
 * places the FDP between line items and totals as the spec requires.
 *
 * Partial silently returns if the operator hasn't configured a threshold
 * ( the SSOT lafka_get_free_delivery_threshold() resolves to <= 0 ), so
 * removing the feature is a single Customizer toggle. */
get_template_part(
	'partials/free-delivery-progress',
	null,
	array(
		'context' => 'cart',
	)
);
?>

<div class="cart-collaterals">
	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
	?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
