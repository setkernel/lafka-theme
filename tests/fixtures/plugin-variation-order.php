<?php
/**
 * Stand-in for lafka-plugin's lafka_sort_variation_options( $product,
 * $attribute, $options ). Loaded ON DEMAND by PdpPickersOrderTest so the
 * "plugin absent" case can run in a separate process without it. Orders the
 * options by $GLOBALS['lafka_test_option_prices'][ $option ] ascending.
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

if ( ! function_exists( 'lafka_sort_variation_options' ) ) {
	function lafka_sort_variation_options( $product, string $attribute, array $options ): array {
		$prices = $GLOBALS['lafka_test_option_prices'] ?? array();
		usort( $options, static fn( $a, $b ) => ( $prices[ $a ] ?? 0 ) <=> ( $prices[ $b ] ?? 0 ) );
		return $options;
	}
}
