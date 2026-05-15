<?php
/**
 * Partial: Home trust strip
 *
 * Three-column trust signal: order time / payment / contact. Reads from
 * Customizer / WC settings — no hardcoded values.
 *
 * Reads:
 *  - lafka_service_eta_*  (existing service-ETA strip data, if any)
 *  - blog phone / address from theme options + WC store settings
 *
 * @package Lafka
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

// Reuse existing service-ETA data if available.
$lafka_trust_eta = function_exists( 'lafka_service_eta_get_data' ) ? lafka_service_eta_get_data() : null;

// Phone fallback chain (most-specific → least-specific):
//   1. Customizer mod `lafka_contact_phone` (new field, set by operator)
//   2. Legacy theme-options `top_bar_message_phone` (existing prod data)
$lafka_trust_phone = (string) get_theme_mod( 'lafka_contact_phone', '' );
if ( '' === $lafka_trust_phone && function_exists( 'lafka_get_option' ) ) {
	$lafka_trust_phone = (string) lafka_get_option( 'top_bar_message_phone' );
}

// Address — compose full street address from WC store settings.
// Plain `woocommerce_store_address` often holds just street; we want
// "<street>, <city>, <state> <postcode>" for the trust strip.
$lafka_trust_address = '';
if ( function_exists( 'WC' ) ) {
	$lafka_trust_addr_parts = array_filter(
		array(
			trim( (string) get_option( 'woocommerce_store_address', '' ) ),
			trim( (string) get_option( 'woocommerce_store_address_2', '' ) ),
			trim( (string) get_option( 'woocommerce_store_city', '' ) ),
			trim( (string) get_option( 'woocommerce_store_postcode', '' ) ),
		),
		static function ( $part ) {
			return '' !== $part;
		}
	);
	$lafka_trust_address    = implode( ', ', $lafka_trust_addr_parts );
}
?>
<section class="lafka-home-trust" aria-label="<?php esc_attr_e( 'Service info', 'lafka' ); ?>">
	<div class="lafka-container">
		<ul class="lafka-home-trust__grid" role="list">

			<?php if ( $lafka_trust_eta && ! empty( $lafka_trust_eta['pickup_minutes'] ) ) : ?>
				<li class="lafka-home-trust__item">
					<span class="lafka-home-trust__icon" aria-hidden="true">⏱</span>
					<span class="lafka-home-trust__label"><?php esc_html_e( 'Ready in', 'lafka' ); ?></span>
					<span class="lafka-home-trust__value">
						<?php
						printf(
							esc_html__( '%d min pickup', 'lafka' ),
							(int) $lafka_trust_eta['pickup_minutes']
						);
						?>
					</span>
				</li>
			<?php endif; ?>

			<?php if ( '' !== $lafka_trust_phone ) : ?>
				<li class="lafka-home-trust__item">
					<span class="lafka-home-trust__icon" aria-hidden="true">📞</span>
					<span class="lafka-home-trust__label"><?php esc_html_e( 'Call to order', 'lafka' ); ?></span>
					<a class="lafka-home-trust__value lafka-home-trust__value--link" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $lafka_trust_phone ) ); ?>">
						<?php echo esc_html( $lafka_trust_phone ); ?>
					</a>
				</li>
			<?php endif; ?>

			<?php if ( '' !== $lafka_trust_address ) : ?>
				<li class="lafka-home-trust__item">
					<span class="lafka-home-trust__icon" aria-hidden="true">📍</span>
					<span class="lafka-home-trust__label"><?php esc_html_e( 'Visit us', 'lafka' ); ?></span>
					<span class="lafka-home-trust__value"><?php echo esc_html( $lafka_trust_address ); ?></span>
				</li>
			<?php endif; ?>

		</ul>
	</div>
</section>
