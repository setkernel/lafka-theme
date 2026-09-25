<?php
/**
 * Shared WordPress shims for the unit suite.
 *
 * Every test runs in one PHP process, so a global function can only be defined
 * once. These are the WordPress (and a few lafka-plugin) functions that several
 * test files need, each backed by a $GLOBALS store instead of hard-coded
 * behaviour. Tests configure the store in setUp(); ResetWpShimsExtension resets
 * it before every test, so no state leaks between tests or files.
 *
 * Stores (all reset per test):
 *   lafka_test_theme_mods          get_theme_mod() values
 *   lafka_test_theme_mod_resolver  optional callable( $name, $default ) that
 *                                  answers get_theme_mod() instead
 *   lafka_test_options             get_option() / update_option()
 *   lafka_test_filters             hook registry: [hook][priority][] = callback
 *                                  (reset to what the test files registered at
 *                                  load time, not to empty)
 *   lafka_test_registered          wp_register_style() calls, by handle
 *   lafka_test_inline              wp_add_inline_style() data, by handle
 *   lafka_test_cache / _transients object-cache and transient stores
 *   lafka_test_counters            call counters (cache/transient reads, writes)
 *   lafka_test_is_preview          is_customize_preview()
 *   lafka_test_is_admin            is_admin()
 *   lafka_test_home_url            home_url() base (default http://example.test)
 *   lafka_test_tpl_dir / _tpl_uri  template directory path / URI
 *   lafka_test_restaurant_info     lafka_get_restaurant_info()
 *   lafka_test_attachments         wp_get_attachment_image(): [ id => src ]
 *   lafka_test_post_meta           get_post_meta(): [ post_id ][ key ] = value
 *   lafka_test_pdp_redesign        lafka_pdp_redesign_enabled() (default true)
 *   lafka_test_free_delivery_threshold  lafka_get_free_delivery_threshold()
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

// ---------------------------------------------------------------- hooks ----

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['lafka_test_filters'][ $hook ][ (int) $priority ][] = $callback;
		return true;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
}
if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $hook, $callback, $priority = 10 ) {
		$removed = false;
		foreach ( $GLOBALS['lafka_test_filters'][ $hook ][ (int) $priority ] ?? array() as $i => $registered ) {
			if ( $registered === $callback ) {
				unset( $GLOBALS['lafka_test_filters'][ $hook ][ (int) $priority ][ $i ] );
				$removed = true;
			}
		}
		return $removed;
	}
}
if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		return remove_filter( $hook, $callback, $priority );
	}
}
if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		$by_priority = $GLOBALS['lafka_test_filters'][ $hook ] ?? array();
		if ( false === $callback ) {
			foreach ( $by_priority as $callbacks ) {
				if ( $callbacks ) {
					return true;
				}
			}
			return false;
		}
		foreach ( $by_priority as $priority => $callbacks ) {
			if ( in_array( $callback, $callbacks, true ) ) {
				return $priority;
			}
		}
		return false;
	}
}
if ( ! function_exists( 'has_action' ) ) {
	function has_action( $hook, $callback = false ) {
		return has_filter( $hook, $callback );
	}
}
if ( ! function_exists( 'lafka_test_callbacks' ) ) {
	/** Registered callbacks for a hook, in priority order. */
	function lafka_test_callbacks( $hook ) {
		$by_priority = $GLOBALS['lafka_test_filters'][ $hook ] ?? array();
		ksort( $by_priority );
		return $by_priority ? array_merge( ...array_values( $by_priority ) ) : array();
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value = null, ...$args ) {
		foreach ( lafka_test_callbacks( $hook ) as $callback ) {
			$value = call_user_func_array( $callback, array_merge( array( $value ), $args ) );
		}
		return $value;
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		foreach ( lafka_test_callbacks( $hook ) as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

if ( ! function_exists( '__return_true' ) ) {
	function __return_true() {
		return true;
	}
}
if ( ! function_exists( '__return_false' ) ) {
	function __return_false() {
		return false;
	}
}

// ------------------------------------------------------ settings stores ----

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default = false ) {
		$resolver = $GLOBALS['lafka_test_theme_mod_resolver'] ?? null;
		if ( is_callable( $resolver ) ) {
			return $resolver( $name, $default );
		}
		$mods = $GLOBALS['lafka_test_theme_mods'] ?? array();
		return array_key_exists( $name, $mods ) ? $mods[ $name ] : $default;
	}
}
if ( ! function_exists( 'set_theme_mod' ) ) {
	function set_theme_mod( $name, $value ) {
		$GLOBALS['lafka_test_theme_mods'][ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_theme_mods' ) ) {
	function get_theme_mods() {
		return $GLOBALS['lafka_test_theme_mods'] ?? array();
	}
}
if ( ! function_exists( 'remove_theme_mod' ) ) {
	function remove_theme_mod( $name ) {
		unset( $GLOBALS['lafka_test_theme_mods'][ $name ] );
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		$options = $GLOBALS['lafka_test_options'] ?? array();
		return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['lafka_test_options'][ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		$GLOBALS['lafka_test_counters']['cache_get'] = ( $GLOBALS['lafka_test_counters']['cache_get'] ?? 0 ) + 1;
		return $GLOBALS['lafka_test_cache'][ $group ][ $key ] ?? false;
	}
}
if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
		$GLOBALS['lafka_test_counters']['cache_set'] = ( $GLOBALS['lafka_test_counters']['cache_set'] ?? 0 ) + 1;
		$GLOBALS['lafka_test_cache'][ $group ][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		$GLOBALS['lafka_test_counters']['transient_get'] = ( $GLOBALS['lafka_test_counters']['transient_get'] ?? 0 ) + 1;
		return $GLOBALS['lafka_test_transients'][ $key ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['lafka_test_counters']['transient_set'] = ( $GLOBALS['lafka_test_counters']['transient_set'] ?? 0 ) + 1;
		$GLOBALS['lafka_test_transients'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'is_customize_preview' ) ) {
	function is_customize_preview() {
		return (bool) ( $GLOBALS['lafka_test_is_preview'] ?? false );
	}
}

// ------------------------------------------------------ request state ----

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return (bool) ( $GLOBALS['lafka_test_is_admin'] ?? false );
	}
}
if ( ! function_exists( 'is_feed' ) ) {
	function is_feed() {
		return false;
	}
}

// ------------------------------------------------------ theme / assets ----

if ( ! function_exists( 'get_template' ) ) {
	function get_template() {
		return 'lafka';
	}
}
if ( ! function_exists( 'get_stylesheet' ) ) {
	function get_stylesheet() {
		return 'lafka';
	}
}
if ( ! function_exists( 'get_template_directory' ) ) {
	function get_template_directory() {
		return $GLOBALS['lafka_test_tpl_dir'] ?? dirname( __DIR__, 2 );
	}
}
if ( ! function_exists( 'get_template_directory_uri' ) ) {
	function get_template_directory_uri() {
		return $GLOBALS['lafka_test_tpl_uri'] ?? 'http://example.test/wp-content/themes/lafka';
	}
}
if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme( $stylesheet = null ) {
		return new class() {
			public function get( $header ) {
				return 'Version' === $header ? '9.9.9' : '';
			}
		};
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return ( $GLOBALS['lafka_test_home_url'] ?? 'http://example.test' ) . $path;
	}
}
if ( ! function_exists( 'wp_register_style' ) ) {
	function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		$GLOBALS['lafka_test_registered'][ $handle ] = array(
			'src'  => $src,
			'deps' => $deps,
			'ver'  => $ver,
		);
		return true;
	}
}
if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( $handle, $data ) {
		$GLOBALS['lafka_test_inline'][ $handle ][] = $data;
		return true;
	}
}
if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
		return 'https://example.test/wp-content/uploads/lafka-fixture-' . (int) $attachment_id . '.jpg';
	}
}

// ------------------------------------------- formatting / i18n / escaping ----

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo $text; // phpcs:ignore
	}
}
if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo $text; // phpcs:ignore
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $context = 'display' ) {
		return $url;
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, (int) $component );
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( (string) $value, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) $text ) );
	}
}
if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return 1 === (int) $number ? $single : $plural;
	}
}
if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals );
	}
}
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}
if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	/** Renders attachments registered in $GLOBALS['lafka_test_attachments'][ id ] = src. */
	function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
		$src = $GLOBALS['lafka_test_attachments'][ (int) $attachment_id ] ?? '';
		if ( '' === $src ) {
			return '';
		}
		$html = '<img src="' . $src . '"';
		foreach ( (array) $attr as $name => $value ) {
			$html .= ' ' . $name . '="' . $value . '"';
		}
		return $html . '>';
	}
}
if ( ! function_exists( 'get_post_meta' ) ) {
	/** Reads $GLOBALS['lafka_test_post_meta'][ post_id ][ key ]. */
	function get_post_meta( $post_id, $key = '', $single = false ) {
		$meta = $GLOBALS['lafka_test_post_meta'][ (int) $post_id ] ?? array();
		if ( '' === $key ) {
			return $meta;
		}
		if ( ! array_key_exists( $key, $meta ) ) {
			return $single ? '' : array();
		}
		return $single ? $meta[ $key ] : array( $meta[ $key ] );
	}
}
if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = false, $gmt = false ) {
		return date( $format, false === $timestamp ? time() : (int) $timestamp ); // phpcs:ignore
	}
}
if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return 'en_US';
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		return '2026-07-06 00:00:00';
	}
}

// --------------------------------------------------- lafka-plugin API ----

if ( ! function_exists( 'lafka_get_restaurant_info' ) ) {
	function lafka_get_restaurant_info() {
		return $GLOBALS['lafka_test_restaurant_info'] ?? array();
	}
}

// GX4 (lafka-plugin gx4p contracts, store-backed; reset per test):
//   lafka_test_required_addons   [ product_id => bool ]
//   lafka_test_fulfilment_modes  list<'pickup'|'delivery'>
//   lafka_test_fulfilment_pref   'pickup'|'delivery'|''
//   lafka_test_order_hours_on    is_lafka_order_hours() (module on/off)
if ( ! function_exists( 'is_lafka_order_hours' ) ) {
	function is_lafka_order_hours( $lafka_options = null ) {
		return (bool) ( $GLOBALS['lafka_test_order_hours_on'] ?? false );
	}
}
if ( ! function_exists( 'lafka_product_has_required_addons' ) ) {
	function lafka_product_has_required_addons( int $product_id ): bool {
		return (bool) ( $GLOBALS['lafka_test_required_addons'][ $product_id ] ?? false );
	}
}
if ( ! function_exists( 'lafka_fulfilment_modes' ) ) {
	function lafka_fulfilment_modes(): array {
		return $GLOBALS['lafka_test_fulfilment_modes'] ?? array( 'pickup', 'delivery' );
	}
}
if ( ! function_exists( 'lafka_fulfilment_preference' ) ) {
	function lafka_fulfilment_preference(): string {
		return (string) ( $GLOBALS['lafka_test_fulfilment_pref'] ?? '' );
	}
}

if ( ! function_exists( 'lafka_get_free_delivery_threshold' ) ) {
	function lafka_get_free_delivery_threshold() {
		return $GLOBALS['lafka_test_free_delivery_threshold'] ?? 0;
	}
}
if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) ) {
	function lafka_pdp_redesign_enabled() {
		return (bool) ( $GLOBALS['lafka_test_pdp_redesign'] ?? true );
	}
}

// ------------------------------------------- GX4: WooCommerce catalogue ----
//
// Stores (reset per test):
//   lafka_test_variations     wc_get_product_variation_attributes(): [ vid => [ 'attribute_pa_size' => 'medium' ] ]
//                             (WC_Product_Variable's constructor fills it from its rows)
//   lafka_test_attr_terms     wc_get_product_terms(): [ taxonomy => list<WP_Term> ] in attribute (custom) order
//   lafka_test_attr_labels    wc_attribute_label(): [ name => label ] (else derived: pa_size -> Size)
//   lafka_test_products       wc_get_product(): [ id => WC_Product ]

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
		$title = strtolower( trim( wp_strip_all_tags( (string) $title ) ) );
		$title = (string) preg_replace( '/[^a-z0-9_\-]+/', '-', $title );
		return trim( $title, '-' );
	}
}
if ( ! function_exists( 'wc_get_product_variation_attributes' ) ) {
	function wc_get_product_variation_attributes( $variation_id ) {
		return $GLOBALS['lafka_test_variations'][ (int) $variation_id ] ?? array();
	}
}
if ( ! function_exists( 'wc_get_product_terms' ) ) {
	function wc_get_product_terms( $product_id, $taxonomy, $args = array() ) {
		return $GLOBALS['lafka_test_attr_terms'][ $taxonomy ] ?? array();
	}
}
if ( ! function_exists( 'wc_attribute_label' ) ) {
	function wc_attribute_label( $name, $product = '' ) {
		if ( isset( $GLOBALS['lafka_test_attr_labels'][ $name ] ) ) {
			return $GLOBALS['lafka_test_attr_labels'][ $name ];
		}
		return ucfirst( str_replace( array( 'pa_', '-', '_' ), array( '', ' ', ' ' ), (string) $name ) );
	}
}
if ( ! function_exists( 'wc_get_price_to_display' ) ) {
	function wc_get_price_to_display( $product, $args = array() ) {
		return (float) ( $args['price'] ?? $product->get_price() );
	}
}
if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $product_id = false ) {
		return $GLOBALS['lafka_test_products'][ (int) $product_id ] ?? null;
	}
}

// ------------------------------------------- GX4: terms, catalogue, parts ----
//
// Stores (reset per test):
//   lafka_test_terms           get_terms(): [ taxonomy => list<WP_Term> ] (sorted by ->order, then ->name)
//   lafka_test_term_meta       get_term_meta(): [ term_id ][ key ] = value
//   lafka_test_catalog         wc_get_products(): list<WC_Product> in menu order; a product's
//                              data['cats'] lists its product_cat slugs
//   lafka_test_parts_live      get_template_part() includes the real part when true
//                              (default false = no-op, which older render tests rely on)

if ( ! function_exists( 'taxonomy_exists' ) ) {
	/** Registered taxonomies: $GLOBALS['lafka_test_taxonomies'] (default none). */
	function taxonomy_exists( $taxonomy ) {
		return in_array( $taxonomy, (array) ( $GLOBALS['lafka_test_taxonomies'] ?? array() ), true );
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return class_exists( 'WP_Error' ) && $thing instanceof WP_Error;
	}
}
if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array(), $deprecated = '' ) {
		$args     = (array) $args;
		$taxonomy = (string) ( $args['taxonomy'] ?? '' );
		$terms    = array_values( $GLOBALS['lafka_test_terms'][ $taxonomy ] ?? array() );
		$exclude  = array_map( 'intval', (array) ( $args['exclude'] ?? array() ) );
		$terms    = array_values(
			array_filter(
				$terms,
				static function ( $t ) use ( $args, $exclude ) {
					if ( isset( $args['parent'] ) && (int) $t->parent !== (int) $args['parent'] ) {
						return false;
					}
					if ( ! empty( $args['hide_empty'] ) && (int) $t->count < 1 ) {
						return false;
					}
					return ! in_array( (int) $t->term_id, $exclude, true );
				}
			)
		);
		usort(
			$terms,
			static function ( $a, $b ) {
				return array( (int) $a->order, (string) $a->name ) <=> array( (int) $b->order, (string) $b->name );
			}
		);
		return $terms;
	}
}
if ( ! function_exists( 'get_term_link' ) ) {
	function get_term_link( $term, $taxonomy = '' ) {
		$slug = is_object( $term ) ? (string) $term->slug : (string) $term;
		return home_url( '/product-category/' . $slug . '/' );
	}
}
if ( ! function_exists( 'get_term_meta' ) ) {
	function get_term_meta( $term_id, $key = '', $single = false ) {
		$value = $GLOBALS['lafka_test_term_meta'][ (int) $term_id ][ $key ] ?? '';
		return $single ? $value : ( '' === $value ? array() : array( $value ) );
	}
}
if ( ! function_exists( 'wc_get_products' ) ) {
	function wc_get_products( $args = array() ) {
		$args     = (array) $args;
		$products = array_values( $GLOBALS['lafka_test_catalog'] ?? array() );
		$cats     = (array) ( $args['category'] ?? array() );
		$exclude  = array_map( 'intval', (array) ( $args['exclude'] ?? array() ) );
		$include  = array_map( 'intval', (array) ( $args['include'] ?? array() ) );
		$products = array_values(
			array_filter(
				$products,
				static function ( $p ) use ( $args, $cats, $exclude, $include ) {
					if ( $cats && ! array_intersect( $cats, (array) ( $p->data['cats'] ?? array() ) ) ) {
						return false;
					}
					if ( isset( $args['featured'] ) && (bool) $args['featured'] !== $p->is_featured() ) {
						return false;
					}
					if ( $include && ! in_array( (int) $p->get_id(), $include, true ) ) {
						return false;
					}
					return ! in_array( (int) $p->get_id(), $exclude, true );
				}
			)
		);
		$total = count( $products );
		$limit = (int) ( $args['limit'] ?? -1 );
		if ( $limit >= 0 ) {
			$products = array_slice( $products, 0, $limit );
		}
		if ( 'ids' === ( $args['return'] ?? '' ) ) {
			$products = array_map( static fn( $p ) => (int) $p->get_id(), $products );
		}
		if ( ! empty( $args['paginate'] ) ) {
			return (object) array(
				'products'      => $products,
				'total'         => $total,
				'max_num_pages' => $limit > 0 ? (int) ceil( $total / $limit ) : 1,
			);
		}
		return $products;
	}
}
if ( ! function_exists( 'get_template_part' ) ) {
	function get_template_part( $slug, $name = null, $args = array() ) {
		if ( empty( $GLOBALS['lafka_test_parts_live'] ) ) {
			return null;
		}
		$dir  = get_template_directory();
		$file = ( null !== $name && '' !== $name && is_file( "{$dir}/{$slug}-{$name}.php" ) ) ? "{$dir}/{$slug}-{$name}.php" : "{$dir}/{$slug}.php";
		if ( ! is_file( $file ) ) {
			return false;
		}
		( static function ( $lafka_test_file, $args ) {
			require $lafka_test_file;
		} )( $file, (array) $args );
		return null;
	}
}

// ------------------------------------------------- GX4: chrome helpers ----
//
// Stores (reset per test):
//   lafka_test_bloginfo        get_bloginfo(): [ show => value ] (name defaults to "Example Kitchen")
//   lafka_test_nav_menus       has_nav_menu() / wp_nav_menu(): [ location => list<array{label,url}> ]

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '', $filter = 'raw' ) {
		$info = ( $GLOBALS['lafka_test_bloginfo'] ?? array() ) + array( 'name' => 'Example Kitchen' );
		return (string) ( $info[ '' === $show ? 'name' : $show ] ?? '' );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}
if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $display = true ) {
		$result = (string) $checked === (string) $current ? " checked='checked'" : '';
		if ( $display ) {
			echo $result; // phpcs:ignore
		}
		return $result;
	}
}
if ( ! function_exists( 'has_nav_menu' ) ) {
	function has_nav_menu( $location ) {
		return ! empty( $GLOBALS['lafka_test_nav_menus'][ $location ] );
	}
}
if ( ! function_exists( 'wp_nav_menu' ) ) {
	function wp_nav_menu( $args = array() ) {
		$items = $GLOBALS['lafka_test_nav_menus'][ $args['theme_location'] ?? '' ] ?? array();
		$html  = '<ul class="' . ( $args['menu_class'] ?? 'menu' ) . '">';
		foreach ( $items as $item ) {
			$html .= '<li class="menu-item"><a href="' . $item['url'] . '">' . $item['label'] . '</a></li>';
		}
		$html .= '</ul>';
		if ( ! empty( $args['echo'] ) || ! array_key_exists( 'echo', $args ) ) {
			echo $html; // phpcs:ignore
			return null;
		}
		return $html;
	}
}

// ------------------------------------------------- GX4: loop context ----
//
// Stores (reset per test):
//   lafka_test_post_terms      wp_get_post_terms(): [ post_id ][ taxonomy ] = list<string> (names / slugs alike)
//   lafka_test_is_tax          is_tax() (false)
//   lafka_test_title           get_the_title() / single_term_title()

if ( ! function_exists( 'wp_get_post_terms' ) ) {
	function wp_get_post_terms( $post_id, $taxonomy = 'post_tag', $args = array() ) {
		return $GLOBALS['lafka_test_post_terms'][ (int) $post_id ][ $taxonomy ] ?? array();
	}
}
if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $taxonomy = '', $term = '' ) {
		return (bool) ( $GLOBALS['lafka_test_is_tax'] ?? false );
	}
}
if ( ! function_exists( 'single_term_title' ) ) {
	function single_term_title( $prefix = '', $display = true ) {
		return (string) ( $GLOBALS['lafka_test_title'] ?? '' );
	}
}
if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		return (string) ( $GLOBALS['lafka_test_title'] ?? '' );
	}
}
if ( ! function_exists( 'is_cart' ) ) {
	function is_cart() {
		return (bool) ( $GLOBALS['lafka_test_is_cart'] ?? false );
	}
}
if ( ! function_exists( 'is_checkout' ) ) {
	function is_checkout() {
		return (bool) ( $GLOBALS['lafka_test_is_checkout'] ?? false );
	}
}
if ( ! function_exists( 'is_product' ) ) {
	function is_product() {
		return (bool) ( $GLOBALS['lafka_test_is_product'] ?? false );
	}
}
if ( ! function_exists( 'is_page' ) ) {
	function is_page( $page = '' ) {
		return false;
	}
}
if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0, $leavename = false ) {
		return home_url( '/?p=' . (int) ( is_object( $post ) ? $post->ID : $post ) );
	}
}
if ( ! function_exists( 'get_the_privacy_policy_link' ) ) {
	/** $GLOBALS['lafka_test_privacy_link'] ('' = no policy page). */
	function get_the_privacy_policy_link( $before = '', $after = '' ) {
		$link = (string) ( $GLOBALS['lafka_test_privacy_link'] ?? '' );
		return '' === $link ? '' : $before . $link . $after;
	}
}
if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) {
		$words = preg_split( '/\s+/', trim( wp_strip_all_tags( (string) $text ) ) );
		return count( $words ) > $num_words ? implode( ' ', array_slice( $words, 0, $num_words ) ) . '…' : implode( ' ', $words );
	}
}

if ( ! function_exists( 'lafka_test_reset_wp' ) ) {
	/**
	 * Reset every per-test store. $filters is the load-time hook registry to
	 * restore (hooks the theme files registered when the tests required them).
	 */
	function lafka_test_reset_wp( array $filters = array() ) {
		$GLOBALS['lafka_test_filters']            = $filters;
		$GLOBALS['lafka_test_theme_mods']         = array();
		$GLOBALS['lafka_test_theme_mod_resolver'] = null;
		$GLOBALS['lafka_test_options']            = array();
		$GLOBALS['lafka_test_registered']         = array();
		$GLOBALS['lafka_test_inline']             = array();
		$GLOBALS['lafka_test_cache']              = array();
		$GLOBALS['lafka_test_transients']         = array();
		$GLOBALS['lafka_test_counters']           = array();
		$GLOBALS['lafka_test_is_preview']         = false;
		$GLOBALS['lafka_test_is_admin']           = false;
		$GLOBALS['lafka_test_attachments']        = array();
		$GLOBALS['lafka_test_post_meta']          = array();
		$GLOBALS['lafka_test_pdp_redesign']       = true;
		$GLOBALS['lafka_test_free_delivery_threshold'] = 0;
		$GLOBALS['lafka_test_variations']         = array();
		$GLOBALS['lafka_test_attr_terms']         = array();
		$GLOBALS['lafka_test_attr_labels']        = array();
		$GLOBALS['lafka_test_products']           = array();
		$GLOBALS['lafka_test_required_addons']    = array();
		$GLOBALS['lafka_test_terms']              = array();
		$GLOBALS['lafka_test_taxonomies']         = array();
		$GLOBALS['lafka_test_term_meta']          = array();
		$GLOBALS['lafka_test_catalog']            = array();
		$GLOBALS['lafka_test_parts_live']         = false;
		$GLOBALS['lafka_test_bloginfo']           = array();
		$GLOBALS['lafka_test_nav_menus']          = array();
		$GLOBALS['lafka_test_post_terms']         = array();
		$GLOBALS['lafka_test_is_tax']             = false;
		$GLOBALS['lafka_test_is_cart']            = false;
		$GLOBALS['lafka_test_is_checkout']        = false;
		$GLOBALS['lafka_test_is_product']         = false;
		$GLOBALS['lafka_test_title']              = '';
		$GLOBALS['lafka_test_privacy_link']       = '';
		$GLOBALS['lafka_chooser_registry']        = array();
		$GLOBALS['lafka_test_fulfilment_modes']   = array( 'pickup', 'delivery' );
		$GLOBALS['lafka_test_fulfilment_pref']    = '';
		if ( class_exists( 'Lafka_Order_Hours' ) ) {
			Lafka_Order_Hours::$lafka_order_hours_options = array();
			Lafka_Order_Hours::$shop_open                 = true;
			Lafka_Order_Hours::$next_open_human           = '';
			Lafka_Order_Hours::$lafka_order_hours_force_override_check = false;
		}
		$GLOBALS['lafka_test_order_hours_on'] = false;
		unset( $GLOBALS['lafka_test_home_url'], $GLOBALS['lafka_test_tpl_dir'], $GLOBALS['lafka_test_tpl_uri'], $GLOBALS['lafka_test_restaurant_info'] );
	}
}
