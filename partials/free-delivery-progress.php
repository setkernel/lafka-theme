<?php
/**
 * Free-delivery progress component (Pillar 3A, v6.9.0).
 *
 * Renders a richer visual indicator of how close the cart subtotal is to
 * the operator-configured free-delivery threshold. Used in two places:
 *
 *   1. partials/cart-drawer.php — inside .lafka-cart-drawer__total on
 *      initial server render (the plugin's fragment refresh still owns
 *      AJAX updates; the JS tracker rebuilds the rich markup after each
 *      refresh — see js/lafka-fdp-tracker.js).
 *   2. woocommerce/cart/cart.php — above the cart totals card.
 *
 * Inputs (all optional, all read from WC()->cart and Customizer):
 *   $args['context'] — 'drawer' | 'cart' — used to namespace the
 *                       modifier class on the root element. Default 'drawer'.
 *   $args['cart_total'] — float, current cart subtotal. Computed if absent.
 *   $args['threshold'] — float, free-delivery threshold. Computed if absent.
 *
 * Early-exits silently if WooCommerce isn't bootstrapped or the threshold
 * is 0 (operator-disabled). Matches v6.7.4 gating in cart-drawer.php.
 *
 * @package Lafka
 * @since   6.9.0
 *
 * @param array<string,mixed> $args See above.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}

/**
 * Args are passed in via get_template_part( 'partials/free-delivery-progress', null, $args )
 * which exposes them as $args at this scope on WP 5.5+. Stay defensive for
 * the (vanishingly rare) caller that includes this partial directly.
 */
if ( ! isset( $args ) || ! is_array( $args ) ) {
	$args = array();
}

$lafka_fdp_context = isset( $args['context'] ) ? (string) $args['context'] : 'drawer';
$lafka_fdp_context = in_array( $lafka_fdp_context, array( 'drawer', 'cart' ), true ) ? $lafka_fdp_context : 'drawer';

$lafka_fdp_total = isset( $args['cart_total'] )
	? (float) $args['cart_total']
	: (float) WC()->cart->get_cart_contents_total();

$lafka_fdp_threshold_setting = function_exists( 'get_theme_mod' )
	? (float) get_theme_mod( 'lafka_pdp_free_delivery_threshold', 0 )
	: 0.0;
$lafka_fdp_threshold = isset( $args['threshold'] )
	? (float) $args['threshold']
	: (float) apply_filters( 'lafka_pdp_free_delivery_threshold', $lafka_fdp_threshold_setting );

// Threshold disabled — render nothing. Matches the v6.7.4 cart-drawer gate.
if ( $lafka_fdp_threshold <= 0 ) {
	return;
}

$lafka_fdp_remaining = max( 0, $lafka_fdp_threshold - $lafka_fdp_total );
$lafka_fdp_reached   = ( $lafka_fdp_remaining <= 0 );

// Progress percentage — capped 0..100 so a runaway cart never overshoots.
$lafka_fdp_pct = 0;
if ( $lafka_fdp_threshold > 0 ) {
	$lafka_fdp_pct = (int) min( 100, max( 0, round( ( $lafka_fdp_total / $lafka_fdp_threshold ) * 100 ) ) );
}

$lafka_fdp_state = $lafka_fdp_reached ? 'reached' : 'below';
$lafka_fdp_class = 'lafka-fdp lafka-fdp--' . ( 'cart' === $lafka_fdp_context ? 'cart-page' : 'drawer' );

if ( $lafka_fdp_reached ) {
	$lafka_fdp_title = esc_html__( 'Free delivery unlocked ✓', 'lafka' );
} else {
	$lafka_fdp_title = sprintf(
		/* translators: %s — amount remaining to qualify for free delivery, formatted (e.g. "$7.50"). */
		esc_html__( 'Add %s more for free delivery!', 'lafka' ),
		wp_kses_post( wc_price( $lafka_fdp_remaining ) )
	);
}

$lafka_fdp_sub = sprintf(
	/* translators: 1: cart subtotal formatted; 2: threshold formatted; 3: percentage 0-100. */
	esc_html__( '%1$s / %2$s · %3$d%%', 'lafka' ),
	wp_kses_post( wc_price( $lafka_fdp_total ) ),
	wp_kses_post( wc_price( $lafka_fdp_threshold ) ),
	$lafka_fdp_pct
);
?>
<div
	class="<?php echo esc_attr( $lafka_fdp_class ); ?>"
	data-lafka-fdp
	data-state="<?php echo esc_attr( $lafka_fdp_state ); ?>"
	data-threshold="<?php echo esc_attr( (string) $lafka_fdp_threshold ); ?>"
	data-value="<?php echo esc_attr( (string) $lafka_fdp_total ); ?>"
	data-remaining="<?php echo esc_attr( (string) $lafka_fdp_remaining ); ?>"
	data-pct="<?php echo esc_attr( (string) $lafka_fdp_pct ); ?>"
	role="status"
	aria-live="polite"
>
	<div class="lafka-fdp__label">
		<span class="lafka-fdp__title"><?php echo wp_kses_post( $lafka_fdp_title ); ?></span>
	</div>
	<div class="lafka-fdp__bar" aria-hidden="true">
		<div class="lafka-fdp__fill" style="width: <?php echo esc_attr( (string) $lafka_fdp_pct ); ?>%;"></div>
	</div>
	<div class="lafka-fdp__sub"><?php echo wp_kses_post( $lafka_fdp_sub ); ?></div>
</div>
