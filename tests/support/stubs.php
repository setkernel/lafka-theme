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

if ( ! class_exists( 'Lafka_Order_Hours' ) ) {
	/** lafka-plugin's order-hours API, driven by statics. */
	class Lafka_Order_Hours {
		public static $lafka_order_hours_options = array();
		public static $shop_open                  = true;
		public static $next_open_human            = '';

		public static function is_shop_open() {
			return self::$shop_open;
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
