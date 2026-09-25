<?php
/**
 * Age / ID notice for operator-chosen categories (GX QA M-25).
 *
 * Beer, wine, cannabis-adjacent or any other restricted items: the operator
 * lists category slugs (Customizer → Lafka — Menu Landing → Behaviour →
 * "Age-restricted categories"; empty by default, so nothing renders on a
 * fresh install) and every product in — or below — those categories, and
 * their menu sections, carry a short notice.
 *
 *   lafka_age_notice_slugs()             the configured category slugs
 *   lafka_age_notice_applies( $terms )   any term (or an ancestor) is restricted
 *   lafka_age_notice_text()              operator text, else the neutral default
 *   lafka_age_notice_html( $terms, $ctx) the notice, or ''
 *   lafka_product_age_notice_html( $p )  the notice for one product, or ''
 *
 * Filters: `lafka_age_notice_categories` (string[] slugs),
 * `lafka_age_notice_text` (string), `lafka_age_notice_html` (string, $terms, $ctx).
 * The cart / checkout line is lafka-plugin's and the order-path theme files'
 * concern; this helper is safe to call from them.
 *
 * @package Lafka
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_age_notice_slugs' ) ) {
	/** @return list<string> */
	function lafka_age_notice_slugs(): array {
		$raw   = (string) get_theme_mod( 'lafka_age_notice_categories', '' );
		$slugs = array();
		foreach ( preg_split( '/[\s,]+/', $raw ) as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}
		return array_values( array_unique( array_map( 'strval', (array) apply_filters( 'lafka_age_notice_categories', $slugs ) ) ) );
	}
}

if ( ! function_exists( 'lafka_age_notice_applies' ) ) {
	/**
	 * Whether any of these product categories — or one of their ancestors —
	 * is age-restricted.
	 *
	 * @param array<int, WP_Term|string> $terms Terms or slugs.
	 */
	function lafka_age_notice_applies( array $terms ): bool {
		$restricted = lafka_age_notice_slugs();
		if ( empty( $restricted ) ) {
			return false;
		}
		foreach ( $terms as $term ) {
			$slug = is_object( $term ) ? (string) ( $term->slug ?? '' ) : (string) $term;
			if ( in_array( $slug, $restricted, true ) ) {
				return true;
			}
			if ( is_object( $term ) && ! empty( $term->term_id ) && function_exists( 'get_ancestors' ) && function_exists( 'get_term' ) ) {
				foreach ( (array) get_ancestors( (int) $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
					$ancestor = get_term( (int) $ancestor_id, 'product_cat' );
					if ( is_object( $ancestor ) && in_array( (string) ( $ancestor->slug ?? '' ), $restricted, true ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}

if ( ! function_exists( 'lafka_age_notice_text' ) ) {
	/** The notice copy: the operator's text, else a neutral default. */
	function lafka_age_notice_text(): string {
		$text = trim( (string) get_theme_mod( 'lafka_age_notice_text', '' ) );
		if ( '' === $text ) {
			$text = __( 'Age-restricted item. Valid photo ID is required at pickup or delivery.', 'lafka' );
		}
		return (string) apply_filters( 'lafka_age_notice_text', $text );
	}
}

if ( ! function_exists( 'lafka_age_notice_html' ) ) {
	/**
	 * The notice markup for a set of categories, or '' when none is restricted.
	 *
	 * @param array<int, WP_Term|string> $terms   Categories (terms or slugs).
	 * @param string                     $context menu | pdp.
	 */
	function lafka_age_notice_html( array $terms, string $context = 'pdp' ): string {
		if ( ! lafka_age_notice_applies( $terms ) ) {
			return '';
		}
		$context = sanitize_key( $context );
		$html    = sprintf(
			'<p class="lafka-age-notice lafka-age-notice--%1$s"><span class="lafka-age-notice__badge" aria-hidden="true">%2$s</span> %3$s</p>',
			esc_attr( $context ),
			/* translators: short badge on age-restricted items (photo ID). */
			esc_html__( 'ID', 'lafka' ),
			esc_html( lafka_age_notice_text() )
		);
		return (string) apply_filters( 'lafka_age_notice_html', $html, $terms, $context );
	}
}

if ( ! function_exists( 'lafka_product_age_notice_html' ) ) {
	/**
	 * The notice for one product (its categories and their ancestors).
	 *
	 * @param WC_Product|null $product Product.
	 */
	function lafka_product_age_notice_html( $product ): string {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) || empty( lafka_age_notice_slugs() ) ) {
			return '';
		}
		$terms = function_exists( 'wp_get_post_terms' ) ? wp_get_post_terms( (int) $product->get_id(), 'product_cat' ) : array();
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}
		return lafka_age_notice_html( $terms, 'pdp' );
	}
}
