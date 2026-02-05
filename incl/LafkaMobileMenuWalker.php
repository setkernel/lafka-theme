<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('LafkaMobileMenuWalker')) {
	class LafkaMobileMenuWalker extends Walker_Nav_Menu {

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			// If is mega menu description item, don't show it in the mobile menu
			if (!get_post_meta($item->ID, '_lafka-menu-item-is_description', true)) {
				parent::start_el( $output, $item, $depth, $args, $id );
			}
		}

		public function end_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			// If is mega menu description item, don't show it in the mobile menu
			if (!get_post_meta($item->ID, '_lafka-menu-item-is_description', true)) {
				parent::end_el( $output, $item, $depth, $args, $id );
			}
		}

	}
}