<?php
/**
 * GX T-04: responsive-image hints for the counter layout.
 *
 * `sizes` must describe the slot the image actually renders in, or the browser
 * over-fetches: the featured deal asked for 90vw on phones and tablets and so
 * pulled the 1024px original (768 KB) into a ~310px box; the logo said 150px
 * for a 40–64px slot. Each value below was measured at 375 / 768 / 1024 / 1280
 * / 1440 against lafka-counter.css. They are filterable per slot because a
 * child theme that changes the layout must change the hint with it.
 *
 * The srcset filter drops degenerate candidates (a "1w" / "10w" entry from a
 * mis-generated intermediate size on the logo) that no slot ever wants.
 *
 * Filter surface:
 *   lafka_counter_image_sizes( string $sizes, string $slot )
 *   lafka_srcset_min_width( int $min ) — smallest srcset candidate kept (32).
 *
 * @package Lafka
 * @since   7.3.0 (GX T-04)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_counter_image_sizes' ) ) {
	/**
	 * The `sizes` attribute for a counter image slot.
	 *
	 * @param string $slot logo | hero-front | hero-back | deal-featured | deal-small | row-photo | row-compact.
	 */
	function lafka_counter_image_sizes( string $slot ): string {
		$map = array(
			// .lafka-counter-header__logo: 40 / 52 (≥600) / 64 (≥1280) px.
			'logo'          => '(min-width: 1280px) 64px, (min-width: 600px) 52px, 40px',
			// Hero dishes: 58% / 66% of the art column (≥1024), 44% (≥600), 58% / 54% below.
			'hero-front'    => '(min-width: 1440px) 400px, (min-width: 1024px) 28vw, (min-width: 600px) 40vw, 53vw',
			'hero-back'     => '(min-width: 1440px) 456px, (min-width: 1024px) 32vw, (min-width: 600px) 40vw, 49vw',
			// Featured deal card: ~85vw phones, 40vw tablets, 28vw desktop, 400px cap.
			'deal-featured' => '(min-width: 1440px) 400px, (min-width: 1024px) 28vw, (min-width: 768px) 40vw, 85vw',
			'deal-small'    => '(min-width: 1024px) 173px, (min-width: 600px) 46vw, 32vw',
			// Menu / co-star photo rows: 120px, 160px from 1024.
			'row-photo'     => '(min-width: 1024px) 160px, 120px',
			'row-compact'   => '96px',
		);
		$sizes = $map[ $slot ] ?? '';

		/**
		 * Filter a counter image slot's `sizes` attribute.
		 *
		 * @param string $sizes Measured default.
		 * @param string $slot  Slot name.
		 */
		return (string) apply_filters( 'lafka_counter_image_sizes', $sizes, $slot );
	}
}

if ( ! function_exists( 'lafka_srcset_drop_tiny_candidates' ) ) {
	/**
	 * `wp_calculate_image_srcset`: drop candidates narrower than a usable
	 * image (a 1w / 10w entry from a broken intermediate size). Never empties
	 * the set.
	 *
	 * @param array<int|string,array<string,mixed>>|mixed $sources Candidates keyed by width.
	 * @return array<int|string,array<string,mixed>>|mixed
	 */
	function lafka_srcset_drop_tiny_candidates( $sources ) {
		if ( ! is_array( $sources ) || ! $sources ) {
			return $sources;
		}
		$min  = (int) apply_filters( 'lafka_srcset_min_width', 32 );
		$kept = array();
		foreach ( $sources as $width => $source ) {
			$value = is_array( $source ) && isset( $source['value'] ) ? (int) $source['value'] : (int) $width;
			$is_w  = ! is_array( $source ) || ! isset( $source['descriptor'] ) || 'w' === $source['descriptor'];
			if ( $is_w && $value < $min ) {
				continue;
			}
			$kept[ $width ] = $source;
		}
		return $kept ? $kept : $sources;
	}
}
add_filter( 'wp_calculate_image_srcset', 'lafka_srcset_drop_tiny_candidates' );
