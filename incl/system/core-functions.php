<?php
defined( 'ABSPATH' ) || exit;

/**
 * Replacement for deprecated get_page_by_title() (deprecated since WP 6.2).
 * Uses WP_Query to find a page by its title.
 *
 * @param string $title   Page title.
 * @param string $output  Optional. The required return type. OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT.
 * @param string $post_type Optional. Post type. Default 'page'.
 * @return WP_Post|array|null WP_Post on success, or null on failure.
 */
if ( ! function_exists( 'lafka_get_page_by_title' ) ) {
	function lafka_get_page_by_title( $title, $output = OBJECT, $post_type = 'page' ) {
		$query = new WP_Query( array(
			'post_type'              => $post_type,
			'title'                  => $title,
			'post_status'            => 'all',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		) );

		if ( ! empty( $query->post ) ) {
			$page = $query->post;
			if ( ARRAY_A === $output ) {
				return get_object_vars( $page );
			} elseif ( ARRAY_N === $output ) {
				return array_values( get_object_vars( $page ) );
			}
			return $page;
		}

		return null;
	}
}

/* Register Theme Features */

/* Hook into the 'after_setup_theme' action */
add_action('after_setup_theme', 'lafka_register_theme_features');
if (!function_exists('lafka_register_theme_features')) {

	function lafka_register_theme_features() {

		// Add post-thumbnails support
		add_theme_support('post-thumbnails');

		// Add Content Width theme support
		if (!isset($content_width)) {
			$content_width = 1220;
		}

		// Add Feed Links theme support
		add_theme_support('automatic-feed-links');

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support('title-tag');

		// Add theme support for Custom Background
		$background_args = array(
				'default-color' => '',
				'default-image' => '',
				'wp-head-callback' => '_custom_background_cb',
				'admin-head-callback' => '',
				'admin-preview-callback' => '',
		);
		add_theme_support('custom-background', $background_args);

		//  Add theme suppport for aside, gallery, link, image, quote, status, video, audio, chat
		add_theme_support('post-formats', array('aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat'));

		// Gutenberg
		add_theme_support( 'align-wide' );

		// Use the classic widget editor — theme widgets are WP_Widget-based.
		// Filter is used instead of remove_theme_support because WP adds the
		// default support at after_setup_theme priority 99999 (after themes).
		add_filter( 'use_widgets_block_editor', '__return_false' );

		if (defined('LAFKA_IS_WOOCOMMERCE') && LAFKA_IS_WOOCOMMERCE) {
            // Add support for woocommerce
			add_theme_support('woocommerce');
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
        }

	}

}


// Register top navigation menu
register_nav_menu('primary', esc_html__('Main Menu', 'lafka'));

// Register mobile navigation menu
register_nav_menu('mobile', esc_html__('Mobile Menu', 'lafka'));

// Register top left menu
register_nav_menu('top-left', esc_html__('Top Left Menu', 'lafka'));

// Register top right menu
register_nav_menu('top-right', esc_html__('Top Right Menu', 'lafka'));

// Register footer navigation menu
register_nav_menu('tertiary', esc_html__('Footer Menu', 'lafka'));

add_action('widgets_init', 'lafka_register_sidebars');
if (!function_exists('lafka_register_sidebars')) {

	/**
	 * Register sidebars
	 */
	function lafka_register_sidebars() {
		if (function_exists('register_sidebar')) {

			// Define default sidebar
			register_sidebar(array(
					'name' => esc_html__('Default Sidebar', 'lafka'),
					'id' => 'right_sidebar',
					'description' => esc_html__('Default Blog widget area', 'lafka'),
					'before_widget' => '<div id="%1$s" class="widget box %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3>',
					'after_title' => '</h3>',
			));

			// Define bottom footer widget area
			register_sidebar(array(
					'name' => esc_html__('Footer Sidebar', 'lafka'),
					'id' => 'bottom_footer_sidebar',
					'description' => esc_html__('Footer widget area', 'lafka'),
					'before_widget' => '<div id="%1$s" class="widget %2$s ">',
					'after_widget' => '</div>',
					'before_title' => '<h3>',
					'after_title' => '</h3>',
			));

			// Define Pre header widget area
			register_sidebar(array(
					'name' => esc_html__('Pre Header Sidebar', 'lafka'),
					'id' => 'pre_header_sidebar',
					'description' => esc_html__('Pre header widget area', 'lafka'),
					'before_widget' => '<div id="%1$s" class="widget %2$s ">',
					'after_widget' => '</div>',
					'before_title' => '<h3>',
					'after_title' => '</h3>',
			));

			if (LAFKA_IS_WOOCOMMERCE) {
				// Define shop sidebar if woocommerce is active
				register_sidebar(array(
						'name' => esc_html__('Shop Sidebar', 'lafka'),
						'id' => 'shop',
						'description' => esc_html__('Default Shop sidebar', 'lafka'),
						'before_widget' => '<div id="%1$s" class="widget box %2$s">',
						'after_widget' => '</div>',
						'before_title' => '<h3>',
						'after_title' => '</h3>',
				));

				// Define widget area for product filters
				register_sidebar(array(
					'name' => esc_html__('Product Filters Sidebar', 'lafka'),
					'id' => 'lafka_product_filters_sidebar',
					'description' => esc_html__('Product filters widget area, shown on shop and product category pages', 'lafka'),
					'before_widget' => '<div id="%1$s" class="widget box %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3>',
					'after_title' => '</h3>',
				));
			}

			if (LAFKA_IS_BBPRESS) {
				// Define shop sidebar if BBpress is active
				register_sidebar(array(
						'name' => 'Forum Sidebar',
						'id' => 'lafka_forum',
						'description' => esc_html__('Default Forum sidebar', 'lafka'),
						'before_widget' => '<div id="%1$s" class="widget box %2$s">',
						'after_widget' => '</div>',
						'before_title' => '<h3>',
						'after_title' => '</h3>',
				));
			}

			// Register the custom sidbars
			$lafka_custom_sdbrs = substr(lafka_get_option('sidebar_ids'), 0, -1);

			if ($lafka_custom_sdbrs) {
				$sdbrsArr = explode(';', $lafka_custom_sdbrs);
				foreach ($sdbrsArr as $sdbr) {
					$sdbr_id = lafka_generate_slug($sdbr, 45);
					register_sidebar(array(
							'name' => $sdbr,
							'id' => $sdbr_id,
							'before_widget' => '<div id="%1$s" class="widget box %2$s">',
							'after_widget' => '</div>',
							'before_title' => '<h3>',
							'after_title' => '</h3>',
					));
				}
			}
		}
	}

}

add_action('tgmpa_register', 'lafka_register_required_plugins');

/**
 * Register the required plugins for this theme.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
if (!function_exists('lafka_register_required_plugins')) {

	function lafka_register_required_plugins() {

		/**
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		/**
		 * Lafka Plugin source: first check for a local zip in the theme's plugins/ directory,
		 * otherwise fetch latest version from GitHub via the updater class.
		 */
		$lafka_plugin_info  = Lafka_GitHub_Updater::get_latest_plugin_info();
		$lafka_plugin_local = get_template_directory() . '/plugins/lafka-plugin.zip';
		$lafka_plugin_source = file_exists( $lafka_plugin_local )
			? $lafka_plugin_local
			: $lafka_plugin_info['source'];

		$plugins = array(
				array(
						'name'               => esc_html__( 'Lafka Plugin - Lafka Theme companion plugin', 'lafka' ),
						'slug'               => 'lafka-plugin',
						'source'             => $lafka_plugin_source,
						'required'           => true,
						'force_activation'   => false,
						'force_deactivation' => false,
						'version'            => $lafka_plugin_info['version'],
				),
				array(
						'name'     => esc_html__( 'WooCommerce', 'lafka' ),
						'slug'     => 'woocommerce',
						'required' => false,
				),
				array(
						'name'     => esc_html__( 'YITH WooCommerce Wishlist', 'lafka' ),
						'slug'     => 'yith-woocommerce-wishlist',
						'required' => false,
				),
				// Commercial plugins — must be purchased and installed separately.
				// If you have a zip, place it in the theme's plugins/ directory and uncomment the 'source' line.
				array(
						'name'     => esc_html__( 'Revolution Slider (optional, commercial)', 'lafka' ),
						'slug'     => 'revslider',
						// 'source'=> get_template_directory() . '/plugins/revslider.zip',
						'required' => false,
				),
				array(
						'name'     => esc_html__( 'WPBakery Page Builder (optional, commercial)', 'lafka' ),
						'slug'     => 'js_composer',
						// 'source'=> get_template_directory() . '/plugins/js_composer.zip',
						'required' => false,
				),
		);


		/**
		 * Array of configuration settings. Amend each line as needed.
		 * If you want the default strings to be available under your own theme domain,
		 * leave the strings uncommented.
		 * Some of the strings are added into a sprintf, so see the comments at the
		 * end of each line for what each argument will be.
		 */
		$config = array(
				'id' => 'lafka', // Unique ID for hashing notices for multiple instances of TGMPA.
				'default_path' => '', // Default absolute path to bundled plugins.
				'menu' => 'tgmpa-install-plugins', // Menu slug.
				'has_notices' => true, // Show admin notices or not.
				'is_automatic' => false, // Automatically activate plugins after installation or not.
				'dismissable' => true, // If false, a user cannot dismiss the nag message.
				'dismiss_msg' => '', // If 'dismissable' is false, this message will be output at top of nag.
				'message' => '', // Message to output right before the plugins table.
				'strings' => array(
						'page_title' => esc_html__('Install Required Plugins', 'lafka'),
						'menu_title' => esc_html__('Install Plugins', 'lafka'),
						/* translators: %s: plugin name. */
						'installing' => esc_html__('Installing Plugin: %s', 'lafka'),
						/* translators: %s: plugin name. */
						'updating' => esc_html__('Updating Plugin: %s', 'lafka'),
						'oops' => esc_html__('Something went wrong with the plugin API.', 'lafka'),
						'notice_can_install_required' => _n_noop(
										/* translators: 1: plugin name(s). */
										'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'lafka'
						),
						'notice_can_install_recommended' => _n_noop(
										/* translators: 1: plugin name(s). */
										'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', 'lafka'
						),
						'notice_ask_to_update' => _n_noop(
										/* translators: 1: plugin name(s). */
										'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'lafka'
						),
						'notice_ask_to_update_maybe' => _n_noop(
										/* translators: 1: plugin name(s). */
										'There is an update available for: %1$s. Prior update please make sure that the theme is compatible with the new version.', 'There are updates available for the following plugins: %1$s. Prior update please make sure that the theme is compatible with the new version.', 'lafka'
						),
						'notice_can_activate_required' => _n_noop(
										/* translators: 1: plugin name(s). */
										'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'lafka'
						),
						'notice_can_activate_recommended' => _n_noop(
										/* translators: 1: plugin name(s). */
										'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'lafka'
						),
						'install_link' => _n_noop(
										'Begin installing plugin', 'Begin installing plugins', 'lafka'
						),
						'update_link' => _n_noop(
										'Begin updating plugin', 'Begin updating plugins', 'lafka'
						),
						'activate_link' => _n_noop(
										'Begin activating plugin', 'Begin activating plugins', 'lafka'
						),
						'return' => esc_html__('Return to Required Plugins Installer', 'lafka'),
						'plugin_activated' => esc_html__('Plugin activated successfully.', 'lafka'),
						'activated_successfully' => esc_html__('The following plugin was activated successfully:', 'lafka'),
						/* translators: 1: plugin name. */
						'plugin_already_active' => esc_html__('No action taken. Plugin %1$s was already active.', 'lafka'),
						/* translators: 1: plugin name. */
						'plugin_needs_higher_version' => esc_html__('Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'lafka'),
						/* translators: 1: dashboard link. */
						'complete' => esc_html__('All plugins installed and activated successfully. %1$s', 'lafka'),
						'dismiss' => esc_html__('Dismiss this notice', 'lafka'),
						'notice_cannot_install_activate' => esc_html__('There are one or more required or recommended plugins to install, update or activate.', 'lafka'),
						'contact_admin' => esc_html__('Please contact the administrator of this site for help.', 'lafka'),
						'nag_type' => '', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
				),
		);

		tgmpa($plugins, $config);
	}

}

/**
 * Enqueues scripts and styles in the admin
 *
 * @param type $hook
 * @return type
 */
if (!function_exists('lafka_enqueue_admin_js')) {

	function lafka_enqueue_admin_js($hook) {
		// Lightweight admin CSS — safe on every page.
		wp_enqueue_style('lafka-admin', get_template_directory_uri() . "/styles/lafka-admin.css");

		// Heavy scripts only on pages that need them.
		$needs_editor = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$needs_menus  = ( 'nav-menus.php' === $hook );
		$needs_options = ( false !== strpos( $hook, 'lafka' ) || false !== strpos( $hook, 'theme-options' ) );

		if ( $needs_editor || $needs_options ) {
			wp_register_script('lafka-medialibrary-uploader', LAFKA_OPTIONS_FRAMEWORK_DIRECTORY . 'js/lafka-medialibrary-uploader.js', array('jquery-ui-accordion', 'media-upload'), false, true);
			wp_enqueue_script('lafka-medialibrary-uploader');
		}

		if ( $needs_editor || $needs_menus || $needs_options ) {
			// wp-color-picker
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script('wp-color-picker', array('jquery'));
			// font-awesome
			wp_enqueue_style('font_awesome_6_v4shims', get_template_directory_uri() . "/styles/font-awesome/css/v4-shims.min.css", array(), false, 'screen');
			wp_enqueue_style('font_awesome_6', get_template_directory_uri() . "/styles/font-awesome/css/all.min.css", array('font_awesome_6_v4shims'), false, 'screen');
			// et-line-font
			wp_enqueue_style('et-line-font', get_template_directory_uri() . "/styles/et-line-font/style.css", false, false, 'screen');
		}

		if ( $needs_menus ) {
			// Flaticon + Fonticonpicker — only used on menu editor
			wp_enqueue_style('flaticon', get_template_directory_uri() . "/styles/flaticon/font/flaticon.css", false, false, 'screen');
			wp_enqueue_script('fonticonpicker', get_template_directory_uri() . "/js/fonticonpicker/jquery.fonticonpicker.min.js", array('jquery'), false, true);
			wp_enqueue_style('fonticonpicker', get_template_directory_uri() . "/styles/fonticonpicker/css/jquery.fonticonpicker.min.css");
			wp_enqueue_style('fonticonpicker-gray-theme', get_template_directory_uri() . "/styles/fonticonpicker/themes/grey-theme/jquery.fonticonpicker.grey.min.css", array('fonticonpicker'));

			// Mega Menu
			wp_enqueue_style('lafka-mega-menu', get_template_directory_uri() . '/styles/lafka-admin-megamenu.css');
			wp_enqueue_script('lafka-mega-menu', get_template_directory_uri() . '/js/lafka-admin-mega-menu.js', array('jquery', 'jquery-ui-sortable'), false, true);
			wp_localize_script('lafka-mega-menu', 'lafka_mega_menu_js_params', array(
				'mega_menu_label' => esc_html__('Mega Menu', 'lafka'),
				'column_label' => esc_html__('Column', 'lafka')
			));
		}

		if ( $needs_editor || $needs_menus || $needs_options ) {
			wp_enqueue_script('nice-select', get_template_directory_uri() . "/js/jquery.nice-select.min.js", array('jquery'), '1.0.0', true);

			$new_orders_push_notifications = 'no';
			if ( LAFKA_IS_WOOCOMMERCE && current_user_can('manage_woocommerce' ) && lafka_get_option( 'order_notifications' ) ) {
				$new_orders_push_notifications = 'yes';
			}
			wp_enqueue_script('lafka-back', get_template_directory_uri() . "/js/lafka-back.js", array('jquery', 'jquery-ui-dialog', 'nice-select', 'wp-color-picker'), false, true);
			wp_localize_script('lafka-back', 'lafka_back_js_params', array(
				'new_orders_push_notifications' => $new_orders_push_notifications,
				'new_orders_push_notifications_allow_label' => esc_html__('Set Permission', 'lafka'),
				'new_orders_push_notifications_cancel_label' => esc_html__('Close', 'lafka'),
				'service_worker_path' => get_template_directory_uri() . "/js/sw.js",
				'nonce' => wp_create_nonce( 'lafka_ajax_nonce' ),
				'import_nonce' => wp_create_nonce( 'lafka_import_nonce' ),
				'admin_url' => admin_url( 'admin-ajax.php' )
			));
		}
	}

}
add_action('admin_enqueue_scripts', 'lafka_enqueue_admin_js');

add_action('enqueue_block_editor_assets', 'lafka_enqueue_gutenberg_styles');
if (!function_exists('lafka_enqueue_gutenberg_styles')) {
	/**
	 * Enqueue the Gutenberg styles
	 */
	function lafka_enqueue_gutenberg_styles() {
		wp_enqueue_style('lafka_block_editor_assets', get_template_directory_uri() . "/styles/lafka-gutenberg-styles.css");
		lafka_typography_enqueue_google_font();
	}
}

/**
 * Checks if post has 'lafka_video_bckgr_url' meta
 * and return the custom fields.
 * If not - returns false
 *
 * @return boolean
 */
if (!function_exists('lafka_has_post_video_bckgr')) {

	function lafka_has_post_video_bckgr() {

		$custom = false;

		if (is_singular()) {
			$custom = get_post_custom();
		}

		if ($custom && array_key_exists('lafka_video_bckgr_url', $custom) && $custom['lafka_video_bckgr_url'][0]) {
			return $custom;
		}

		return false;
	}

}

/**
 * Used to generate slugs
 * Used mainly for custom sidebars
 *
 * @param String $phrase
 * @param Integer $maxLength
 * @return String
 */
if (!function_exists('lafka_generate_slug')) {

	function lafka_generate_slug($phrase, $maxLength) {
		$result = strtolower($phrase);

		$result = preg_replace("/[^a-z0-9\s-]/", "", $result);
		$result = trim(preg_replace("/[\s-]+/", " ", $result));
		$result = trim(substr($result, 0, $maxLength));
		$result = preg_replace("/\s/", "-", $result);

		return $result;
	}

}

/**
 * Returns string with links to all parent taxonomies
 */
if (!function_exists('lafka_get_taxonomy_parents')) {

	function lafka_get_taxonomy_parents($id, $taxonomy, $link = false, $separator = '/', $nicename = false, $visited = array()) {
		$chain = '';
		$parent = get_term($id, $taxonomy);
		if (is_wp_error($parent))
			return $parent;

		if ($nicename)
			$name = $parent->slug;
		else
			$name = $parent->name;

		if ($parent->parent && ( $parent->parent != $parent->term_id ) && !in_array($parent->parent, $visited)) {
			$visited[] = $parent->parent;
			$chain .= lafka_get_taxonomy_parents($parent->parent, $taxonomy, $link, $separator, $nicename, $visited);
		}

		if ($link) {
			$term_link = get_term_link($parent, $taxonomy);
			$chain .= '<a href="' . esc_url($term_link) . '">' . $name . '</a>' . $separator;
		} else
			$chain .= $name . $separator;
		return $chain;
	}

}

if (!function_exists('lafka_get_more_featured_images')) {

	/**
	 * Get custom featured images by post_id
	 *
	 * @param int $post_id
	 * @return array of custom featured images. If not - empty array
	 */
	function lafka_get_more_featured_images($post_id) {
		$featured_imgs = array();
		$post_meta = get_post_meta($post_id);

		for ($i = 2; $i <= 6; $i++) {
			if (isset($post_meta['lafka_featured_imgid_' . $i][0]) && $post_meta['lafka_featured_imgid_' . $i][0]) {
				$featured_imgs['lafka_featured_imgid_' . $i] = $post_meta['lafka_featured_imgid_' . $i][0];
			}
		}

		return $featured_imgs;
	}

}

if (!function_exists('lafka_wp_lang_to_valid_language_code')) {

	function lafka_wp_lang_to_valid_language_code($wp_lang) {
		$wp_lang = str_replace('_', '-', $wp_lang);
		switch (strtolower($wp_lang)) {
			// arabic
			case 'ar':
			case 'ar-ae':
			case 'ar-bh':
			case 'ar-dz':
			case 'ar-eg':
			case 'ar-iq':
			case 'ar-jo':
			case 'ar-kw':
			case 'ar-lb':
			case 'ar-ly':
			case 'ar-ma':
			case 'ar-om':
			case 'ar-qa':
			case 'ar-sa':
			case 'ar-sy':
			case 'ar-tn':
			case 'ar-ye': return 'ar';

			// bulgarian
			case 'bg':
			case 'bg-bg': return 'bg';

			// bosnian
			case 'bs':
			case 'bs-ba': return 'bs';

			// catalan
			case 'ca':
			case 'ca-es': return 'ca';

			// czech
			case 'cs':
			case 'cs-cz': return 'cs';

			case 'cy': return 'cy';

			// danish
			case 'da':
			case 'da-dk': return 'da';

			// german
			case 'de':
			case 'de-at':
			case 'de-ch':
			case 'de-de':
			case 'de-li':
			case 'de-lu': return 'de';

			// greek
			case 'el':
			case 'el-gr': return 'el';

			// spanish
			case 'es':
			case 'es-ar':
			case 'es-bo':
			case 'es-cl':
			case 'es-co':
			case 'es-cr':
			case 'es-do':
			case 'es-ec':
			case 'es-es':

			case 'es-gt':
			case 'es-hn':
			case 'es-mx':
			case 'es-ni':
			case 'es-pa':
			case 'es-pe':
			case 'es-pr':
			case 'es-py':
			case 'es-sv':
			case 'es-uy':
			case 'es-ve': return 'es';

			// estonian
			case 'et':
			case 'et-ee': return 'et';

			// farsi/persian
			case 'fa':
			case 'fa fa-ir': return 'fa';

			// finnish
			case 'fi':
			case 'fi-fi': return 'fi';

			// french
			case 'fr':
			case 'fr-be':
			case 'fr-ca':
			case 'fr-ch':
			case 'fr-fr':
			case 'fr-lu':
			case 'fr-mc': return 'fr';

			// galician
			case 'gl':
			case 'gl-es': return 'gl';

			// gujarati
			case 'gu':
			case 'gu-in': return 'gu';

			// hebrew
			case 'he':
			case 'he-il': return 'he';

			// croatian
			case 'hr':
			case 'hr-ba':
			case 'hr-hr': return 'hr';

			// hungarian
			case 'hu':
			case 'hu-hu': return 'hu';

			// armenian
			case 'hy':
			case 'hy-am': return 'hy';

			// indonesian
			case 'id':
			case 'id-id': return 'id';

			// italian
			case 'it':
			case 'it-ch':
			case 'it-it': return 'it';

			// japanese
			case 'ja':
			case 'ja-jp': return 'ja';

			// kannada
			case 'kn':
			case 'kn-in': return 'kn';

			// korean
			case 'ko':
			case 'ko-kr': return 'ko';

			// lithuanian
			case 'lt':
			case 'lt-lt': return 'lt';

			// latvian
			case 'lv':
			case 'lv-lv': return 'lv';

			// malay
			case 'ms':
			case 'ms-bn':
			case 'ms-my': return 'ms';

			// burmese
			case 'my': return 'my';

			// norwegian
			case 'nb':
			case 'nb-no': return 'nb';

			// dutch
			case 'nl':
			case 'nl-be':
			case 'nl-nl': return 'nl';

			// polish
			case 'pl':
			case 'pl-pl': return 'pl';

			// portuguese
			case 'pt':
			case 'pt-br':
			case 'pt-pt': return 'pt-br';

			// romanian
			case 'ro':
			case 'ro-ro': return 'ro';

			// russian
			case 'ru':
			case 'ru-ru': return 'ru';

			// slovak
			case 'sk':
			case 'sk-sk': return 'sk';

			// slovenian
			case 'sl':
			case 'sl-si': return 'sl';

			// albanian
			case 'sq':
			case 'sq-al': return 'sq';

			// serbian
			case 'sr-ba':
			case 'sr-sp':
			case 'sr-rs': return 'sr-rs';

			// swedish
			case 'sv':
			case 'sv-fi':
			case 'sv-se': return 'sv';

			// thai
			case 'th':
			case 'th-th': return 'th';

			// turkish
			case 'tr':
			case 'tr-tr': return 'tr';

			// ukranian
			case 'uk':
			case 'uk-ua': return 'uk';

			// urdu
			case 'ur':
			case 'ur-pk': return 'ur';

			// uzbek
			case 'uz':
			case 'uz-uz': return 'uz';

			// vietnamese
			case 'vi':
			case 'vi-vn': return 'vi';

			// chinese/simplified
			case 'zh-cn': return 'zh-cn';

			// chinese/traditional
			case 'zh':
			case 'zh-hk':
			case 'zh-mo':
			case 'zh-sg':
			case 'zh-tw': return 'zh-tw';

			/* these don't exist and have no real language code? */

			// malaylam
			case 'ml': return 'ml';

			// assume english
			default: return '';
		}
	}

}

/**
 * Checks font options to see if a Google font is selected.
 * If so, builds an url to enqueue the styles
 */
if (!function_exists('lafka_typography_google_fonts_url')) {

	function lafka_typography_google_fonts_url() {

		$font_families = array();

		/* Translators: If there are characters in your language that are not
		 * supported by that font, translate this to 'off'. Do not translate
		 * into your own language.
		 */
		if ('off' !== _x('on', 'Google fonts: on or off', 'lafka')) {
			$all_google_fonts = array_keys(lafka_typography_get_google_fonts());

			// Define all the options that possibly have a unique Google font
			$body_font = lafka_get_option('body_font');
			$headings_font = lafka_get_option('headings_font');

			// Get the font face for each option and put it in an array
			$selected_fonts = array(
					$body_font['face'],
					$headings_font['face']);

			// Remove any duplicates in the list
			$selected_fonts = array_unique($selected_fonts);

			// Check each of the unique fonts against the defined Google fonts
			// If it is a Google font, go ahead and call the function to enqueue it
			foreach ($selected_fonts as $font) {
				if (in_array($font, $all_google_fonts)) {
					$font_families[] = $font;
				}
			}
		}

		$font_url = '';

		if (!empty($font_families)) {
			$font_families_string_to_encode = implode('|', $font_families);
			$font_url = add_query_arg( array(
				'family' => urlencode( $font_families_string_to_encode . ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&subset=' . lafka_get_google_subsets() ),
				'display' => 'swap',
			), '//fonts.googleapis.com/css' );
		}

		return $font_url;
	}

}
add_action('wp_enqueue_scripts', 'lafka_typography_enqueue_google_font');
add_action('admin_enqueue_scripts', 'lafka_typography_enqueue_google_font');

/**
 * Enqueues the Google $font that is passed
 */
if (!function_exists('lafka_typography_enqueue_google_font')) {
	function lafka_typography_enqueue_google_font() {
		wp_enqueue_style( 'lafka-fonts', lafka_typography_google_fonts_url(), array(), false, 'print' );
	}
}

add_filter( 'style_loader_tag', 'lafka_style_loader_tag_filter', 10, 2 );
if ( ! function_exists( 'lafka_style_loader_tag_filter' ) ) {
	function lafka_style_loader_tag_filter( $html, $handle ) {
		if ( in_array( $handle, array( 'lafka-fonts', 'font_awesome_6_v4shims', 'font_awesome_6', 'et-line-font', 'flaticon' ) ) ) {
			$link_stylesheet = str_replace( "rel='stylesheet'", "rel='stylesheet' onload=\"this.media='all'\"", $html );
			$link_preload    = str_replace( "rel='stylesheet'", "rel='preload' as='style'", $html );
			$link_preload    = str_replace( "media='print'", "", $link_preload );
			$link_preload    = str_replace( "id='" . $handle . "-css'", "", $link_preload );

			return $link_preload . $link_stylesheet;
		} else if ( in_array( $handle, array( 'feather', 'tiza' ) ) ) {
			$link_preload = str_replace( "rel='stylesheet'", "rel='preload' as='font'", $html );
			$link_preload = str_replace( "type='text/css'", "type='font/woff' crossorigin='anonymous'", $link_preload );
			$link_preload = str_replace( "media='all'", "", $link_preload );

			return $link_preload;
		}

		return $html;
	}
}

/**
 * Register / Enqueue theme scripts
 */
add_action('wp_enqueue_scripts', 'lafka_enqueue_scripts_and_styles');
if (!function_exists('lafka_enqueue_scripts_and_styles')) {

	function lafka_enqueue_scripts_and_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Preloader style
		if (lafka_get_option('show_preloader')) {
			wp_enqueue_style('lafka-preloader', get_template_directory_uri() . "/styles/lafka-preloader.css");
		}

		// Load the main stylesheet (use template URI so parent styles load even with a child theme).
		wp_enqueue_style( 'lafka-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( get_template() )->get( 'Version' ) );
		// Load the rtl stylesheet.
		if ( is_rtl() ) {
			wp_enqueue_style( 'lafka-rtl', get_template_directory_uri() . "/styles/rtl.css", array('lafka-style'), wp_get_theme()->get( 'Version' ) );
		}

		// Load the responsive stylesheet if enabled
		if (lafka_get_option('is_responsive')) {
			wp_enqueue_style('lafka-responsive', get_template_directory_uri() . "/styles/lafka-responsive.css", array('lafka-style'));
		}

		wp_enqueue_style( 'font_awesome_6_v4shims', get_template_directory_uri() . "/styles/font-awesome/css/v4-shims.min.css", array(),false, 'print' );
		wp_enqueue_style( 'font_awesome_6', get_template_directory_uri() . "/styles/font-awesome/css/all.min.css", array( 'font_awesome_6_v4shims' ), false, 'print' );
		wp_enqueue_style('et-line-font', get_template_directory_uri() . "/styles/et-line-font/style.css", array(),false, 'print');
		// Flaticon
		wp_enqueue_style('flaticon', get_template_directory_uri() . "/styles/flaticon/font/flaticon.css", false, false, 'print');

		wp_enqueue_style('tiza', get_template_directory_uri() . "/styles/fonts/tiza.woff",array(),null);
		wp_enqueue_style('feather', get_template_directory_uri() . "/styles/fonts/feather.woff",array(), null);

		// Modernizr — minimal touch-detection build (no dependencies)
		wp_enqueue_script('modernizr', get_template_directory_uri() . "/js/modernizr.custom.js", array(), '3.0.0', true);

		// nicescroll
		wp_enqueue_script('nicescroll', get_template_directory_uri() . "/js/jquery.nicescroll/jquery.nicescroll.min.js", array('jquery'), '3.7.6', true);

		/* loading jquery-ui-slider only for price filter */
		if (LAFKA_IS_WOOCOMMERCE && lafka_get_option('show_pricefilter') && is_woocommerce() && !is_product()) {
			wp_enqueue_script('jquery-ui-slider');
		}

		$cart_redirect_after_add = 'no';
		$cart_url                = '';
		if ( LAFKA_IS_WOOCOMMERCE && get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
			$cart_redirect_after_add = 'yes';
			$cart_url                = apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null );
		}

		$enable_ajax_add_to_cart = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option('ajax_to_cart_single') ) {
			$enable_ajax_add_to_cart = 'yes';
		}

		$enable_infinite_on_shop = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'enable_shop_infinite' ) ) {
			$enable_infinite_on_shop = 'yes';
		}

		$use_load_more_on_shop = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'use_load_more_on_shop' ) ) {
			$use_load_more_on_shop = 'yes';
		}

		$use_product_filter_ajax = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'use_product_filter_ajax' ) ) {
			$use_product_filter_ajax = 'yes';
		}

		$categories_fancy = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'categories_fancy' ) ) {
			$categories_fancy = 'yes';
		}

		$shopping_cart_on_add = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'shopping_cart_on_add' ) ) {
			$shopping_cart_on_add = 'yes';
		}

		$order_hours_cart_update = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && class_exists( 'Lafka_Order_Hours' ) && isset( Lafka_Order_Hours::$lafka_order_hours_options['lafka_order_hours_cache_enable'] ) && Lafka_Order_Hours::$lafka_order_hours_options['lafka_order_hours_cache_enable'] ) {
			$order_hours_cart_update = 'yes';
		}

		$lafka_front_deps = array('jquery', 'jquery-ui-tabs', 'nicescroll');
		if(LAFKA_IS_VC) {
			$lafka_front_deps[] = 'wpb_composer_front_js';
        }

		wp_enqueue_script('lafka-front', get_template_directory_uri() . "/js/lafka-front.js", $lafka_front_deps, false, true);
		wp_localize_script('lafka-front', 'lafka_main_js_params', array(
				'img_path' => esc_js(LAFKA_IMAGES_PATH),
				'admin_url' => esc_js(admin_url('admin-ajax.php')),
				'nonce' => wp_create_nonce( 'lafka_ajax_nonce' ),
				'product_label' =>  esc_js(__('Product', 'lafka')),
				'added_to_cart_label' => esc_js(__('was added to the cart', 'lafka')),
				'show_preloader' => esc_js(lafka_get_option('show_preloader')),
				'sticky_header' => esc_js(lafka_get_option('sticky_header')),
				'enable_smooth_scroll' => esc_js(lafka_get_option('enable_smooth_scroll')),
				'login_label' => esc_js(__('Login', 'lafka')),
				'register_label' => esc_js(__('Register', 'lafka')),
				'cart_redirect_after_add' => $cart_redirect_after_add,
				'cart_url' => $cart_url,
				'enable_ajax_add_to_cart' => $enable_ajax_add_to_cart,
				'enable_infinite_on_shop' => $enable_infinite_on_shop,
				'use_load_more_on_shop' => $use_load_more_on_shop,
				'use_product_filter_ajax' => $use_product_filter_ajax,
			    'categories_fancy' => $categories_fancy,
				'order_hours_cart_update' => $order_hours_cart_update,
				'shopping_cart_on_add' => $shopping_cart_on_add,
				'is_rtl' => (is_rtl() ? 'true' : 'false')
		));

		/* imagesloaded */
		wp_enqueue_script('imagesloaded', '',array('jquery'), false, true);

		// flexslider
		wp_enqueue_script('flexslider', get_template_directory_uri() . "/js/flex/jquery.flexslider-min.js", array('jquery'), '2.7.2', true);
		wp_enqueue_style('flexslider', get_template_directory_uri() . "/styles/flex/flexslider.css", array(), '2.7.2');

		// owl-carousel
		wp_enqueue_script('owl-carousel', get_template_directory_uri() . "/js/owl-carousel2-dist/owl.carousel.min.js", array('jquery'), '2.3.4', true);
		wp_enqueue_style('owl-carousel', get_template_directory_uri() . "/styles/owl-carousel2-dist/assets/owl.carousel.min.css", array(), '2.3.4');
		wp_enqueue_style('owl-carousel-theme-default', get_template_directory_uri() . "/styles/owl-carousel2-dist/assets/owl.theme.default.min.css", array(), '2.3.4');
		wp_enqueue_style('owl-carousel-animate', get_template_directory_uri() . "/styles/owl-carousel2-dist/assets/animate.css", array(), '2.3.4');

		// cloud-zoom — only on single product pages
		wp_register_script('cloud-zoom', get_template_directory_uri() . "/js/cloud-zoom/cloud-zoom.1.0.2.min.js", array('jquery'), '1.0.2', true);
		wp_register_style('cloud-zoom', get_template_directory_uri() . "/styles/cloud-zoom/cloud-zoom.css", array(), '1.0.2');
		if ( function_exists('is_product') && is_product() ) {
			wp_enqueue_script('cloud-zoom');
			wp_enqueue_style('cloud-zoom');
		}

		// countdown — only on single product pages when enabled
		wp_register_script('jquery-plugin', get_template_directory_uri() . "/js/count/jquery.plugin.min.js", array('jquery'), '2.1.0', true);
		wp_register_script('countdown', get_template_directory_uri() . "/js/count/jquery.countdown.min.js", array('jquery', 'jquery-plugin'), '2.1.0', true);
		if ( function_exists('is_product') && is_product() ) {
			wp_enqueue_script('countdown');
		}

        // magnific — register globally, enqueue on product pages and pages with galleries
		wp_register_script('magnific', get_template_directory_uri() . "/js/magnific/jquery.magnific-popup.min.js", array('jquery'), '1.1.0', true);
		wp_register_style('magnific', get_template_directory_uri() . "/styles/magnific/magnific-popup.css", array(), '1.1.0');
		if ( function_exists('is_product') && is_product() || is_singular() ) {
			wp_enqueue_script('magnific');
			wp_enqueue_style('magnific');
		}

		// appear
		wp_enqueue_script('appear', get_template_directory_uri() . "/js/jquery.appear.min.js", array('jquery'), '1.0.0', true);

		// typed.js v2 — standalone, no jQuery dependency
		wp_enqueue_script('typed', get_template_directory_uri() . "/js/typed.min.js", array(), '2.0.16', true);

		// nice-select
		wp_enqueue_script('nice-select', get_template_directory_uri() . "/js/jquery.nice-select.min.js", array('jquery'), '1.0.0', true);

		// is-in-viewport
		wp_enqueue_script('is-in-viewport', get_template_directory_uri() . "/js/isInViewport.min.js", array('jquery'), '1.0.0', true);

		// register Isotope
		wp_register_script('isotope', get_template_directory_uri() . "/js/isotope/dist/isotope.pkgd.min.js", array('jquery', 'imagesloaded'), false, true);
		if ( is_post_type_archive( 'lafka-foodmenu' ) || is_tax( 'lafka_foodmenu_category' ) || ( lafka_get_option( 'general_blog_style' ) === 'lafka_blog_masonry' && ( is_archive() || is_category() || lafka_is_blog() ) ) ) {
			// load Isotope
			wp_enqueue_script( 'isotope' );
		}

		// enqueue google map api
		$lafka_maps_api_key = lafka_get_option('google_maps_api_key');
		wp_register_script('lafka-google-maps', 'https://maps.googleapis.com/maps/api/js?'.( $lafka_maps_api_key ? 'key=' . $lafka_maps_api_key . '&' : '' ).'sensor=false&callback=Function.prototype', array('jquery'), false, true);

		$lafka_local = lafka_wp_lang_to_valid_language_code(get_locale());
		if ($lafka_local) {
			wp_enqueue_script('jquery-countdown-local', get_template_directory_uri() . "/js/count/jquery.countdown-$lafka_local.js", array('jquery', 'countdown'), false, true);
		}

		$is_compare = false;
		if (isset($_GET['action']) && $_GET['action'] === 'yith-woocompare-view-table') {
			$is_compare = true;
		}

		$to_include_backgr_video = lafka_has_to_include_backgr_video($is_compare);

		/* JavaScript to pages with the comment form
		 * to support sites with threaded comments (when in use).
		 */
		if (is_singular() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

		/* Include js configs — conditionally loaded scripts removed from hard deps */
		$lafka_libs_deps = array(
			'jquery',
			'wp-util',
			'flexslider',
			'owl-carousel',
			'appear',
			'typed',
			'nice-select',
			'is-in-viewport'
		);
		if ( function_exists('is_product') && is_product() ) {
			$lafka_libs_deps[] = 'cloud-zoom';
			$lafka_libs_deps[] = 'countdown';
		}
		if ( function_exists('is_product') && is_product() || is_singular() ) {
			$lafka_libs_deps[] = 'magnific';
		}
		wp_enqueue_script( 'lafka-libs-config', get_template_directory_uri() . "/js/lafka-libs-config" . $suffix . ".js", $lafka_libs_deps, false, true );

		// send is_rtl to js for owl carousel
		wp_localize_script( 'lafka-libs-config', 'lafka_rtl',
			array(
				'is_rtl' => ( is_rtl() ? 'true' : 'false' )
			) );

		if ( LAFKA_IS_WOOCOMMERCE && lafka_get_option( 'use_quickview' ) ) {
			wp_localize_script( 'lafka-libs-config', 'lafka_quickview',
				array(
					'lafka_ajax_url' => esc_js( admin_url( 'admin-ajax.php' ) ),
					'nonce' => wp_create_nonce( 'lafka_ajax_nonce' ),
					'wc_ajax_url'                      => WC_AJAX::get_endpoint( "%%endpoint%%" ),
					'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'lafka' ),
					'i18n_make_a_selection_text'       => esc_attr__( 'Please select some product options before adding this product to your cart.', 'lafka' ),
					'i18n_unavailable_text'            => esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'lafka' ),
				) );
		}

		$search_options = lafka_get_option('search_options');
		if (lafka_get_option('show_searchform') && $search_options['use_ajax']) {
			wp_localize_script('lafka-libs-config', 'lafka_ajax_search', array(
					'include' => 'true'
			));
		}

		if (LAFKA_IS_WOOCOMMERCE && is_product()) {
			wp_localize_script('lafka-libs-config', 'lafka_variation_prod_cloudzoom', array(
					'include' => 'true',
			));
		}

		// Register video background plugin
		wp_register_style('ytplayer', get_template_directory_uri() . "/styles/jquery.mb.YTPlayer/css/jquery.mb.YTPlayer.min.css");
		wp_register_script('ytplayer', get_template_directory_uri() . "/js/jquery.mb.YTPlayer/jquery.mb.YTPlayer.min.js", array('jquery'), '3.3.8', true);

		// Load video background plugin
		if ($to_include_backgr_video) {
			wp_enqueue_style('ytplayer');
			wp_enqueue_script('ytplayer');
			wp_localize_script('lafka-libs-config', 'lafka_ytplayer_conf', array(
					'include' => 'true',
			));
		}

		// Output WP Bakery Page Builder shortcodes custom styles on shop page
		if ( function_exists( "is_shop" ) ) {
			$shortcodes_custom_css = get_post_meta( wc_get_page_id( 'shop' ), '_wpb_shortcodes_custom_css', true );
			if ( is_shop() && ! empty( $shortcodes_custom_css ) ) {
				wp_add_inline_style( 'lafka-style', esc_html( $shortcodes_custom_css ) );
			}
		}
	}

}

// Deregister additional font awesome registration. We always register our own.
add_action( 'wp_enqueue_scripts', 'lafka_deregister_plugins_awesome_stylesheet', 20 );
if ( ! function_exists( 'lafka_deregister_plugins_awesome_stylesheet' ) ) {
	function lafka_deregister_plugins_awesome_stylesheet() {
		if ( class_exists( 'Vc_Manager' ) ) {
			wp_deregister_style( 'vc_font_awesome_5_shims' );
			wp_deregister_style( 'vc_font_awesome_5' );
			wp_deregister_style( 'vc_font_awesome_6' );
		}
		if ( function_exists( 'yith_wishlist_install' ) ) {
			wp_dequeue_style( 'yith-wcwl-font-awesome' );
			wp_dequeue_style( 'yith-wcwl-main' );
		}
	}
}

// Add defer to non-critical scripts for faster initial render
add_filter( 'script_loader_tag', 'lafka_defer_non_critical_scripts', 10, 3 );
if ( ! function_exists( 'lafka_defer_non_critical_scripts' ) ) {
	function lafka_defer_non_critical_scripts( $tag, $handle, $src ) {
		// Don't defer in admin or for critical scripts
		if ( is_admin() ) {
			return $tag;
		}
		$no_defer = array( 'jquery', 'jquery-core', 'jquery-migrate', 'wp-util', 'underscore', 'wp-i18n', 'wp-api-fetch', 'wp-hooks', 'wp-polyfill' );
		if ( in_array( $handle, $no_defer, true ) ) {
			return $tag;
		}
		// Skip if already has defer or async
		if ( strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) {
			return $tag;
		}
		return str_replace( ' src=', ' defer src=', $tag );
	}
}

add_filter( 'wp_resource_hints', 'lafka_add_resource_hints', 20, 2 );
if ( ! function_exists( 'lafka_add_resource_hints' ) ) {
	function lafka_add_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array(
				'href' => 'https://fonts.gstatic.com',
				'crossorigin'
			);
		}

		return $urls;
	}
}

if (!function_exists('lafka_generate_excerpt')) {

	/**
	 * Return excerpt
	 *
	 * @param string $input	input to truncate
	 * @param number $limit	 number of chars to reach to tuncate
	 * @param string $break
	 * @param string $more more string
	 * @param boolean $strip_it strip tags
	 * @param string $exclude exclude tags
	 * @param boolean $safe_truncate use mb_strimwidth()
	 * @return string the generated excerpt
	 */
	function lafka_generate_excerpt($input, $limit, $break = ".", $more = "...", $strip_it = false, $exclude = '<strong><em><span>', $safe_truncate = false) {
		if ($strip_it) {
			$input = strip_shortcodes(strip_tags($input, $exclude));
		}

		if (strlen($input) <= $limit) {
			return $input;
		}

		$breakpoint = strpos($input, $break, $limit);

		if ($breakpoint != false) {
			if ($breakpoint < strlen($input) - 1) {
				if ($safe_truncate || is_rtl()) {
					$input = mb_strimwidth($input, 0, $breakpoint) . $more;
				} else {
					$input = substr($input, 0, $breakpoint) . $more;
				}
			}
		}

		// prevent accidental word break
		if (!$breakpoint && strlen(strip_tags($input)) == strlen($input)) {
			if ($safe_truncate || is_rtl()) {
				$input = mb_strimwidth($input, 0, $limit) . $more;
			} else {
				$input = substr($input, 0, $limit) . $more;
			}
		}

		return $input;
	}

}

if (!function_exists('lafka_get_option')) {

	/**
	 * Get Option.
	 *
	 * The function is in use
	 * This should be starting point when implementing skins
	 */
	function lafka_get_option($name, $default = false) {

		static $options = null;
		if ( null === $options ) {
			$options = get_option('lafka');
		}

		if (isset($options) && isset($options[$name])) {
			return $options[$name];
		}

		if ($default) {
			return $default;
		}

		static $all_defaults = null;
		if ( null === $all_defaults ) {
			$all_defaults = lafka_get_default_values();
		}

		return isset($all_defaults[$name]) ? $all_defaults[$name] : false;
	}

}

if (!function_exists('lafka_has_to_include_backgr_video')) {

	/**
	 * Checks if video background js plugin has to be included
	 * and returns the places that is should appear, or
	 * false if not.
	 *
	 * @global type $post
	 * @param type $is_compare
	 * @return string|boolean
	 */
	function lafka_has_to_include_backgr_video($is_compare = false) {

		// The post has video background
		if (lafka_has_post_video_bckgr()) {
			return 'postmeta';
			// If is blog page and video background is set
		} elseif (lafka_is_blog() && lafka_get_option('show_blog_video_bckgr') && lafka_get_option('blog_video_bckgr_url')) {
			return 'blog';
			// If is shopwide
		} elseif (!$is_compare && LAFKA_IS_WOOCOMMERCE && is_woocommerce() && lafka_get_option('show_shop_video_bckgr') && lafka_get_option('shopwide_video_bckgr') && lafka_get_option('shop_video_bckgr_url')) {
			return 'shopwide';
			// If is shop page and video background is set
		} elseif (!$is_compare && LAFKA_IS_WOOCOMMERCE && is_shop() && lafka_get_option('show_shop_video_bckgr') && lafka_get_option('shop_video_bckgr_url')) {
			return 'shop';
			// If Global video background is set
		} elseif (!$is_compare && lafka_get_option('show_video_bckgr') && lafka_get_option('video_bckgr_url')) {
			return 'global';
		}

		return false;
	}

}

if (!function_exists('lafka_is_blog')) {

	/**
	 * Return true if this is the Blog page
	 * @return boolean
	 */
	function lafka_is_blog() {

		if (is_front_page() && is_home()) {
			return false;
		} elseif (is_front_page()) {
			return false;
		} elseif (is_home()) {
			// BLOG - return true
			return true;
		} else {
			return false;
		}
	}

}

add_action('after_switch_theme', 'lafka_redirect_to_options', 99);
if (!function_exists('lafka_redirect_to_options')) {

	// Redirect to theme options on theme activation
	function lafka_redirect_to_options() {
		wp_redirect(admin_url("themes.php?page=lafka-optionsframework"));
	}

}

add_filter('wp_nav_menu_args', 'lafka_set_menu_on_primary');
if (!function_exists('lafka_set_menu_on_primary')) {

	/**
	 * Set selected menu for 'top_menu' location
	 *
	 * @param Array $args
	 * @return Array
	 */
	function lafka_set_menu_on_primary($args) {
		if ($args['theme_location'] === 'primary') {
			if (lafka_is_blog()) {
				return lafka_set_menu_on_primary_helper($args, lafka_get_option('blog_top_menu'));
			}
			if (LAFKA_IS_WOOCOMMERCE && is_shop()) {
				return lafka_set_menu_on_primary_helper($args, lafka_get_option('shop_top_menu'));
			}
			if (LAFKA_IS_BBPRESS && bbp_is_forum_archive()) {
				return lafka_set_menu_on_primary_helper($args, lafka_get_option('forum_top_menu'));
			}
			if (LAFKA_IS_EVENTS) {
				$mode_and_title = lafka_get_current_events_display_mode_and_title();
				$events_mode = $mode_and_title['display_mode'];
				if(in_array($events_mode, array('MAIN_CALENDAR', 'CALENDAR_CATEGORY', 'MAIN_EVENTS', 'CATEGORY_EVENTS', 'SINGLE_EVENT_DAYS'))) {
					return lafka_set_menu_on_primary_helper( $args, lafka_get_option( 'events_top_menu' ) );
				}
			}

			$chosen_menu = get_post_meta(get_the_ID(), 'lafka_top_menu', true);
			return lafka_set_menu_on_primary_helper($args, $chosen_menu);
		} else {
			return $args;
		}
	}

}

if (!function_exists('lafka_set_menu_on_primary_helper')) {

	/**
	 * Helper
	 *
	 * @param array $args
	 * @param string $chosen_menu
	 * @return string
	 */
	function lafka_set_menu_on_primary_helper($args, $chosen_menu) {
		if ('default' === $chosen_menu) {
			return $args;
		} else if ('none' === $chosen_menu) {
			$args['theme_location'] = 'lafka_none_existing_location';
			return $args;
		} else {
			$args['menu'] = (int) $chosen_menu;
			return $args;
		}
	}

}
/*
 * Check for sidebar
 */
add_filter('lafka_has_sidebar', 'lafka_check_for_sidebar');
if (!function_exists('lafka_check_for_sidebar')) {

	function lafka_check_for_sidebar() {

		$options = array();
		$is_cat_tag_tax_archive = false;

		if (is_category() || is_tag() || is_tax() || is_archive() || is_search() || (is_home())) {
			$is_cat_tag_tax_archive = true;
		}

		$blog_categoty_sidebar = lafka_get_option('blog_categoty_sidebar');
		$foodmenu_categoty_sidebar = lafka_get_option('foodmenu_categoty_sidebar');

		if (LAFKA_IS_WOOCOMMERCE) {
			$woocommerce_sidebar = lafka_get_option('woocommerce_sidebar');
		}

		if (LAFKA_IS_BBPRESS) {
			$bbpress_sidebar = lafka_get_option('bbpress_sidebar');
		}

		if(LAFKA_IS_EVENTS) {
			$events_sidebar = lafka_get_option('events_sidebar');
		}

		if (is_single() || is_page()) {
			$options = get_post_custom(get_queried_object_id());
		}

		$show_sidebar_from_meta = 'yes';
		if (isset($options['lafka_show_sidebar']) && trim($options['lafka_show_sidebar'][0]) != '') {
			$show_sidebar_from_meta = $options['lafka_show_sidebar'][0];
		}

		$sidebar_choice = 'none';

		if (LAFKA_IS_WOOCOMMERCE && function_exists('is_woocommerce') && is_woocommerce() && isset($woocommerce_sidebar)) {
			$sidebar_choice = $woocommerce_sidebar;
		} elseif (LAFKA_IS_BBPRESS && is_bbpress() && isset($bbpress_sidebar) && (empty($options) || (isset($options['lafka_custom_sidebar']) && $options['lafka_custom_sidebar'][0] == 'default' && $show_sidebar_from_meta == 'yes'))) {
			$sidebar_choice = $bbpress_sidebar;
		} elseif ( LAFKA_IS_EVENTS && lafka_is_events_part() && isset($events_sidebar) && ( empty($options) || ( isset($options['lafka_custom_sidebar']) && $options['lafka_custom_sidebar'][0] == 'default' && $show_sidebar_from_meta == 'yes'))) {
			$sidebar_choice = $events_sidebar;
		} elseif (is_tax('lafka_foodmenu_category') || is_post_type_archive('lafka-foodmenu')) {
			$sidebar_choice = $foodmenu_categoty_sidebar;
		} elseif ($is_cat_tag_tax_archive) {
			$sidebar_choice = $blog_categoty_sidebar;
		} elseif (isset($options['lafka_custom_sidebar']) && $show_sidebar_from_meta == 'yes') {
			if ($options['lafka_custom_sidebar'][0] == 'default') {
				$sidebar_choice = 'right_sidebar';
			} else {
				$sidebar_choice = $options['lafka_custom_sidebar'][0];
			}
		} else {
			$sidebar_choice = 'none';
		}

		return $sidebar_choice;
	}

}

/*
 * Check for sidebar
 */
add_filter('lafka_has_offcanvas_sidebar', 'lafka_check_for_offcanvas_sidebar');
if (!function_exists('lafka_check_for_offcanvas_sidebar')) {

	function lafka_check_for_offcanvas_sidebar() {
		$meta_options = array();
		if (is_single() || is_page()) {
			$meta_options = get_post_custom(get_queried_object_id());
		}

		if (isset($meta_options['lafka_show_offcanvas_sidebar']) && trim($meta_options['lafka_show_offcanvas_sidebar'][0]) === 'no') {
			return 'none';
		}

		$offcanvas_sidebar_choice = lafka_get_option('offcanvas_sidebar');
		if (isset($meta_options['lafka_custom_offcanvas_sidebar']) && $meta_options['lafka_custom_offcanvas_sidebar'][0] !== 'default') {
			$offcanvas_sidebar_choice = $meta_options['lafka_custom_offcanvas_sidebar'][0];
		}

		return $offcanvas_sidebar_choice;
	}

}

add_filter('lafka_left_sidebar_position_class', 'lafka_check_for_sidebar_position');
if (!function_exists('lafka_check_for_sidebar_position')) {

	/**
	 * Check position of sidebar
	 *
	 * @return string - Empty string for left and the class name for right
	 */
	function lafka_check_for_sidebar_position() {
		$meta_options = array();
		if (is_single() || is_page()) {
			$meta_options = get_post_custom(get_queried_object_id());
		}

		$sidebar_position = lafka_get_option('sidebar_position');
		if (isset($meta_options['lafka_sidebar_position']) && $meta_options['lafka_sidebar_position'][0] !== 'default') {
			$sidebar_position = $meta_options['lafka_sidebar_position'][0];
		}

		if ( defined( 'LAFKA_IS_WOOCOMMERCE' ) && LAFKA_IS_WOOCOMMERCE && is_woocommerce() ) {
			if ( ! is_product() && lafka_get_option( 'shop_sidebar_position' ) !== 'default' ) {
				$sidebar_position = lafka_get_option( 'shop_sidebar_position' );
			} elseif ( is_product() && lafka_get_option( 'product_sidebar_position' ) !== 'default' ) {
				$sidebar_position = lafka_get_option( 'product_sidebar_position' );
			}
		} elseif ( lafka_get_option( 'blog_sidebar_position' ) !== 'default' && ( is_category() || is_tag() || is_author() || is_date() || is_search() || is_home() ) ) {
			$sidebar_position = lafka_get_option( 'blog_sidebar_position' );
		}

		return $sidebar_position;
	}

}


if (!function_exists('lafka_get_choose_menu_options')) {

	/**
	 * Get options to use for choose menu select
	 *
	 * @return Array
	 */
	function lafka_get_choose_menu_options() {
		$registered_menus = wp_get_nav_menus();
		$choose_menu_options = array(
				'none' => esc_html__('- No menu -', 'lafka'),
				'default' => esc_html__('- Use global set top menu -', 'lafka')
		);

		foreach ($registered_menus as $menu) {
			$choose_menu_options[$menu->term_id] = $menu->name;
		}

		return $choose_menu_options;
	}

}

// Disable BBPress breadcrumb
add_filter('bbp_no_breadcrumb', '__return_true');

if ( ! function_exists( 'lafka_get_current_events_display_mode_and_title' ) ) {

	/**
	 * Returns current events display mode and page title specific for the Events Calendar Plugin
	 *
	 * @param $id int  post/page id
	 *
	 * @return array Array[display_mode, title]
	 */
	function lafka_get_current_events_display_mode_and_title( $id = 0 ) {

		if ( $id == 0 ) {
			global $wp_query;

			if ( isset($wp_query->post) ) {
				$id = $wp_query->post->ID;
			}
		}

		$return_arr = array(
			'display_mode' => '',
			'title'        => ''
		);

		// If Event calendar is active follow the procedure to display the title
		if ( function_exists( 'tribe_is_month' ) ) {
			if ( tribe_is_month() && ! is_tax('', $id) ) { // The Main Calendar Page
				if ( lafka_get_option( 'events_title' ) ) {
					$title = lafka_get_option( 'events_title' );
				} else {
					$title = esc_html__( 'The Main Calendar', 'lafka' );
				}
				$mode = 'MAIN_CALENDAR';
			} elseif ( tribe_is_month() && is_tax('', $id) ) { // Calendar Category Pages
				$title = esc_html__( 'Calendar Category', 'lafka' ) . ': ' . tribe_meta_event_category_name();
				$mode  = 'CALENDAR_CATEGORY';
			} elseif ( tribe_is_event( $id ) && ! tribe_is_day() && ! is_singular() && ! is_tax('', $id) ) { // The Main Events List
				if ( lafka_get_option( 'events_title' ) ) {
					$title = lafka_get_option( 'events_title' );
				} else {
					$title = esc_html__( 'Events List', 'lafka' );
				}
				$mode = 'MAIN_EVENTS';
			} elseif ( tribe_is_event( $id ) && ! tribe_is_day() && ! is_singular() && is_tax('', $id) ) { // Category Events List
				$title = esc_html__( 'Events List', 'lafka' ) . ': ' . tribe_meta_event_category_name();
				$mode  = 'CATEGORY_EVENTS';
			} elseif ( tribe_is_event( $id ) && is_singular() ) { // Single Events
				$title = get_the_title( $id );
				$mode  = 'SINGLE_EVENTS';
			} elseif ( tribe_is_day() ) { // Single Event Days
				$title = esc_html__( 'Events on', 'lafka' ) . ': ' . date( 'F j, Y', strtotime( get_query_var( 'eventDate' ) ) );
				$mode  = 'SINGLE_EVENT_DAYS';
			} elseif ( tribe_is_venue( $id ) ) { // Single Venues
				$title = get_the_title( $id );
				$mode  = 'VENUE';
			} else {
				$title = get_the_title( $id );
				$mode  = '';
			}
		} else {
			$title = get_the_title( $id );
			$mode  = '';
		}

		$return_arr['title']        = $title;
		$return_arr['display_mode'] = $mode;

		return $return_arr;
	}
}

if ( ! function_exists( 'lafka_is_events_part' ) ) {

	/**
	 * Detect if we are on an Events Calendar page
	 *
	 * @return bool
	 */
	function lafka_is_events_part() {

		if ( LAFKA_IS_EVENTS && function_exists( 'tribe_is_event' ) && ( tribe_is_month() || tribe_is_event() || tribe_is_event_category() || tribe_is_in_main_loop() || tribe_is_view() || 'tribe_events' == get_post_type() || is_singular( 'tribe_events' ) ) ) {
			return true;
		}

		return false;
	}
}


/**
 * Strip the "script" tag from given string
 * Used for inline js code given to wp_add_inline_script
 *
 * @param string $source JS source code
 *
 * @return string The string without "script" tag
 */
function lafka_strip_script_tag_from_js_block( $source ) {
	return trim( preg_replace( '#<script[^>]*>(.*)</script>#is', '$1', $source ) );
}

if ( ! function_exists('lafka_write_log')) {
	function lafka_write_log ( $log )  {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}

// Fix Wishlist issue (adding prettyPhoto): https://wordpress.org/support/topic/conflict-with-the-wpbakery-gallery/
add_filter( 'yith_wcwl_main_script_deps', function( $deps ) {
	if ( isset( $deps[2] ) ) {
		unset( $deps[2] ); // remove lightbox.
	}

	return $deps;
} );