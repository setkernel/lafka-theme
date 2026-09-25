<?php
/**
 * GX4: per-person value line for shareable deals ("For 2: about $11.50 each").
 *
 * The line renders ONLY from real product data: a "serves N" value (N >= 2)
 * supplied by lafka-plugin's `_lafka_serves` product field through the
 * `lafka_product_serves` filter. Nothing is inferred from product names — no
 * value, no line (no fabricated content).
 *
 * Filters:
 *   lafka_product_serves( int $n, WC_Product $p )             (plugin fills it)
 *   lafka_per_person_rounding( float $step = 0.25 )
 *   lafka_per_person_text( string $text, float $price, int $serves, float $each )
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_serves_for' ) ) {
	/**
	 * How many people a product serves (0 = not set).
	 *
	 * @param WC_Product $product Product.
	 */
	function lafka_serves_for( $product ): int {
		return max( 0, (int) apply_filters( 'lafka_product_serves', 0, $product ) );
	}
}

if ( ! function_exists( 'lafka_per_person_text' ) ) {
	/**
	 * "For 2: about $11.50 each" — '' when serves < 2. The per-person amount is
	 * rounded to the nearest `lafka_per_person_rounding` step (0.25) and whole
	 * amounts drop their cents ("about $10 each").
	 *
	 * @param float $price  The deal's display price.
	 * @param int   $serves People served.
	 */
	function lafka_per_person_text( float $price, int $serves ): string {
		if ( $serves < 2 || $price <= 0 ) {
			return '';
		}
		$step = (float) apply_filters( 'lafka_per_person_rounding', 0.25 );
		$each = $price / $serves;
		if ( $step > 0 ) {
			$each = round( $each / $step ) * $step;
		}
		$text = sprintf(
			/* translators: 1: number of people, 2: approximate price per person (e.g. "$11.50") */
			_n( 'For %1$d: about %2$s each', 'For %1$d: about %2$s each', $serves, 'lafka' ),
			$serves,
			function_exists( 'lafka_price_plain' ) ? lafka_price_plain( $each, true ) : '$' . number_format( $each, 2 )
		);
		return (string) apply_filters( 'lafka_per_person_text', $text, $price, $serves, $each );
	}
}
