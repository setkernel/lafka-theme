<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('Lafka_Transfer_Content')) {

	/**
	 * Singelton class to manage import/export functionality
	 *
	 * @author aatanasov
	 */
	class Lafka_Transfer_Content {

		/**
		 * Current theme options
		 *
		 * @var String
		 */
		private $theme_options;

		/**
		 * Location of demo files
		 *
		 * @var String
		 */
		private $demo_location;

		/**
		 * Export file name
		 *
		 * @var String
		 */
		public $export_filename;

		/**
		 * Delimiter for separating different settings in the file
		 *
		 * @var String
		 */
		private $delimiter;

		/**
		 * Returns the *Lafka_Transfer_Content* instance of this class.
		 *
		 * @staticvar Singleton $instance The *Lafka_Transfer_Content* instances of this class.
		 *
		 * @return Lafka_Transfer_Content The *Lafka_Transfer_Content* instance.
		 */
		public static function getInstance() {
			static $instance = null;
			if ($instance === null) {
				$instance = new Lafka_Transfer_Content();
			}

			return $instance;
		}

		/**
		 * Protected constructor to prevent creating a new instance of the
		 * *Lafka_Transfer_Content* via the `new` operator from outside of this class.
		 */
		protected function __construct() {

			$this->setDelimiter('|||');
			$this->export_filename = get_template_directory() . '/store/settings/' . get_bloginfo('name') . '_settings_' . date('Y_m_d') . '.txt';
			$this->demo_location = get_template_directory() . '/store/demo/';

			global $wp_filesystem;

			if (empty($wp_filesystem)) {
				require_once (ABSPATH . '/wp-admin/includes/file.php');
				WP_Filesystem();
			}
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Lafka_Transfer_Content* instance.
		 *
		 * @return void
		 */
		private function __clone() {

		}

		/**
		 * Private unserialize method to prevent unserializing of the *Lafka_Transfer_Content*
		 * instance.
		 *
		 * @return void
		 */
		public function __wakeup() {

		}

		/**
		 * Set delimiter
		 *
		 * @return String
		 */
		public function getDelimiter() {
			return $this->delimiter;
		}

		/**
		 * Get delimiter
		 *
		 * @param String $delimiter
		 */
		public function setDelimiter($delimiter) {
			$this->delimiter = $delimiter;
		}

		/**
		 * Get demo location
		 *
		 * @return String
		 */
		public function getDemoLocation() {
			return $this->demo_location;
		}

		/**
		 * Gets the theme name from the stylesheet (lowercase and without spaces)
		 *
		 * @return String
		 */
		private function getCurrentThemeDirToLower() {
			// This gets the theme name from the stylesheet (lowercase and without spaces)
			$themename_orig = get_option('stylesheet');
			$themename = preg_replace("/\W/", "_", strtolower($themename_orig));

			if(!get_option($themename)) {
				// remove the '_child' if there is
				$themename = substr($themename, 0,-6);
			}

			return $themename;
		}

		/**
		 * Get theme options
		 *
		 * @return String theme options
		 */
		private function getThemeOptions() {
			if (!$this->theme_options) {
				$this->theme_options = get_option($this->getCurrentThemeDirToLower());
			}

			return $this->theme_options;
		}

		/**
		 * Encodes the options and stores the export.
		 *
		 * @return int Result
		 */
		protected function getEncodedOptions() {

			$encodedOptions = json_encode($this->getThemeOptions());

			return $encodedOptions;
		}

		/**
		 * Stores given file.
		 *
		 * @param String $filename Filename of the export file
		 * @param String $data Data to be stored
		 * @return int Result
		 */
		protected function storeFile($filename, $data) {

			/**
			 * @global WP_Filesystem_Base $wp_filesystem subclass
			 */
			global $wp_filesystem;

			return $wp_filesystem->put_contents($filename, $data);
		}

		/**
		 * Decodes settings during Import
		 *
		 * @param String $encoded_settings Encoded settings
		 * @return mixed
		 */
		protected function decodeSettings($encoded_settings) {
			return json_decode($encoded_settings, TRUE);
		}

		/**
		 * Imports encoded options
		 *
		 * @param String $encoded_options Encoded options
		 */
		protected function importOptions($encoded_options) {
			$options = $this->decodeSettings($encoded_options);

			update_option($this->getCurrentThemeDirToLower(), $options);
		}

		/**
		 * Import WP content
		 */
		public function importWPContent($file, $import_attachments = true) {

			global $wpdb;

			add_filter("http_request_args", array(&$this, "setHttpRequestTimeout"), 10, 1);

			if (class_exists('LafkaImport')) {

				$wp_import = new LafkaImport();
				$wp_import->fetch_attachments = $import_attachments;
				$wp_import->import($file);
			}

		}

		public function setHttpRequestTimeout($req) {
			$req["timeout"] = 600;
			return $req;
		}

		protected function getEncodedWidgets() {

			$sidebars_array = get_option('sidebars_widgets');
			$widget_types = array();
			$all_widgets_options = array();

			// get all registered widget types
			foreach ($sidebars_array as $sidebar_name => $widgets) {
				if ('wp_inactive_widgets' !== $sidebar_name && is_array($widgets)) {
					foreach ($widgets as $widget_index) {
						$widget_types[] = trim(substr($widget_index, 0, strrpos($widget_index, '-')));
					}
				}
			}

			// remove duplicates
			array_unique($widget_types);

			// get widget values for each type
			foreach ($widget_types as $widget_type) {
				$all_widgets_options['widget_' . $widget_type] = get_option('widget_' . $widget_type);
			}

			$sidebars_and_widgets = array('sidebars' => $sidebars_array, 'widgets' => $all_widgets_options);
			$encodedWidgets = json_encode($sidebars_and_widgets);

			return $encodedWidgets;
		}

		public function exportSettings() {

			$encodedOptions = $this->getEncodedOptions();
			$encodedWidgets = $this->getEncodedWidgets();

			return $this->storeFile($this->export_filename, $encodedOptions . $this->getDelimiter() . $encodedWidgets);
		}

		public function exportThemeOptions() {

			$encodedOptions = $this->getEncodedOptions();
			$upload_dir = wp_upload_dir();
			$file_name = 'lafka_theme_options_' . date('Y_m_d') . '.txt';
			$file_path = $upload_dir['path'] . '/'.$file_name;

			if($this->storeFile($file_path, $encodedOptions)) {
				return $file_path;
			}

			return false;
		}

		public function importSettings($filename, $widget_menu_1, $widget_menu_2, $widget_menu_3, $only_options = false) {
			/**
			 * @global WP_Filesystem_Base $wp_filesystem subclass
			 */
			global $wp_filesystem;

			$file_error = false;
			$data = $wp_filesystem->get_contents($filename);

			if ($data) {
				$settings_array = explode($this->getDelimiter(), $data);
				$sidebars_and_widgets = '';

				if ( is_array( $settings_array ) && ! empty( $settings_array ) ) {
					$options              = $this->decodeSettings( $settings_array[0] );
					if(array_key_exists(1, $settings_array)) {
						$sidebars_and_widgets = $this->decodeSettings( $settings_array[1] );
					}

					update_option( $this->getCurrentThemeDirToLower(), $options );
					if($sidebars_and_widgets && !$only_options) {
						update_option( 'sidebars_widgets', $sidebars_and_widgets['sidebars'] );

						foreach ( $sidebars_and_widgets['widgets'] as $widget_option_name => $widget_options ) {

							if ( $widget_option_name == 'widget_nav_menu' ) {
								foreach ( $widget_options as $key => $option ) {
									if ( is_array( $option ) ) {
										if ( strcasecmp( $option['title'], 'Information' ) == 0 && $widget_menu_1 ) {
											$widget_options[ $key ]['nav_menu'] = $widget_menu_1->term_id;
										} elseif ( strcasecmp( $option['title'], 'Extras' ) == 0 && $widget_menu_2 ) {
											$widget_options[ $key ]['nav_menu'] = $widget_menu_2->term_id;
										} elseif ( strcasecmp( $option['title'], 'The Shop' ) == 0 && $widget_menu_3 ) {
											$widget_options[ $key ]['nav_menu'] = $widget_menu_3->term_id;
										}
									}
								}

							}
							update_option( $widget_option_name, $widget_options );

						}
					}
				} else {
					$file_error = true;
				}
			}

			if ($file_error) {
				return new WP_Error('settings_import_file_error', esc_html__('There was error with settings file.', 'lafka'));
			}
		}

		/**
		 * Import all revolution sliders
		 *
		 * @param string $demo_name
		 * @return boolean
		 */
		public function importRevSliders($demo_name = 'one') {
			if (!class_exists('RevSliderSliderImport')) {
				return false;
			}

			$rev_directory = $this->getDemoLocation() . $demo_name . '/revsliders/';
			foreach ( glob( $rev_directory . '*.zip' ) as $filename ) { // get all files from revsliders data dir
				$filename    = basename( $filename );
				$rev_files[] = $rev_directory . $filename;
			}

			if ( ! isset( $rev_files ) || ! is_array( $rev_files ) ) {
				return false;
			}

			$revSliderSliderImport = new RevSliderSliderImport();

			$is_template = false;
			$single_slide = true;
			$update_animation = true;
			$update_navigation = true;
			$install = true;
			foreach ($rev_files as $rev_file) { // finally import rev slider data files
				$importSliderResult = $revSliderSliderImport->import_slider( $update_animation, $rev_file, $is_template, $single_slide, $update_navigation, $install );
			}
		}

		/**
		 * @param string $demo_name
		 *
		 * @return bool
		 */
		public function doImportDemo($demo_name = 'one') {

			echo 'import started ' . date(DATE_RFC2822) . '<br/>';

			// Delete current menus
			$all_pages_menu_for_del = wp_get_nav_menu_object('Main menu');
			$mobile_menu_for_del = wp_get_nav_menu_object('Mobile Menu');
			$top_left_menu_for_del = wp_get_nav_menu_object('Top Menu Left');
			$top_right_menu_for_del = wp_get_nav_menu_object('Top Menu Right');
			$footer_menu_for_del = wp_get_nav_menu_object('Footer Menu');

			$widget_menu_1_for_del =  wp_get_nav_menu_object('Information');
			$widget_menu_2_for_del =  wp_get_nav_menu_object('Extras');
			$widget_menu_3_for_del =  wp_get_nav_menu_object('The Shop');

			if ($all_pages_menu_for_del) {
				wp_delete_nav_menu('Main menu');
			}
			if ($mobile_menu_for_del) {
				wp_delete_nav_menu('Mobile Menu');
			}
			if ($top_left_menu_for_del) {
				wp_delete_nav_menu('Top Menu Left');
			}
			if ($top_right_menu_for_del) {
				wp_delete_nav_menu('Top Menu Right');
			}
			if ($footer_menu_for_del) {
				wp_delete_nav_menu('Footer Menu');
			}
			if ($widget_menu_1_for_del) {
				wp_delete_nav_menu('Information');
			}
			if ($widget_menu_2_for_del) {
				wp_delete_nav_menu('Extras');
			}
			if ($widget_menu_3_for_del) {
				wp_delete_nav_menu('The Shop');
			}

			// Force all variations to be imported
			add_filter( 'wp_import_existing_post', function ( $post_exists, $post ) {
				if ( $post['post_type'] === 'product_variation' ) {
					return 0;
				}

				return $post_exists;
			}, 10, 2 );

			$this->importWPContent( $this->getDemoLocation() . '/' . $demo_name . '/demo.xml' );

			echo 'lafka_wp_xml_import_success ' . date(DATE_RFC2822) . '<br/>';

			// Get Widget menus IDs so we can pass them to widget import, so we have popper options set
			$widget_menu_1 = wp_get_nav_menu_object('Information');
			$widget_menu_2 = wp_get_nav_menu_object('Extras');
			$widget_menu_3 = wp_get_nav_menu_object('Shop Widget');

			$settings_imp_result = $this->importSettings($this->getDemoLocation() . '/' . $demo_name . '/demo.txt', $widget_menu_1, $widget_menu_2, $widget_menu_3);
			if (is_wp_error($settings_imp_result)) {
				echo 'lafka_settings_import_error';
				return false;
			}
			echo 'lafka_settings_import_success ' . date(DATE_RFC2822) . '<br/>';

			// Install woocommerce pages
			if (defined('LAFKA_IS_WOOCOMMERCE') && LAFKA_IS_WOOCOMMERCE) {
				if ($demo_name === 'lafka0') {
					// Set shop page to display both categories and products
					update_option( 'woocommerce_shop_page_display', 'both' );
					update_option( 'woocommerce_category_archive_display', 'both' );
				} elseif($demo_name === 'lafka1') {
					update_option( 'woocommerce_shop_page_display', '' );
					update_option( 'woocommerce_category_archive_display', '' );
				}

				$my_account_page = get_page_by_path( 'my-account' );
				if(!is_null($my_account_page )) {
					wp_delete_post($my_account_page->ID, true);
				}
				$shop_page = get_page_by_title('Order Online');
				if(is_a($shop_page, 'WP_Post')) {
					wc_create_page( esc_sql( $shop_page->post_name ), 'woocommerce_shop_page_id', $shop_page->post_title, '', '' );
				}
				WC_Install::create_pages();
				// We no longer need to install pages
				delete_option('_wc_needs_pages');
				WC_Admin_Notices::remove_notice('install');
				delete_transient('_wc_activation_redirect');

				// Set attributes to the correct swatches
				$product_attributes = wc_get_attribute_taxonomies();
				foreach ( $product_attributes as $attribute ) {
					switch ( $attribute->attribute_name ) {
						case 'size':
							wc_create_attribute( array(
								'id'   => $attribute->attribute_id,
								'name' => $attribute->attribute_name,
								'slug' => $attribute->attribute_name,
								'type' => 'label',
								'has_archives' => 1
							) );
							break;
						case 'combo-burgers':
						case 'combo-drinks':
						case 'combo-sides':
						case 'pizza-flavors':
							wc_create_attribute( array(
								'id'   => $attribute->attribute_id,
								'name' => $attribute->attribute_name,
								'slug' => $attribute->attribute_name,
								'type' => 'image',
								'has_archives' => 1
							) );
							break;
						case 'combo-desserts':
							wc_create_attribute( array(
								'id'   => $attribute->attribute_id,
								'name' => $attribute->attribute_name,
								'slug' => $attribute->attribute_name,
								'type' => 'select',
								'has_archives' => 1
							) );
							break;
					}
				}
			}

			// Set Wishlist page
			if (defined('LAFKA_IS_WISHLIST') && LAFKA_IS_WISHLIST) {
				$wishlist_page_title = 'Favorites';

				if($demo_name === 'lafka1') {
					$wishlist_page_title = 'Favorite Products';
				}
				if($demo_name === 'lafka2') {
					$wishlist_page_title = 'My favourites';
				}

				/** @var WP_Post $wishlist_page */
				$wishlist_page = get_page_by_title( $wishlist_page_title );

				if($wishlist_page instanceof WP_Post) {
					update_option( 'yith-wcwl-page-id', $wishlist_page->ID );
					update_option( 'yith_wcwl_wishlist_page_id', $wishlist_page->ID );
				}
			}

			global /** @var WP_Rewrite $wp_rewrite */
			$wp_rewrite;
			$wp_rewrite->set_permalink_structure('/%postname%/');
			$wp_rewrite->flush_rules();

			// Set menus
			$all_pages_menu = wp_get_nav_menu_object('Main menu');
			// Set mega menu on main demo
			if ($demo_name === 'lafka0' && isset($all_pages_menu->term_id)) {
				$menu_items = wp_get_nav_menu_items($all_pages_menu->term_id);

				foreach ($menu_items as $item) {
					if (in_array($item->title, array('Pizza', 'Burgers', 'Combos'))) {
						update_post_meta($item->ID, '_lafka-menu-item-is_megamenu', 'active');
					}

					// Menu descriptions that contain banners
					if(in_array($item->title, array('image 1', 'image 2', 'image 3', 'about pizza text', 'sub 1', 'sub 2', 'sub 3', 'burgers text', 'sub1'))) {
						update_post_meta($item->ID, '_lafka-menu-item-is_description', 'active');
					}

					// Menu labels settings: [navigation label] => array([menu label], [menu color])
					$menu_labels_settings = array(
						'Pizza' => array('3 STYLES', ''),
						'Sandwiches' => array('& WRAPS', '#e88835'),
						'Combos' => array('SAVE', '#afc93e'),
					);
					// Now do the labels
					foreach ($menu_labels_settings as $title => $setting){
						if($item->title === $title) {
							update_post_meta($item->ID, '_lafka-menu-item-custom_label', $setting[0]);
							update_post_meta($item->ID, '_lafka-menu-item-label_color', $setting[1]);
						}
					}

					// Menu icons settings: [menu name] => [icon code]
					$menu_icons_settings = array(
						'Pizza' => 'flaticon-023-pizza-slice',
						'Burgers' => 'flaticon-007-burger-1',
						'Sandwiches' => 'flaticon-030-kebab',
						'Sides & Salads' => 'flaticon-008-fried-potatoes',
						'Combos' => 'flaticon-050-french-fries',
						'Drinks' => 'flaticon-012-cola',
						'Desserts' => 'flaticon-011-ice-cream-1',
						'Classic Subs' => 'flaticon-015-hot-dog-1',
						'Club Sandwich' => 'flaticon-049-breakfast',
						'Cheese Melts' => 'flaticon-018-cheese',
						'Chicken Wraps' => 'flaticon-030-kebab',
						'Burger + Fries' => 'flaticon-006-burger-2',
						'Burger + Shake' => 'flaticon-050-french-fries',
						'Burger + Fries & Soda' => 'flaticon-029-package',
						'Crispy Tenders + Fries' => 'flaticon-021-fried-chicken',
						'Naked Tenders + Salad' => 'flaticon-049-breakfast',
					);
					// Now do the icons
					foreach ($menu_icons_settings as $title => $setting){
						if($item->title === $title) {
							update_post_meta($item->ID, '_lafka-menu-item-icon', $setting);
						}
					}
				}
			} elseif ($demo_name === 'lafka1' && isset($all_pages_menu->term_id)) {
				// Menu icons settings: [menu name] => [icon code]
				$menu_icons_settings = array(
					'Angus Burgers' => 'flaticon-007-burger-1',
					'Steak Burgers' => 'flaticon-010-burger',
					'Eggsy Burgers' => 'flaticon-020-fried-egg',
					'Sides' => 'flaticon-008-fried-potatoes',
					'Drinks' => 'flaticon-037-soda',
					'Desserts' => 'flaticon-027-donut',
				);
				// Now do the icons
				$menu_items = wp_get_nav_menu_items($all_pages_menu->term_id);
				foreach ($menu_items as $item) {
					foreach ( $menu_icons_settings as $title => $setting ) {
						if ( $item->title === $title ) {
							update_post_meta( $item->ID, '_lafka-menu-item-icon', $setting );
						}
					}
				}
			} elseif ($demo_name === 'lafka2' && isset($all_pages_menu->term_id)) {
				// Menu labels settings: [navigation label] => array([menu label], [menu color])
				$menu_labels_settings = array(
					'Order Online' => array('Delivery & Pickup', '#a4cc3f'),
				);
				// Menu icons settings: [menu name] => [icon code]
				$menu_icons_settings = array(
					'Home' => 'flaticon-038-take-away',
					'About Pizza™' => 'flaticon-023-pizza-slice',
					'Our Menu' => 'flaticon-047-menu',
					'Order Online' => 'flaticon-029-package',
					'News' => 'flaticon-022-serving-dish',
					'Contacts' => 'flaticon-033-waiter-1',
				);
				// Now do the icons
				$menu_items = wp_get_nav_menu_items($all_pages_menu->term_id);
				foreach ($menu_items as $item) {
					// Now do the labels
					foreach ($menu_labels_settings as $title => $setting){
						if($item->title === $title) {
							update_post_meta($item->ID, '_lafka-menu-item-custom_label', $setting[0]);
							update_post_meta($item->ID, '_lafka-menu-item-label_color', $setting[1]);
						}
					}
					foreach ( $menu_icons_settings as $title => $setting ) {
						if ( $item->title === $title ) {
							update_post_meta( $item->ID, '_lafka-menu-item-icon', $setting );
						}
					}
				}
			}

			$mobile_menu = wp_get_nav_menu_object('Mobile Menu');
			$top_left_menu = wp_get_nav_menu_object('Top Menu Left');
			$top_right_menu = wp_get_nav_menu_object('Top Menu Right');
			$footer_menu = wp_get_nav_menu_object('Footer Menu');

			$locations = get_theme_mod('nav_menu_locations');
			if ($all_pages_menu) {
				$locations['primary'] = $all_pages_menu->term_id;
				$locations['tertiary'] = $all_pages_menu->term_id;
			}
			if ($mobile_menu) {
				$locations['mobile'] = $mobile_menu->term_id;
			}
			if ($top_left_menu) {
				$locations['top-left'] = $top_left_menu->term_id;
			}
			if ($top_right_menu) {
				$locations['top-right'] = $top_right_menu->term_id;
			}
			if ($footer_menu) {
				$locations['tertiary'] = $footer_menu->term_id;
			}
			set_theme_mod('nav_menu_locations', $locations);

			// Set home and blog pages
			if($demo_name === 'lafka0') {
				$front_page = get_page_by_path( 'Home New' );
			} else {
				$front_page = get_page_by_path( 'Home' );
			}

			if(get_page_by_title('Blog')) {
				$blog_page = get_page_by_title( 'Blog' );
			} else {
				$blog_page = get_page_by_title( 'News' );
			}

			if ($front_page) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $front_page->ID);
			}

			if ($blog_page) {
				update_option('show_on_front', 'page');
				update_option('page_for_posts', $blog_page->ID);
			}

			// import rev sliders
			if (LAFKA_IS_REVOLUTION) {
				$this->importRevSliders($demo_name);
			}

			return true;
		}

	}

}