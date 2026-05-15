<?php
/**
 * Service ETA strip — pickup + delivery time estimates from Customizer.
 *
 * Renders an inline strip on the site header and inside the cart /
 * checkout surfaces. Values come from "Lafka — Service ETA" Customizer
 * panel and are free-text (operators can use ranges, single numbers,
 * or any localised string). Defaults are empty so the strip is
 * invisible until configured.
 *
 * Filter surface:
 *   lafka_service_eta_data(array $data) — override the rendered values
 *   lafka_service_eta_html(string $html, array $data, string $context)
 *
 * @package Lafka
 * @since   5.30.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_service_eta_get_data' ) ) {
	/**
	 * Read pickup + delivery ETA strings from Customizer.
	 *
	 * @return array{pickup: string, delivery: string}|null Null when nothing is configured.
	 */
	function lafka_service_eta_get_data() {
		$pickup   = trim( (string) get_theme_mod( 'lafka_service_eta_pickup', '' ) );
		$delivery = trim( (string) get_theme_mod( 'lafka_service_eta_delivery', '' ) );

		if ( '' === $pickup && '' === $delivery ) {
			$data = null;
		} else {
			$data = array(
				'pickup'   => $pickup,
				'delivery' => $delivery,
			);
		}

		return apply_filters( 'lafka_service_eta_data', $data );
	}
}

if ( ! function_exists( 'lafka_service_eta_render' ) ) {
	/**
	 * Echo the ETA strip. No-op if nothing is configured.
	 *
	 * @param string $context Where the strip is rendered ('header', 'cart').
	 */
	function lafka_service_eta_render( $context = 'header' ) {
		$data = lafka_service_eta_get_data();
		if ( ! $data ) {
			return;
		}

		ob_start();
		?>
		<div class="lafka-service-eta lafka-service-eta--<?php echo esc_attr( $context ); ?>" role="group" aria-label="<?php esc_attr_e( 'Service times', 'lafka' ); ?>">
			<?php if ( '' !== $data['pickup'] ) : ?>
				<span class="lafka-service-eta__item lafka-service-eta__item--pickup">
					<span class="lafka-service-eta__label"><?php esc_html_e( 'Pickup', 'lafka' ); ?></span>
					<span class="lafka-service-eta__value"><?php echo esc_html( $data['pickup'] ); ?></span>
				</span>
			<?php endif; ?>
			<?php if ( '' !== $data['pickup'] && '' !== $data['delivery'] ) : ?>
				<span class="lafka-service-eta__separator" aria-hidden="true">·</span>
			<?php endif; ?>
			<?php if ( '' !== $data['delivery'] ) : ?>
				<span class="lafka-service-eta__item lafka-service-eta__item--delivery">
					<span class="lafka-service-eta__label"><?php esc_html_e( 'Delivery', 'lafka' ); ?></span>
					<span class="lafka-service-eta__value"><?php echo esc_html( $data['delivery'] ); ?></span>
				</span>
			<?php endif; ?>
		</div>
		<?php
		$html = (string) ob_get_clean();
		echo apply_filters( 'lafka_service_eta_html', $html, $data, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'lafka_service_eta_render_header' ) ) {
	/**
	 * Header-strip placement. Hooks into wp_footer so the strip is
	 * teleported into the existing header info bar via CSS / JS-less
	 * positioning — but to keep this simple, render at the END of the
	 * site header info area via the lafka_header_info_after action.
	 *
	 * For maximum theme compatibility we attach to a low-priority WP
	 * hook (wp_body_open) that fires inside the document body but
	 * before the visual header has typically rendered.
	 *
	 * Operators wanting custom placement can dequeue this hook and
	 * call lafka_service_eta_render() directly in their child theme.
	 */
	function lafka_service_eta_render_header() {
		if ( ! (bool) get_theme_mod( 'lafka_service_eta_show_header', true ) ) {
			return;
		}
		lafka_service_eta_render( 'header' );
	}
}

if ( ! function_exists( 'lafka_service_eta_render_cart' ) ) {
	/**
	 * Cart + checkout placement — hooks into WC's cart totals area so
	 * the strip sits next to the running subtotal where customers are
	 * actively deciding whether to proceed.
	 */
	function lafka_service_eta_render_cart() {
		if ( ! (bool) get_theme_mod( 'lafka_service_eta_show_cart', true ) ) {
			return;
		}
		lafka_service_eta_render( 'cart' );
	}
}

// Header — fire before main content. wp_body_open puts the strip at the
// very top of <body>, then CSS positions it as a header sub-bar.
add_action( 'wp_body_open', 'lafka_service_eta_render_header', 50 );

// Cart + checkout — render above the WC cart totals area.
add_action( 'woocommerce_before_cart_totals', 'lafka_service_eta_render_cart' );
add_action( 'woocommerce_review_order_before_payment', 'lafka_service_eta_render_cart' );

if ( ! function_exists( 'lafka_service_eta_checkout_button_text' ) ) {
	/**
	 * Append the ETA to the checkout "Place order" button. Prefers the
	 * delivery ETA (typically the longer / more conservative value);
	 * falls back to pickup ETA if only pickup is configured.
	 *
	 * @param string $text Original button text from WC.
	 * @return string Decorated text or unchanged if nothing configured.
	 */
	function lafka_service_eta_checkout_button_text( $text ) {
		if ( ! (bool) get_theme_mod( 'lafka_service_eta_show_cart', true ) ) {
			return $text;
		}
		if ( ! function_exists( 'lafka_service_eta_get_data' ) ) {
			return $text;
		}
		$data = lafka_service_eta_get_data();
		if ( ! $data ) {
			return $text;
		}
		$eta = '' !== $data['delivery'] ? $data['delivery'] : $data['pickup'];
		if ( '' === $eta ) {
			return $text;
		}
		/* translators: 1: original button text (e.g. "Place order"), 2: ETA (e.g. "30 min") */
		return sprintf( __( '%1$s · ETA %2$s', 'lafka' ), $text, $eta );
	}
}
add_filter( 'woocommerce_order_button_text', 'lafka_service_eta_checkout_button_text' );
