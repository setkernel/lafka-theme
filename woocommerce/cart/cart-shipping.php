<?php
/**
 * Shipping Methods Display
 *
 * Lafka override of WooCommerce core cart/cart-shipping.php 11.2.0 (synced
 * with WooCommerce 11.2.0: the package index is normalised with %d in every
 * printf, as core does). Two changes, both
 * presentation only — every input keeps core's name/id/value/data-index, so
 * WooCommerce's cart.js / checkout.js shipping updates are untouched:
 *
 *  - The row spans both columns of the totals table (one <td colspan="2">
 *    headed by the package name, which labels the list), so the options get
 *    the full width instead of a 60–140px value column, and they render as
 *    choice cards (`lafka-shipping-choices`, one `lafka-shipping-choice` per
 *    rate, `is-selected` on the chosen one); styled in
 *    styles/lafka-checkout-handoff.css (O-06).
 *  - When the chosen rate is a customer pickup, the /cart/ "Shipping to
 *    {destination}." line and the shipping-calculator toggle are not printed:
 *    nothing is shipped to that address (O-16). Pickup = the plugin's
 *    lafka_is_pickup_shipping_method() when present, else WooCommerce's two
 *    pickup methods (local_pickup, pickup_location).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_formatted_destination    = isset( $lafka_formatted_destination ) ? $lafka_formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$lafka_has_calculated_shipping  = ! empty( $lafka_has_calculated_shipping );
$lafka_show_shipping_calculator = ! empty( $lafka_show_shipping_calculator );
$lafka_calculator_text          = '';

// Lafka: is the chosen rate a customer pickup?
$lafka_chosen_method = isset( $chosen_method ) ? (string) $chosen_method : '';
if ( '' === $lafka_chosen_method && ! empty( $available_methods ) && is_array( $available_methods ) && 1 === count( $available_methods ) ) {
	$lafka_only_method   = reset( $available_methods );
	$lafka_chosen_method = is_object( $lafka_only_method ) && isset( $lafka_only_method->id ) ? (string) $lafka_only_method->id : '';
}
if ( function_exists( 'lafka_is_pickup_shipping_method' ) ) {
	$lafka_is_pickup = '' !== $lafka_chosen_method && (bool) lafka_is_pickup_shipping_method( $lafka_chosen_method );
} else {
	$lafka_is_pickup = in_array( strtok( $lafka_chosen_method, ':' ), array( 'local_pickup', 'pickup_location' ), true );
}
if ( $lafka_is_pickup ) {
	// Nothing is shipped: no destination line, no "Change address" calculator.
	$lafka_show_shipping_calculator = false;
}
?>
<tr class="woocommerce-shipping-totals shipping lafka-shipping-totals<?php echo $lafka_is_pickup ? ' lafka-shipping-totals--pickup' : ''; ?>">
	<td colspan="2" data-title="<?php echo esc_attr( wp_strip_all_tags( $package_name ) ); ?>">
		<span class="lafka-shipping-totals__label" id="lafka-shipping-label-<?php echo absint( $index ); ?>"><?php echo wp_kses_post( $package_name ); ?></span>
		<?php if ( ! empty( $available_methods ) && is_array( $available_methods ) ) : ?>
			<ul id="shipping_method" class="woocommerce-shipping-methods lafka-shipping-choices" aria-labelledby="lafka-shipping-label-<?php echo absint( $index ); ?>">
				<?php foreach ( $available_methods as $lafka_method ) : ?>
					<li class="lafka-shipping-choice<?php echo ( $lafka_method->id === $lafka_chosen_method ) ? ' is-selected' : ''; ?>">
						<?php
						if ( 1 < count( $available_methods ) ) {
							printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', absint( $index ), esc_attr( sanitize_title( $lafka_method->id ) ), esc_attr( $lafka_method->id ), checked( $lafka_method->id, $chosen_method, false ) );
						} else {
							printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', absint( $index ), esc_attr( sanitize_title( $lafka_method->id ) ), esc_attr( $lafka_method->id ) );
						}
						printf( '<label for="shipping_method_%1$d_%2$s">%3$s</label>', absint( $index ), esc_attr( sanitize_title( $lafka_method->id ) ), wp_kses_post( wc_cart_totals_shipping_method_label( $lafka_method ) ) );
						do_action( 'woocommerce_after_shipping_rate', $lafka_method, $index );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( is_cart() && ! $lafka_is_pickup ) : ?>
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $lafka_formatted_destination ) {
						// Translators: $s shipping destination.
						printf( esc_html__( 'Shipping to %s.', 'lafka' ) . ' ', '<strong>' . esc_html( $lafka_formatted_destination ) . '</strong>' );
						$lafka_calculator_text = esc_html__( 'Change address', 'lafka' );
					} else {
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'lafka' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
		elseif ( ! $lafka_has_calculated_shipping || ! $lafka_formatted_destination ) :
			if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'lafka' ) ) );
			} else {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'lafka' ) ) );
			}
		elseif ( ! is_cart() ) :
			echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'lafka' ) ) );
		else :
			echo wp_kses_post(
				/**
				 * Provides a means of overriding the default 'no shipping available' HTML string.
				 *
				 * @since 3.0.0
				 *
				 * @param string $html                  HTML message.
				 * @param string $formatted_destination The formatted shipping destination.
				 */
				apply_filters(
					'woocommerce_cart_no_shipping_available_html',
					// Translators: $s shipping destination.
					sprintf( esc_html__( 'No shipping options were found for %s.', 'lafka' ) . ' ', '<strong>' . esc_html( $lafka_formatted_destination ) . '</strong>' ),
					$lafka_formatted_destination
				)
			);
			$lafka_calculator_text = esc_html__( 'Enter a different address', 'lafka' );
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>

		<?php if ( $lafka_show_shipping_calculator ) : ?>
			<?php woocommerce_shipping_calculator( $lafka_calculator_text ); ?>
		<?php endif; ?>
	</td>
</tr>
