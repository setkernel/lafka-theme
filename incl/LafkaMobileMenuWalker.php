<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'LafkaMobileMenuWalker' ) ) {
	class LafkaMobileMenuWalker extends Walker_Nav_Menu {

		private static $description_cache = array();

		private static function is_description_item( $item_id ) {
			if ( ! isset( self::$description_cache[ $item_id ] ) ) {
				self::$description_cache[ $item_id ] = (bool) get_post_meta( $item_id, '_lafka-menu-item-is_description', true );
			}
			return self::$description_cache[ $item_id ];
		}

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( ! self::is_description_item( $item->ID ) ) {
				parent::start_el( $output, $item, $depth, $args, $id );
			}
		}

		public function end_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( ! self::is_description_item( $item->ID ) ) {
				parent::end_el( $output, $item, $depth, $args, $id );
			}
		}
	}
}
