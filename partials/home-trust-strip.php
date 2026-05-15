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

// Phone from Customizer (set by operator).
$lafka_trust_phone = (string) get_theme_mod( 'lafka_contact_phone', '' );
if ( '' === $lafka_trust_phone && function_exists( 'lafka_get_option' ) ) {
	$lafka_trust_phone = (string) lafka_get_option( 'header_phone' );
}

// Address — WC store settings preferred.
$lafka_trust_address = '';
if ( function_exists( 'WC' ) && WC()->countries ) {
	$lafka_trust_address = (string) get_option( 'woocommerce_store_address', '' );
}
?>
<section class="lafka-home-trust" aria-label="<?php esc_attr_e( 'Service info', 'lafka' ); ?>">
	<div class="lafka-home-trust__inner">
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
