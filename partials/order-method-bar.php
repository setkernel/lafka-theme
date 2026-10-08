<?php
/**
 * Always-sticky address/method bar.
 *
 * Hooked to wp_body_open in lafka-theme/functions.php (W4-T21).
 * Reads from lafka_get_restaurant_info() for store address + hours.
 *
 * @package Lafka\Partials
 * @since   5.16.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) || ! lafka_pdp_redesign_enabled() ) {
	return;
}
if ( ! function_exists( 'lafka_get_restaurant_info' ) ) {
	return;
}
// GX4: the counter header owns fulfilment + status + phone.
if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'header', 'counter' ) ) {
	return;
}

$lafka_info        = lafka_get_restaurant_info();
$lafka_method      = isset( $_COOKIE['lafka_order_method'] ) && 'pickup' === $_COOKIE['lafka_order_method'] ? 'pickup' : 'delivery';
$lafka_is_open     = function_exists( 'lafka_pdp_is_store_open' ) ? lafka_pdp_is_store_open() : true;
$lafka_today_hours = $lafka_info['hours'][ wp_date( 'l' ) ] ?? '';
$lafka_close_time  = '';
if ( preg_match( '/-(\d{2}:\d{2})$/', $lafka_today_hours, $m ) ) {
	$lafka_close_time = $m[1];
}
?>
<div class="lafka-order-method-bar" data-method="<?php echo esc_attr( $lafka_method ); ?>">
	<div class="lafka-order-method-bar__inner">
		<button type="button" class="lafka-order-method-bar__method" data-lafka-method-toggle>
			<span class="lafka-order-method-bar__icon" aria-hidden="true">
				<?php echo 'pickup' === $lafka_method ? '🏪' : '🚚'; ?>
			</span>
			<span class="lafka-order-method-bar__method-label">
				<?php
				echo 'pickup' === $lafka_method
					? esc_html__( 'Pickup at', 'lafka' )
					: esc_html__( 'Delivery to', 'lafka' );
				?>
			</span>
			<span class="lafka-order-method-bar__location">
				<?php
				if ( 'pickup' === $lafka_method ) {
					// Operator-specific value MUST come from the resolver
					// (theme_mod → option → WP-core → empty). Never hardcode
					// a literal address — the lafka-* repos are public OSS
					// and operator data must not leak into them. If the
					// resolver returns empty, render an empty span rather
					// than fall back to a literal string.
					echo esc_html( $lafka_info['address_short'] );
				} else {
					echo esc_html( $lafka_info['city'] . ', ' . $lafka_info['region'] );
				}
				?>
			</span>
			<span class="lafka-order-method-bar__switch"><?php esc_html_e( 'Switch', 'lafka' ); ?></span>
		</button>
		<div class="lafka-order-method-bar__right">
			<?php if ( ! empty( $lafka_info['phone_display'] ) ) : ?>
				<a class="lafka-order-method-bar__phone" href="tel:<?php echo esc_attr( $lafka_info['phone_e164'] ); ?>">📞 <?php echo esc_html( $lafka_info['phone_display'] ); ?></a>
			<?php endif; ?>
			<span class="lafka-order-method-bar__hours">
				<?php if ( $lafka_is_open && $lafka_close_time ) : ?>
					<?php
					/* translators: %s: closing time. */
					printf( esc_html__( 'Open until %s', 'lafka' ), esc_html( $lafka_close_time ) );
					?>
				<?php elseif ( $lafka_is_open ) : ?>
					<?php esc_html_e( 'Open', 'lafka' ); ?>
				<?php else : ?>
					<?php
					esc_html_e( 'Closed', 'lafka' );

					if ( class_exists( 'Lafka_Order_Hours' ) ) {
						$lafka_next_open = Lafka_Order_Hours::get_next_opening_time();
						$lafka_human     = Lafka_Order_Hours::format_next_open_time_human( $lafka_next_open );
						if ( '' !== $lafka_human ) {
							/* translators: %s: when the kitchen next opens, e.g. "today at 11:00 AM". */
							echo ' &middot; ' . esc_html( sprintf( __( 'Opens %s', 'lafka' ), $lafka_human ) );
						}
					}
					?>
				<?php endif; ?>
			</span>
		</div>
	</div>
</div>
