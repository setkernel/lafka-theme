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

$lafka_info   = lafka_get_restaurant_info();
$lafka_method = class_exists( 'Lafka_Fulfilment' ) && 'pickup' === Lafka_Fulfilment::current_mode() ? 'pickup' : 'delivery';
$lafka_status = function_exists( 'lafka_open_status' ) ? lafka_open_status() : null;
?>
<div class="lafka-order-method-bar" data-method="<?php echo esc_attr( $lafka_method ); ?>" data-lafka-fulfilment-root>
	<div class="lafka-order-method-bar__inner">
		<button type="button" class="lafka-order-method-bar__method" data-lafka-method-toggle>
			<?php
			// Both modes' words are printed, so js/lafka-fulfilment.js can switch them in
			// place. Operator values come from the resolver, never a literal.
			$lafka_texts = array(
				'icon'     => array( '🏪', '🚚' ),
				'label'    => array( __( 'Pickup at', 'lafka' ), __( 'Delivery to', 'lafka' ) ),
				'location' => array( (string) $lafka_info['address_short'], $lafka_info['city'] . ', ' . $lafka_info['region'] ),
			);
			foreach ( $lafka_texts as $lafka_part => $lafka_pair ) :
				$lafka_class = 'icon' === $lafka_part ? 'icon' : ( 'label' === $lafka_part ? 'method-label' : 'location' );
				?>
				<span
					class="lafka-order-method-bar__<?php echo esc_attr( $lafka_class ); ?>"
					<?php echo 'icon' === $lafka_part ? 'aria-hidden="true"' : ''; ?>
					data-lafka-fulfilment-text
					data-lafka-fulfilment-pickup="<?php echo esc_attr( $lafka_pair[0] ); ?>"
					data-lafka-fulfilment-delivery="<?php echo esc_attr( $lafka_pair[1] ); ?>"
				><?php echo esc_html( 'pickup' === $lafka_method ? $lafka_pair[0] : $lafka_pair[1] ); ?></span>
			<?php endforeach; ?>
			<span class="lafka-order-method-bar__switch"><?php esc_html_e( 'Switch', 'lafka' ); ?></span>
		</button>
		<div class="lafka-order-method-bar__right">
			<?php if ( ! empty( $lafka_info['phone_display'] ) ) : ?>
				<a class="lafka-order-method-bar__phone" href="tel:<?php echo esc_attr( $lafka_info['phone_e164'] ); ?>">📞 <?php echo esc_html( $lafka_info['phone_display'] ); ?></a>
			<?php endif; ?>
			<?php if ( $lafka_status ) : ?>
				<span class="lafka-order-method-bar__hours"><?php echo esc_html( $lafka_status['label'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>
