<?php
/**
 * Empty cart — handoff-spec rebuild (v5.90.0).
 *
 * Overrides WC's stock "Your cart is currently empty / Return to shop"
 * dead-end with the handoff layout:
 *   - 🛒 centered emoji
 *   - "Your cart is empty." h2 in Fraunces 700
 *   - Muted helper line (max 360px)
 *   - Primary "Browse the menu" pill CTA
 *   - Followed by the existing lafka-cart-empty-popular "Add a side?"
 *     section (hooked separately via woocommerce_cart_is_empty)
 *
 * Replaces: woocommerce/templates/cart/cart-empty.php
 *
 * @package Lafka\WooCommerce
 * @since   5.90.0
 */

defined( 'ABSPATH' ) || exit;

// WC core fires woocommerce_cart_is_empty BEFORE this template renders by
// default; in our override we own the empty-state shell and let the action
// fire AFTER (so the "Add a side?" upsell sits below the CTA, not above).

// Canonical browse target (f104): the /menu/ page, resolved via the shared
// lafka_get_menu_url() helper so this CTA always tracks the header "Order now"
// button and the JSON-LD Menu links. The dedicated `lafka_cart_empty_menu_url`
// filter stays as a more-specific override for operators who want the empty-cart
// CTA to differ from the global menu URL.
$lafka_cart_empty_menu_url = (string) apply_filters(
	'lafka_cart_empty_menu_url',
	lafka_theme_menu_url()
);
?>
<section class="lafka-cart-empty" data-lafka-cart-empty>
	<span class="lafka-cart-empty__emoji" aria-hidden="true">🛒</span>
	<h2 class="lafka-cart-empty__title">
		<?php
		echo esc_html(
			(string) apply_filters(
				'wc_empty_cart_message',
				esc_html__( 'Your cart is empty.', 'lafka' )
			)
		);
		?>
	</h2>
	<p class="lafka-cart-empty__lead">
		<?php esc_html_e( 'Build an order from the menu — pizza, poutine, donair and more, ready to go.', 'lafka' ); ?>
	</p>
	<a class="lafka-cart-empty__cta" href="<?php echo esc_url( $lafka_cart_empty_menu_url ); ?>">
		<?php esc_html_e( 'Browse the menu', 'lafka' ); ?>
	</a>
</section>

<?php
/**
 * The lafka-cart-empty-popular module hooks this action and renders the
 * "Add a side?" upsell strip. Triggering it AFTER our empty-state shell
 * means the upsell sits below the CTA — matching the handoff structure.
 */
do_action( 'woocommerce_cart_is_empty' );
