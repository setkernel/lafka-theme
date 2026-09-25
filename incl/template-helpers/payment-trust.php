<?php
/**
 * Payment trust line — "Secure checkout · Credit card · Cash".
 *
 * Shown under the checkout CTA in the cart drawer and on the classic cart
 * page. It names ONLY the payment methods the store has enabled in
 * WooCommerce, so it can never advertise a method the customer won't find
 * at checkout (the literal "Apple Pay · Visa · Mastercard" it replaces was a
 * false claim on most installs).
 *
 * Source: the ENABLED gateways (WC()->payment_gateways()->payment_gateways(),
 * `enabled === 'yes'`), deliberately not get_available_payment_gateways():
 * availability is cart- and shipping-dependent (COD per shipping method,
 * gateway min/max order totals), runs every gateway's is_available() plus the
 * woocommerce_available_payment_gateways filter on every page, and the drawer
 * markup lands in full-page caches shared by every visitor. Enabled gateways
 * are store-level configuration: stable, cache-safe, no extra queries beyond
 * WC's own gateway objects. The checkout still narrows the list per order.
 *
 * Operator surface:
 *   Customizer → Lafka — Order Flow → Payment trust line
 *     lafka_payment_trust_enabled (bool, default true)
 *     lafka_payment_trust_text    (string, default '' = build automatically)
 *   Filters:
 *     lafka_payment_trust_label_map( array $map )  gateway id => short label
 *     lafka_payment_trust_line( string $line, array $labels, array $gateways )
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_payment_trust_gateways' ) ) {
	/**
	 * WooCommerce's enabled payment gateways, keyed by gateway id.
	 *
	 * @return array<string, object> Empty without WooCommerce.
	 */
	function lafka_payment_trust_gateways(): array {
		$wc = function_exists( 'WC' ) ? WC() : null;
		if ( ! is_object( $wc ) || ! method_exists( $wc, 'payment_gateways' ) ) {
			return array();
		}
		$registry = $wc->payment_gateways();
		if ( ! is_object( $registry ) || ! method_exists( $registry, 'payment_gateways' ) ) {
			return array();
		}

		$enabled = array();
		foreach ( (array) $registry->payment_gateways() as $id => $gateway ) {
			if ( is_object( $gateway ) && isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				$enabled[ (string) ( $gateway->id ?? $id ) ] = $gateway;
			}
		}

		return $enabled;
	}
}

if ( ! function_exists( 'lafka_payment_trust_label_map' ) ) {
	/**
	 * Neutral short labels for WooCommerce core gateway ids.
	 *
	 * Core ids only — every other gateway is named by its own (operator-set)
	 * title. Mapping COD to "Cash" keeps the line neutral when a plugin
	 * retitles it per fulfilment ("Pay at pickup" / "Pay on delivery").
	 *
	 * @return array<string, string> gateway id => label.
	 */
	function lafka_payment_trust_label_map(): array {
		return (array) apply_filters(
			'lafka_payment_trust_label_map',
			array(
				'cod'    => __( 'Cash', 'lafka' ),
				'bacs'   => __( 'Bank transfer', 'lafka' ),
				'cheque' => __( 'Cheque', 'lafka' ),
				'paypal' => __( 'PayPal', 'lafka' ),
			)
		);
	}
}

if ( ! function_exists( 'lafka_payment_trust_labels' ) ) {
	/**
	 * One short label per gateway, de-duplicated (case-insensitive).
	 *
	 * @param array<string, object> $gateways Enabled gateways keyed by id.
	 * @return array<string, string> gateway id => label.
	 */
	function lafka_payment_trust_labels( array $gateways ): array {
		$map    = lafka_payment_trust_label_map();
		$labels = array();
		$seen   = array();
		foreach ( $gateways as $id => $gateway ) {
			$id    = (string) $id;
			$label = isset( $map[ $id ] )
				? (string) $map[ $id ]
				: ( method_exists( $gateway, 'get_title' ) ? (string) $gateway->get_title() : '' );
			$label = trim( wp_strip_all_tags( $label ) );
			$key   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $label ) : strtolower( $label );
			if ( '' === $label || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ]  = true;
			$labels[ $id ] = $label;
		}

		return $labels;
	}
}

if ( ! function_exists( 'lafka_payment_trust_line' ) ) {
	/**
	 * The trust line as plain text ('' = render nothing).
	 *
	 * @return string
	 */
	function lafka_payment_trust_line(): string {
		if ( ! (bool) get_theme_mod( 'lafka_payment_trust_enabled', true ) ) {
			return '';
		}

		$gateways = lafka_payment_trust_gateways();
		$labels   = lafka_payment_trust_labels( $gateways );
		$override = trim( (string) get_theme_mod( 'lafka_payment_trust_text', '' ) );

		$line = '' !== $override
			? $override
			: implode( ' · ', array_merge( array( __( 'Secure checkout', 'lafka' ) ), array_values( $labels ) ) );

		/**
		 * Filter the payment trust line.
		 *
		 * @param string                $line     Built (or operator-overridden) text; '' hides it.
		 * @param array<string, string> $labels   gateway id => label for each enabled gateway.
		 * @param array<string, object> $gateways Enabled WC_Payment_Gateway objects keyed by id.
		 */
		return trim( (string) apply_filters( 'lafka_payment_trust_line', $line, $labels, $gateways ) );
	}
}

if ( ! function_exists( 'lafka_payment_trust_render' ) ) {
	/**
	 * Print the trust line in a <p> with the given class (nothing when empty).
	 *
	 * @param string $css_class Class for the wrapper paragraph.
	 */
	function lafka_payment_trust_render( string $css_class ): void {
		$line = lafka_payment_trust_line();
		if ( '' === $line ) {
			return;
		}
		?>
		<p class="<?php echo esc_attr( $css_class ); ?>"><span aria-hidden="true">🔒</span> <?php echo esc_html( $line ); ?></p>
		<?php
	}
}

add_action( 'woocommerce_after_cart_totals', 'lafka_cart_totals_trust_line' );
if ( ! function_exists( 'lafka_cart_totals_trust_line' ) ) {
	/**
	 * Classic cart page: trust line under "Proceed to checkout" (inside
	 * .cart_totals, so WC's AJAX totals refresh re-renders it too).
	 */
	function lafka_cart_totals_trust_line(): void {
		lafka_payment_trust_render( 'lafka-cart-trust' );
	}
}
