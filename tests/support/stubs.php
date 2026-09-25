<?php
/**
 * Class stubs shared by render tests (a class can only be declared once per
 * process). Instances are configured per test; statics are reset by
 * lafka_test_reset_wp().
 *
 * @package Lafka\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WC_Product' ) ) {
	/** Simple product with overridable fields. */
	class WC_Product {
		/** @var array<string, mixed> */
		public array $data;

		public function __construct( array $data = array() ) {
			$this->data = $data + array(
				'id'                => 42,
				'name'              => 'Margherita Pizza',
				'type'              => 'simple',
				'image_id'          => 0,
				'permalink'         => 'http://example.test/product/margherita/',
				'short_description' => '<p>Classic tomato and mozzarella.</p>',
				'price'             => '12.50',
			);
		}
		public function get_id() {
			return $this->data['id'];
		}
		public function get_name() {
			return $this->data['name'];
		}
		public function is_type( $type ) {
			return $type === $this->data['type'];
		}
		public function get_image_id() {
			return $this->data['image_id'];
		}
		public function get_permalink() {
			return $this->data['permalink'];
		}
		public function get_short_description() {
			return $this->data['short_description'];
		}
		public function get_price() {
			return $this->data['price'];
		}
		public function get_variation_price( $min_or_max = 'min', $display = false ) {
			return '8.00';
		}
		// GX4: the fields the counter rows / chooser read. Defaults keep every
		// pre-GX4 test's product behaving exactly as before.
		public function get_status() {
			return $this->data['status'] ?? 'publish';
		}
		public function get_type() {
			return $this->data['type'];
		}
		public function is_purchasable() {
			return $this->data['purchasable'] ?? true;
		}
		public function is_in_stock() {
			return $this->data['in_stock'] ?? true;
		}
		public function is_visible() {
			return $this->data['visible'] ?? true;
		}
		public function is_featured() {
			return $this->data['featured'] ?? false;
		}
		public function get_description() {
			return $this->data['description'] ?? '';
		}
		public function get_price_html() {
			return '<span class="amount">$' . $this->data['price'] . '</span>';
		}
		public function get_attributes() {
			return $this->data['attributes'] ?? array();
		}
		public function get_default_attributes() {
			return $this->data['default_attributes'] ?? array();
		}
		public function get_meta( $key, $single = true ) {
			return $this->data['meta'][ $key ] ?? '';
		}
		public function get_category_ids() {
			return $this->data['category_ids'] ?? array();
		}
		public function add_to_cart_url() {
			return '?add-to-cart=' . $this->data['id'];
		}
	}
}

if ( ! class_exists( 'WC_Product_Variable' ) ) {
	/**
	 * Variable product. Variations are data rows:
	 *   'variations' => [ vid => [ 'price' => 19.45, 'attributes' => [ 'attribute_pa_size' => 'medium', … ] ] ]
	 *   'variation_attributes' => [ 'pa_size' => [ 'small', 'medium' ], … ]  (product attribute order)
	 * wc_get_product_variation_attributes() (wp-shims) answers from the same rows
	 * via $GLOBALS['lafka_test_variations'], which the constructor registers.
	 */
	class WC_Product_Variable extends WC_Product {
		public function __construct( array $data = array() ) {
			parent::__construct( $data + array( 'type' => 'variable' ) );
			foreach ( (array) ( $this->data['variations'] ?? array() ) as $vid => $row ) {
				$GLOBALS['lafka_test_variations'][ (int) $vid ] = (array) ( $row['attributes'] ?? array() );
			}
		}
		public function get_children() {
			return array_map( 'intval', array_keys( (array) ( $this->data['variations'] ?? array() ) ) );
		}
		public function get_variation_prices( $for_display = false ) {
			$prices = array();
			foreach ( (array) ( $this->data['variations'] ?? array() ) as $vid => $row ) {
				if ( ! isset( $row['price'] ) || false === ( $row['visible'] ?? true ) ) {
					continue;
				}
				$prices[ (int) $vid ] = (string) $row['price'];
			}
			asort( $prices, SORT_NUMERIC );
			return array(
				'price'         => $prices,
				'regular_price' => $prices,
				'sale_price'    => $prices,
			);
		}
		public function get_variation_attributes() {
			return $this->data['variation_attributes'] ?? array();
		}
	}
}

if ( ! class_exists( 'WC_Product_Attribute' ) ) {
	/** A product's attribute row (custom attributes keep their option order). */
	class WC_Product_Attribute {
		/** @var array<string, mixed> */
		private array $row;

		public function __construct( array $row = array() ) {
			$this->row = $row + array(
				'name'      => '',
				'options'   => array(),
				'taxonomy'  => false,
				'variation' => true,
			);
		}
		public function get_name() {
			return $this->row['name'];
		}
		public function get_options() {
			return $this->row['options'];
		}
		public function is_taxonomy() {
			return (bool) $this->row['taxonomy'];
		}
		public function get_variation() {
			return (bool) $this->row['variation'];
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	/** Minimal term object (get_terms store rows). */
	class WP_Term {
		public $term_id     = 0;
		public $name        = '';
		public $slug        = '';
		public $taxonomy    = 'product_cat';
		public $parent      = 0;
		public $count       = 0;
		public $description = '';
		public $order       = 0;

		public function __construct( array $fields = array() ) {
			foreach ( $fields as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Customize_Manager' ) ) {
	/**
	 * Records Customizer registrations so a *_customizer_register() callback
	 * can be run and its panels / sections / settings / controls asserted.
	 * Create a fresh instance per test.
	 */
	class WP_Customize_Manager {
		/** @var array<string, array> */
		public array $panels = array();
		/** @var array<string, array> */
		public array $sections = array();
		/** @var array<string, array> */
		public array $settings = array();
		/** @var array<string, array> */
		public array $controls = array();

		public function add_panel( $id, $args = array() ) {
			$this->panels[ $id ] = $args;
		}
		public function add_section( $id, $args = array() ) {
			$this->sections[ $id ] = $args;
		}
		public function add_setting( $id, $args = array() ) {
			$this->settings[ $id ] = $args;
		}
		public function add_control( $id, $args = array() ) {
			$key                    = is_object( $id ) ? (string) ( $id->id ?? spl_object_id( $id ) ) : $id;
			$this->controls[ $key ] = $args;
		}
	}
}

if ( ! class_exists( 'Lafka_Order_Hours' ) ) {
	/** lafka-plugin's order-hours API, driven by statics. */
	class Lafka_Order_Hours {
		public static $lafka_order_hours_options = array();
		public static $shop_open                  = true;
		public static $next_open_human            = '';
		public static $can_order_ahead            = false;
		// GX4: the plugin's force-override static (read by lafka_counter_open_status()).
		public static $lafka_order_hours_force_override_check = false;

		public static function is_shop_open() {
			return self::$shop_open;
		}
		public static function can_order_ahead() {
			return self::$can_order_ahead;
		}
		// Mirrors the plugin: closed + opted in + no ordering ahead.
		public static function is_add_to_cart_blocked() {
			return ! self::$shop_open
				&& ! empty( self::$lafka_order_hours_options['lafka_order_hours_disable_add_to_cart'] )
				&& ! self::$can_order_ahead;
		}
		public static function get_next_opening_time() {
			return null;
		}
		public static function format_next_open_time_human( $opening ) {
			return self::$next_open_human;
		}
		public static function echo_closed_store_message() {
			echo '<div class="lafka-store-closed-card"><p class="lafka-store-closed-card__title">Closed right now</p></div>';
		}
	}
}
