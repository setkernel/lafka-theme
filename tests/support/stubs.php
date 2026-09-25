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
