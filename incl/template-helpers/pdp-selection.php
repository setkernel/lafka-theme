<?php
/**
 * PDP variation pickers: what is preselected, what the price line and the
 * add button say before the customer has chosen (GX QA M-10, M-11, M-18).
 *
 *   lafka_attribute_display_label( $label )     "size" → "Size" (operator lowercase)
 *   lafka_pdp_choose_label( $label, $name, $p ) "Choose size" (translatable, filterable)
 *   lafka_pdp_variation_rows( $product )        available variations as rows
 *   lafka_pdp_initial_selection( $product )     preselected chips + the resolved variation
 *   lafka_pdp_price_html( $product, $sel )      the price line before any choice
 *
 * Rules:
 *   - an attribute with ONE option is not a choice: its chip is preselected;
 *   - the operator's default attributes preselect ONLY when, together, they
 *     resolve to an available variation (a half default never shows a price
 *     that is not the one being bought);
 *   - otherwise the price line reads "From $min" (or the single price when
 *     every variation costs the same) — never a variation nobody picked.
 *
 * @package Lafka
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_attribute_display_label' ) ) {
	/**
	 * Capitalise an all-lowercase attribute label ("size" → "Size"), leaving an
	 * operator's deliberate casing ("Crust", "pH level") alone.
	 *
	 * @param string $label Attribute label.
	 */
	function lafka_attribute_display_label( string $label ): string {
		$label = trim( $label );
		if ( '' === $label || mb_strtolower( $label ) !== $label ) {
			return $label;
		}
		return mb_strtoupper( mb_substr( $label, 0, 1 ) ) . mb_substr( $label, 1 );
	}
}

if ( ! function_exists( 'lafka_pdp_choose_label' ) ) {
	/**
	 * The add button's prompt while an attribute is still unchosen:
	 * "Choose size", "Choose pieces". Filter `lafka_pdp_choose_label`
	 * ($text, $attribute_name, $label, $product) rewords it ("Choose a size").
	 *
	 * @param string     $label   Attribute label.
	 * @param string     $name    Attribute name (pa_size / Variety).
	 * @param WC_Product $product Product.
	 */
	function lafka_pdp_choose_label( string $label, string $name = '', $product = null ): string {
		/* translators: %s: attribute name in lower case, e.g. "size", "pieces". */
		$text = sprintf( __( 'Choose %s', 'lafka' ), mb_strtolower( trim( $label ) ) );
		return (string) apply_filters( 'lafka_pdp_choose_label', $text, $name, $label, $product );
	}
}

if ( ! function_exists( 'lafka_pdp_variation_rows' ) ) {
	/**
	 * Available variations: [ { id, price, attributes: { attribute_x: value|'' } } ].
	 *
	 * @param WC_Product $product Variable product.
	 * @return list<array{id:int, price:float, attributes:array<string,string>}>
	 */
	function lafka_pdp_variation_rows( $product ): array {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_available_variations' ) ) {
			return array();
		}
		$rows = array();
		foreach ( (array) $product->get_available_variations() as $v ) {
			if ( ! is_array( $v ) || empty( $v['variation_id'] ) ) {
				continue;
			}
			if ( isset( $v['is_purchasable'] ) && ! $v['is_purchasable'] ) {
				continue;
			}
			$attributes = array();
			foreach ( (array) ( $v['attributes'] ?? array() ) as $key => $value ) {
				$attributes[ strtolower( (string) $key ) ] = (string) $value;
			}
			$rows[] = array(
				'id'         => (int) $v['variation_id'],
				'price'      => (float) ( $v['display_price'] ?? 0 ),
				'attributes' => $attributes,
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'lafka_pdp_rows_match' ) ) {
	/**
	 * PURE: the first row matching a FULL selection ('' on a row = any), or null.
	 *
	 * @param list<array{id:int, price:float, attributes:array<string,string>}> $rows      Rows.
	 * @param array<string,string>                                           $selection attribute_x => value (lowercased keys).
	 * @param list<string>                                                   $keys      Every attribute key the product has.
	 * @return array{id:int, price:float, attributes:array<string,string>}|null
	 */
	function lafka_pdp_rows_match( array $rows, array $selection, array $keys ): ?array {
		foreach ( $keys as $key ) {
			if ( ! isset( $selection[ $key ] ) || '' === (string) $selection[ $key ] ) {
				return null;
			}
		}
		foreach ( $rows as $row ) {
			$ok = true;
			foreach ( $keys as $key ) {
				$have = (string) ( $row['attributes'][ $key ] ?? '' );
				if ( '' !== $have && $have !== (string) $selection[ $key ] ) {
					$ok = false;
					break;
				}
			}
			if ( $ok ) {
				return $row;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'lafka_pdp_initial_selection' ) ) {
	/**
	 * The chips preselected on load, and the variation they resolve to.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array{selection:array<string,string>, variation:?array{id:int, price:float, attributes:array<string,string>}}
	 */
	function lafka_pdp_initial_selection( $product ): array {
		$out = array(
			'selection' => array(),
			'variation' => null,
		);
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_variation_attributes' ) ) {
			return $out;
		}
		$attributes = (array) $product->get_variation_attributes();
		$rows       = lafka_pdp_variation_rows( $product );
		$defaults   = method_exists( $product, 'get_default_attributes' ) ? (array) $product->get_default_attributes() : array();
		$keys       = array();
		$singles    = array();
		$full       = array();
		foreach ( $attributes as $name => $options ) {
			$key     = 'attribute_' . sanitize_title( (string) $name );
			$keys[]  = $key;
			$options = array_values( array_map( 'strval', (array) $options ) );
			if ( 1 === count( $options ) ) {
				$singles[ $key ] = $options[0];
			}
			$default = (string) ( $defaults[ sanitize_title( (string) $name ) ] ?? ( $defaults[ (string) $name ] ?? '' ) );
			if ( '' !== $default && in_array( $default, $options, true ) ) {
				$full[ $key ] = $default;
			} elseif ( isset( $singles[ $key ] ) ) {
				$full[ $key ] = $singles[ $key ];
			}
		}

		$match = lafka_pdp_rows_match( $rows, $full, $keys );
		if ( null !== $match ) {
			$out['selection'] = $full;
			$out['variation'] = $match;
		} else {
			$out['selection'] = $singles;
			$out['variation'] = lafka_pdp_rows_match( $rows, $singles, $keys );
		}
		return (array) apply_filters( 'lafka_pdp_initial_selection', $out, $product );
	}
}

if ( ! function_exists( 'lafka_pdp_price_html' ) ) {
	/**
	 * The PDP price line before the customer chooses: the resolved variation's
	 * price, else "From $min" (or the one price every variation shares).
	 *
	 * @param WC_Product                                            $product Variable product.
	 * @param array{selection:array<string,string>, variation:?array} $initial lafka_pdp_initial_selection().
	 */
	function lafka_pdp_price_html( $product, array $initial ): string {
		if ( ! empty( $initial['variation'] ) ) {
			return wc_price( (float) $initial['variation']['price'] );
		}
		$prices = array_column( lafka_pdp_variation_rows( $product ), 'price' );
		if ( ! $prices ) {
			return wc_price( (float) $product->get_price() );
		}
		$min = min( $prices );
		if ( max( $prices ) - $min < 0.005 ) {
			return wc_price( $min );
		}
		/* translators: %s: the lowest price, formatted. */
		return sprintf( esc_html__( 'From %s', 'lafka' ), wc_price( $min ) );
	}
}
