<?php
/**
 * GX4: size-price columns + the 2-tap size-chooser payload.
 *
 * A counter product row shows every size with its price ("Small $13.95 ·
 * Medium $19.45 · …") and a worded Add. Both the columns and the chooser are
 * computed from WooCommerce's own data — nothing is inferred from names:
 *
 *   lafka_price_columns( $product )  -> single | columns | from (+ the columns)
 *   lafka_chooser_payload( $product ) -> the JSON js/lafka-size-chooser.js reads
 *   lafka_chooser_register() / lafka_chooser_print_data() -> one JSON island
 *                                      per page (wp_footer)
 *
 * Filters:
 *   lafka_price_columns_max( int $max = 4, WC_Product $p )
 *   lafka_price_columns( array $result, WC_Product $p )
 *   lafka_chooser_payload( array $payload, WC_Product $p )
 *   lafka_product_has_addons( bool $has, WC_Product $p )
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_price_plain' ) ) {
	/**
	 * A price as plain text in the store currency format ("$19.45"), for
	 * compact rows and accessible names. With $trim_whole, a whole amount drops
	 * its zero cents ("$10").
	 *
	 * @param float $amount     Amount (display price, i.e. tax handled by WC).
	 * @param bool  $trim_whole Drop ".00" on whole amounts.
	 */
	function lafka_price_plain( float $amount, bool $trim_whole = false ): string {
		if ( function_exists( 'wc_price' ) ) {
			$text = html_entity_decode( wp_strip_all_tags( (string) wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
		} else {
			$text = '$' . number_format( $amount, 2 );
		}
		$text = trim( $text );
		if ( $trim_whole && abs( $amount - round( $amount ) ) < 0.005 ) {
			$sep  = function_exists( 'wc_get_price_decimal_separator' ) ? (string) wc_get_price_decimal_separator() : '.';
			$text = (string) preg_replace( '/' . preg_quote( $sep, '/' ) . '0+(?=\D*$)/', '', $text );
		}
		return $text;
	}
}

if ( ! function_exists( 'lafka_price_columns_attribute_key' ) ) {
	/**
	 * The variation meta key for an attribute name as returned by
	 * WC_Product_Variable::get_variation_attributes() ("pa_size" or a custom
	 * attribute's display name "Size").
	 *
	 * @param string $attribute Attribute name.
	 */
	function lafka_price_columns_attribute_key( string $attribute ): string {
		return 'attribute_' . sanitize_title( $attribute );
	}
}

if ( ! function_exists( 'lafka_price_columns_options' ) ) {
	/**
	 * Ordered options (value => label) of one variation attribute:
	 *   - taxonomy attributes: wc_get_product_terms(), which honours the
	 *     attribute's "custom ordering" (NOT variation order, which is often
	 *     alphabetical L/M/S/XL);
	 *   - custom attributes: the options in the order the operator saved them.
	 *
	 * @param WC_Product $product   Product.
	 * @param string     $attribute Attribute name.
	 * @return array<string,string>
	 */
	function lafka_price_columns_options( $product, string $attribute ): array {
		$out = array();
		if ( 0 === strpos( $attribute, 'pa_' ) && function_exists( 'wc_get_product_terms' ) ) {
			foreach ( (array) wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) ) as $term ) {
				if ( is_object( $term ) && isset( $term->slug ) ) {
					$out[ (string) $term->slug ] = (string) $term->name;
				}
			}
			return $out;
		}
		$attributes = (array) $product->get_attributes();
		$key        = sanitize_title( $attribute );
		$row        = $attributes[ $key ] ?? ( $attributes[ $attribute ] ?? null );
		if ( is_object( $row ) && method_exists( $row, 'get_options' ) ) {
			foreach ( (array) $row->get_options() as $option ) {
				$out[ (string) $option ] = (string) $option;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'lafka_price_columns_rows' ) ) {
	/**
	 * Visible variation rows: vid => [ price, attributes, purchasable ].
	 * Prices come from get_variation_prices( true ) — WooCommerce's cached
	 * DISPLAY prices (hidden / price-less variations excluded). Out-of-stock
	 * variations stay in that cache unless "hide out of stock" is on, so each
	 * row records whether it can be bought right now.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int,array{price:float,attributes:array<string,string>,purchasable:bool}>
	 */
	function lafka_price_columns_rows( $product ): array {
		$prices = (array) ( $product->get_variation_prices( true )['price'] ?? array() );
		$rows   = array();
		foreach ( $prices as $vid => $price ) {
			// WC keeps out-of-stock variations in the price cache unless "hide out
			// of stock" is on: record whether this one can be bought right now.
			$variation = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $vid ) : null;
			$rows[ (int) $vid ] = array(
				'price'       => (float) $price,
				'attributes'  => function_exists( 'wc_get_product_variation_attributes' ) ? (array) wc_get_product_variation_attributes( (int) $vid ) : array(),
				'purchasable' => ! is_object( $variation ) || ( $variation->is_purchasable() && $variation->is_in_stock() ),
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'lafka_price_columns' ) ) {
	/**
	 * The price display of one product row.
	 *
	 * Algorithm:
	 *  1. A non-variable product: `single` at wc_get_price_to_display().
	 *  2. Variable: rows from WC's cached display prices (visible + purchasable).
	 *  3. For each variation attribute A, the MIN price per value of A (a
	 *     variation whose A is empty — "any" — is skipped). A's score is the
	 *     number of distinct mins; the highest score is the price-driving
	 *     attribute (ties -> the earlier attribute in product order). So pizza
	 *     crust x size picks size, and each size shows its cheapest crust.
	 *  4. Score <= 1: every price equal -> `single`, else `from` (the minimum).
	 *  5. Columns in term order (taxonomy) or saved option order (custom).
	 *  6. More than lafka_price_columns_max (4) columns -> `from`.
	 *
	 * @param WC_Product           $product Product.
	 * @param array<string, mixed> $args    `max_columns` (int).
	 * @return array{type:string, price:float, attribute?:string, attribute_label?:string, columns?:list<array{value:string,label:string,price:float}>}
	 */
	function lafka_price_columns( $product, array $args = array() ): array {
		$max = (int) apply_filters( 'lafka_price_columns_max', (int) ( $args['max_columns'] ?? 4 ), $product );

		if ( ! method_exists( $product, 'is_type' ) || ! $product->is_type( 'variable' ) || ! method_exists( $product, 'get_variation_prices' ) ) {
			$result = array(
				'type'  => 'single',
				'price' => (float) ( function_exists( 'wc_get_price_to_display' ) ? wc_get_price_to_display( $product ) : $product->get_price() ),
			);
			return (array) apply_filters( 'lafka_price_columns', $result, $product );
		}

		$rows = lafka_price_columns_rows( $product );
		if ( ! $rows ) {
			$result = array(
				'type'  => 'single',
				'price' => (float) $product->get_price(),
			);
			return (array) apply_filters( 'lafka_price_columns', $result, $product );
		}
		$all_prices = array_column( $rows, 'price' );
		$min_price  = (float) min( $all_prices );

		$best       = '';
		$best_score = 0;
		$best_mins  = array();
		foreach ( array_keys( (array) $product->get_variation_attributes() ) as $attribute ) {
			$attribute = (string) $attribute;
			$key       = lafka_price_columns_attribute_key( $attribute );
			$mins      = array();
			foreach ( $rows as $row ) {
				$value = isset( $row['attributes'][ $key ] ) ? (string) $row['attributes'][ $key ] : '';
				if ( '' === $value ) {
					continue; // "any" value.
				}
				$mins[ $value ] = isset( $mins[ $value ] ) ? min( $mins[ $value ], $row['price'] ) : $row['price'];
			}
			$score = count( array_unique( array_map( static fn( $p ) => number_format( $p, 2, '.', '' ), $mins ) ) );
			if ( $score > $best_score ) {
				$best       = $attribute;
				$best_score = $score;
				$best_mins  = $mins;
			}
		}

		if ( $best_score <= 1 ) {
			$result = array(
				'type'  => ( max( $all_prices ) - $min_price ) < 0.005 ? 'single' : 'from',
				'price' => $min_price,
			);
			return (array) apply_filters( 'lafka_price_columns', $result, $product );
		}

		$columns = array();
		foreach ( lafka_price_columns_options( $product, $best ) as $value => $label ) {
			if ( isset( $best_mins[ $value ] ) ) {
				$columns[] = array(
					'value' => (string) $value,
					'label' => $label,
					'price' => (float) $best_mins[ $value ],
				);
				unset( $best_mins[ $value ] );
			}
		}
		foreach ( $best_mins as $value => $price ) { // values missing from the term list keep their order.
			$columns[] = array(
				'value' => (string) $value,
				'label' => (string) $value,
				'price' => (float) $price,
			);
		}

		if ( count( $columns ) > $max ) {
			$result = array(
				'type'  => 'from',
				'price' => $min_price,
			);
			return (array) apply_filters( 'lafka_price_columns', $result, $product );
		}

		$result = array(
			'type'            => 'columns',
			'price'           => $min_price,
			'attribute'       => $best,
			'attribute_label' => function_exists( 'wc_attribute_label' ) ? (string) wc_attribute_label( $best, $product ) : $best,
			'columns'         => $columns,
		);
		return (array) apply_filters( 'lafka_price_columns', $result, $product );
	}
}

if ( ! function_exists( 'lafka_product_has_addons' ) ) {
	/**
	 * Whether a product has ANY add-on groups (drives the chooser's
	 * "More choices" link). Reads the plugin engine when present.
	 *
	 * @param WC_Product $product Product.
	 */
	function lafka_product_has_addons( $product ): bool {
		$has = false;
		if ( class_exists( 'Lafka_Engine_Helper' ) && method_exists( 'Lafka_Engine_Helper', 'get_product_addons' ) ) {
			$has = ! empty( Lafka_Engine_Helper::get_product_addons( (int) $product->get_id() ) );
		}
		return (bool) apply_filters( 'lafka_product_has_addons', $has, $product );
	}
}

if ( ! function_exists( 'lafka_chooser_payload' ) ) {
	/**
	 * The size-chooser payload for one product.
	 *
	 * `addable` is false — the row renders "Choose" (to the product page)
	 * instead of Add — when:
	 *   - `any_attribute`   a variation leaves an attribute as "any" (the
	 *                       customer must pick it on the product page);
	 *   - `required_addons` lafka_product_has_required_addons() (lafka-plugin);
	 *   - `unavailable`     not purchasable or out of stock;
	 *   - `no_variations`   a variable product with no visible variation.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	function lafka_chooser_payload( $product ): array {
		$id       = (int) $product->get_id();
		$variable = $product->is_type( 'variable' ) && method_exists( $product, 'get_variation_prices' );
		$payload  = array(
			'id'         => $id,
			'name'       => wp_strip_all_tags( (string) $product->get_name() ),
			'url'        => (string) $product->get_permalink(),
			'mode'       => $variable ? 'chooser' : 'direct',
			'primary'    => '',
			'attributes' => array(),
			'variations' => array(),
			'addable'    => true,
			'reason'     => '',
			'has_addons' => lafka_product_has_addons( $product ),
		);

		$reason = '';
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			$reason = 'unavailable';
		} elseif ( function_exists( 'lafka_product_has_required_addons' ) && lafka_product_has_required_addons( $id ) ) {
			$reason = 'required_addons';
		}

		if ( $variable ) {
			$rows    = lafka_price_columns_rows( $product );
			$columns = lafka_price_columns( $product );
			$names   = array_keys( (array) $product->get_variation_attributes() );
			$primary = isset( $columns['attribute'] ) ? (string) $columns['attribute'] : ( $names ? (string) $names[0] : '' );
			$defaults = (array) $product->get_default_attributes();

			foreach ( $rows as $row ) {
				foreach ( $names as $name ) {
					if ( '' === (string) ( $row['attributes'][ lafka_price_columns_attribute_key( (string) $name ) ] ?? '' ) ) {
						$reason = $reason ? $reason : 'any_attribute';
					}
				}
			}
			// Only sizes that can be bought now are offered (sold-out ones read
			// "Not available"); the price columns above still list every size.
			$rows = array_filter( $rows, static fn( $row ) => ! empty( $row['purchasable'] ) );
			if ( ! $rows ) {
				$reason = $reason ? $reason : 'no_variations';
			}

			foreach ( $rows as $vid => $row ) {
				$payload['variations'][] = array(
					'id'         => (int) $vid,
					'price'      => $row['price'],
					'price_text' => lafka_price_plain( $row['price'] ),
					'attributes' => $row['attributes'],
				);
			}

			// Secondary attributes first (radio groups), the primary last (the buttons).
			$selection = array();
			foreach ( $names as $name ) {
				$name    = (string) $name;
				$options = array();
				foreach ( lafka_price_columns_options( $product, $name ) as $value => $label ) {
					$options[] = array(
						'value' => (string) $value,
						'label' => $label,
					);
				}
				$values  = array_column( $options, 'value' );
				$default = isset( $defaults[ sanitize_title( $name ) ] ) ? (string) $defaults[ sanitize_title( $name ) ] : ( $defaults[ $name ] ?? '' );
				if ( ! in_array( $default, $values, true ) ) {
					$default = $values ? $values[0] : '';
				}
				$key                = lafka_price_columns_attribute_key( $name );
				$selection[ $key ]  = $default;
				$payload['attributes'][] = array(
					'key'     => $key,
					'name'    => $name,
					'label'   => function_exists( 'wc_attribute_label' ) ? (string) wc_attribute_label( $name, $product ) : $name,
					'primary' => $name === $primary,
					'default' => $default,
					'options' => $options,
				);
			}

			// Availability of each primary option under the default secondary selection.
			$primary_key = lafka_price_columns_attribute_key( $primary );
			foreach ( $payload['attributes'] as $i => $attr ) {
				if ( ! $attr['primary'] ) {
					continue;
				}
				foreach ( $attr['options'] as $j => $option ) {
					$want                                                     = array( $primary_key => $option['value'] ) + $selection;
					$payload['attributes'][ $i ]['options'][ $j ]['available'] = null !== lafka_chooser_match( $rows, $want );
				}
			}
			usort(
				$payload['attributes'],
				static function ( $a, $b ) {
					return (int) $a['primary'] - (int) $b['primary'];
				}
			);
			$payload['primary'] = $primary_key;
		}

		if ( '' !== $reason ) {
			$payload['addable'] = false;
			$payload['reason']  = $reason;
		}

		return (array) apply_filters( 'lafka_chooser_payload', $payload, $product );
	}
}

if ( ! function_exists( 'lafka_chooser_match' ) ) {
	/**
	 * The variation id matching a full attribute selection, or null.
	 *
	 * @param array<int,array{price:float,attributes:array<string,string>}> $rows      Variation rows.
	 * @param array<string,string>                                          $selection attribute_* => value.
	 */
	function lafka_chooser_match( array $rows, array $selection ): ?int {
		foreach ( $rows as $vid => $row ) {
			$ok = true;
			foreach ( $selection as $key => $value ) {
				$have = (string) ( $row['attributes'][ $key ] ?? '' );
				if ( '' !== $have && $have !== $value ) {
					$ok = false;
					break;
				}
			}
			if ( $ok ) {
				return (int) $vid;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'lafka_chooser_register' ) ) {
	/**
	 * Queue one product's payload for the page's JSON island. Idempotent by id.
	 *
	 * @param WC_Product $product Product.
	 */
	function lafka_chooser_register( $product ): void {
		$GLOBALS['lafka_chooser_registry'][ (int) $product->get_id() ] = lafka_chooser_payload( $product );
	}
}

if ( ! function_exists( 'lafka_chooser_reset' ) ) {
	/** Empty the registry (tests / nested renders). */
	function lafka_chooser_reset(): void {
		$GLOBALS['lafka_chooser_registry'] = array();
	}
}

if ( ! function_exists( 'lafka_chooser_print_data' ) ) {
	/**
	 * Print the registered payloads as ONE `application/json` island plus the
	 * dialog template (wp_footer). Nothing prints when no row registered.
	 */
	function lafka_chooser_print_data(): void {
		$registry = $GLOBALS['lafka_chooser_registry'] ?? array();
		if ( empty( $registry ) ) {
			return;
		}
		$json = wp_json_encode( $registry, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE );
		echo '<script type="application/json" id="lafka-chooser-data">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with every HTML-significant char hex-escaped.
		if ( function_exists( 'get_template_part' ) ) {
			get_template_part( 'partials/counter/chooser-template' );
		}
	}
}
add_action( 'wp_footer', 'lafka_chooser_print_data', 5 );
