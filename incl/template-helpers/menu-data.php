<?php
/**
 * GX4: menu data for the counter home, /menu/ and the category archive.
 *
 *   lafka_counter_settings()             the operator's Customizer choices (+ defaults)
 *   lafka_menu_top_categories()          top-level product_cat in WooCommerce order
 *   lafka_counter_resolve_sections()     PURE: deals / co-stars / the rest
 *   lafka_counter_section_products()     featured first, then menu order, then title
 *   lafka_counter_sections()             the homepage's section data (cached)
 *   lafka_category_tagline()             the short line under a section heading
 *
 * WooCommerce category order = the drag order in Products → Categories (term
 * meta `order`), with the name as the tiebreak. No category slug is hardcoded:
 * the deals category is a Customizer setting, else the first term whose slug
 * is in the filterable `lafka_counter_deals_slugs` list.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_counter_settings' ) ) {
	/**
	 * The counter home's settings (Customizer → Lafka — Home Page → Counter
	 * sections), clamped and typed.
	 *
	 * @return array<string,mixed>
	 */
	function lafka_counter_settings(): array {
		$clamp = static function ( $value, int $min, int $max, int $default ): int {
			$value = (int) $value;
			return $value < $min || $value > $max ? $default : $value;
		};
		$style = (string) get_theme_mod( 'lafka_counter_menu_style', 'compact' );
		return array(
			'costar_a'       => absint( get_theme_mod( 'lafka_counter_costar_a', 0 ) ),
			'costar_b'       => absint( get_theme_mod( 'lafka_counter_costar_b', 0 ) ),
			'hero_product_a' => absint( get_theme_mod( 'lafka_counter_hero_product_a', 0 ) ),
			'hero_product_b' => absint( get_theme_mod( 'lafka_counter_hero_product_b', 0 ) ),
			'deals_cat'      => absint( get_theme_mod( 'lafka_counter_deals_cat', 0 ) ),
			'featured_deal'  => absint( get_theme_mod( 'lafka_counter_featured_deal', 0 ) ),
			'deals_heading'  => (string) get_theme_mod( 'lafka_counter_deals_heading', __( "Today's deals", 'lafka' ) ),
			'deals_lead'     => (string) get_theme_mod( 'lafka_counter_deals_lead', '' ),
			'deals_limit'    => $clamp( get_theme_mod( 'lafka_counter_deals_limit', 6 ), 1, 12, 6 ),
			'costar_limit'   => $clamp( get_theme_mod( 'lafka_counter_costar_limit', 3 ), 1, 12, 3 ),
			'menu_limit'     => $clamp( get_theme_mod( 'lafka_counter_menu_limit', 6 ), 1, 24, 6 ),
			'menu_style'     => in_array( $style, array( 'compact', 'photo' ), true ) ? $style : 'compact',
			'menu_thumbs'    => (bool) get_theme_mod( 'lafka_counter_menu_thumbs', true ),
			'menu_heading'   => (string) get_theme_mod( 'lafka_counter_menu_heading', __( 'More from our menu', 'lafka' ) ),
			'jump_links'     => (bool) get_theme_mod( 'lafka_counter_jump_links', true ),
			'show_find_us'   => (bool) get_theme_mod( 'lafka_counter_show_find_us', true ),
		);
	}
}

if ( ! function_exists( 'lafka_menu_top_categories' ) ) {
	/**
	 * Top-level, non-empty product categories in WooCommerce order, minus the
	 * operator-excluded "Uncategorized" ids, through the existing
	 * `lafka_menu_landing_categories` filter (the /menu/ page's list).
	 *
	 * @param array<string,mixed> $args Extra get_terms() args.
	 * @return WP_Term[]
	 */
	function lafka_menu_top_categories( array $args = array() ): array {
		$term_args = array_merge(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'orderby'    => 'menu_order',
			),
			$args
		);
		if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
			$term_args['exclude'] = lafka_uncategorized_excluded_ids();
		}
		$terms = get_terms( $term_args );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			$terms = array();
		}
		return array_values( (array) apply_filters( 'lafka_menu_landing_categories', $terms ) );
	}
}

if ( ! function_exists( 'lafka_counter_resolve_sections' ) ) {
	/**
	 * PURE: split the ordered categories into the homepage sections.
	 *
	 *  - deals:   settings[deals_cat], else the first term whose slug is in
	 *             `lafka_counter_deals_slugs` (deals, combos, specials), else null;
	 *  - costars: settings[costar_a/_b], else the first two non-deal terms;
	 *  - rest:    everything else, in order.
	 * No term appears twice.
	 *
	 * @param WP_Term[]           $terms    Ordered top-level categories.
	 * @param array<string,mixed> $settings lafka_counter_settings() subset.
	 * @return array{deals:?WP_Term, costars:list<WP_Term>, rest:list<WP_Term>}
	 */
	function lafka_counter_resolve_sections( array $terms, array $settings ): array {
		$by_id = array();
		foreach ( $terms as $term ) {
			if ( is_object( $term ) && isset( $term->term_id ) ) {
				$by_id[ (int) $term->term_id ] = $term;
			}
		}

		$deals    = null;
		$deals_id = (int) ( $settings['deals_cat'] ?? 0 );
		if ( $deals_id && isset( $by_id[ $deals_id ] ) ) {
			$deals = $by_id[ $deals_id ];
		} else {
			$slugs = array_map( 'strval', (array) apply_filters( 'lafka_counter_deals_slugs', array( 'deals', 'combos', 'specials' ) ) );
			foreach ( $by_id as $term ) {
				if ( in_array( (string) $term->slug, $slugs, true ) ) {
					$deals = $term;
					break;
				}
			}
		}
		$used = $deals ? array( (int) $deals->term_id => true ) : array();

		$costars = array();
		foreach ( array( 'costar_a', 'costar_b' ) as $key ) {
			$id = (int) ( $settings[ $key ] ?? 0 );
			if ( $id && isset( $by_id[ $id ] ) && ! isset( $used[ $id ] ) ) {
				$costars[]   = $by_id[ $id ];
				$used[ $id ] = true;
			}
		}
		foreach ( $by_id as $id => $term ) {
			if ( count( $costars ) >= 2 ) {
				break;
			}
			if ( ! isset( $used[ $id ] ) ) {
				$costars[]   = $term;
				$used[ $id ] = true;
			}
		}
		// Keep the co-stars in WC order unless both were picked explicitly.
		if ( ! ( ( $settings['costar_a'] ?? 0 ) && ( $settings['costar_b'] ?? 0 ) ) ) {
			$order = array_flip( array_keys( $by_id ) );
			usort( $costars, static fn( $a, $b ) => $order[ (int) $a->term_id ] <=> $order[ (int) $b->term_id ] );
		}

		$rest = array();
		foreach ( $by_id as $id => $term ) {
			if ( ! isset( $used[ $id ] ) ) {
				$rest[] = $term;
			}
		}

		return array(
			'deals'   => $deals,
			'costars' => $costars,
			'rest'    => $rest,
		);
	}
}

if ( ! function_exists( 'lafka_counter_section_products' ) ) {
	/**
	 * Up to $limit published product ids of one category — featured first, then
	 * WooCommerce menu order, then title — plus the category's true total.
	 *
	 * @param WP_Term $term  Category.
	 * @param int     $limit Max rows.
	 * @return array{ids:list<int>, total:int}
	 */
	function lafka_counter_section_products( $term, int $limit ): array {
		if ( ! function_exists( 'wc_get_products' ) || ! is_object( $term ) ) {
			return array(
				'ids'   => array(),
				'total' => 0,
			);
		}
		$base = array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'category'   => array( (string) $term->slug ),
			'orderby'    => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'return'     => 'ids',
		);

		$featured = (array) wc_get_products(
			$base + array(
				'featured' => true,
				'limit'    => $limit,
			)
		);
		$featured = array_map( 'intval', $featured );
		$rest     = wc_get_products(
			$base + array(
				'exclude'  => $featured,
				'limit'    => max( 1, $limit - count( $featured ) ),
				'paginate' => true,
			)
		);
		$rest_ids = is_object( $rest ) && isset( $rest->products ) ? array_map( 'intval', (array) $rest->products ) : array();
		$total    = count( $featured ) + ( is_object( $rest ) && isset( $rest->total ) ? (int) $rest->total : count( $rest_ids ) );

		return array(
			'ids'   => array_slice( array_merge( $featured, $rest_ids ), 0, $limit ),
			'total' => $total,
		);
	}
}

if ( ! function_exists( 'lafka_counter_cache_key' ) ) {
	/**
	 * Transient key of the homepage section data: settings + locale + the
	 * WooCommerce product cache version + our own generation counter.
	 *
	 * @param array<string,mixed> $settings Settings.
	 */
	function lafka_counter_cache_key( array $settings ): string {
		$wc_version = class_exists( 'WC_Cache_Helper' ) ? (string) WC_Cache_Helper::get_transient_version( 'product' ) : '0';
		$locale     = function_exists( 'get_locale' ) ? get_locale() : '';
		$gen        = (int) get_option( 'lafka_counter_home_gen', 0 );
		return 'lafka_counter_home_v1_' . md5( wp_json_encode( $settings ) . '|' . $locale . '|' . $gen ) . '_' . $wc_version;
	}
}

if ( ! function_exists( 'lafka_counter_sections' ) ) {
	/**
	 * The homepage's section data (term objects + product ids), cached in a
	 * transient; bypassed in the Customizer preview.
	 *
	 * @return array{deals:?array{term:WP_Term,featured_id:int,ids:list<int>,total:int}, costars:list<array{term:WP_Term,ids:list<int>,total:int}>, rest:list<array{term:WP_Term,ids:list<int>,total:int}>}
	 */
	function lafka_counter_sections(): array {
		$settings = lafka_counter_settings();
		$preview  = function_exists( 'is_customize_preview' ) && is_customize_preview();
		$key      = lafka_counter_cache_key( $settings );
		if ( ! $preview ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$resolved = lafka_counter_resolve_sections( lafka_menu_top_categories(), $settings );
		$data     = array(
			'deals'   => null,
			'costars' => array(),
			'rest'    => array(),
		);

		if ( $resolved['deals'] ) {
			$deal_rows = lafka_counter_section_products( $resolved['deals'], (int) $settings['deals_limit'] );
			$ids       = $deal_rows['ids'];
			$featured  = (int) $settings['featured_deal'];
			if ( $featured && ! in_array( $featured, $ids, true ) ) {
				$product = function_exists( 'wc_get_product' ) ? wc_get_product( $featured ) : null;
				if ( ! $product ) {
					$featured = 0;
				} else {
					$ids = array_slice( array_merge( array( $featured ), $ids ), 0, (int) $settings['deals_limit'] );
				}
			}
			if ( $featured ) {
				$ids = array_values( array_unique( array_merge( array( $featured ), $ids ) ) );
			}
			if ( $ids ) {
				$data['deals'] = array(
					'term'        => $resolved['deals'],
					'featured_id' => $featured ? $featured : (int) $ids[0],
					'ids'         => $ids,
					'total'       => $deal_rows['total'],
				);
			}
		}
		foreach ( $resolved['costars'] as $term ) {
			$rows = lafka_counter_section_products( $term, (int) $settings['costar_limit'] );
			if ( $rows['ids'] ) {
				$data['costars'][] = array( 'term' => $term ) + $rows;
			}
		}
		foreach ( $resolved['rest'] as $term ) {
			$rows = lafka_counter_section_products( $term, (int) $settings['menu_limit'] );
			if ( $rows['ids'] ) {
				$data['rest'][] = array( 'term' => $term ) + $rows;
			}
		}

		if ( ! $preview ) {
			set_transient( $key, $data, defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 );
		}
		return $data;
	}
}

if ( ! function_exists( 'lafka_counter_prime' ) ) {
	/**
	 * Prime post, term and meta caches for every product the page renders (one
	 * query each instead of one per row).
	 *
	 * @param int[] $ids Product ids.
	 */
	function lafka_counter_prime( array $ids ): void {
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		if ( ! $ids ) {
			return;
		}
		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $ids, true, true );
		}
		if ( function_exists( 'update_object_term_cache' ) ) {
			update_object_term_cache( $ids, 'product' );
		}
	}
}

if ( ! function_exists( 'lafka_counter_bust_cache' ) ) {
	/** Invalidate every cached homepage section set (bumps the generation). */
	function lafka_counter_bust_cache(): void {
		update_option( 'lafka_counter_home_gen', (int) get_option( 'lafka_counter_home_gen', 0 ) + 1, true );
	}
}

if ( ! function_exists( 'lafka_counter_bust_on_term_meta' ) ) {
	/**
	 * Term-meta writes that change the homepage: WC's category drag order
	 * (`order`) and the category tagline (`lafka_tagline`).
	 *
	 * @param int    $meta_id   Meta id.
	 * @param int    $object_id Term id.
	 * @param string $meta_key  Key.
	 */
	function lafka_counter_bust_on_term_meta( $meta_id, $object_id, $meta_key ): void {
		if ( in_array( (string) $meta_key, array( 'order', 'lafka_tagline' ), true ) ) {
			lafka_counter_bust_cache();
		}
	}
}
foreach ( array( 'edited_product_cat', 'created_product_cat', 'delete_product_cat', 'customize_save_after', 'lafka_menu_data_changed' ) as $lafka_counter_bust_hook ) {
	add_action( $lafka_counter_bust_hook, 'lafka_counter_bust_cache' );
}
unset( $lafka_counter_bust_hook );
add_action( 'updated_term_meta', 'lafka_counter_bust_on_term_meta', 10, 3 );
add_action( 'added_term_meta', 'lafka_counter_bust_on_term_meta', 10, 3 );

if ( ! function_exists( 'lafka_category_tagline' ) ) {
	/**
	 * The short line under a category heading: term meta `lafka_tagline`
	 * (lafka-plugin's category field), else the first sentence of the category
	 * description (<= 110 chars, never cut mid-word), else ''.
	 *
	 * @param WP_Term $term Category.
	 */
	function lafka_category_tagline( $term ): string {
		if ( ! is_object( $term ) ) {
			return '';
		}
		$tagline = trim( (string) get_term_meta( (int) $term->term_id, 'lafka_tagline', true ) );
		if ( '' === $tagline ) {
			$text = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) ( $term->description ?? '' ) ) ) );
			if ( '' !== $text ) {
				$sentence = preg_match( '/^(.+?[.!?])(\s|$)/u', $text, $m ) ? $m[1] : $text;
				if ( mb_strlen( $sentence ) > 110 ) {
					$cut      = mb_substr( $sentence, 0, 110 );
					$space    = mb_strrpos( $cut, ' ' );
					$sentence = rtrim( false !== $space ? mb_substr( $cut, 0, $space ) : $cut, ' ,;:' ) . '…';
				}
				$tagline = $sentence;
			}
		}
		return (string) apply_filters( 'lafka_category_tagline', $tagline, $term );
	}
}
