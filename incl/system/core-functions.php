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
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

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
add_action( 'after_setup_theme', 'lafka_register_theme_features' );
if ( ! function_exists( 'lafka_register_theme_features' ) ) {

	function lafka_register_theme_features() {

		// Add post-thumbnails support
		add_theme_support( 'post-thumbnails' );

		// Add Content Width theme support
		if ( ! isset( $content_width ) ) {
			$content_width = 1220;
		}

		// Add Feed Links theme support
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		// Add theme support for Custom Background
		$background_args = array(
			'default-color'          => '',
			'default-image'          => '',
			'wp-head-callback'       => '_custom_background_cb',
			'admin-head-callback'    => '',
			'admin-preview-callback' => '',
		);
		add_theme_support( 'custom-background', $background_args );

		// v6.3.0: WP-standard custom-logo (since WP 4.5). Replaces the
		// legacy lafka_get_option('theme_logo') flow — operator now
		// uploads via Appearance → Customize → Site Identity → Logo
		// (the WP-native location for theme branding).
		add_theme_support(
			'custom-logo',
			array(
				'height'               => 96,
				'width'                => 360,
				'flex-height'          => true,
				'flex-width'           => true,
				'header-text'          => array( 'site-title' ),
				'unlink-homepage-logo' => false,
			)
		);

		//  Add theme suppport for aside, gallery, link, image, quote, status, video, audio, chat
		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ) );

		// Gutenberg
		add_theme_support( 'align-wide' );

		// Use the classic widget editor — theme widgets are WP_Widget-based.
		// Filter is used instead of remove_theme_support because WP adds the
		// default support at after_setup_theme priority 99999 (after themes).
		add_filter( 'use_widgets_block_editor', '__return_false' );

		if ( defined( 'LAFKA_IS_WOOCOMMERCE' ) && LAFKA_IS_WOOCOMMERCE ) {
			// Add support for woocommerce
			add_theme_support( 'woocommerce' );
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
		}
	}

}


add_action( 'after_setup_theme', 'lafka_register_nav_menus' );
function lafka_register_nav_menus() {
	register_nav_menus(
		array(
			'primary'   => esc_html__( 'Main Menu', 'lafka' ),
			'mobile'    => esc_html__( 'Mobile Menu', 'lafka' ),
			'top-left'  => esc_html__( 'Top Left Menu', 'lafka' ),
			'top-right' => esc_html__( 'Top Right Menu', 'lafka' ),
			'tertiary'  => esc_html__( 'Footer Menu', 'lafka' ),
		)
	);
}

add_action( 'widgets_init', 'lafka_register_sidebars' );
if ( ! function_exists( 'lafka_register_sidebars' ) ) {

	/**
	 * Register sidebars
	 */
	function lafka_register_sidebars() {
		if ( function_exists( 'register_sidebar' ) ) {

			// Define default sidebar
			register_sidebar(
				array(
					'name'          => esc_html__( 'Default Sidebar', 'lafka' ),
					'id'            => 'right_sidebar',
					'description'   => esc_html__( 'Default Blog widget area', 'lafka' ),
					'before_widget' => '<div id="%1$s" class="widget box %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3>',
					'after_title'   => '</h3>',
				)
			);

			// Define bottom footer widget area
			register_sidebar(
				array(
					'name'          => esc_html__( 'Footer Sidebar', 'lafka' ),
					'id'            => 'bottom_footer_sidebar',
					'description'   => esc_html__( 'Footer widget area', 'lafka' ),
					'before_widget' => '<div id="%1$s" class="widget %2$s ">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3>',
					'after_title'   => '</h3>',
				)
			);

			// Define Pre header widget area
			register_sidebar(
				array(
					'name'          => esc_html__( 'Pre Header Sidebar', 'lafka' ),
					'id'            => 'pre_header_sidebar',
					'description'   => esc_html__( 'Pre header widget area', 'lafka' ),
					'before_widget' => '<div id="%1$s" class="widget %2$s ">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3>',
					'after_title'   => '</h3>',
				)
			);

			if ( LAFKA_IS_WOOCOMMERCE ) {
				// Define shop sidebar if woocommerce is active
				register_sidebar(
					array(
						'name'          => esc_html__( 'Shop Sidebar', 'lafka' ),
						'id'            => 'shop',
						'description'   => esc_html__( 'Default Shop sidebar', 'lafka' ),
						'before_widget' => '<div id="%1$s" class="widget box %2$s">',
						'after_widget'  => '</div>',
						'before_title'  => '<h3>',
						'after_title'   => '</h3>',
					)
				);

				// Define widget area for product filters
				register_sidebar(
					array(
						'name'          => esc_html__( 'Product Filters Sidebar', 'lafka' ),
						'id'            => 'lafka_product_filters_sidebar',
						'description'   => esc_html__( 'Product filters widget area, shown on shop and product category pages', 'lafka' ),
						'before_widget' => '<div id="%1$s" class="widget box %2$s">',
						'after_widget'  => '</div>',
						'before_title'  => '<h3>',
						'after_title'   => '</h3>',
					)
				);
			}

			if ( LAFKA_IS_BBPRESS ) {
				// Define shop sidebar if BBpress is active
				register_sidebar(
					array(
						'name'          => 'Forum Sidebar',
						'id'            => 'lafka_forum',
						'description'   => esc_html__( 'Default Forum sidebar', 'lafka' ),
						'before_widget' => '<div id="%1$s" class="widget box %2$s">',
						'after_widget'  => '</div>',
						'before_title'  => '<h3>',
						'after_title'   => '</h3>',
					)
				);
			}

			// Register the custom sidbars
			$lafka_custom_sdbrs = substr( get_theme_mod( 'lafka_sidebar_ids', '' ), 0, -1 );

			if ( $lafka_custom_sdbrs ) {
				$sdbrsArr = explode( ';', $lafka_custom_sdbrs );
				foreach ( $sdbrsArr as $sdbr ) {
					$sdbr_id = lafka_generate_slug( $sdbr, 45 );
					register_sidebar(
						array(
							'name'          => $sdbr,
							'id'            => $sdbr_id,
							'before_widget' => '<div id="%1$s" class="widget box %2$s">',
							'after_widget'  => '</div>',
							'before_title'  => '<h3>',
							'after_title'   => '</h3>',
						)
					);
				}
			}
		}
	}

}

add_action( 'tgmpa_register', 'lafka_register_required_plugins' );

/**
 * Register the required plugins for this theme.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
if ( ! function_exists( 'lafka_register_required_plugins' ) ) {

	function lafka_register_required_plugins() {

		/**
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		/**
		 * Lafka Plugin source: first check for a local zip in the theme's plugins/ directory,
		 * otherwise fetch latest version from GitHub via the updater class.
		 */
		$lafka_plugin_info   = Lafka_GitHub_Updater::get_latest_plugin_info();
		$lafka_plugin_local  = get_template_directory() . '/plugins/lafka-plugin.zip';
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
			'id'           => 'lafka', // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '', // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'has_notices'  => true, // Show admin notices or not.
			'is_automatic' => false, // Automatically activate plugins after installation or not.
			'dismissable'  => true, // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '', // If 'dismissable' is false, this message will be output at top of nag.
			'message'      => '', // Message to output right before the plugins table.
			'strings'      => array(
				'page_title'                      => esc_html__( 'Install Required Plugins', 'lafka' ),
				'menu_title'                      => esc_html__( 'Install Plugins', 'lafka' ),
				/* translators: %s: plugin name. */
				'installing'                      => esc_html__( 'Installing Plugin: %s', 'lafka' ),
				/* translators: %s: plugin name. */
				'updating'                        => esc_html__( 'Updating Plugin: %s', 'lafka' ),
				'oops'                            => esc_html__( 'Something went wrong with the plugin API.', 'lafka' ),
				'notice_can_install_required'     => _n_noop(
								/* translators: 1: plugin name(s). */
					'This theme requires the following plugin: %1$s.',
					'This theme requires the following plugins: %1$s.',
					'lafka'
				),
				'notice_can_install_recommended'  => _n_noop(
								/* translators: 1: plugin name(s). */
					'This theme recommends the following plugin: %1$s.',
					'This theme recommends the following plugins: %1$s.',
					'lafka'
				),
				'notice_ask_to_update'            => _n_noop(
								/* translators: 1: plugin name(s). */
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
					'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
					'lafka'
				),
				'notice_ask_to_update_maybe'      => _n_noop(
								/* translators: 1: plugin name(s). */
					'There is an update available for: %1$s. Prior update please make sure that the theme is compatible with the new version.',
					'There are updates available for the following plugins: %1$s. Prior update please make sure that the theme is compatible with the new version.',
					'lafka'
				),
				'notice_can_activate_required'    => _n_noop(
								/* translators: 1: plugin name(s). */
					'The following required plugin is currently inactive: %1$s.',
					'The following required plugins are currently inactive: %1$s.',
					'lafka'
				),
				'notice_can_activate_recommended' => _n_noop(
								/* translators: 1: plugin name(s). */
					'The following recommended plugin is currently inactive: %1$s.',
					'The following recommended plugins are currently inactive: %1$s.',
					'lafka'
				),
				'install_link'                    => _n_noop(
					'Begin installing plugin',
					'Begin installing plugins',
					'lafka'
				),
				'update_link'                     => _n_noop(
					'Begin updating plugin',
					'Begin updating plugins',
					'lafka'
				),
				'activate_link'                   => _n_noop(
					'Begin activating plugin',
					'Begin activating plugins',
					'lafka'
				),
				'return'                          => esc_html__( 'Return to Required Plugins Installer', 'lafka' ),
				'plugin_activated'                => esc_html__( 'Plugin activated successfully.', 'lafka' ),
				'activated_successfully'          => esc_html__( 'The following plugin was activated successfully:', 'lafka' ),
				/* translators: 1: plugin name. */
				'plugin_already_active'           => esc_html__( 'No action taken. Plugin %1$s was already active.', 'lafka' ),
				/* translators: 1: plugin name. */
				'plugin_needs_higher_version'     => esc_html__( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'lafka' ),
				/* translators: 1: dashboard link. */
				'complete'                        => esc_html__( 'All plugins installed and activated successfully. %1$s', 'lafka' ),
				'dismiss'                         => esc_html__( 'Dismiss this notice', 'lafka' ),
				'notice_cannot_install_activate'  => esc_html__( 'There are one or more required or recommended plugins to install, update or activate.', 'lafka' ),
				'contact_admin'                   => esc_html__( 'Please contact the administrator of this site for help.', 'lafka' ),
				'nag_type'                        => '', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
			),
		);

		tgmpa( $plugins, $config );
	}

}

/**
 * Return filemtime-based version string for a theme asset.
 * Falls back to the theme version if the file is missing.
 *
 * @param string $relative_path Path relative to get_template_directory(), e.g. '/js/lafka-front.js'.
 * @return string|false
 * @since 5.7.0
 */
if ( ! function_exists( 'lafka_asset_version' ) ) {
	// Fallback: only fires if lafka-plugin is not active. Plugin's class-lafka-options.php
	// definition supersedes this when both load. Canonical plugin version:
	// lafka-plugin/lafka-plugin.php → lafka_plugin_asset_version().
	function lafka_asset_version( $relative_path ) {
		$file = get_template_directory() . $relative_path;
		return file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme( get_template() )->get( 'Version' );
	}
}

/**
 * Enqueues scripts and styles in the admin
 *
 * @param type $hook
 * @return type
 */
if ( ! function_exists( 'lafka_enqueue_admin_js' ) ) {

	function lafka_enqueue_admin_js( $hook ) {
		// Lightweight admin CSS — safe on every page.
		wp_enqueue_style( 'lafka-admin', get_template_directory_uri() . '/styles/lafka-admin.css', array(), lafka_asset_version( '/styles/lafka-admin.css' ) );

		// Heavy scripts only on pages that need them.
		$needs_editor  = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$needs_menus   = ( 'nav-menus.php' === $hook );
		$needs_options = ( false !== strpos( $hook, 'lafka' ) || false !== strpos( $hook, 'theme-options' ) );

		if ( $needs_editor || $needs_options ) {
			wp_register_script( 'lafka-medialibrary-uploader', LAFKA_OPTIONS_FRAMEWORK_DIRECTORY . 'js/lafka-medialibrary-uploader.js', array( 'jquery-ui-accordion', 'media-upload' ), lafka_asset_version( '/incl/lafka-options-framework/js/lafka-medialibrary-uploader.js' ), true );
			wp_enqueue_script( 'lafka-medialibrary-uploader' );
		}

		if ( $needs_editor || $needs_menus || $needs_options ) {
			// wp-color-picker
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			// font-awesome
			wp_enqueue_style( 'font_awesome_6_v4shims', get_template_directory_uri() . '/styles/font-awesome/css/v4-shims.min.css', array(), lafka_asset_version( '/styles/font-awesome/css/v4-shims.min.css' ), 'screen' );
			wp_enqueue_style( 'font_awesome_6', get_template_directory_uri() . '/styles/font-awesome/css/all.min.css', array( 'font_awesome_6_v4shims' ), lafka_asset_version( '/styles/font-awesome/css/all.min.css' ), 'screen' );
			// et-line-font
			wp_enqueue_style( 'et-line-font', get_template_directory_uri() . '/styles/et-line-font/style.css', false, lafka_asset_version( '/styles/et-line-font/style.css' ), 'screen' );
		}

		if ( $needs_menus ) {
			// Flaticon + Fonticonpicker — only used on menu editor
			wp_enqueue_style( 'flaticon', get_template_directory_uri() . '/styles/flaticon/font/flaticon.css', false, lafka_asset_version( '/styles/flaticon/font/flaticon.css' ), 'screen' );
			wp_enqueue_script( 'fonticonpicker', get_template_directory_uri() . '/js/fonticonpicker/jquery.fonticonpicker.min.js', array( 'jquery' ), lafka_asset_version( '/js/fonticonpicker/jquery.fonticonpicker.min.js' ), true );
			wp_enqueue_style( 'fonticonpicker', get_template_directory_uri() . '/styles/fonticonpicker/css/jquery.fonticonpicker.min.css', array(), lafka_asset_version( '/styles/fonticonpicker/css/jquery.fonticonpicker.min.css' ) );
			wp_enqueue_style( 'fonticonpicker-gray-theme', get_template_directory_uri() . '/styles/fonticonpicker/themes/grey-theme/jquery.fonticonpicker.grey.min.css', array( 'fonticonpicker' ), lafka_asset_version( '/styles/fonticonpicker/themes/grey-theme/jquery.fonticonpicker.grey.min.css' ) );

			// Mega Menu
			wp_enqueue_style( 'lafka-mega-menu', get_template_directory_uri() . '/styles/lafka-admin-megamenu.css', array(), lafka_asset_version( '/styles/lafka-admin-megamenu.css' ) );
			wp_enqueue_script( 'lafka-mega-menu', get_template_directory_uri() . '/js/lafka-admin-mega-menu.js', array( 'jquery', 'jquery-ui-sortable' ), lafka_asset_version( '/js/lafka-admin-mega-menu.js' ), true );
			wp_localize_script(
				'lafka-mega-menu',
				'lafka_mega_menu_js_params',
				array(
					'mega_menu_label' => esc_html__( 'Mega Menu', 'lafka' ),
					'column_label'    => esc_html__( 'Column', 'lafka' ),
				)
			);
		}

		if ( $needs_editor || $needs_menus || $needs_options ) {
			wp_enqueue_script( 'nice-select', get_template_directory_uri() . '/js/jquery.nice-select.min.js', array( 'jquery' ), lafka_asset_version( '/js/jquery.nice-select.min.js' ), true );

			// New-order notification poller moved to lafka-plugin (NX1-08b); the theme
			// only ships the remaining admin helpers (colour pickers, metabox layout,
			// menu-icon picker) here plus the options-import nonce.
			wp_enqueue_script( 'lafka-back', get_template_directory_uri() . '/js/lafka-back.js', array( 'jquery', 'nice-select', 'wp-color-picker' ), lafka_asset_version( '/js/lafka-back.js' ), true );
			wp_localize_script(
				'lafka-back',
				'lafka_back_js_params',
				array(
					'import_nonce' => wp_create_nonce( 'lafka_import_nonce' ),
					'admin_url'    => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

}
add_action( 'admin_enqueue_scripts', 'lafka_enqueue_admin_js' );

add_action( 'enqueue_block_editor_assets', 'lafka_enqueue_gutenberg_styles' );
if ( ! function_exists( 'lafka_enqueue_gutenberg_styles' ) ) {
	/**
	 * Enqueue the Gutenberg styles
	 */
	function lafka_enqueue_gutenberg_styles() {
		wp_enqueue_style( 'lafka_block_editor_assets', get_template_directory_uri() . '/styles/lafka-gutenberg-styles.css', array(), lafka_asset_version( '/styles/lafka-gutenberg-styles.css' ) );
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
if ( ! function_exists( 'lafka_has_post_video_bckgr' ) ) {

	function lafka_has_post_video_bckgr() {

		$custom = false;

		if ( is_singular() ) {
			$custom = get_post_custom();
		}

		if ( $custom && array_key_exists( 'lafka_video_bckgr_url', $custom ) && $custom['lafka_video_bckgr_url'][0] ) {
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
if ( ! function_exists( 'lafka_generate_slug' ) ) {

	function lafka_generate_slug( $phrase, $maxLength ) {
		$result = strtolower( $phrase );

		$result = preg_replace( '/[^a-z0-9\s-]/', '', $result );
		$result = trim( preg_replace( '/[\s-]+/', ' ', $result ) );
		$result = trim( substr( $result, 0, $maxLength ) );
		$result = preg_replace( '/\s/', '-', $result );

		return $result;
	}

}

/**
 * Returns string with links to all parent taxonomies
 */
if ( ! function_exists( 'lafka_get_taxonomy_parents' ) ) {

	function lafka_get_taxonomy_parents( $id, $taxonomy, $link = false, $separator = '/', $nicename = false, $visited = array() ) {
		$chain  = '';
		$parent = get_term( $id, $taxonomy );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		if ( $nicename ) {
			$name = $parent->slug;
		} else {
			$name = $parent->name;
		}

		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && ! in_array( $parent->parent, $visited, true ) ) {
			$visited[] = $parent->parent;
			$chain    .= lafka_get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}

		if ( $link ) {
			$term_link = get_term_link( $parent, $taxonomy );
			$chain    .= '<a href="' . esc_url( $term_link ) . '">' . $name . '</a>' . $separator;
		} else {
			$chain .= $name . $separator;
		}
		return $chain;
	}

}

if ( ! function_exists( 'lafka_get_more_featured_images' ) ) {

	/**
	 * Get custom featured images by post_id
	 *
	 * @param int $post_id
	 * @return array of custom featured images. If not - empty array
	 */
	function lafka_get_more_featured_images( $post_id ) {
		$featured_imgs = array();
		$post_meta     = get_post_meta( $post_id );

		for ( $i = 2; $i <= 6; $i++ ) {
			if ( isset( $post_meta[ 'lafka_featured_imgid_' . $i ][0] ) && $post_meta[ 'lafka_featured_imgid_' . $i ][0] ) {
				$featured_imgs[ 'lafka_featured_imgid_' . $i ] = $post_meta[ 'lafka_featured_imgid_' . $i ][0];
			}
		}

		return $featured_imgs;
	}

}

if ( ! function_exists( 'lafka_wp_lang_to_valid_language_code' ) ) {

	function lafka_wp_lang_to_valid_language_code( $wp_lang ) {
		$wp_lang = str_replace( '_', '-', $wp_lang );
		switch ( strtolower( $wp_lang ) ) {
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
			case 'ar-ye':
				return 'ar';

			// bulgarian
			case 'bg':
			case 'bg-bg':
				return 'bg';

			// bosnian
			case 'bs':
			case 'bs-ba':
				return 'bs';

			// catalan
			case 'ca':
			case 'ca-es':
				return 'ca';

			// czech
			case 'cs':
			case 'cs-cz':
				return 'cs';

			case 'cy':
				return 'cy';

			// danish
			case 'da':
			case 'da-dk':
				return 'da';

			// german
			case 'de':
			case 'de-at':
			case 'de-ch':
			case 'de-de':
			case 'de-li':
			case 'de-lu':
				return 'de';

			// greek
			case 'el':
			case 'el-gr':
				return 'el';

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
			case 'es-ve':
				return 'es';

			// estonian
			case 'et':
			case 'et-ee':
				return 'et';

			// farsi/persian
			case 'fa':
			case 'fa fa-ir':
				return 'fa';

			// finnish
			case 'fi':
			case 'fi-fi':
				return 'fi';

			// french
			case 'fr':
			case 'fr-be':
			case 'fr-ca':
			case 'fr-ch':
			case 'fr-fr':
			case 'fr-lu':
			case 'fr-mc':
				return 'fr';

			// galician
			case 'gl':
			case 'gl-es':
				return 'gl';

			// gujarati
			case 'gu':
			case 'gu-in':
				return 'gu';

			// hebrew
			case 'he':
			case 'he-il':
				return 'he';

			// croatian
			case 'hr':
			case 'hr-ba':
			case 'hr-hr':
				return 'hr';

			// hungarian
			case 'hu':
			case 'hu-hu':
				return 'hu';

			// armenian
			case 'hy':
			case 'hy-am':
				return 'hy';

			// indonesian
			case 'id':
			case 'id-id':
				return 'id';

			// italian
			case 'it':
			case 'it-ch':
			case 'it-it':
				return 'it';

			// japanese
			case 'ja':
			case 'ja-jp':
				return 'ja';

			// kannada
			case 'kn':
			case 'kn-in':
				return 'kn';

			// korean
			case 'ko':
			case 'ko-kr':
				return 'ko';

			// lithuanian
			case 'lt':
			case 'lt-lt':
				return 'lt';

			// latvian
			case 'lv':
			case 'lv-lv':
				return 'lv';

			// malay
			case 'ms':
			case 'ms-bn':
			case 'ms-my':
				return 'ms';

			// burmese
			case 'my':
				return 'my';

			// norwegian
			case 'nb':
			case 'nb-no':
				return 'nb';

			// dutch
			case 'nl':
			case 'nl-be':
			case 'nl-nl':
				return 'nl';

			// polish
			case 'pl':
			case 'pl-pl':
				return 'pl';

			// portuguese
			case 'pt':
			case 'pt-br':
			case 'pt-pt':
				return 'pt-br';

			// romanian
			case 'ro':
			case 'ro-ro':
				return 'ro';

			// russian
			case 'ru':
			case 'ru-ru':
				return 'ru';

			// slovak
			case 'sk':
			case 'sk-sk':
				return 'sk';

			// slovenian
			case 'sl':
			case 'sl-si':
				return 'sl';

			// albanian
			case 'sq':
			case 'sq-al':
				return 'sq';

			// serbian
			case 'sr-ba':
			case 'sr-sp':
			case 'sr-rs':
				return 'sr-rs';

			// swedish
			case 'sv':
			case 'sv-fi':
			case 'sv-se':
				return 'sv';

			// thai
			case 'th':
			case 'th-th':
				return 'th';

			// turkish
			case 'tr':
			case 'tr-tr':
				return 'tr';

			// ukranian
			case 'uk':
			case 'uk-ua':
				return 'uk';

			// urdu
			case 'ur':
			case 'ur-pk':
				return 'ur';

			// uzbek
			case 'uz':
			case 'uz-uz':
				return 'uz';

			// vietnamese
			case 'vi':
			case 'vi-vn':
				return 'vi';

			// chinese/simplified
			case 'zh-cn':
				return 'zh-cn';

			// chinese/traditional
			case 'zh':
			case 'zh-hk':
			case 'zh-mo':
			case 'zh-sg':
			case 'zh-tw':
				return 'zh-tw';

			/* these don't exist and have no real language code? */

			// malaylam
			case 'ml':
				return 'ml';

			// assume english
			default:
				return '';
		}
	}

}

/**
 * Checks font options to see if a Google font is selected.
 * If so, builds an url to enqueue the styles
 */
if ( ! function_exists( 'lafka_typography_google_fonts_url' ) ) {

	function lafka_typography_google_fonts_url() {
		// PERF-H20: Cache result — this function is called by both wp_enqueue_scripts and
		// admin_enqueue_scripts, and involves multiple option reads + array operations.
		static $cached_url = null;
		if ( null !== $cached_url ) {
			return $cached_url;
		}

		$font_families = array();

		/* Translators: If there are characters in your language that are not
		 * supported by that font, translate this to 'off'. Do not translate
		 * into your own language.
		 */
		if ( 'off' !== _x( 'on', 'Google fonts: on or off', 'lafka' ) ) {
			$all_google_fonts = array_keys( lafka_typography_get_google_fonts() );

			// Define all the options that possibly have a unique Google font.
			// NX1-02.dyncss-typography-backgrounds: read the migrated
			// `lafka_<key>` theme_mods; the Options-Framework `std` face 'Rubik'
			// is passed as the default so a fresh install enqueues the same
			// (self-hosted → CDN-stripped) family set as before.
			$body_font     = get_theme_mod(
				'lafka_body_font',
				array(
					'face'  => 'Rubik',
					'size'  => '16px',
					'color' => '#5e5e5e',
				)
			);
			$headings_font = get_theme_mod( 'lafka_headings_font', array( 'face' => 'Rubik' ) );

			// Get the font face for each option and put it in an array
			$selected_fonts = array(
				$body_font['face'],
				$headings_font['face'],
			);

			// Remove any duplicates in the list
			$selected_fonts = array_unique( $selected_fonts );

			// Check each of the unique fonts against the defined Google fonts
			// If it is a Google font, go ahead and call the function to enqueue it
			foreach ( $selected_fonts as $font ) {
				if ( in_array( $font, $all_google_fonts, true ) ) {
					$font_families[] = $font;
				}
			}
		}

		$font_url = '';

		if ( ! empty( $font_families ) ) {
			$font_families_string_to_encode = implode( '|', $font_families );
			$font_url                       = add_query_arg(
				array(
					'family'  => urlencode( $font_families_string_to_encode . ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&subset=' . lafka_get_google_subsets() ),
					'display' => 'swap',
				),
				'//fonts.googleapis.com/css'
			);
		}

		$cached_url = $font_url;

		return $cached_url;
	}

}
add_action( 'wp_enqueue_scripts', 'lafka_typography_enqueue_google_font' );
add_action( 'admin_enqueue_scripts', 'lafka_typography_enqueue_google_font' );

/**
 * Enqueues the Google $font that is passed
 */
if ( ! function_exists( 'lafka_typography_enqueue_google_font' ) ) {
	function lafka_typography_enqueue_google_font() {
		// P6-PERF-3: Rubik is self-hosted via @font-face in style.css — strip it from the
		// Google Fonts CDN URL so it is never fetched from fonts.googleapis.com.
		$url = lafka_typography_google_fonts_url();
		if ( ! empty( $url ) ) {
			// Parse and rebuild the family param, removing 'Rubik' entries.
			$parsed = wp_parse_url( $url );
			if ( isset( $parsed['query'] ) ) {
				parse_str( $parsed['query'], $params );
				if ( isset( $params['family'] ) ) {
					$families = array_filter(
						explode( '|', $params['family'] ),
						function ( $f ) {
							return 0 !== strpos( $f, 'Rubik' );
						}
					);
					if ( empty( $families ) ) {
						return; // Only 'Rubik' was requested — nothing to fetch from CDN.
					}
					$params['family'] = implode( '|', $families );
					$url              = ( isset( $parsed['scheme'] ) ? $parsed['scheme'] . ':' : '' )
						. '//' . $parsed['host']
						. ( isset( $parsed['path'] ) ? $parsed['path'] : '' )
						. '?' . http_build_query( $params );
				}
			}
		}
		wp_enqueue_style( 'lafka-fonts', $url, array(), false, 'print' );
	}
}

add_filter( 'style_loader_tag', 'lafka_style_loader_tag_filter', 10, 2 );
if ( ! function_exists( 'lafka_style_loader_tag_filter' ) ) {
	function lafka_style_loader_tag_filter( $html, $handle ) {
		if ( in_array( $handle, array( 'lafka-fonts', 'font_awesome_6_v4shims', 'font_awesome_6', 'et-line-font', 'flaticon' ), true ) ) {
			$link_stylesheet = str_replace( "rel='stylesheet'", "rel='stylesheet' onload=\"this.media='all'\"", $html );
			$link_preload    = str_replace( "rel='stylesheet'", "rel='preload' as='style'", $html );
			$link_preload    = str_replace( "media='print'", '', $link_preload );
			$link_preload    = str_replace( "id='" . $handle . "-css'", '', $link_preload );

			return $link_preload . $link_stylesheet;
		} elseif ( in_array( $handle, array( 'feather', 'tiza' ), true ) ) {
			$link_preload = str_replace( "rel='stylesheet'", "rel='preload' as='font'", $html );
			$link_preload = str_replace( "type='text/css'", "type='font/woff' crossorigin='anonymous'", $link_preload );
			$link_preload = str_replace( "media='all'", '', $link_preload );

			return $link_preload;
		}

		return $html;
	}
}

if ( ! function_exists( 'lafka_is_block_cart_checkout_page' ) ) {
	/**
	 * Whether the current request renders WooCommerce's BLOCK Cart or Checkout
	 * (NX1-04b). True only when the queried page actually contains the
	 * `woocommerce/cart` or `woocommerce/checkout` block AND Lafka is in blocks
	 * checkout mode.
	 *
	 * The mode gate reads the plugin's SSOT helper (Lafka_Checkout_Mode) behind a
	 * class_exists guard so the theme still degrades to plain-WC block styling when
	 * the plugin is absent, and NEVER treats a classic-mode page (where the shim
	 * serves the shortcode checkout, or the pages are physically shortcodes) as a
	 * block page. Used to gate both the block-checkout stylesheet and the
	 * defer-suppression below — the WooCommerce Blocks runtime must not be
	 * `defer`-reordered (see lafka_defer_non_critical_scripts).
	 *
	 * @return bool
	 */
	function lafka_is_block_cart_checkout_page() {
		if ( ! function_exists( 'has_block' ) ) {
			return false;
		}
		// Classic mode ⇒ never a block page (shim serves shortcodes). Absent
		// plugin ⇒ fall through and style whatever WC block pages exist.
		if ( class_exists( 'Lafka_Checkout_Mode' ) && ! Lafka_Checkout_Mode::is_blocks() ) {
			return false;
		}

		return has_block( 'woocommerce/checkout' ) || has_block( 'woocommerce/cart' );
	}
}

if ( ! function_exists( 'lafka_is_legacy_blog_surface' ) ) {
	/**
	 * Whether the current request renders the classic LEGACY blog layout
	 * (content.php via index.php / archive.php / search.php, or single.php) whose
	 * blog / widget / sidebar CSS was extracted from the style.css monolith into
	 * styles/legacy-blog.css (NX1-10a).
	 *
	 * Deliberately blog-SPECIFIC: is_singular('post') (not is_single(), which is
	 * also true for single products) and the blog taxonomies (not is_archive(),
	 * which is also true for the WooCommerce shop/product archives). The posts
	 * index is gated with `is_home() && ! is_front_page()` so that when
	 * show_on_front=posts — where `/` is is_home() yet renders the DESIGNED
	 * front-page.php, not the blog layout — the handoff home does not pull this
	 * sheet. The six handoff routes (home, /menu/, PDP, cart, checkout) match
	 * none of these, so they never download legacy-blog.css. Comment/review CSS
	 * is NOT here — it stays in style.css because it also styles WooCommerce
	 * product reviews.
	 *
	 * @return bool
	 */
	function lafka_is_legacy_blog_surface() {
		return ( is_home() && ! is_front_page() )
			|| is_category() || is_tag() || is_author() || is_date()
			|| is_singular( 'post' )
			|| is_search()
			|| is_attachment();
	}
}

if ( ! function_exists( 'lafka_needs_legacy_shortcode_styles' ) ) {
	/**
	 * Whether the current request may render legacy lafka_* shortcode / WPBakery
	 * / foodmenu-grid / post-slider markup whose CSS was extracted into
	 * styles/legacy-shortcodes.css (NX1-10a). Loaded on: the blog surfaces (post
	 * galleries/sliders), the legacy foodmenu CPT, and any singular content whose
	 * post_content embeds a lafka_* shortcode or WPBakery row. The handoff routes
	 * carry none of that markup, so they never download it.
	 *
	 * @return bool
	 */
	function lafka_needs_legacy_shortcode_styles() {
		if ( lafka_is_legacy_blog_surface() ) {
			return true;
		}
		if ( is_post_type_archive( 'lafka_foodmenu' )
			|| is_singular( 'lafka_foodmenu' )
			|| ( function_exists( 'is_tax' ) && is_tax( 'lafka_foodmenu_category' ) ) ) {
			return true;
		}
		if ( is_singular() && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
			$content = (string) $GLOBALS['post']->post_content;
			if ( false !== strpos( $content, '[lafka_' )
				|| false !== strpos( $content, 'vc_row' )
				|| false !== strpos( $content, '[vc_' ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Register / Enqueue theme scripts
 */
add_action( 'wp_enqueue_scripts', 'lafka_enqueue_scripts_and_styles' );
if ( ! function_exists( 'lafka_enqueue_scripts_and_styles' ) ) {

	function lafka_enqueue_scripts_and_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// v5.26.0: design-system tokens — enqueued FIRST so every other
		// Lafka stylesheet can read --lafka-* custom properties. See
		// styles/lafka-tokens.css for the full token list.
		wp_enqueue_style( 'lafka-tokens', get_template_directory_uri() . '/styles/lafka-tokens.css', array(), lafka_asset_version( '/styles/lafka-tokens.css' ) );

		// v6.13.0: parent baseline a11y/CLS rules for markup the parent itself
		// emits (.section-subtitle, .ingredients, .screen-reader-text, owl
		// reservation). Previously these lived only in lafka-child, leaving the
		// OSS parent non-accessible on its own. (Audit 2026-06-27 #6.)
		wp_enqueue_style( 'lafka-base', get_template_directory_uri() . '/styles/lafka-base.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-base.css' ) );

		// v6.13.0: header search overlay — only when the search icon is shown
		// (Customizer "show_searchform"). Wires the otherwise-dead header
		// trigger to a native <dialog>. (Audit 2026-06-27 #3.)
		if ( function_exists( 'lafka_get_option' ) && get_theme_mod( 'lafka_show_searchform', true ) ) {
			wp_enqueue_style( 'lafka-search', get_template_directory_uri() . '/styles/lafka-search.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-search.css' ) );
			wp_enqueue_script( 'lafka-search', get_template_directory_uri() . '/js/lafka-search.js', array(), lafka_asset_version( '/js/lafka-search.js' ), true );
		}

		// v5.26.0: sticky cart bar — opt-out via Customizer "Lafka — Order
		// Flow". Skips the asset cost on cart/checkout where the bar is
		// suppressed anyway (the partial early-returns there too).
		$lafka_sticky_cart_active = ! ( function_exists( 'is_cart' ) && is_cart() )
			&& ! ( function_exists( 'is_checkout' ) && is_checkout() )
			&& (bool) get_theme_mod( 'lafka_sticky_cart_enabled', true );
		if ( $lafka_sticky_cart_active ) {
			wp_enqueue_style( 'lafka-sticky-cart', get_template_directory_uri() . '/styles/lafka-sticky-cart.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-sticky-cart.css' ) );
			wp_enqueue_script(
				'lafka-sticky-cart',
				get_template_directory_uri() . '/js/lafka-sticky-cart.js',
				array( 'jquery' ),
				lafka_asset_version( '/js/lafka-sticky-cart.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		// v5.28.0: archive-card quick-add — conditionally enqueued on pages that
		// render the product card. v6.14.0: added the custom /menu/ page template
		// + the home featured grid (both use loop/lafka-product-card.php), so the
		// one-tap pill actually works there (previously dead on /menu/ — the
		// highest-traffic surface). Still skips cart/checkout/content pages.
		// page-menu.php is selected via the page-{slug} hierarchy for the 'menu'
		// page, so is_page('menu') — not is_page_template() — is the right check.
		$lafka_archive_quickadd_active = function_exists( 'is_woocommerce' )
			&& ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()
				|| is_page( 'menu' ) || is_front_page() )
			&& (bool) get_theme_mod( 'lafka_archive_quickadd_enabled', true );
		if ( $lafka_archive_quickadd_active ) {
			wp_enqueue_style(
				'lafka-archive-quickadd',
				get_template_directory_uri() . '/styles/lafka-archive-quickadd.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-archive-quickadd.css' )
			);
			wp_enqueue_script(
				'lafka-archive-quickadd',
				get_template_directory_uri() . '/js/lafka-archive-quickadd.js',
				array( 'jquery' ),
				lafka_asset_version( '/js/lafka-archive-quickadd.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		// v5.27.0: PDP sticky CTA (legacy "System A") — conditionally enqueued
		// on single-product pages only. Skips the asset cost everywhere else.
		// Gated OFF when the PDP redesign owns the page: pdp-pickers.js +
		// pdp-summary's .lafka-pdp-mobile-cta already provide the sticky CTA,
		// auto-select and live total. Loading both runs two uncoordinated
		// variation auto-selectors against the same form.variations_form and
		// ships a dead second bottom bar with a full-reload add-to-cart
		// fallback. Both this enqueue and the wp_footer render in
		// template-parts/lafka-pdp-cta.php are independently gated.
		$lafka_pdp_cta_active = function_exists( 'is_product' ) && is_product()
			&& (bool) get_theme_mod( 'lafka_pdp_sticky_cta_enabled', true )
			&& ( ! function_exists( 'lafka_pdp_redesign_enabled' ) || ! lafka_pdp_redesign_enabled() );
		// v5.32.0: empty-cart "Popular" — only on /cart/ and only when
		// the toggle is on. We can't easily know in advance if the cart
		// will be empty server-side, so enqueue on all cart pages.
		$lafka_cart_empty_popular_active = function_exists( 'is_cart' ) && is_cart()
			&& (bool) get_theme_mod( 'lafka_cart_empty_popular_enabled', true );
		if ( $lafka_cart_empty_popular_active ) {
			wp_enqueue_style(
				'lafka-cart-empty-popular',
				get_template_directory_uri() . '/styles/lafka-cart-empty-popular.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-cart-empty-popular.css' )
			);
		}

		// v5.30.0: service ETA strip — enqueued anywhere the strip might
		// appear (header on every page, cart/checkout via WC hooks). Tiny
		// stylesheet (~1 KB), only loads when operator has configured
		// pickup or delivery values.
		$lafka_service_eta_active = function_exists( 'lafka_service_eta_get_data' )
			&& lafka_service_eta_get_data();
		if ( $lafka_service_eta_active ) {
			wp_enqueue_style(
				'lafka-service-eta',
				get_template_directory_uri() . '/styles/lafka-service-eta.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-service-eta.css' )
			);
		}

		// v5.29.0: social-proof widget — enqueued where the widget can render
		// (PDPs for now). Cheap stylesheet (~1 KB), only loads when the
		// operator has configured a rating or review count.
		$lafka_social_proof_active = function_exists( 'is_product' ) && is_product()
			&& (bool) get_theme_mod( 'lafka_social_proof_show_pdp', true )
			&& function_exists( 'lafka_social_proof_get_data' )
			&& lafka_social_proof_get_data();
		if ( $lafka_social_proof_active ) {
			wp_enqueue_style(
				'lafka-social-proof',
				get_template_directory_uri() . '/styles/lafka-social-proof.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-social-proof.css' )
			);
		}

		// v5.70.0: lafka-home.css deleted (deprecated by v5.59 hero +
		// v5.60 sections 2–7 rebuild). Reusable primitives (.lafka-btn,
		// .lafka-status-pill) moved into lafka-components.css and now
		// loaded site-wide below.

		// v5.70.0: shared component primitives (.lafka-btn, .lafka-status-pill).
		wp_enqueue_style(
			'lafka-components',
			get_template_directory_uri() . '/styles/lafka-components.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-components.css' )
		);

		// v5.51.0: site-wide promo bar. Renders via wp_body_open on every
		// page (or hides itself if not enabled in Customizer). Tiny CSS
		// (~1.5 KB), zero cost when disabled because the partial returns
		// early before any markup.
		wp_enqueue_style(
			'lafka-promo-bar',
			get_template_directory_uri() . '/styles/lafka-promo-bar.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-promo-bar.css' )
		);

		// v5.54.0: site-wide announce bar (dark strip with live open/closed
		// status, delivery info, phone). Renders via wp_body_open priority 5
		// so it sits above the promo bar. CSS + JS are both tiny.
		wp_enqueue_style(
			'lafka-announce-bar',
			get_template_directory_uri() . '/styles/lafka-announce-bar.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-announce-bar.css' )
		);
		wp_enqueue_script(
			'lafka-announce-bar',
			get_template_directory_uri() . '/js/lafka-announce-bar.js',
			array(),
			lafka_asset_version( '/js/lafka-announce-bar.js' ),
			true
		);

		// v5.55.0: header chrome. CSS for the rebuilt header.php
		// (handoff-spec single-row layout). No JS — Order now CTA and
		// cart count are rendered server-side in PHP.
		wp_enqueue_style(
			'lafka-header-chrome',
			get_template_directory_uri() . '/styles/lafka-header-chrome.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-header-chrome.css' )
		);

		// v5.56.0: mobile slide-out nav drawer. CSS + small JS for the
		// toggle / scroll-lock / ESC-close behaviour.
		wp_enqueue_style(
			'lafka-mobile-nav',
			get_template_directory_uri() . '/styles/lafka-mobile-nav.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-mobile-nav.css' )
		);
		wp_enqueue_script(
			'lafka-mobile-nav',
			get_template_directory_uri() . '/js/lafka-mobile-nav.js',
			array(),
			lafka_asset_version( '/js/lafka-mobile-nav.js' ),
			true
		);

		// v5.57.0: cart drawer (right slide-out, WC AJAX). CSS + JS run
		// on every page so the .lafka-header__cart icon can open it
		// from anywhere — used to be PDP-gated.
		if ( defined( 'LAFKA_IS_WOOCOMMERCE' ) && LAFKA_IS_WOOCOMMERCE ) {
			wp_enqueue_style(
				'lafka-cart-drawer',
				get_template_directory_uri() . '/styles/lafka-cart-drawer.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-cart-drawer.css' )
			);
			wp_enqueue_script(
				'lafka-cart-drawer',
				get_template_directory_uri() . '/js/cart-drawer.js',
				array( 'jquery' ),
				lafka_asset_version( '/js/cart-drawer.js' ),
				true
			);

			// v6.9.0 (Pillar 3A): free-delivery progress component CSS +
			// tracker JS. CSS site-wide because the drawer ships on every
			// WC page (header cart icon can open it from anywhere). JS
			// site-wide for the same reason — it also wires
			// window.lafkaDataLayer.cartSnapshot for the plugin's
			// sticky_cart_open analytics event.
			wp_enqueue_style(
				'lafka-free-delivery-progress',
				get_template_directory_uri() . '/styles/lafka-free-delivery-progress.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-free-delivery-progress.css' )
			);
			wp_enqueue_script(
				'lafka-fdp-tracker',
				get_template_directory_uri() . '/js/lafka-fdp-tracker.js',
				array( 'jquery' ),
				lafka_asset_version( '/js/lafka-fdp-tracker.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			// v6.10.0 (Pillar 3C): exit-intent reminder toast. Loads when
			// the operator has enabled it AND we're not on a conversion
			// page (cart / checkout / order-received / my-account). On a
			// conversion page the toast would be redundant — the customer
			// is already in the funnel. Script also bails internally on
			// missing cartSnapshot, missing payload, or session-shown flag,
			// so this server-side gate is purely an asset-cost optimisation.
			$lafka_exit_intent_enabled = (bool) get_theme_mod( 'lafka_exit_intent_enabled', false );
			$lafka_on_conversion_page  = ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() )
				|| ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) );
			if ( $lafka_exit_intent_enabled && ! $lafka_on_conversion_page ) {
				wp_enqueue_style(
					'lafka-exit-intent',
					get_template_directory_uri() . '/styles/lafka-exit-intent.css',
					array( 'lafka-tokens' ),
					lafka_asset_version( '/styles/lafka-exit-intent.css' )
				);
				wp_enqueue_script(
					'lafka-exit-intent',
					get_template_directory_uri() . '/js/lafka-exit-intent.js',
					// Depends on lafka-fdp-tracker so cartSnapshot is wired
					// before the toast tries to read it. fdp-tracker has
					// jquery as its only dep so we inherit that chain.
					array( 'lafka-fdp-tracker' ),
					lafka_asset_version( '/js/lafka-exit-intent.js' ),
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
				$lafka_exit_intent_cart_url = function_exists( 'wc_get_cart_url' )
					? wc_get_cart_url()
					: home_url( '/cart/' );
				wp_localize_script(
					'lafka-exit-intent',
					'lafkaExitIntentSettings',
					array(
						'enabled'            => true,
						'gracePeriodSeconds' => (int) get_theme_mod( 'lafka_exit_intent_grace_seconds', 30 ),
						// Path tokens — JS uses indexOf so a token like
						// '/cart/' matches '/wp-shop/cart/' on subdir installs.
						'pageBlocklist'      => array(
							'/cart/',
							'/checkout/',
							'/order-received/',
							'/my-account/',
						),
						'cartUrl'            => $lafka_exit_intent_cart_url,
						'headlineBelow'      => get_theme_mod( 'lafka_exit_intent_headline_below', __( 'Add {amount} more for free delivery', 'lafka' ) ),
						'headlineReached'    => get_theme_mod( 'lafka_exit_intent_headline_reached', __( 'Your cart is ready — checkout in 30 seconds', 'lafka' ) ),
						'bodyText'           => get_theme_mod( 'lafka_exit_intent_body', __( 'Tap below to pick up where you left off.', 'lafka' ) ),
						'ctaLabel'           => get_theme_mod( 'lafka_exit_intent_cta_label', __( 'Resume checkout', 'lafka' ) ),
						'dismissLabel'       => get_theme_mod( 'lafka_exit_intent_dismiss_label', __( 'Maybe later', 'lafka' ) ),
						'closeAriaLabel'     => __( 'Close reminder', 'lafka' ),
					)
				);
			}

			// v6.11.0 (Pillar 3D): post-purchase review banner. Loads when the
			// operator has enabled it AND we're not on a conversion page (the
			// partial double-gates the same blocklist server-side, but enqueue
			// is still gated so we don't ship the assets on /cart/ etc.).
			// The banner is rendered from a wp_footer partial only when the
			// plugin has set the `lafka_review_prompt_show` cookie (server-side
			// gate — checks the current user's completed-order recency).
			$lafka_review_banner_enabled = '1' === (string) get_theme_mod( 'lafka_review_banner_enabled', '0' );
			$lafka_review_on_conv_page   = ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() )
				|| ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) );
			if ( $lafka_review_banner_enabled && ! $lafka_review_on_conv_page ) {
				wp_enqueue_style(
					'lafka-review-banner',
					get_template_directory_uri() . '/styles/lafka-review-banner.css',
					array( 'lafka-tokens' ),
					lafka_asset_version( '/styles/lafka-review-banner.css' )
				);
				wp_enqueue_script(
					'lafka-review-banner',
					get_template_directory_uri() . '/js/lafka-review-banner.js',
					array(),
					lafka_asset_version( '/js/lafka-review-banner.js' ),
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
				wp_localize_script(
					'lafka-review-banner',
					'lafkaReviewBannerSettings',
					array(
						'restRoot'      => esc_url_raw( rest_url() ),
						'restNonce'     => function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '',
						'pageBlocklist' => array(
							'/cart/',
							'/checkout/',
							'/order-received/',
							'/my-account/',
						),
					)
				);
			}

			// v6.12.0 (Pillar 3E): Web Push subscribe prompt. Loads when both
			// the master `lafka_push_enabled` toggle AND the prompt channel
			// toggle are ON, AND the operator has pasted a VAPID public key,
			// AND we're not on a conversion page. The JS additionally gates on
			// pageview count + Notification.permission state + 30-day
			// suppression localStorage.
			$lafka_push_master_on  = '1' === (string) get_theme_mod( 'lafka_push_enabled', '0' );
			$lafka_push_prompt_on  = '1' === (string) get_theme_mod( 'lafka_push_subscribe_prompt_enabled', '1' );
			$lafka_push_vapid_pub  = (string) get_theme_mod( 'lafka_push_vapid_public_key', '' );
			$lafka_push_on_conv    = ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() )
				|| ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) );
			if ( $lafka_push_master_on && $lafka_push_prompt_on && '' !== $lafka_push_vapid_pub && ! $lafka_push_on_conv ) {
				wp_enqueue_style(
					'lafka-push-prompt',
					get_template_directory_uri() . '/styles/lafka-push-prompt.css',
					array( 'lafka-tokens' ),
					lafka_asset_version( '/styles/lafka-push-prompt.css' )
				);
				wp_enqueue_script(
					'lafka-push-subscribe',
					get_template_directory_uri() . '/js/lafka-push-subscribe.js',
					array(),
					lafka_asset_version( '/js/lafka-push-subscribe.js' ),
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
				wp_localize_script(
					'lafka-push-subscribe',
					'lafkaPushSettings',
					array(
						'enabled'              => true,
						'applicationServerKey' => $lafka_push_vapid_pub,
						'restRoot'             => esc_url_raw( rest_url() ),
						'restNonce'            => function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '',
						'threshold'            => (int) get_theme_mod( 'lafka_push_subscribe_prompt_threshold', 2 ),
						'swUrl'                => esc_url_raw( get_template_directory_uri() . '/js/sw.js' ),
					)
				);
			}
		}

		// v5.58.0: footer chrome — handoff-spec 4-col dark footer.
		wp_enqueue_style(
			'lafka-footer-chrome',
			get_template_directory_uri() . '/styles/lafka-footer-chrome.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-footer-chrome.css' )
		);

		// v5.59.0+: home page sections — handoff rebuild. Hero in its own
		// file, sections 2–7 in lafka-home-v2.css. Only loads on the
		// front page.
		if ( is_front_page() ) {
			wp_enqueue_style(
				'lafka-hero',
				get_template_directory_uri() . '/styles/lafka-hero.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-hero.css' )
			);
			wp_enqueue_style(
				'lafka-home-v2',
				get_template_directory_uri() . '/styles/lafka-home-v2.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-home-v2.css' )
			);
		}

		// v5.61.0: menu archive — handoff. Loads on shop + product
		// taxonomies. Also pulls in lafka-home-v2 because product cards
		// reuse the .lafka-favs__card component.
		// v5.86.0: also load on the /menu/ slug template (page-menu.php
		// is now a true template emitting the same handoff structure).
		// v5.88.0: page-menu.php is a SLUG template (auto-mapped by WP
		// based on filename), not a registered page-template via meta,
		// so is_page_template() returns false and is_page('menu') alone
		// proved unreliable on caching layers. Use the post_name check
		// directly — the template loader has already resolved which
		// page is rendering by the time wp_enqueue_scripts fires.
		$lafka_is_menu_slug = false;
		if ( function_exists( 'is_page' ) && is_page() ) {
			$lafka_queried = get_queried_object();
			if ( $lafka_queried && isset( $lafka_queried->post_name ) && 'menu' === $lafka_queried->post_name ) {
				$lafka_is_menu_slug = true;
			}
		}
		if (
			( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() ) )
			|| $lafka_is_menu_slug
		) {
			wp_enqueue_style(
				'lafka-home-v2',
				get_template_directory_uri() . '/styles/lafka-home-v2.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-home-v2.css' )
			);
			wp_enqueue_style(
				'lafka-menu-archive',
				get_template_directory_uri() . '/styles/lafka-menu-archive.css',
				array( 'lafka-tokens', 'lafka-home-v2' ),
				lafka_asset_version( '/styles/lafka-menu-archive.css' )
			);
			// v5.68.0: menu controls — fulfilment toggle + search + dietary chips.
			wp_enqueue_script(
				'lafka-menu-controls',
				get_template_directory_uri() . '/js/lafka-menu-controls.js',
				array(),
				lafka_asset_version( '/js/lafka-menu-controls.js' ),
				true
			);
		}

		// v5.62.0: PDP handoff polish — layered on top of pdp-redesign.css
		// to align type, colour, and spacing without a markup rewrite.
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_style(
				'lafka-pdp-handoff',
				get_template_directory_uri() . '/styles/lafka-pdp-handoff.css',
				array( 'lafka-tokens', 'lafka-pdp-redesign' ),
				lafka_asset_version( '/styles/lafka-pdp-handoff.css' )
			);
		}

		// v5.64.0: cart page — handoff. Loads on the cart page only.
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_style(
				'lafka-cart-handoff',
				get_template_directory_uri() . '/styles/lafka-cart-handoff.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-cart-handoff.css' )
			);
			// v5.68.0: cart page controls — pickup/delivery tabs + clear-order.
			wp_enqueue_script(
				'lafka-cart-controls',
				get_template_directory_uri() . '/js/lafka-cart-controls.js',
				array(),
				lafka_asset_version( '/js/lafka-cart-controls.js' ),
				true
			);
		}

		// v5.65.0: checkout + order-received — handoff. Same CSS file
		// covers both pages. Also re-applied on cart for empty-state polish.
		$lafka_is_checkout_flow = ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) )
			|| ( function_exists( 'is_cart' ) && is_cart() );
		if ( $lafka_is_checkout_flow ) {
			wp_enqueue_style(
				'lafka-checkout-handoff',
				get_template_directory_uri() . '/styles/lafka-checkout-handoff.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-checkout-handoff.css' )
			);
		}

		// NX1-04b: block Cart/Checkout skin. Applies the handoff visual language
		// to WooCommerce's block cart + checkout and to the plugin's lafka- block
		// components (order_type/branch fields, timeslot picker, free-delivery
		// progress, addon item_data lines). CONDITIONAL — only when the page
		// actually renders a block cart/checkout AND Lafka is in blocks mode
		// (never in classic mode; the handoff sheets above own the shortcode
		// path). Deliberately kept OUT of the always-on asset budget.
		if ( lafka_is_block_cart_checkout_page() ) {
			wp_enqueue_style(
				'lafka-blocks-checkout',
				get_template_directory_uri() . '/styles/lafka-blocks-checkout.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-blocks-checkout.css' )
			);
		}

		// v5.66.0: contact (template-contact.php) + 404. Single CSS file
		// covers both pages. Contact via page-template check, 404 via is_404().
		// v6.7.8: also enqueue when the page slug is "contact" or "contact-us"
		// so operators who created the page on the default template (not the
		// Lafka editorial template) still get FAQ + NAP styling — without
		// this the .lafka-contact__faq markup emitted from the page content
		// rendered as an unstyled <details> list with a bare "+" suffix.
		$lafka_is_404 = function_exists( 'is_404' ) ? is_404() : false;
		$lafka_is_contact_tpl = false;
		if ( function_exists( 'is_page_template' ) ) {
			$lafka_is_contact_tpl = is_page_template( 'template-contact.php' );
		}
		$lafka_is_contact_slug = false;
		if ( function_exists( 'is_page' ) && is_page() && function_exists( 'get_post_field' ) ) {
			$lafka_slug = (string) get_post_field( 'post_name' );
			$lafka_is_contact_slug = in_array( $lafka_slug, array( 'contact', 'contact-us' ), true );
		}
		if ( $lafka_is_404 || $lafka_is_contact_tpl || $lafka_is_contact_slug ) {
			wp_enqueue_style(
				'lafka-404-contact',
				get_template_directory_uri() . '/styles/lafka-404-contact.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-404-contact.css' )
			);
		}

		// v5.75.0: static page reading layout (About / FAQ / Privacy /
		// Terms / generic). Loads on any singular page except the
		// front page and custom templates that supply their own
		// stylesheet (contact, menu page, editorial templates).
		$lafka_skip_page_css = false;
		if ( function_exists( 'is_page_template' ) ) {
			$lafka_skip_page_templates = array(
				'template-contact.php',
				'page-menu.php',
				'page_templates/template-editorial-home.php',
				'page_templates/template-editorial-contact.php',
				'page_templates/blank-page.php',
			);
			foreach ( $lafka_skip_page_templates as $lafka_skip_tpl ) {
				if ( is_page_template( $lafka_skip_tpl ) ) {
					$lafka_skip_page_css = true;
					break;
				}
			}
		}
		$lafka_is_static_page = is_singular( 'page' )
			&& ! ( function_exists( 'is_front_page' ) && is_front_page() )
			&& ! $lafka_skip_page_css;
		// v5.76.0: also load lafka-page.css for single blog posts —
		// single.php reuses .lafka-page primitives + adds .lafka-post__*.
		$lafka_is_blog_single = function_exists( 'is_single' ) ? is_single() : false;
		if ( $lafka_is_static_page || $lafka_is_blog_single ) {
			wp_enqueue_style(
				'lafka-page',
				get_template_directory_uri() . '/styles/lafka-page.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-page.css' )
			);
		}

		// v5.39.0: tokenized account & WC forms — login / register /
		// lost-password / order-tracking / dashboard. is_account_page()
		// covers every WC account endpoint plus the order-tracking page.
		$lafka_account_styles_active = function_exists( 'is_account_page' )
			&& ( is_account_page() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-tracking' ) ) );
		if ( $lafka_account_styles_active ) {
			wp_enqueue_style(
				'lafka-account',
				get_template_directory_uri() . '/styles/lafka-account.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-account.css' )
			);
		}

		// v5.31.0: topping chip grid — CSS-only transformation of the
		// WC Product Add-Ons checkbox groups into a 2-col chip toggle.
		// Cheap stylesheet, only on PDPs, opt-out via Customizer.
		$lafka_pdp_topping_chips_active = function_exists( 'is_product' ) && is_product()
			&& (bool) get_theme_mod( 'lafka_pdp_topping_chips', true );
		if ( $lafka_pdp_topping_chips_active ) {
			wp_enqueue_style(
				'lafka-pdp-toppings',
				get_template_directory_uri() . '/styles/lafka-pdp-toppings.css',
				array( 'lafka-tokens' ),
				lafka_asset_version( '/styles/lafka-pdp-toppings.css' )
			);
		}

		if ( $lafka_pdp_cta_active ) {
			wp_enqueue_style( 'lafka-pdp-cta', get_template_directory_uri() . '/styles/lafka-pdp-cta.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-pdp-cta.css' ) );
			wp_enqueue_script(
				'lafka-pdp-cta',
				get_template_directory_uri() . '/js/lafka-pdp-cta.js',
				array( 'jquery' ),
				lafka_asset_version( '/js/lafka-pdp-cta.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			$lafka_pdp_strategy             = get_theme_mod( 'lafka_pdp_default_variation_strategy', 'median' );
			$lafka_pdp_default_variation_id = (int) apply_filters( 'lafka_pdp_default_variation', 0, get_the_ID() );

			// v5.27.1: read WC's product-level "Default form values" first.
			// These are operator-set per product (WooCommerce → Product →
			// Variations → Default form values) and represent the intended
			// initial selection. Falling back to algorithmic median only
			// when these aren't set avoids surfacing niche choices
			// (e.g. gluten-free crust) as the auto-select.
			$lafka_pdp_wc_defaults = array();
			if ( function_exists( 'wc_get_product' ) ) {
				$lafka_pdp_product = wc_get_product( get_the_ID() );
				if ( $lafka_pdp_product && method_exists( $lafka_pdp_product, 'get_default_attributes' ) ) {
					$lafka_pdp_raw_defaults = (array) $lafka_pdp_product->get_default_attributes();
					foreach ( $lafka_pdp_raw_defaults as $lafka_pdp_attr_name => $lafka_pdp_attr_value ) {
						if ( $lafka_pdp_attr_value ) {
							$lafka_pdp_wc_defaults[ 'attribute_' . $lafka_pdp_attr_name ] = $lafka_pdp_attr_value;
						}
					}
				}
			}

			wp_localize_script(
				'lafka-pdp-cta',
				'lafkaPdpCtaConfig',
				array(
					'defaultStrategy'    => $lafka_pdp_strategy,
					'defaultVariationId' => $lafka_pdp_default_variation_id,
					'wcDefaultAttrs'     => $lafka_pdp_wc_defaults,
					'currencySymbol'     => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '$',
					'addLabel'           => __( 'Add', 'lafka' ),
					'pickLabel'          => __( 'Select options', 'lafka' ),
					'outOfStockLabel'    => __( 'Out of stock', 'lafka' ),
				)
			);
		}

		// Preloader style
		if ( get_theme_mod( 'lafka_show_preloader', true ) ) {
			wp_enqueue_style( 'lafka-preloader', get_template_directory_uri() . '/styles/lafka-preloader.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-preloader.css' ) );
		}

		// Load the main stylesheet (use template URI so parent styles load even with a child theme).
		wp_enqueue_style( 'lafka-style', get_template_directory_uri() . '/style.css', array( 'lafka-tokens' ), wp_get_theme( get_template() )->get( 'Version' ) );

		// NX1-10a: the legacy monolith remainder, split out of style.css into
		// scoped sheets that load ONLY on the surfaces which render the matching
		// legacy markup. Every rule in them was proven (scripts/nx1-10a-extract.mjs)
		// to match zero elements on the six handoff pages, so home/menu/PDP/cart/
		// checkout download none of it. Each depends on lafka-style so its
		// @layer legacy rules keep the monolith's original source order (below the
		// unlayered modular sheets).
		$lafka_legacy_ver = wp_get_theme( get_template() )->get( 'Version' );
		if ( lafka_is_legacy_blog_surface() ) {
			wp_enqueue_style( 'lafka-legacy-blog', get_template_directory_uri() . '/styles/legacy-blog.css', array( 'lafka-style' ), $lafka_legacy_ver );
		}
		if ( lafka_needs_legacy_shortcode_styles() ) {
			wp_enqueue_style( 'lafka-legacy-shortcodes', get_template_directory_uri() . '/styles/legacy-shortcodes.css', array( 'lafka-style' ), $lafka_legacy_ver );
		}
		if ( function_exists( 'is_bbpress' ) ) {
			wp_enqueue_style( 'lafka-legacy-forum', get_template_directory_uri() . '/styles/legacy-forum.css', array( 'lafka-style' ), $lafka_legacy_ver );
		}
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			wp_enqueue_style( 'lafka-legacy-events', get_template_directory_uri() . '/styles/legacy-events.css', array( 'lafka-style' ), $lafka_legacy_ver );
		}

		// v5.40.0: tokenized WC notices (success / error / info). Loads
		// site-wide after lafka-style so source order wins over the legacy
		// WC notice rules without specificity bumps.
		wp_enqueue_style(
			'lafka-notices',
			get_template_directory_uri() . '/styles/lafka-notices.css',
			array( 'lafka-style', 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-notices.css' )
		);
		// Load the rtl stylesheet.
		if ( is_rtl() ) {
			wp_enqueue_style( 'lafka-rtl', get_template_directory_uri() . '/styles/rtl.css', array( 'lafka-style' ), wp_get_theme()->get( 'Version' ) );
		}

		// Load the responsive stylesheet if enabled
		if ( get_theme_mod( 'lafka_is_responsive', true ) ) {
			wp_enqueue_style( 'lafka-responsive', get_template_directory_uri() . '/styles/lafka-responsive.css', array( 'lafka-style' ), lafka_asset_version( '/styles/lafka-responsive.css' ) );
		}

		wp_enqueue_style( 'font_awesome_6_v4shims', get_template_directory_uri() . '/styles/font-awesome/css/v4-shims.min.css', array(), lafka_asset_version( '/styles/font-awesome/css/v4-shims.min.css' ), 'print' );
		wp_enqueue_style( 'font_awesome_6', get_template_directory_uri() . '/styles/font-awesome/css/all.min.css', array( 'font_awesome_6_v4shims' ), lafka_asset_version( '/styles/font-awesome/css/all.min.css' ), 'print' );

		// P6-PERF-4 (W3-T2, 2026-04-28): et-line-font loaded conditionally — only
		// enqueue when the current page content contains a VC/Lafka icon shortcode
		// with type="etline". The font is ~80 KB; most pages have no etline icons.
		// Admin enqueue (line ~433) is unaffected — the icon picker still needs it.
		$current_post_content = ( is_singular() && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post )
			? (string) $GLOBALS['post']->post_content
			: '';
		$has_etline  = false !== strpos( $current_post_content, 'type="etline"' )
			|| false !== strpos( $current_post_content, "type='etline'" );
		$has_flaticon = false !== strpos( $current_post_content, 'type="flaticon"' )
			|| false !== strpos( $current_post_content, "type='flaticon'" )
			|| false !== strpos( $current_post_content, 'i_type="flaticon"' )
			|| false !== strpos( $current_post_content, "i_type='flaticon'" )
			|| false !== strpos( $current_post_content, 'icon_flaticon=' );

		if ( $has_etline ) {
			wp_enqueue_style( 'et-line-font', get_template_directory_uri() . '/styles/et-line-font/style.css', array(), lafka_asset_version( '/styles/et-line-font/style.css' ), 'print' );
		}

		// P6-PERF-4 (W3-T2, 2026-04-28): flaticon loaded conditionally — same
		// pattern as et-line-font above. Flaticon is used by lafka_icon and
		// lafka_icon_teaser shortcodes with type="flaticon". ~80 KB saved on pages
		// that don't use those shortcodes.
		// Admin enqueue (line ~438) is unaffected — the menu icon picker still needs it.
		if ( $has_flaticon ) {
			wp_enqueue_style( 'flaticon', get_template_directory_uri() . '/styles/flaticon/font/flaticon.css', false, lafka_asset_version( '/styles/flaticon/font/flaticon.css' ), 'print' );
		}

		// `tiza.woff` (159 KB) and `feather.woff` (29 KB) used to be enqueued as
		// stylesheets — Chrome treated them as render-blocking CSS, charged them
		// to the CSS budget, and logged "preload but not used" warnings on every
		// page. The actual @font-face declarations live in et-line-font/style.css
		// + flaticon/font/flaticon.css above. Removed in P5-Sec/Perf audit
		// (Session 4) — saves 188 KB of render-blocking weight per page.

		// Modernizr removed — custom build only exposed `window.Modernizr.touch`, which no
		// theme/plugin code actually reads. Modern browsers all support pointer events natively.
		//
		// NiceScroll removed — modern browsers ship native momentum scrolling on iOS/Android,
		// and on desktop the OS scrollbar is the right thing. lafka-front.js calls .niceScroll()
		// in two places; those calls are guarded by a `typeof $.fn.niceScroll === 'function'`
		// check so they no-op safely when the lib is absent.

		/* loading jquery-ui-slider only for price filter */
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_show_pricefilter', true ) && is_woocommerce() && ! is_product() ) {
			wp_enqueue_script( 'jquery-ui-slider' );
		}

		$cart_redirect_after_add = 'no';
		$cart_url                = '';
		if ( LAFKA_IS_WOOCOMMERCE && get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
			$cart_redirect_after_add = 'yes';
			$cart_url                = apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null );
		}

		$enable_ajax_add_to_cart = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_ajax_to_cart_single', true ) ) {
			$enable_ajax_add_to_cart = 'yes';
		}

		$enable_infinite_on_shop = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_enable_shop_infinite', true ) ) {
			$enable_infinite_on_shop = 'yes';
		}

		$use_load_more_on_shop = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_use_load_more_on_shop', false ) ) {
			$use_load_more_on_shop = 'yes';
		}

		$use_product_filter_ajax = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_use_product_filter_ajax', true ) ) {
			$use_product_filter_ajax = 'yes';
		}

		$categories_fancy = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_categories_fancy', false ) ) {
			$categories_fancy = 'yes';
		}

		$shopping_cart_on_add = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_shopping_cart_on_add', true ) ) {
			$shopping_cart_on_add = 'yes';
		}

		$order_hours_cart_update = 'no';
		if ( LAFKA_IS_WOOCOMMERCE && class_exists( 'Lafka_Order_Hours' ) && isset( Lafka_Order_Hours::$lafka_order_hours_options['lafka_order_hours_cache_enable'] ) && Lafka_Order_Hours::$lafka_order_hours_options['lafka_order_hours_cache_enable'] ) {
			$order_hours_cart_update = 'yes';
		}

		// jquery-ui-tabs was a transitive dep of the legacy mobile-menu tab
		// switcher (the jQuery UI .tabs() widget). P6-A11Y-3 (W2) replaced
		// that with a native IIFE + ARIA tab pattern in lafka-front.js
		// (see comment at line 808). The dependency is therefore dead
		// weight — WP no longer needs to enqueue ~40 KB of jQuery UI Tabs
		// CSS + JS on every page render. Confirmed by grep: no .tabs( call
		// remains in lafka-front.js or anywhere else in the theme.
		// v6.14.0 (perf): the old hard dependency on wpb_composer_front_js forced
		// WPBakery's front JS onto EVERY page (lafka-front is global) and undid the
		// native-page dequeue. Dropped. v6.18.0: the last dead .vc_* jQuery
		// selectors were also removed from lafka-front.js (theme is WPBakery-free).
		$lafka_front_deps = array( 'jquery', 'lafka-dialog' );

		// PERF-26: WP 6.3 native defer strategy. Inline `wp_localize_script`
		// blocks emitted below are plain object literals, so they evaluate
		// instantly and don't depend on the deferred external script being
		// parsed first.
		wp_enqueue_script(
			'lafka-front',
			get_template_directory_uri() . '/js/lafka-front' . $suffix . '.js',
			$lafka_front_deps,
			lafka_asset_version( '/js/lafka-front' . $suffix . '.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'lafka-front',
			'lafka_main_js_params',
			array(
				'img_path'                => esc_js( LAFKA_IMAGES_PATH ),
				'admin_url'               => esc_js( admin_url( 'admin-ajax.php' ) ),
				'nonce'                   => wp_create_nonce( 'lafka_ajax_nonce' ),
				'product_label'           => esc_js( __( 'Product', 'lafka' ) ),
				'added_to_cart_label'     => esc_js( __( 'was added to the cart', 'lafka' ) ),
				'show_preloader'          => esc_js( get_theme_mod( 'lafka_show_preloader', true ) ),
				'sticky_header'           => esc_js( get_theme_mod( 'lafka_sticky_header', true ) ),
				'enable_smooth_scroll'    => esc_js( get_theme_mod( 'lafka_enable_smooth_scroll', true ) ),
				'login_label'             => esc_js( __( 'Login', 'lafka' ) ),
				'register_label'          => esc_js( __( 'Register', 'lafka' ) ),
				'cart_redirect_after_add' => $cart_redirect_after_add,
				'cart_url'                => $cart_url,
				'enable_ajax_add_to_cart' => $enable_ajax_add_to_cart,
				'enable_infinite_on_shop' => $enable_infinite_on_shop,
				'use_load_more_on_shop'   => $use_load_more_on_shop,
				'use_product_filter_ajax' => $use_product_filter_ajax,
				'categories_fancy'        => $categories_fancy,
				'order_hours_cart_update' => $order_hours_cart_update,
				'shopping_cart_on_add'    => $shopping_cart_on_add,
				'is_rtl'                  => ( is_rtl() ? 'true' : 'false' ),
			)
		);

		/* imagesloaded */
		wp_enqueue_script( 'imagesloaded', '', array( 'jquery' ), false, true );

		// PERF-2/16/17/26: bias every optional library toward `wp_register_*`
		// + a conditional `wp_enqueue_*`, and use the native WP 6.3 defer
		// strategy via the array form of the args param so inline localisations
		// are correctly emitted before the deferred external file runs.
		$footer_defer = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);

		global $post;
		$post_content_for_lib_detect = ( is_singular() && $post instanceof WP_Post ) ? (string) $post->post_content : '';

		// flexslider — `lafka-libs-config.js` calls `$(...).flexslider()`
		// unconditionally on `window.load`, so we keep it enqueued globally.
		// The defer strategy is the real win here. JS guard added at the call
		// site protects against future narrowing.
		wp_enqueue_script( 'flexslider', get_template_directory_uri() . '/js/flex/jquery.flexslider-min.js', array( 'jquery' ), lafka_asset_version( '/js/flex/jquery.flexslider-min.js' ), $footer_defer );
		wp_enqueue_style( 'flexslider', get_template_directory_uri() . '/styles/flex/flexslider.css', array(), lafka_asset_version( '/styles/flex/flexslider.css' ) );
		$flex_enqueue = true;

		// owl-carousel — same story; `lafka-libs-config.js` runs
		// `.owlCarousel()` on multiple selectors at `window.load`. Defer is the
		// safe gain. PERF-2 narrowing here is deferred until the JS file is
		// converted to a "if-element-exists, lazy-import" pattern (P3-05).
		wp_enqueue_script( 'owl-carousel', get_template_directory_uri() . '/js/owl-carousel2-dist/owl.carousel.min.js', array( 'jquery' ), lafka_asset_version( '/js/owl-carousel2-dist/owl.carousel.min.js' ), $footer_defer );
		wp_enqueue_style( 'owl-carousel', get_template_directory_uri() . '/styles/owl-carousel2-dist/assets/owl.carousel.min.css', array(), lafka_asset_version( '/styles/owl-carousel2-dist/assets/owl.carousel.min.css' ) );
		wp_enqueue_style( 'owl-carousel-theme-default', get_template_directory_uri() . '/styles/owl-carousel2-dist/assets/owl.theme.default.min.css', array(), lafka_asset_version( '/styles/owl-carousel2-dist/assets/owl.theme.default.min.css' ) );
		wp_enqueue_style( 'owl-carousel-animate', get_template_directory_uri() . '/styles/owl-carousel2-dist/assets/animate.css', array(), lafka_asset_version( '/styles/owl-carousel2-dist/assets/animate.css' ) );
		$owl_enqueue = true;

		// cloud-zoom — only on single product pages
		wp_register_script( 'cloud-zoom', get_template_directory_uri() . '/js/cloud-zoom/cloud-zoom.1.0.2.min.js', array( 'jquery' ), lafka_asset_version( '/js/cloud-zoom/cloud-zoom.1.0.2.min.js' ), $footer_defer );
		wp_register_style( 'cloud-zoom', get_template_directory_uri() . '/styles/cloud-zoom/cloud-zoom.css', array(), lafka_asset_version( '/styles/cloud-zoom/cloud-zoom.css' ) );
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script( 'cloud-zoom' );
			wp_enqueue_style( 'cloud-zoom' );
		}

		// countdown — only on single product pages when enabled
		wp_register_script( 'jquery-plugin', get_template_directory_uri() . '/js/count/jquery.plugin.min.js', array( 'jquery' ), lafka_asset_version( '/js/count/jquery.plugin.min.js' ), $footer_defer );
		wp_register_script( 'countdown', get_template_directory_uri() . '/js/count/jquery.countdown.min.js', array( 'jquery', 'jquery-plugin' ), lafka_asset_version( '/js/count/jquery.countdown.min.js' ), $footer_defer );
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script( 'countdown' );
		}

		// P3-04: magnific replaced by lafka-dialog (native <dialog>). The
		// vendor magnific.js + css are still registered (plugin side) so
		// the branch-locations modal — which is a critical-path ordering
		// flow and ships as a pre-minified vendor file — can still depend
		// on it. Everywhere else uses window.lafkaDialog.
		wp_enqueue_script( 'lafka-dialog', get_template_directory_uri() . '/js/lafka-dialog' . $suffix . '.js', array(), lafka_asset_version( '/js/lafka-dialog' . $suffix . '.js' ), $footer_defer );
		wp_enqueue_style( 'lafka-dialog', get_template_directory_uri() . '/styles/lafka-dialog.css', array(), lafka_asset_version( '/styles/lafka-dialog.css' ) );

		// jquery.appear + isInViewport replaced by lafkaOnVisible() (IntersectionObserver)
		// inside lafka-front.js — see P3-05. No standalone scripts to enqueue.

		// typed.js v2 — only when the page actually uses `[lafka_typed]`. Lib is
		// ~8 KB minified; loading it everywhere was pure overhead.
		wp_register_script( 'typed', get_template_directory_uri() . '/js/typed.min.js', array(), lafka_asset_version( '/js/typed.min.js' ), $footer_defer );
		if ( is_singular() && false !== strpos( $post_content_for_lib_detect, '[lafka_typed' ) ) {
			wp_enqueue_script( 'typed' );
		}

		// nice-select — `lafka-front.js` calls `$(...).niceSelect()`
		// unconditionally on `document.ready` (now JS-guarded), so keep
		// enqueued globally; defer strategy is the safe perf win.
		wp_enqueue_script( 'nice-select', get_template_directory_uri() . '/js/jquery.nice-select.min.js', array( 'jquery' ), lafka_asset_version( '/js/jquery.nice-select.min.js' ), $footer_defer );
		$nice_enqueue = true;

		// is-in-viewport replaced by native getBoundingClientRect() check in
		// lafka-front.js infinite-scroll handler (P3-05). No script to enqueue.

		// register Isotope
		wp_register_script( 'isotope', get_template_directory_uri() . '/js/isotope/dist/isotope.pkgd.min.js', array( 'jquery', 'imagesloaded' ), lafka_asset_version( '/js/isotope/dist/isotope.pkgd.min.js' ), true );
		if ( is_post_type_archive( 'lafka-foodmenu' ) || is_tax( 'lafka_foodmenu_category' ) || ( get_theme_mod( 'lafka_general_blog_style', '' ) === 'lafka_blog_masonry' && ( is_archive() || is_category() || lafka_is_blog() ) ) ) {
			// load Isotope
			wp_enqueue_script( 'isotope' );
		}

		// enqueue google map api — only when an API key is configured. Without
		// a key Google's loader still serves the JS, but every Geocoding /
		// Places call returns 401 + a console error ("You must use an API key
		// to authenticate each request"). Skip the registration entirely so
		// dependent enqueues fail-closed (handlers null-check `lafka-google-maps`
		// being available).
		$lafka_maps_api_key = lafka_get_option( 'google_maps_api_key' );
		if ( ! empty( $lafka_maps_api_key ) ) {
			wp_register_script(
				'lafka-google-maps',
				'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $lafka_maps_api_key ) . '&sensor=false&callback=Function.prototype',
				array( 'jquery' ),
				false,
				true
			);
		}

		$lafka_local = lafka_wp_lang_to_valid_language_code( get_locale() );
		if ( $lafka_local ) {
			wp_enqueue_script( 'jquery-countdown-local', get_template_directory_uri() . "/js/count/jquery.countdown-$lafka_local.js", array( 'jquery', 'countdown' ), lafka_asset_version( "/js/count/jquery.countdown-$lafka_local.js" ), true );
		}

		$is_compare = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- YITH WooCompare view detection from $_GET['action']; read-only display gating, no state mutation.
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'yith-woocompare-view-table' ) {
			$is_compare = true;
		}

		$to_include_backgr_video = lafka_has_to_include_backgr_video( $is_compare );

		/* JavaScript to pages with the comment form
		 * to support sites with threaded comments (when in use).
		 */
		if ( is_singular() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		/* Include js configs — conditionally loaded scripts removed from hard deps */
		$lafka_libs_deps = array( 'jquery', 'wp-util' );
		if ( $flex_enqueue ) {
			$lafka_libs_deps[] = 'flexslider';
		}
		if ( $owl_enqueue ) {
			$lafka_libs_deps[] = 'owl-carousel';
		}
		if ( wp_script_is( 'typed', 'enqueued' ) ) {
			$lafka_libs_deps[] = 'typed';
		}
		if ( $nice_enqueue ) {
			$lafka_libs_deps[] = 'nice-select';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			$lafka_libs_deps[] = 'cloud-zoom';
			$lafka_libs_deps[] = 'countdown';
		}
		// lafka-libs-config calls window.lafkaDialog — depend on it explicitly
		// so the script load order is correct even when defer is on.
		$lafka_libs_deps[] = 'lafka-dialog';
		wp_enqueue_script( 'lafka-libs-config', get_template_directory_uri() . '/js/lafka-libs-config' . $suffix . '.js', $lafka_libs_deps, lafka_asset_version( '/js/lafka-libs-config' . $suffix . '.js' ), $footer_defer );

		// send is_rtl to js for owl carousel
		wp_localize_script(
			'lafka-libs-config',
			'lafka_rtl',
			array(
				'is_rtl' => ( is_rtl() ? 'true' : 'false' ),
			)
		);

		if ( LAFKA_IS_WOOCOMMERCE && get_theme_mod( 'lafka_use_quickview', true ) ) {
			wp_localize_script(
				'lafka-libs-config',
				'lafka_quickview',
				array(
					'lafka_ajax_url'                   => esc_js( admin_url( 'admin-ajax.php' ) ),
					'nonce'                            => wp_create_nonce( 'lafka_ajax_nonce' ),
					'wc_ajax_url'                      => WC_AJAX::get_endpoint( '%%endpoint%%' ),
					'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'lafka' ),
					'i18n_make_a_selection_text'       => esc_attr__( 'Please select some product options before adding this product to your cart.', 'lafka' ),
					'i18n_unavailable_text'            => esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'lafka' ),
				)
			);
		}

		$search_options = get_theme_mod(
			'lafka_search_options',
			array(
				'use_ajax'      => '1',
				'only_products' => '1',
			)
		);
		if ( get_theme_mod( 'lafka_show_searchform', true ) && $search_options['use_ajax'] ) {
			wp_localize_script(
				'lafka-libs-config',
				'lafka_ajax_search',
				array(
					'include' => 'true',
				)
			);
		}

		if ( LAFKA_IS_WOOCOMMERCE && is_product() ) {
			wp_localize_script(
				'lafka-libs-config',
				'lafka_variation_prod_cloudzoom',
				array(
					'include' => 'true',
				)
			);
		}

		// Register video background plugin
		wp_register_style( 'ytplayer', get_template_directory_uri() . '/styles/jquery.mb.YTPlayer/css/jquery.mb.YTPlayer.min.css', array(), lafka_asset_version( '/styles/jquery.mb.YTPlayer/css/jquery.mb.YTPlayer.min.css' ) );
		wp_register_script( 'ytplayer', get_template_directory_uri() . '/js/jquery.mb.YTPlayer/jquery.mb.YTPlayer.min.js', array( 'jquery' ), lafka_asset_version( '/js/jquery.mb.YTPlayer/jquery.mb.YTPlayer.min.js' ), true );

		// Load video background plugin
		if ( $to_include_backgr_video ) {
			wp_enqueue_style( 'ytplayer' );
			wp_enqueue_script( 'ytplayer' );
			wp_localize_script(
				'lafka-libs-config',
				'lafka_ytplayer_conf',
				array(
					'include' => 'true',
				)
			);
		}

		// Output WP Bakery Page Builder shortcodes custom styles on shop page
		if ( function_exists( 'is_shop' ) ) {
			$shortcodes_custom_css = get_post_meta( wc_get_page_id( 'shop' ), '_wpb_shortcodes_custom_css', true );
			if ( is_shop() && ! empty( $shortcodes_custom_css ) ) {
				wp_add_inline_style( 'lafka-style', esc_html( $shortcodes_custom_css ) );
			}
		}
	}

}

// v5.83.0: dequeue render-blocking assets from plugins that don't apply
// to the current page context. Saves ~120ms LCP on PDP (per Lighthouse
// perf audit 2026-05-15). Runs at priority 99 so plugin enqueues fire
// first and we strip them after.
add_action( 'wp_enqueue_scripts', 'lafka_dequeue_irrelevant_plugin_assets', 99 );
if ( ! function_exists( 'lafka_dequeue_irrelevant_plugin_assets' ) ) {
	function lafka_dequeue_irrelevant_plugin_assets() {
		if ( is_admin() ) {
			return;
		}

		$is_checkout_or_account = false;
		if ( function_exists( 'is_checkout' ) ) {
			$is_checkout_or_account = is_checkout()
				|| ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_cart' ) && is_cart() );
		}

		// Authorize.net CIM gateway assets are only needed on checkout
		// (card form) + account (saved cards). On every other page they
		// add ~30KB of render-blocking CSS for nothing.
		if ( ! $is_checkout_or_account ) {
			wp_dequeue_style( 'sv-wc-payment-gateway-payment-form' );
			wp_dequeue_style( 'wc-authorize-net-cim-checkout-block' );
			wp_dequeue_style( 'wc-authorize-net-cim-credit-card' );
			wp_dequeue_script( 'sv-wc-payment-gateway-payment-form-v5_15_4' );
			wp_dequeue_script( 'wc-authorize-net-cim' );
		}

		// WPBakery (js_composer) frontend assets are only needed on pages
		// that actually use the visual composer. The handoff rebuild
		// emits native partials — PDP / cart / checkout / menu / cart
		// / shop / account / 404 / contact / WC endpoints don't need it.
		$wpbakery_unused_here = is_front_page() // native front-page.php — never renders vc content
			|| ( function_exists( 'is_product' ) && is_product() )
			|| ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_shop' ) && is_shop() )
			|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() )
			|| is_404();
		// v6.14.0 (perf): also drop WPBakery on any singular page/post whose
		// content carries no [vc_ shortcode — those render from native markup, so
		// the 455KB js_composer CSS + JS is pure waste. Pages still built in
		// WPBakery keep it until migrated to native templates.
		if ( ! $wpbakery_unused_here && is_singular() ) {
			$lafka_cur_post = get_post();
			if ( $lafka_cur_post && false === strpos( (string) $lafka_cur_post->post_content, '[vc_' ) ) {
				$wpbakery_unused_here = true;
			}
		}
		if ( $wpbakery_unused_here ) {
			wp_dequeue_style( 'js_composer_front' );
			wp_dequeue_style( 'js_composer_custom_css' );
			wp_dequeue_script( 'wpb_composer_front_js' );
			wp_dequeue_script( 'vc_woocommerce-add-to-cart-js' );
			// Visual Composer's own UI libs that ship even when not used:
			wp_dequeue_script( 'isotope' );
			wp_dequeue_script( 'jquery-waypoints' );
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
		// NX1-04b: never brute-force `defer` on a block Cart/Checkout page. The
		// WooCommerce Blocks runtime is a graph of wp-*/wc-* scripts that carry
		// inline `-before`/`-after` data (the wc-settings Store API preload +
		// nonce, wp-date's moment settings, wp-url). Those inline blocks run
		// synchronously in source order; deferring the EXTERNAL file makes the
		// inline data execute first and throw (normalizePath / moment /
		// setSettings undefined), which leaves the block cart & checkout wedged on
		// their empty-cart fallback and silently blocks every block-mode order.
		// WordPress already orders these correctly without defer, so we simply opt
		// the whole page out (classic pages are unaffected — their cart/checkout
		// are shortcodes, not blocks).
		if ( function_exists( 'lafka_is_block_cart_checkout_page' ) && lafka_is_block_cart_checkout_page() ) {
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
	/**
	 * Preconnect / dns-prefetch hints for third-party origins this theme depends on.
	 *
	 * Preconnect kicks off DNS+TCP+TLS handshakes early so the actual font / map
	 * request finds an open connection waiting. Cuts ~100-300 ms from FCP on
	 * cold visits when those resources are above-the-fold.
	 *
	 * fonts.gstatic.com is always-on (font files); fonts.googleapis.com is
	 * conditional on whether Google Fonts CSS is enqueued; maps.googleapis.com
	 * is conditional on whether a Google Maps API key is set.
	 */
	function lafka_add_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array(
				'href' => 'https://fonts.gstatic.com',
				'crossorigin',
			);

			if ( wp_style_is( 'lafka-google-fonts', 'enqueued' ) ) {
				$urls[] = array(
					'href' => 'https://fonts.googleapis.com',
					'crossorigin',
				);
			}

			if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'google_maps_api_key' ) ) {
				$urls[] = array(
					'href' => 'https://maps.googleapis.com',
					'crossorigin',
				);
				$urls[] = array(
					'href' => 'https://maps.gstatic.com',
					'crossorigin',
				);
			}
		}

		// dns-prefetch is a cheaper hint for resources we may load lazily.
		if ( 'dns-prefetch' === $relation_type ) {
			if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'google_maps_api_key' ) ) {
				$urls[] = 'https://maps.googleapis.com';
				$urls[] = 'https://maps.gstatic.com';
			}
		}

		return $urls;
	}
}

if ( ! function_exists( 'lafka_generate_excerpt' ) ) {

	/**
	 * Return excerpt
	 *
	 * @param string $input input to truncate
	 * @param number $limit  number of chars to reach to tuncate
	 * @param string $break
	 * @param string $more more string
	 * @param boolean $strip_it strip tags
	 * @param string $exclude exclude tags
	 * @param boolean $safe_truncate use mb_strimwidth()
	 * @return string the generated excerpt
	 */
	function lafka_generate_excerpt( $input, $limit, $break = '.', $more = '...', $strip_it = false, $exclude = '<strong><em><span>', $safe_truncate = false ) {
		if ( $strip_it ) {
			$input = strip_shortcodes( strip_tags( $input, $exclude ) );
		}

		if ( strlen( $input ) <= $limit ) {
			return $input;
		}

		$breakpoint = strpos( $input, $break, $limit );

		if ( $breakpoint != false ) {
			if ( $breakpoint < strlen( $input ) - 1 ) {
				if ( $safe_truncate || is_rtl() ) {
					$input = mb_strimwidth( $input, 0, $breakpoint ) . $more;
				} else {
					$input = substr( $input, 0, $breakpoint ) . $more;
				}
			}
		}

		// prevent accidental word break
		if ( ! $breakpoint && strlen( strip_tags( $input ) ) == strlen( $input ) ) {
			if ( $safe_truncate || is_rtl() ) {
				$input = mb_strimwidth( $input, 0, $limit ) . $more;
			} else {
				$input = substr( $input, 0, $limit ) . $more;
			}
		}

		return $input;
	}

}

if ( ! function_exists( 'lafka_get_option' ) ) {

	/**
	 * Get Option — DEPRECATED one-cycle back-compat shim (NX1-02, theme 7.0).
	 *
	 * The theme's legacy Options Framework is retired. Every FIRST-PARTY theme
	 * reader of a migrated appearance key was re-pointed at its
	 * `get_theme_mod( 'lafka_<key>', <default> )` home by the NX1-02 slices
	 * (enforced by tests/Unit/LegacyOptionShimScanTest.php). This shim survives
	 * only so third-party / child-theme code still calling the legacy helper for
	 * a MAPPED key keeps resolving the value from its new theme_mod home for one
	 * major cycle, with a WP_DEBUG deprecation notice pointing at the new API.
	 *
	 * Resolution order:
	 *   1. Mapped appearance key  -> get_theme_mod( <mod_key>, $default ) (+ notice).
	 *   2. Unmapped / plugin-owned key -> the shared Lafka_Options helper (plugin
	 *      canonical) when active, else the raw `lafka` array, else the slim
	 *      plugin-owned defaults (lafka_get_default_values()). This path is
	 *      unchanged and never touches plugin code — it delegates to the plugin's
	 *      own public accessor exactly as before.
	 *
	 * Fallback: this theme definition only fires standalone (plugin inactive);
	 * the plugin defines lafka_get_option() first, so its function_exists() guard
	 * supersedes this whenever both load.
	 */
	function lafka_get_option( $name, $default = false ) {
		// 1. Mapped appearance key: its home is now a `lafka_<key>` theme_mod.
		$lafka_option_map = function_exists( 'lafka_legacy_migrate_map' ) ? lafka_legacy_migrate_map() : array();
		if ( isset( $lafka_option_map[ $name ] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( '_doing_it_wrong' ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					esc_html(
						sprintf(
							/* translators: 1: legacy option key, 2: replacement theme_mod key. */
							__( 'The "%1$s" theme setting moved to the Customizer theme_mod "%2$s". Read it with get_theme_mod() — lafka_get_option() for this key is deprecated and will be removed in a future major version.', 'lafka' ),
							$name,
							$lafka_option_map[ $name ]
						)
					),
					'7.0.0'
				);
			}
			return get_theme_mod( $lafka_option_map[ $name ], $default );
		}

		// 2. Unmapped / plugin-owned key. If the shared helper is available
		// (loaded by the plugin), use it.
		if ( class_exists( 'Lafka_Options' ) ) {
			return Lafka_Options::get( $name, $default ?: null );
		}

		// Standalone fallback when plugin is not active.
		static $options = null;
		if ( null === $options ) {
			$options = get_option( 'lafka' );
		}

		if ( isset( $options ) && isset( $options[ $name ] ) ) {
			return $options[ $name ];
		}

		if ( $default ) {
			return $default;
		}

		static $all_defaults = null;
		if ( null === $all_defaults ) {
			$all_defaults = lafka_get_default_values();
		}

		return isset( $all_defaults[ $name ] ) ? $all_defaults[ $name ] : false;
	}

}

if ( ! function_exists( 'lafka_has_to_include_backgr_video' ) ) {

	/**
	 * Checks if video background js plugin has to be included
	 * and returns the places that is should appear, or
	 * false if not.
	 *
	 * @global type $post
	 * @param type $is_compare
	 * @return string|boolean
	 */
	function lafka_has_to_include_backgr_video( $is_compare = false ) {

		// The post has video background
		if ( lafka_has_post_video_bckgr() ) {
			return 'postmeta';
			// If is blog page and video background is set
		} elseif ( lafka_is_blog() && get_theme_mod( 'lafka_show_blog_video_bckgr', false ) && get_theme_mod( 'lafka_blog_video_bckgr_url', '' ) ) {
			return 'blog';
			// If is shopwide
		} elseif ( ! $is_compare && LAFKA_IS_WOOCOMMERCE && is_woocommerce() && get_theme_mod( 'lafka_show_shop_video_bckgr', false ) && get_theme_mod( 'lafka_shopwide_video_bckgr', '0' ) && get_theme_mod( 'lafka_shop_video_bckgr_url', '' ) ) {
			return 'shopwide';
			// If is shop page and video background is set
		} elseif ( ! $is_compare && LAFKA_IS_WOOCOMMERCE && is_shop() && get_theme_mod( 'lafka_show_shop_video_bckgr', false ) && get_theme_mod( 'lafka_shop_video_bckgr_url', '' ) ) {
			return 'shop';
			// If Global video background is set
		} elseif ( ! $is_compare && get_theme_mod( 'lafka_show_video_bckgr', false ) && get_theme_mod( 'lafka_video_bckgr_url', '' ) ) {
			return 'global';
		}

		return false;
	}

}

if ( ! function_exists( 'lafka_is_blog' ) ) {

	/**
	 * Return true if this is the Blog page
	 * @return boolean
	 */
	function lafka_is_blog() {

		if ( is_front_page() && is_home() ) {
			return false;
		} elseif ( is_front_page() ) {
			return false;
		} elseif ( is_home() ) {
			// BLOG - return true
			return true;
		} else {
			return false;
		}
	}

}

add_action( 'after_switch_theme', 'lafka_redirect_to_options', 99 );
if ( ! function_exists( 'lafka_redirect_to_options' ) ) {

	// Redirect to theme options on theme activation
	function lafka_redirect_to_options() {
		wp_redirect( admin_url( 'themes.php?page=lafka-optionsframework' ) );
	}

}

add_filter( 'wp_nav_menu_args', 'lafka_set_menu_on_primary' );
if ( ! function_exists( 'lafka_set_menu_on_primary' ) ) {

	/**
	 * Set selected menu for 'top_menu' location
	 *
	 * @param Array $args
	 * @return Array
	 */
	function lafka_set_menu_on_primary( $args ) {
		if ( $args['theme_location'] === 'primary' ) {
			if ( lafka_is_blog() ) {
				return lafka_set_menu_on_primary_helper( $args, get_theme_mod( 'lafka_blog_top_menu', 'default' ) );
			}
			if ( LAFKA_IS_WOOCOMMERCE && is_shop() ) {
				return lafka_set_menu_on_primary_helper( $args, get_theme_mod( 'lafka_shop_top_menu', 'default' ) );
			}
			if ( LAFKA_IS_BBPRESS && bbp_is_forum_archive() ) {
				return lafka_set_menu_on_primary_helper( $args, get_theme_mod( 'lafka_forum_top_menu', 'default' ) );
			}
			if ( LAFKA_IS_EVENTS ) {
				$mode_and_title = lafka_get_current_events_display_mode_and_title();
				$events_mode    = $mode_and_title['display_mode'];
				if ( in_array( $events_mode, array( 'MAIN_CALENDAR', 'CALENDAR_CATEGORY', 'MAIN_EVENTS', 'CATEGORY_EVENTS', 'SINGLE_EVENT_DAYS' ), true ) ) {
					return lafka_set_menu_on_primary_helper( $args, get_theme_mod( 'lafka_events_top_menu', 'default' ) );
				}
			}

			$chosen_menu = get_post_meta( get_the_ID(), 'lafka_top_menu', true );
			return lafka_set_menu_on_primary_helper( $args, $chosen_menu );
		} else {
			return $args;
		}
	}

}

if ( ! function_exists( 'lafka_set_menu_on_primary_helper' ) ) {

	/**
	 * Helper
	 *
	 * @param array $args
	 * @param string $chosen_menu
	 * @return string
	 */
	function lafka_set_menu_on_primary_helper( $args, $chosen_menu ) {
		if ( 'default' === $chosen_menu ) {
			return $args;
		} elseif ( 'none' === $chosen_menu ) {
			$args['theme_location'] = 'lafka_none_existing_location';
			return $args;
		} else {
			$args['menu'] = (int) $chosen_menu;
			return $args;
		}
	}

}
if ( ! function_exists( 'lafka_registered_sidebar_default' ) ) {

	/**
	 * Resolve the default sidebar for a section, mirroring the retired Options
	 * Framework's dynamic `std` for the WooCommerce / bbPress / Events sidebar
	 * selects (NX1-02.layout-behaviour-toggles).
	 *
	 * The framework defaulted those selects to a preferred sidebar id when that
	 * sidebar was actually registered, else to 'none'. Reproducing that at the
	 * `get_theme_mod()` read default keeps a fresh install byte-identical while
	 * still degrading to 'none' on installs where the preferred sidebar is
	 * absent (e.g. WooCommerce inactive).
	 *
	 * @param string $preferred Preferred registered-sidebar id (shop / lafka_forum / right_sidebar).
	 * @return string The preferred id when registered, otherwise 'none'.
	 */
	function lafka_registered_sidebar_default( $preferred ) {
		global $wp_registered_sidebars;
		return ( is_array( $wp_registered_sidebars ) && array_key_exists( $preferred, $wp_registered_sidebars ) )
			? $preferred
			: 'none';
	}
}

/*
 * Check for sidebar
 */
add_filter( 'lafka_has_sidebar', 'lafka_check_for_sidebar' );
if ( ! function_exists( 'lafka_check_for_sidebar' ) ) {

	function lafka_check_for_sidebar() {

		$options                = array();
		$is_cat_tag_tax_archive = false;

		if ( is_category() || is_tag() || is_tax() || is_archive() || is_search() || ( is_home() ) ) {
			$is_cat_tag_tax_archive = true;
		}

		$blog_categoty_sidebar     = get_theme_mod( 'lafka_blog_categoty_sidebar', 'right_sidebar' );
		$foodmenu_categoty_sidebar = get_theme_mod( 'lafka_foodmenu_categoty_sidebar', 'none' );

		if ( LAFKA_IS_WOOCOMMERCE ) {
			$woocommerce_sidebar = get_theme_mod( 'lafka_woocommerce_sidebar', lafka_registered_sidebar_default( 'shop' ) );
		}

		if ( LAFKA_IS_BBPRESS ) {
			$bbpress_sidebar = get_theme_mod( 'lafka_bbpress_sidebar', lafka_registered_sidebar_default( 'lafka_forum' ) );
		}

		if ( LAFKA_IS_EVENTS ) {
			$events_sidebar = get_theme_mod( 'lafka_events_sidebar', lafka_registered_sidebar_default( 'right_sidebar' ) );
		}

		if ( is_single() || is_page() ) {
			$options = get_post_custom( get_queried_object_id() );
		}

		$show_sidebar_from_meta = 'yes';
		if ( isset( $options['lafka_show_sidebar'] ) && trim( $options['lafka_show_sidebar'][0] ) != '' ) {
			$show_sidebar_from_meta = $options['lafka_show_sidebar'][0];
		}

		$sidebar_choice = 'none';

		if ( LAFKA_IS_WOOCOMMERCE && function_exists( 'is_woocommerce' ) && is_woocommerce() && isset( $woocommerce_sidebar ) ) {
			$sidebar_choice = $woocommerce_sidebar;
		} elseif ( LAFKA_IS_BBPRESS && is_bbpress() && isset( $bbpress_sidebar ) && ( empty( $options ) || ( isset( $options['lafka_custom_sidebar'] ) && $options['lafka_custom_sidebar'][0] == 'default' && $show_sidebar_from_meta == 'yes' ) ) ) {
			$sidebar_choice = $bbpress_sidebar;
		} elseif ( LAFKA_IS_EVENTS && lafka_is_events_part() && isset( $events_sidebar ) && ( empty( $options ) || ( isset( $options['lafka_custom_sidebar'] ) && $options['lafka_custom_sidebar'][0] == 'default' && $show_sidebar_from_meta == 'yes' ) ) ) {
			$sidebar_choice = $events_sidebar;
		} elseif ( is_tax( 'lafka_foodmenu_category' ) || is_post_type_archive( 'lafka-foodmenu' ) ) {
			$sidebar_choice = $foodmenu_categoty_sidebar;
		} elseif ( $is_cat_tag_tax_archive ) {
			$sidebar_choice = $blog_categoty_sidebar;
		} elseif ( isset( $options['lafka_custom_sidebar'] ) && $show_sidebar_from_meta == 'yes' ) {
			if ( $options['lafka_custom_sidebar'][0] == 'default' ) {
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
add_filter( 'lafka_has_offcanvas_sidebar', 'lafka_check_for_offcanvas_sidebar' );
if ( ! function_exists( 'lafka_check_for_offcanvas_sidebar' ) ) {

	function lafka_check_for_offcanvas_sidebar() {
		$meta_options = array();
		if ( is_single() || is_page() ) {
			$meta_options = get_post_custom( get_queried_object_id() );
		}

		if ( isset( $meta_options['lafka_show_offcanvas_sidebar'] ) && trim( $meta_options['lafka_show_offcanvas_sidebar'][0] ) === 'no' ) {
			return 'none';
		}

		$offcanvas_sidebar_choice = get_theme_mod( 'lafka_offcanvas_sidebar', 'none' );
		if ( isset( $meta_options['lafka_custom_offcanvas_sidebar'] ) && $meta_options['lafka_custom_offcanvas_sidebar'][0] !== 'default' ) {
			$offcanvas_sidebar_choice = $meta_options['lafka_custom_offcanvas_sidebar'][0];
		}

		return $offcanvas_sidebar_choice;
	}

}

add_filter( 'lafka_left_sidebar_position_class', 'lafka_check_for_sidebar_position' );
if ( ! function_exists( 'lafka_check_for_sidebar_position' ) ) {

	/**
	 * Check position of sidebar
	 *
	 * @return string - Empty string for left and the class name for right
	 */
	function lafka_check_for_sidebar_position() {
		$meta_options = array();
		if ( is_single() || is_page() ) {
			$meta_options = get_post_custom( get_queried_object_id() );
		}

		$sidebar_position = get_theme_mod( 'lafka_sidebar_position', 'lafka-right-sidebar' );
		if ( isset( $meta_options['lafka_sidebar_position'] ) && $meta_options['lafka_sidebar_position'][0] !== 'default' ) {
			$sidebar_position = $meta_options['lafka_sidebar_position'][0];
		}

		if ( defined( 'LAFKA_IS_WOOCOMMERCE' ) && LAFKA_IS_WOOCOMMERCE && is_woocommerce() ) {
			if ( ! is_product() && get_theme_mod( 'lafka_shop_sidebar_position', 'default' ) !== 'default' ) {
				$sidebar_position = get_theme_mod( 'lafka_shop_sidebar_position', 'default' );
			} elseif ( is_product() && get_theme_mod( 'lafka_product_sidebar_position', 'default' ) !== 'default' ) {
				$sidebar_position = get_theme_mod( 'lafka_product_sidebar_position', 'default' );
			}
		} elseif ( get_theme_mod( 'lafka_blog_sidebar_position', 'default' ) !== 'default' && ( is_category() || is_tag() || is_author() || is_date() || is_search() || is_home() ) ) {
			$sidebar_position = get_theme_mod( 'lafka_blog_sidebar_position', 'default' );
		}

		return $sidebar_position;
	}

}


if ( ! function_exists( 'lafka_get_choose_menu_options' ) ) {

	/**
	 * Get options to use for choose menu select
	 *
	 * @return Array
	 */
	function lafka_get_choose_menu_options() {
		$registered_menus    = wp_get_nav_menus();
		$choose_menu_options = array(
			'none'    => esc_html__( '- No menu -', 'lafka' ),
			'default' => esc_html__( '- Use global set top menu -', 'lafka' ),
		);

		foreach ( $registered_menus as $menu ) {
			$choose_menu_options[ $menu->term_id ] = $menu->name;
		}

		return $choose_menu_options;
	}

}

// Disable BBPress breadcrumb
add_filter( 'bbp_no_breadcrumb', '__return_true' );

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

			if ( isset( $wp_query->post ) ) {
				$id = $wp_query->post->ID;
			}
		}

		$return_arr = array(
			'display_mode' => '',
			'title'        => '',
		);

		// If Event calendar is active follow the procedure to display the title
		if ( function_exists( 'tribe_is_month' ) ) {
			if ( tribe_is_month() && ! is_tax( '', $id ) ) { // The Main Calendar Page
				if ( get_theme_mod( 'lafka_events_title', '' ) ) {
					$title = get_theme_mod( 'lafka_events_title', '' );
				} else {
					$title = esc_html__( 'The Main Calendar', 'lafka' );
				}
				$mode = 'MAIN_CALENDAR';
			} elseif ( tribe_is_month() && is_tax( '', $id ) ) { // Calendar Category Pages
				$title = esc_html__( 'Calendar Category', 'lafka' ) . ': ' . tribe_meta_event_category_name();
				$mode  = 'CALENDAR_CATEGORY';
			} elseif ( tribe_is_event( $id ) && ! tribe_is_day() && ! is_singular() && ! is_tax( '', $id ) ) { // The Main Events List
				if ( get_theme_mod( 'lafka_events_title', '' ) ) {
					$title = get_theme_mod( 'lafka_events_title', '' );
				} else {
					$title = esc_html__( 'Events List', 'lafka' );
				}
				$mode = 'MAIN_EVENTS';
			} elseif ( tribe_is_event( $id ) && ! tribe_is_day() && ! is_singular() && is_tax( '', $id ) ) { // Category Events List
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

if ( ! function_exists( 'lafka_write_log' ) ) {
	// Fallback: only fires if lafka-plugin is not active. Plugin's class-lafka-options.php
	// definition supersedes this when both load. Both bodies are identical.
	function lafka_write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}

// P6-PERF-5: inline critical CSS + defer non-critical stylesheets.
require_once get_template_directory() . '/incl/system/lafka-critical-css.php';

// Fix Wishlist issue (adding prettyPhoto): https://wordpress.org/support/topic/conflict-with-the-wpbakery-gallery/
add_filter(
	'yith_wcwl_main_script_deps',
	function ( $deps ) {
		if ( isset( $deps[2] ) ) {
			unset( $deps[2] ); // remove lightbox.
		}

		return $deps;
	}
);
