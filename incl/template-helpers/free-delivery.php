<?php
/**
 * Free-delivery threshold helpers — the theme-side SSOT accessors.
 *
 * The enforced threshold lives in the PLUGIN's shipping rule
 * (lafka_get_free_delivery_threshold()); the shared theme_mod
 * 'lafka_announce_bar_delivery_threshold' (0 = off) is the fallback when the
 * plugin isn't active. Every surface that CITES the threshold (announce bar,
 * hero stat, how-it-works, cart, PDP, menu controls) must read it through
 * here so marketing copy can never diverge from what shipping enforces.
 *
 * @package Lafka
 * @since   7.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_free_delivery_threshold' ) ) {
	/**
	 * The resolved free-delivery threshold. 0 = feature off.
	 *
	 * @return float
	 */
	function lafka_free_delivery_threshold(): float {
		return function_exists( 'lafka_get_free_delivery_threshold' )
			? (float) lafka_get_free_delivery_threshold()
			: (float) get_theme_mod( 'lafka_announce_bar_delivery_threshold', 0 );
	}
}

if ( ! function_exists( 'lafka_free_delivery_amount_text' ) ) {
	/**
	 * The threshold as plain text in store currency (e.g. "$30.00").
	 *
	 * @param float|null $threshold Amount; defaults to the resolved threshold.
	 * @return string Empty string when the threshold is off.
	 */
	function lafka_free_delivery_amount_text( ?float $threshold = null ): string {
		$threshold = null === $threshold ? lafka_free_delivery_threshold() : $threshold;
		if ( $threshold <= 0 ) {
			return '';
		}

		return function_exists( 'wc_price' )
			? wp_strip_all_tags( wc_price( $threshold ) )
			: sprintf( '$%s', number_format_i18n( $threshold, 0 ) );
	}
}

if ( ! function_exists( 'lafka_home_hero_stat3_defaults' ) ) {
	/**
	 * Derived DEFAULTS for the hero's free-delivery stat (value + label).
	 *
	 * Both empty when no threshold is configured — a default install must not
	 * advertise a free-delivery offer shipping doesn't enforce (the literal
	 * "delivery $30+" this replaces was a false claim on every fresh site,
	 * same class as the fabricated-rating default retired in audit #5). Used
	 * by the partial AND the Customizer registration so preview == live.
	 *
	 * @return array{value:string,label:string}
	 */
	function lafka_home_hero_stat3_defaults(): array {
		$amount = lafka_free_delivery_amount_text();
		if ( '' === $amount ) {
			return array(
				'value' => '',
				'label' => '',
			);
		}

		return array(
			'value' => __( 'Free', 'lafka' ),
			/* translators: %s: formatted free-delivery threshold amount. */
			'label' => sprintf( __( 'delivery %s+', 'lafka' ), $amount ),
		);
	}
}

if ( ! function_exists( 'lafka_home_how_step2_body_default' ) ) {
	/**
	 * Derived DEFAULT for the how-it-works step-2 body: cites the real
	 * threshold, or drops the free-delivery claim when none is configured.
	 *
	 * @return string
	 */
	function lafka_home_how_step2_body_default(): string {
		$amount = lafka_free_delivery_amount_text();
		if ( '' === $amount ) {
			return __( 'Pickup is fastest, or get it delivered piping hot.', 'lafka' );
		}

		/* translators: %s: formatted free-delivery threshold amount. */
		return sprintf( __( 'Pickup is fastest. Free delivery on orders over %s.', 'lafka' ), $amount );
	}
}
