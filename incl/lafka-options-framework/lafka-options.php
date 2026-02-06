<?php defined( 'ABSPATH' ) || exit; ?>
<?php

/**
 * Defines an array of options that will be used to generate the settings page and be saved in the database.
 * When creating the 'id' fields, make sure to use all lowercase and no spaces.
 *
 */
function lafka_optionsframework_options() {

	// general layout values
	$general_layout_values = array(
			'lafka_fullwidth' => LAFKA_IMAGES_PATH . 'lafka_fullwidth.jpg',
			'lafka_boxed' => LAFKA_IMAGES_PATH . 'lafka_boxed.jpg'
	);


	// Blog style options
	$general_blog_style_values = array(
			'' => esc_html__('Standard', 'lafka'),
			'lafka_blog_masonry' => esc_html__('Masonry Tiles', 'lafka'),
			'lafka_blog_masonry lafka-mozaic' => esc_html__('Mozaic', 'lafka')
	);

	// Header Background Defaults
	$header_background_defaults = array(
			'color' => '#ffffff',
			'image' => '',
			'repeat' => '',
			'position' => '',
			'attachment' => 'scroll'
	);

	// Footer Background Defaults
	$footer_background_defaults = array(
			'color' => '#242424',
			'image' => '',
			'repeat' => '',
			'position' => '',
			'attachment' => 'scroll'
	);

	// Number of columns on products list
	$shop_default_product_columns_values = array(
			'columns-1' => '1',
			'columns-2' => '2',
			'columns-3' => '3',
			'columns-4' => '4',
			'columns-5' => '5',
			'columns-6' => '6',
			'lafka-products-list-view' => esc_html__('List View', 'lafka')
	);

	$header_style_list = array(
			'' => esc_html__('Normal', 'lafka'),
			'lafka_transparent_header' => esc_html__('Transparent - Light Scheme', 'lafka'),
			'lafka_transparent_header lafka-transparent-dark' => esc_html__('Transparent - Dark Scheme', 'lafka')
	);

	$choose_menu_options = lafka_get_choose_menu_options();

	// Date format values
	$date_format_default = array(
		'lafka_format' => esc_html_x('Use "time-ago" (e.g. 3 days ago) date format for posts created in the last 6 months. For older posts, WordPress date format will be used. ', 'theme-options', 'lafka'),
		'default' => esc_html_x('WordPress date format', 'theme-options', 'lafka')
	);

	// Show/hide seasrchform
	$show_searchform_default = 1;

	// Search options values Array
	$search_options_array = array(
			'use_ajax' => esc_html_x('Use Ajax', 'theme-options', 'lafka')
	);

	if (defined('LAFKA_IS_WOOCOMMERCE') && LAFKA_IS_WOOCOMMERCE) {
		$search_options_array['only_products'] = esc_html_x('Search only in Products', 'theme-options', 'lafka');
	}

	// Search options Defaults
	$search_options_defaults = array(
			'use_ajax' => '1',
			'only_products' => '1'
	);

	// Enabled / Disabled select
	$enable_disable_array = array(
			'enabled' => esc_html_x('Enabled', 'theme-options', 'lafka'),
			'disabled' => esc_html_x('Disabled', 'theme-options', 'lafka')
	);

	// "NEW" label active period (days)
	$new_label_period_array = array(
			'0' => esc_html_x('Off', 'theme-options', 'lafka'),
			'10' => 10,
			'20' => 20,
			'30' => 30,
			'45' => 45,
			'60' => 60,
			'90' => 90
	);

	$os_faces = lafka_typography_get_os_fonts();
	$google_fonts = lafka_typography_get_google_fonts();

	asort($os_faces);
	asort($google_fonts);
	$typography_mixed_fonts = array_merge($os_faces, $google_fonts);

	// Default google subsets
	$google_subsets_defaults = array('latin' => '1');

	// Google subsets
	$google_subsets_options = array(
			'cyrillic-ext' => 'Cyrillic Extended (cyrillic-ext)',
			'latin' => 'Latin (latin)',
			'greek-ext' => 'Greek Extended (greek-ext)',
			'greek' => 'Greek (greek)',
			'vietnamese' => 'Vietnamese (vietnamese)',
			'latin-ext' => 'Latin Extended (latin-ext)',
			'cyrillic' => 'Cyrillic (cyrillic)'
	);

	// body font default
	$body_font_default = array(
			'face' => 'Rubik',
			'size' => '16px',
			'color' => '#888888'
	);

	// Headings font face default
	$headings_font_default = array(
			'face' => 'Rubik'
	);

	// Heading fonts style and weight options
	$headings_fonts_styles_weight = array('false' => 'default');

	for ($n = 1; $n < 10; $n++) {
		$headings_fonts_styles_weight['{"font-weight":"' . $n . '00","font-style":"normal"}'] = $n . '00';
		$headings_fonts_styles_weight['{"font-weight":"' . $n . '00","font-style":"italic"}'] = $n . '00 italic';
	}

	// H1 default
	$h1_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '60px',
			'color' => '#22272d',
			'style' => '{"font-weight":"700","font-style":"normal"}'
	);

	// H2 default
	$h2_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '44px',
			'color' => '#22272d',
			'style' => '{"font-weight":"700","font-style":"normal"}'
	);

	// H3 default
	$h3_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '30px',
			'color' => '#22272d',
			'style' => '{"font-weight":"700","font-style":"normal"}'
	);

	// H4 default
	$h4_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '24px',
			'color' => '#22272d',
			'style' => '{"font-weight":"600","font-style":"normal"}'
	);

	// H5 default
	$h5_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '21px',
			'color' => '#22272d',
			'style' => '{"font-weight":"500","font-style":"normal"}'
	);

	// H6 deault
	$h6_font_default = array(
			'face' => $headings_font_default['face'],
			'size' => '19px',
			'color' => '#22272d',
			'style' => '{"font-weight":"500","font-style":"normal"}'
	);

	// promo tooltip positions
	$promo_tooltip_positions = array(
		'above-price' => esc_html_x('Above Price', 'theme-options', 'lafka'),
		'below-price' => esc_html_x('After Price', 'theme-options', 'lafka'),
		'below-add-to-cart' => esc_html_x('Below "Add to cart"', 'theme-options', 'lafka'),
	);

	// Stores registered sidebars
	global $wp_registered_sidebars;
	$registered_sidebars_array = array('none' => 'none');

	foreach ($wp_registered_sidebars as $sidebar_id => $sidebar) {
		if ($sidebar_id != 'pre_header_sidebar') {
			$registered_sidebars_array[$sidebar_id] = $sidebar['name'];
		}
	}

	$sidebar_positions = array(
		'lafka-right-sidebar' => esc_html_x( 'Right', 'theme-options', 'lafka' ),
		'lafka-left-sidebar'  => esc_html_x( 'Left', 'theme-options', 'lafka' )
	);

	$sidebar_positions_with_default = array_merge( array( 'default' => '- ' . esc_html_x( 'Default Position', 'theme-options', 'lafka' ) . ' -' ), $sidebar_positions );

	$wp_editor_settings = array(
			'wpautop' => true, // Default
			'textarea_rows' => 5,
			'tinymce' => array('plugins' => 'wordpress'),
			'media_buttons' => true
	);

	$options = array();

	/*
	 * GENERAL SETTNIGS
	 */

	$options[] = array(
			'name' => esc_html_x('General', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'general'
	);
	$options[] = array(
			'name' => esc_html_x('Responsive', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable responsive design.', 'theme-options', 'lafka'),
			'id' => 'is_responsive',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Use Site Preloader', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable preloader for the whole site.', 'theme-options', 'lafka'),
			'id' => 'show_preloader',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Layout', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose layout to be used sitewide.', 'theme-options', 'lafka'),
			'id' => 'general_layout',
			'std' => 'lafka_fullwidth',
			'type' => 'images',
			'options' => $general_layout_values
	);
	$options[] = array(
			'name' => esc_html_x('Logo', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose or upload new logo.', 'theme-options', 'lafka'),
			'id' => 'theme_logo',
			'std' => '',
			'type' => 'lafka_upload'
	);
	$options[] = array(
		'name' => esc_html_x('Mobile Devices Logo (Optional) - if set, also used in sticky header', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Choose or upload new logo.', 'theme-options', 'lafka').'<br>'.
		          esc_html_x('Recommended image size: 50x50 px.', 'theme-options', 'lafka'),
		'id' => 'mobile_theme_logo',
		'type' => 'lafka_upload'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Logo (Needs to be enabled from Footer area->Show logo in footer)', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose or upload new logo.', 'theme-options', 'lafka'),
			'id' => 'footer_logo',
			'type' => 'lafka_upload'
	);
	$options[] = array(
		'name' => esc_html_x('Disable Logo Point-down Effect', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Remove the logo holder point-down accent.', 'theme-options', 'lafka'),
		'id' => 'disable_logo_point_down',
		'std' => 0,
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => esc_html_x('Logo Background Color', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Set background color for all logo types.', 'theme-options', 'lafka'),
		'id' => 'logo_background_color',
		'std' => '#fccc4c',
		'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Text Logo Typography', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set font options for text logo (only active if there is no image logo selected).', 'theme-options', 'lafka'),
			'id' => 'text_logo_typography',
			'std' => array(
					'size' => '21px',
					'style' => '{"font-weight":"700","font-style":"normal"}',
					'color' => '#ffffff'
			),
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'color' => true
			)
	);
	$options[] = array(
		'name' => esc_html_x( 'Google Maps JavaScript API key', 'theme-options', 'lafka' ),
		'desc' => sprintf( wp_kses( _x( 'Enter your Google Maps JavaScript API key, to be used for google map integration in Map shortcode and in Lafka Shipping Areas (if enabled) <a target="_blank" href="%s">Generate Google Maps JavaScript API key</a>', 'theme-options', 'lafka' ), array(
			'a' => array(
				'target' => array(),
				'href'   => array()
			)
		) ), esc_url( 'https://developers.google.com/maps/documentation/javascript/get-api-key' ) ),
		'id'   => 'google_maps_api_key',
		'std'  => '',
		'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Enable Smooth Scroll', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Smooth scrolling, when using anchors (known as one-pager).', 'theme-options', 'lafka'),
			'id' => 'enable_smooth_scroll',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Show Previous / Next Links', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show Previous / Next Links for posts, products and foodmenus.', 'theme-options', 'lafka'),
			'id' => 'show_prev_next',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
		'name' => esc_html_x('Global Date Format', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Choose between the WordPress settings format and the theme "time ago" format for posts and all custom post types.', 'theme-options', 'lafka'),
		'id' => 'date_format',
		'std' => 'default',
		'type' => 'radio',
		'options' => $date_format_default
	);
	// When 'expandable_option' class is used,
	// the options with class as the id if this element will be shown/hide
	$options[] = array(
			'name' => esc_html_x('Show Search in Header', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show search form in header.', 'theme-options', 'lafka'),
			'id' => 'show_searchform',
			'std' => $show_searchform_default,
			'class' => 'expandable_option',
			'type' => 'checkbox'
	);
	$options[] = array(
			//'name' => esc_html__('Multicheck' , 'lafka'),
			'desc' => esc_html_x('Choose whether to use Ajax or ordinary form. Search only in products or in the whole site (only if WooCommerce is activated).', 'theme-options', 'lafka'),
			'id' => 'search_options',
			'std' => $search_options_defaults, // These items get checked by default
			'type' => 'multicheck',
			'options' => $search_options_array,
			'class' => 'show_searchform'
	);
	$options[] = array(
			'name' => esc_html_x('Enable Breadcrumb', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show breadcrumb.', 'theme-options', 'lafka'),
			'id' => 'show_breadcrumb',
			'std' => 1,
			'type' => 'checkbox'
	);
	/*
	 * HEADER AREA SETTNIGS
	 */
	$options[] = array(
			'name' => esc_html_x('Header area', 'theme-options', 'lafka'),
			'type' => 'heading',
			'class' => 'lafka-expandable-cont',
			'tab_id' => 'headerarea'
	);
	$options[] = array(
			'name' => esc_html_x('Header Size', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Standard / Fullwidth.', 'theme-options', 'lafka'),
			'id' => 'header_width',
			'std' => '',
			'type' => 'select',
			'options' => array(
					'' => esc_html_x('Standard', 'theme-options', 'lafka'),
					'lafka-stretched-header' => esc_html_x('Fullwidth', 'theme-options', 'lafka')
			)
	);
	$options[] = array(
			'name' => esc_html_x('Sticky Header', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable sticky header functionality.', 'theme-options', 'lafka'),
			'id' => 'sticky_header',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html__('Header Background', 'lafka'),
			'desc' => esc_html__('Set Header Background image and/or color.', 'lafka'),
			'id' => 'header_background',
			'std' => $header_background_defaults,
			'type' => 'background'
	);
	$options[] = array(
			'name' => esc_html_x('Top Menu Bar', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show the top menu bar', 'theme-options', 'lafka'),
			'id' => 'enable_top_header',
			'std' => 1,
			'type' => 'checkbox',
			'class' => 'expandable_option'
	);
	$options[] = array(
			'name' => esc_html_x('Top Menu Bar Visible on Mobile', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Will be vsible on mobile devices', 'theme-options', 'lafka'),
			'id' => 'header_top_mobile_visibility',
			'std' => 1,
			'type' => 'checkbox',
			'class' => 'enable_top_header'
	);
	$options[] = array(
		'name' => esc_html_x('Top Menu Typography', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Choose Top menu font size and style.', 'theme-options', 'lafka'),
		'id' => 'top_menu_typography',
		'std' => array(
			'size' => '13px',
			'style' => '{"font-weight":"500","font-style":"normal"}'
		),
		'type' => 'typography',
		'class' => 'enable_top_header',
		'options' => array(
			'faces' => false,
			'styles' => $headings_fonts_styles_weight,
			'color' => false
		)
	);
	$options[] = array(
		'name' => esc_html_x('Short Header Message', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Short Header Message Color. NOTE: This area background color will be the same as the header background color.', 'theme-options', 'lafka'),
		'id' => 'top_bar_message_color',
		'std' => '#4b4b4b',
		'type' => 'color'
	);
	$options[] = array(
			'desc' => esc_html_x('The message will appear in the header.', 'theme-options', 'lafka'),
			'id' => 'top_bar_message',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'desc' => esc_html_x('Append Phone Number.', 'theme-options', 'lafka'),
			'id' => 'top_bar_message_phone',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
		'desc' => esc_html_x('Make phone number a link to dial.', 'theme-options', 'lafka'),
		'id' => 'top_bar_message_phone_link',
		'std' => 1,
		'type' => 'checkbox',
	);
	$options[] = array(
		'name' => esc_html_x('Header Services Icons Color (My Account, Wishlist, Cart, etc.)', 'theme-options', 'lafka'),
		'desc' => esc_html_x('NOTE: This area background color will be the same as the header background color.', 'theme-options', 'lafka'),
		'id' => 'header_services_color',
		'std' => '#333333',
		'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Collapsible Pre-Header', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable Collapsible Pre-Header widget area', 'theme-options', 'lafka'),
			'id' => 'enable_pre_header',
			'std' => 0,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Collapsible Pre-Header Background Color', 'theme-options', 'lafka'),
			'id' => 'collapsible_bckgr_color',
			'std' => '#fcfcfc',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Collapsible Pre-Header Titles Color', 'theme-options', 'lafka'),
			'id' => 'collapsible_titles_color',
			'std' => '#22272d',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Collapsible Pre-Header Titles Border Color', 'theme-options', 'lafka'),
			'id' => 'collapsible_titles_border_color',
			'std' => '#f1f1f1',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Collapsible Pre-Header Links Color', 'theme-options', 'lafka'),
			'id' => 'collapsible_links_color',
			'std' => '#22272d',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Header Top Bar Background Color', 'theme-options', 'lafka'),
			'id' => 'header_top_bar_color',
			'std' => '#222222',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Header Top Bar Menu Links Color', 'theme-options', 'lafka'),
			'id' => 'top_bar_menu_links_color',
			'std' => '#ffffff',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Header Top Bar Menu Links Hover Color', 'theme-options', 'lafka'),
			'id' => 'top_bar_menu_links_hover_color',
			'std' => '#fccc4c',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Typography', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose Main menu font size and style.', 'theme-options', 'lafka'),
			'id' => 'main_menu_typography',
			'std' => array(
					'size' => '15px',
					'style' => '{"font-weight":"600","font-style":"normal"}'
			),
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'color' => false
			)
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Icons Color', 'theme-options', 'lafka'),
			'id' => 'main_menu_icons_color',
			'std' => '#ac8320',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Transform to Uppercase', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Transform main menu top level to uppercase.', 'theme-options', 'lafka'),
			'id' => 'main_menu_transf_to_uppercase',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Background Color', 'theme-options', 'lafka'),
			'id' => 'main_menu_background_color',
			'std' => '#fccc4c',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Links Color', 'theme-options', 'lafka'),
			'id' => 'main_menu_links_color',
			'std' => '#61443e',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Main Menu Links Hover Color', 'theme-options', 'lafka'),
			'id' => 'main_menu_links_hover_color',
			'std' => '#22272d',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Transparent Header Menu Color - Light Scheme', 'theme-options', 'lafka'),
			'id' => 'transparent_header_menu_color',
			'std' => '#ffffff',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Transparent Header Menu Hover Color - Light Scheme', 'theme-options', 'lafka'),
			'id' => 'transparent_header_menu_hover_color',
			'std' => '',
			'type' => 'color'
	);
	$options[] = array(
		'name' => esc_html_x('Transparent Header Menu Color - Dark Scheme', 'theme-options', 'lafka'),
		'id' => 'transparent_header_dark_menu_color',
		'std' => '#22272d',
		'type' => 'color'
	);
	$options[] = array(
		'name' => esc_html_x('Transparent Header Menu Hover - Dark Scheme', 'theme-options', 'lafka'),
		'id' => 'transparent_header_dark_menu_hover_color',
		'std' => '#22272d',
		'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Sub-Menu Color Scheme', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select color scheme for the sub-menu.', 'theme-options', 'lafka'),
			'id' => 'submenu_color_scheme',
			'std' => '',
			'type' => 'select',
			'options' => array(
					'' => esc_html_x('Light', 'theme-options', 'lafka'),
					'lafka-dark-menu' => esc_html_x('Dark', 'theme-options', 'lafka')
			)
	);
	/*
	 * FOOTER AREA SETTNIGS
	 */
	$options[] = array(
			'name' => esc_html_x('Footer area', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'footerarea'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Style', 'theme-options', 'lafka'),
			'desc' => esc_html_x('*Note: Reveal footer is only available in fullwidth layout mode and not available on mobile/touch devices and screens with height lower than 768px for compatibility reasons. Use this feature with extra attention on large footer, because of the fixed footer position, which could prevent the full footer visibility on smaller screens.', 'theme-options', 'lafka'),
			'id' => 'footer_style',
			'std' => '',
			'type' => 'select',
			'options' => array(
					'' => esc_html_x('Standard', 'theme-options', 'lafka'),
					'lafka-reveal-footer' => esc_html_x('Reveal', 'theme-options', 'lafka')
			)
	);
	$options[] = array(
			'name' => esc_html_x('Footer Size', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Standard / Fullwidth.', 'theme-options', 'lafka'),
			'id' => 'footer_width',
			'std' => '',
			'type' => 'select',
			'options' => array(
					'' => esc_html_x('Standard', 'theme-options', 'lafka'),
					'lafka-stretched-footer' => esc_html_x('Fullwidth', 'theme-options', 'lafka')
			)
	);
	$options[] = array(
			'name' => esc_html_x('Show Logo in Footer', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Main logo will be displayed in footer, unless General->Footer Logo is selected. In that case "Footer Logo" will be displayed.', 'theme-options', 'lafka'),
			'id' => 'show_logo_in_footer',
			'std' => 0,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html__('Footer Background', 'lafka'),
			'desc' => esc_html__('Set Footer Background image and/or color.', 'lafka'),
			'id' => 'footer_background',
			'std' => $footer_background_defaults,
			'type' => 'background'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Titles Color', 'theme-options', 'lafka'),
			'id' => 'footer_titles_color',
			'std' => '#ffffff',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Titles Border Color', 'theme-options', 'lafka'),
			'id' => 'footer_title_border_color',
			'std' => '#f1f1f1',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Menu Links Color', 'theme-options', 'lafka'),
			'id' => 'footer_menu_links_color',
			'std' => '#ffffff',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Widgets Links Color', 'theme-options', 'lafka'),
			'id' => 'footer_links_color',
			'std' => '#f5f5f5',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Text Color', 'theme-options', 'lafka'),
			'id' => 'footer_text_color',
			'std' => '#aeaeae',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Copyright Bar Background Color', 'theme-options', 'lafka'),
			'id' => 'footer_copyright_bar_bckgr_color',
			'std' => '#222222',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Copyright Bar Text Color', 'theme-options', 'lafka'),
			'id' => 'footer_copyright_bar_text_color',
			'std' => '#aeaeae',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Footer Copyright Bar Text', 'theme-options', 'lafka'),
			'desc' => sprintf(esc_html_x('Enter Copyright text. Use %s as wildcard to be replaced by current year.', 'theme-options', 'lafka'), '%current_year%'),
			'id' => 'copyright_text',
			'std' => 'Lafka theme by <a target="_blank" title="theAlThemist\'s foodmenu" href="http://themeforest.net/user/theAlThemist/foodmenu?ref=theAlThemist">theAlThemist</a> | &#169; %current_year% All rights reserved!',
			'type' => 'textarea'
	);

	/*
	 * COMMON STYLES
	 */
	$options[] = array(
			'name' => esc_html_x('Common Styles', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'commoncolors'
	);
	$options[] = array(
			'name' => esc_html_x('Site Main Accent Color', 'theme-options', 'lafka'),
			'id' => 'accent_color',
			'std' => '#e4584b',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Links Color', 'theme-options', 'lafka'),
			'id' => 'links_color',
			'std' => '#e4584b',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Links Hover Color', 'theme-options', 'lafka'),
			'id' => 'links_hover_color',
			'std' => '#ce4f44',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Widgets Title Color', 'theme-options', 'lafka'),
			'id' => 'sidebar_titles_color',
			'std' => '#333333',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Default Buttons Style', 'theme-options', 'lafka'),
			'id' => 'all_buttons_style',
			'std' => 'round',
			'type' => 'select',
			'class' => 'mini',
			'options' => array(
					'classic' => esc_html__('Classic', 'lafka'),
					'round' => esc_html__('Round', 'lafka'),
			)
	);
	$options[] = array(
			'name' => esc_html_x('Default Buttons Color', 'theme-options', 'lafka'),
			'id' => 'all_buttons_color',
			'std' => '#e4584b',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Default Buttons Hover Color', 'theme-options', 'lafka'),
			'id' => 'all_buttons_hover_color',
			'std' => '#22272d',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('NEW Label Color', 'theme-options', 'lafka'),
			'id' => 'new_label_color',
			'std' => '#e4584b',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('SALE Label Color', 'theme-options', 'lafka'),
			'id' => 'sale_label_color',
			'std' => '#fccc4c',
			'type' => 'color'
	);
	$options[] = array(
		'name' => esc_html_x('Fancy Title Font', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Use fancy font for all page titles. NOTE: Supports only latin characters', 'theme-options', 'lafka'),
		'id' => 'fancy_title_font',
		'std' => 0,
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => esc_html_x('Page Titles to Uppercase', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Transform page titles to uppercase', 'theme-options', 'lafka'),
		'id' => 'uppercase_page_titles',
		'std' => 1,
		'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Standard page title color (no background image)', 'theme-options', 'lafka'),
			'id' => 'page_title_color',
			'std' => '#22272d',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Standard page subtitle color (no background image)', 'theme-options', 'lafka'),
			'id' => 'page_subtitle_color',
			'std' => '#999999',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Standard page title background color (no background image)', 'theme-options', 'lafka'),
			'id' => 'page_title_bckgr_color',
			'std' => '#f7f7f7',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Default page title background image', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Will be used on all pages, if there is no other explicitly set.', 'theme-options', 'lafka'),
			'id' => 'page_title_default_bckgr_image',
			'std' => '',
			'type' => 'lafka_upload',
			'is_multiple' => false
	);
	$options[] = array(
			'name' => esc_html_x('Standard page title border color (no background image)', 'theme-options', 'lafka'),
			'id' => 'page_title_border_color',
			'std' => '#f0f0f0',
			'type' => 'color'
	);
	$options[] = array(
			'name' => esc_html_x('Customized page title color (with background image)', 'theme-options', 'lafka'),
			'id' => 'custom_page_title_color',
			'std' => '#ffffff',
			'desc' => esc_html_x('Also applies for subtitle and breadcrumb.', 'theme-options', 'lafka'),
			'type' => 'color'
	);

	/*
	 * Restaurant Menu
	 */
	$options[] = array(
			'name' => esc_html_x('Restaurant Menu', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'foodmenu'
	);
	if(!LAFKA_IS_WOOCOMMERCE) {
		$options[] = array(
			'name' => esc_html_x( 'Currency Sign in Menu Prices', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'Set currency sign for menu prices, if WooCommerce is not active. ', 'theme-options', 'lafka' ),
			'id'   => 'foodmenu_currency',
			'std'  => '$',
			'class' => 'mini',
			'type' => 'text'
		);

		$options[] = array(
			'name' => esc_html_x( 'Currency Sign Position in Menu Prices', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'Set position for the currency sign in menu prices, if WooCommerce is not active. ', 'theme-options', 'lafka' ),
			'id'   => 'foodmenu_currency_position',
			'std'  => 'left',
			'type' => 'select',
			'options' => array(
				'left' => esc_html_x('Left', 'theme-options', 'lafka'),
				'right' => esc_html_x('Right', 'theme-options', 'lafka'),
			)
		);
	}
	$options[] = array(
			'name' => esc_html_x('Show Similar Menu Entries', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Display similar menu entries in single menu entry page.', 'theme-options', 'lafka'),
			'id' => 'show_related_menu_entries',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Lightbox', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show link that opens the featured image in lightbox.', 'theme-options', 'lafka'),
			'id' => 'show_light_menu_entries',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
		'name' => esc_html_x('Hide Images', 'theme-options', 'lafka'),
		'desc' => esc_html_x('No images in the menu and menu entries.', 'theme-options', 'lafka'),
		'id' => 'hide_foodmenu_images',
		'std' => 0,
		'type' => 'checkbox'
	);
	$options[] = array(
		'name' => esc_html_x('Simple Menu List', 'theme-options', 'lafka'),
		'desc' => esc_html_x('No links to single menu entry page.', 'theme-options', 'lafka'),
		'id' => 'foodmenu_simple_menu',
		'std' => 0,
		'type' => 'checkbox'
	);
	/*
	 * FONTS
	 */
	$options[] = array(
			'name' => esc_html_x('Fonts', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'fonts'
	);
	$options[] = array(
			'name' => esc_html_x('Body Font', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose font parameters for the body text.', 'theme-options', 'lafka') . '<br>' .
			          '<b>' . esc_html_x('Note', 'lafka') . ': </b>' . esc_html_x('Choose -- None -- to use custom font, not managed by Lafka. Preview will be disabled.', 'lafka'),
			'id' => 'body_font',
			'std' => $body_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => $typography_mixed_fonts,
					'styles' => false,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('Accent and Headings Google Font Face', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose Font Face.', 'theme-options', 'lafka') . '<br>' .
			          '<b>' . esc_html_x('Note', 'lafka') . ': </b>' . esc_html_x('Choose -- None -- to use custom font, not managed by Lafka. Preview will be disabled.', 'lafka'),
			'id' => 'headings_font',
			'std' => $headings_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => $typography_mixed_fonts,
					'styles' => false,
					'color' => false,
					'sizes' => false
			)
	);
	$options[] = array(
			'desc' => esc_html_x('Use selected google font face also for', 'theme-options', 'lafka'),
			'id' => 'use_google_face_for',
			'std' => array(
					'main_menu' => 1,
					'buttons' => 1
			),
			'type' => 'multicheck',
			'options' => array(
					'main_menu' => esc_html_x('Main Menu', 'theme-options', 'lafka'),
					'buttons' => esc_html_x('Buttons', 'theme-options', 'lafka')
			)
	);
	$options[] = array(
			'name' => esc_html__('Google Font Subsets', 'lafka'),
			'desc' => esc_html_x('Choose Subsets.', 'theme-options', 'lafka'),
			'id' => 'google_subsets',
			'std' => $google_subsets_defaults, // These items get checked by default
			'type' => 'multicheck',
			'options' => $google_subsets_options
	);
	$options[] = array(
			'name' => esc_html_x('H1 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H1 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h1_font',
			'std' => $h1_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('H2 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H2 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h2_font',
			'std' => $h2_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('H3 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H3 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h3_font',
			'std' => $h3_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('H4 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H4 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h4_font',
			'std' => $h4_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('H5 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H5 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h5_font',
			'std' => $h5_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	$options[] = array(
			'name' => esc_html_x('H6 Font Options', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose H6 size, style and color.', 'theme-options', 'lafka'),
			'id' => 'h6_font',
			'std' => $h6_font_default,
			'type' => 'typography',
			'options' => array(
					'faces' => false,
					'styles' => $headings_fonts_styles_weight,
					'preview' => true
			)
	);
	/*
	 * Advanced Backgrounds
	 */
	$options[] = array(
			'name' => esc_html_x('Advanced Backgrounds', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'advancedbackgrounds'
	);
	$options[] = array(
			'name' => esc_html_x('Enable YouTube Video Background Sitewide.', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Check to enable below youtube video to be set as background on the whole site.', 'theme-options', 'lafka'),
			'id' => 'show_video_bckgr',
			'std' => 0,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('YouTube Video URL', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Paste the YouTube URL.', 'theme-options', 'lafka'),
			'id' => 'video_bckgr_url',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Start Time', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the seconds the video should start at.', 'theme-options', 'lafka'),
			'id' => 'video_bckgr_start',
			'class' => 'mini',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('End Time', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the seconds the video should stop at.', 'theme-options', 'lafka'),
			'id' => 'video_bckgr_end',
			'class' => 'mini',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Loop', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Loops the movie once ended.', 'theme-options', 'lafka'),
			'id' => 'video_bckgr_loop',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Mute', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Mute the audio.', 'theme-options', 'lafka'),
			'id' => 'video_bckgr_mute',
			'std' => 1,
			'type' => 'checkbox'
	);
	/*
	 * If Woocommerce is activated show the shop options
	 */
	if (defined('LAFKA_IS_WOOCOMMERCE') && LAFKA_IS_WOOCOMMERCE) {
		/*
		 * SHOP
		 */
		$options[] = array(
				'name' => esc_html_x('Shop', 'theme-options', 'lafka'),
				'type' => 'heading',
				'tab_id' => 'shop'
		);
		$options[] = array(
			'name' => esc_html_x( 'Product Addons', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'With Product Addons enabled, you can create different add-ons on global or on per-product level. On global level, you can choose either to display the addons on all products or specific categories. Global Addons are accessible from Products->Lafka Global Add-ons. Per-product addons can be found in the product edit screen.', 'theme-options', 'lafka' ) .
			          '<br><br><b>' . esc_html_x( 'IMPORTANT NOTE', 'theme-options', 'lafka' ) . ': </b>' .
			          esc_html_x( 'Disable this option if you are using third party Addon plugin.', 'theme-options', 'lafka' ),
			'id'   => 'product_addons',
			'std'  => 'enabled',
			'type' => 'select',
			'options' => array(
				'' => esc_html__('Disabled', 'lafka'),
				'enabled' => esc_html__('Enabled', 'lafka')
			)
		);
		$options[] = array(
			'name' => esc_html_x( 'Product Combos', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'With Product Combos enabled, you can group existing simple, variable, and subscription products and sell them together.', 'theme-options', 'lafka' ) .
			          '<br><br><b>' . esc_html_x( 'IMPORTANT NOTE', 'theme-options', 'lafka' ) . ': </b>' .
			          esc_html_x( 'Disable this option if you are using third party Bundles / Combos plugin.', 'theme-options', 'lafka' ),
			'id'   => 'product_combos',
			'std'  => 'enabled',
			'type' => 'select',
			'options' => array(
				'' => esc_html__('Disabled', 'lafka'),
				'enabled' => esc_html__('Enabled', 'lafka')
			)
		);
		$options[] = array(
			'name' => esc_html_x( 'Lafka Shipping Areas and Branch Locations', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'With this option enabled, you can draw your shipping areas into google maps and use them with the WooCommerce Shipping Zones. You can define different rate and other parameters. Those areas can be used to define different branch locations and validate customer addresses against them. This will allow you to have orders for different branches based on the customer address.', 'theme-options', 'lafka' ) .
			          '<br>' . esc_html_x( 'Once enabled, the settings are available in WooCommerce->Lafka Shipping Settings', 'theme-options', 'lafka' ) .
			          '<br>' . esc_html_x( 'Branches can be defined in Products->Lafka Branch Locations', 'theme-options', 'lafka' ) .
			          '<br><br><b>' . esc_html_x( 'IMPORTANT NOTE', 'theme-options', 'lafka' ) . ': </b>' .
			          esc_html_x( 'This functionality works only with Google Maps. To enable it please set your Google Maps API Keys in WooCommerce->Lafka Shipping Settings', 'theme-options', 'lafka' ),
			'id'   => 'shipping_areas',
			'std'  => '',
			'type' => 'select',
			'options' => array(
				'' => esc_html__('Disabled', 'lafka'),
				'enabled' => esc_html__('Enabled', 'lafka')
			)
		);
		$options[] = array(
			'name' => esc_html_x( 'Order Hours', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'With Order Hours enabled, you can control when the store is open or closed for online orders. There are variety of options: Time zone, Weekly schedule, Instant Open / Close functionality, Holiday period. ', 'theme-options', 'lafka' ) .
			          '<br>' . esc_html_x( 'Once enabled, all settings are available under WooCommerce -> Lafka Order Hours', 'theme-options', 'lafka' ) .
			          '<br><br><b>' . esc_html_x( 'IMPORTANT NOTE', 'theme-options', 'lafka' ) . ': </b>' .
			          esc_html_x( 'Disable this option if you are using third party plugin with similar functionality.', 'theme-options', 'lafka' ),
			'id'   => 'order_hours',
			'std'  => '',
			'type' => 'select',
			'options' => array(
				'' => esc_html__('Disabled', 'lafka'),
				'enabled' => esc_html__('Enabled', 'lafka')
			)
		);
		$options[] = array(
			'name' => esc_html_x( 'New Orders Push Notifications', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'Logged in users with at least "Shop Manager" permissions will receive browser push notification for each order which is ready to be processed. The site should be SSL enabled.', 'theme-options', 'lafka' ) . ' '.
			          '<strong>(' . esc_html__( 'If new order is received for a branch with assigned user. Only this user will receive the notification.', 'lafka' ) . ')</strong>',
			'id'   => 'order_notifications',
			'std'  => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Shop Page Header Style', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Choose the header style for the page set as Shop page.', 'theme-options', 'lafka'),
				'id' => 'shop_header_style',
				'std' => '',
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $header_style_list
		);
		$options[] = array(
				'name' => esc_html_x('Shop Page Top Menu', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Choose top menu for the shop page.', 'theme-options', 'lafka'),
				'id' => 'shop_top_menu',
				'std' => 'default',
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $choose_menu_options
		);
		$options[] = array(
				'name' => esc_html_x('Subtitle', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Subtitle for shop page.', 'theme-options', 'lafka'),
				'id' => 'shop_subtitle',
				'std' => '',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Title with Image Background on Shop Page', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Use to manage / upload images.', 'theme-options', 'lafka'),
				'id' => 'shop_title_background_imgid',
				'std' => '',
				'type' => 'lafka_upload',
				'is_multiple' => false
		);
		$options[] = array(
				'desc' => esc_html_x('Title alignment.', 'theme-options', 'lafka'),
				'id' => 'shop_title_alignment',
				'std' => 'centered_title',
				'type' => 'select',
				'options' => array(
						'left_title' => esc_html_x('Left', 'theme-options', 'lafka'),
						'centered_title' => esc_html_x('Center', 'theme-options', 'lafka'),
				)
		);
		$options[] = array(
			'name' => esc_html_x('Product Page Gallery Type', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose between default WooCommerce gallery and image list gallery. It can be set also on per product basis.', 'theme-options', 'lafka'),
			'id' => 'single_product_gallery_type',
			'std' => 'woo_default',
			'type' => 'select',
			'options' => array(
				'woo_default' => esc_html_x('WooCommerce Default Gallery', 'theme-options', 'lafka'),
				'image_list' => esc_html_x('Image List Gallery', 'theme-options', 'lafka'),
				'mosaic_images' => esc_html_x('Mosaic Images Gallery', 'theme-options', 'lafka'),
			)
		);
		$options[] = array(
			'name' => esc_html_x('Enable Infinite Scroll', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable infinite scroll on shop and category pages.', 'theme-options', 'lafka'),
			'id' => 'enable_shop_infinite',
			'std' => 1,
			'type' => 'checkbox',
			'class' => 'expandable_option'
		);
		$options[] = array(
			'name' => esc_html_x('Load More Button', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Use "Load More" button for the additional products to load.', 'theme-options', 'lafka'),
			'id' => 'use_load_more_on_shop',
			'std' => 0,
			'type' => 'checkbox',
			'class' => 'enable_shop_infinite'
		);
		$options[] = array(
				'name' => esc_html_x('Show Products Filters Area', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Show the area with price range and sorting options on products listings', 'theme-options', 'lafka'),
				'id' => 'show_refine_area',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Products Filtering Area Default State', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Opened / Closed state for the product filtering area on products listings', 'theme-options', 'lafka'),
			'id' => 'refine_area_state',
			'std' => 'opened',
			'options' => array(
				'closed' => esc_html_x('Closed', 'theme-options', 'lafka'),
				'opened' => esc_html_x('Opened', 'theme-options', 'lafka'),
			),
			'type' => 'select'
		);
		$options[] = array(
			'name' => esc_html_x('Enable Ajax for Product Filtering', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable ajax for the price filter, sorting, products per page and "Lafka Product Filter" widget', 'theme-options', 'lafka'),
			'id' => 'use_product_filter_ajax',
			'std' => 1,
			'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Show "My Account" Icon', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose whether to display "My Account" icon link in header.', 'theme-options', 'lafka'),
			'id' => 'show_my_account',
			'std' => 1,
			'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Show Shopping Cart', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Choose whether to display shopping cart in header.', 'theme-options', 'lafka'),
				'id' => 'show_shopping_cart',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Show Shopping Cart on Adding Product', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose whether to display shopping cart when adding product to the cart.', 'theme-options', 'lafka'),
			'id' => 'shopping_cart_on_add',
			'std' => 1,
			'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Use Custom Free Delivery Option', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Hide other shipping methods if free delivery is available. NOTE: Do not enable this option if using additional shipping plugins, and always clear WooCommerce transients after change.', 'theme-options', 'lafka'),
			'id' => 'only_free_delivery',
			'std' => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Enable AJAX Add to Cart on Single Product Pages', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Use AJAX when adding product to cart on single product pages.', 'theme-options', 'lafka'),
			'id' => 'ajax_to_cart_single',
			'std' => 1,
			'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Add to Cart Background Color', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Set background color for "Add to Cart" button on products.', 'theme-options', 'lafka'),
				'id' => 'add_to_cart_color',
				'std' => '#e4584b',
				'type' => 'color'
		);
		$options[] = array(
				'name' => esc_html_x('Add to Cart Sound', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Play sound notification when product is added to shopping cart.', 'theme-options', 'lafka'),
				'id' => 'add_to_cart_sound',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Wishlist Counter in Header', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Display wishlist counter in header.', 'theme-options', 'lafka'),
				'id' => 'show_wish_in_header',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Enable Product Quickview', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Enable Quick view on product listings.', 'theme-options', 'lafka'),
				'id' => 'use_quickview',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
			'name'    => esc_html_x( 'Shop and Category Pages Width', 'theme-options', 'lafka' ),
			'desc'    => esc_html_x( 'Choose width for the shop and category pages.', 'theme-options', 'lafka' ),
			'id'      => 'shop_pages_width',
			'std'     => '',
			'type'    => 'select',
			'class'   => 'mini', //mini, tiny, small
			'options' => array( '' => esc_html_x( 'Standard', 'theme-options', 'lafka' ),
			                    'lafka-fullwidth-shop-pages' => esc_html_x( 'Fullwidth', 'theme-options', 'lafka')
			)
		);
		$options[] = array(
			'name'    => esc_html_x( 'Category Description Position', 'theme-options', 'lafka' ),
			'desc'    => esc_html_x( 'Choose position for category description.', 'theme-options', 'lafka' ),
			'id'      => 'category_description_position',
			'std'     => '',
			'type'    => 'select',
			'class'   => 'mini', //mini, tiny, small
			'options' => array( '' => esc_html_x( 'Top', 'theme-options', 'lafka' ),
			                    'lafka-bottom-description' => esc_html_x( 'Bottom', 'theme-options', 'lafka')
			)
		);
		$options[] = array(
			'name'    => esc_html_x( 'Number of Columns in Product Listings on Mobile Phones', 'theme-options', 'lafka' ),
			'desc'    => esc_html_x( 'Choose the number of columns to be displayed on mobile phones.', 'theme-options', 'lafka' ),
			'id'      => 'product_columns_mobile',
			'std'     => '1',
			'type'    => 'select',
			'class'   => 'mini', //mini, tiny, small
			'options' => array( '1' => esc_html_x( 'Single Column', 'theme-options', 'lafka' ),
			                    '2' => esc_html_x( 'Two Columns', 'theme-options', 'lafka')
			)
		);
		$options[] = array(
			'name'    => esc_html_x( 'Manage Buttons Visibility for Product Lists', 'theme-options', 'lafka' ),
			'desc'    => esc_html_x( 'Should "Add to cart" and similar buttons be visible on product listings, or hidden.', 'theme-options', 'lafka' ),
			'id'      => 'product_list_buttons_visibility',
			'std'     => 'lafka-visible-buttons',
			'type'    => 'select',
			'class'   => 'mini', //mini, tiny, small
			'options' => array(
				'lafka-visible-buttons' => esc_html_x( 'Visible', 'theme-options', 'lafka'),
				'lafka-buttons-on-hover' => esc_html_x( 'Hidden', 'theme-options', 'lafka' )
			)
		);
		$options[] = array(
			'name' => esc_html_x('Quantity Selector for Product Lists', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show product quantity selector in shop page, product archives and all product list views. (Quickview must be disabled in order use this option and is available only for simple products)', 'theme-options', 'lafka'),
			'id' => 'show_quantity_on_listing',
			'std' => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
			'name'    => esc_html_x( 'Hover Product Image Behaviour for Product Lists', 'theme-options', 'lafka' ),
			'desc'    => esc_html_x( 'Choose hover behaviour for product listing.', 'theme-options', 'lafka' ),
			'id'      => 'product_hover_onproduct',
			'std'     => 'lafka-prodhover-zoom',
			'type'    => 'select',
			'class'   => 'mini', //mini, tiny, small
			'options' => array( 'lafka-prodhover-zoom' => esc_html_x( 'Zoom', 'theme-options', 'lafka' ),
			                    'lafka-prodhover-swap' => esc_html_x( 'Image Swap', 'theme-options', 'lafka'),
			                    'none'                => esc_html_x( 'No Effect', 'theme-options', 'lafka' )
			)
		);
		$options[] = array(
			'name' => esc_html_x('Hide Zero Price', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Hide price on single product view when it is set to zero. Useful for fully customizable products (User builds product with add-ons ).', 'theme-options', 'lafka'),
			'id' => 'hide_product_price_on_zero',
			'std' => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Price Color in Product Listings', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set color for prices in product listings.', 'theme-options', 'lafka'),
			'id' => 'price_color_in_listings',
			'std' => '#feda5e',
			'type' => 'color'
		);
		$options[] = array(
			'name' => esc_html_x('Price Background Color in Product Listings', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set background color for prices in product listings.', 'theme-options', 'lafka'),
			'id' => 'price_background_color_in_listings',
			'std' => '#4d2c21',
			'type' => 'color'
		);
		$options[] = array(
			'name' => esc_html_x('Product Categories Fancy Font', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable fancy font and styling for category list. (NOTE: Not recommended if you have categories with long names)', 'theme-options', 'lafka'),
			'id' => 'categories_fancy',
			'std' => 0,
			'type' => 'checkbox',
			'class' => 'expandable_option'
		);
		$options[] = array(
			'name' => esc_html_x('Category Title Color', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set category title\'s color when fancy font is used.', 'theme-options', 'lafka'),
			'id' => 'fancy_category_title_color',
			'std' => '#dd3333',
			'type' => 'color',
			'class' => 'categories_fancy'

		);
		$options[] = array(
				'name' => esc_html_x('Enable Carousel for Shop Categories', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Enable carousel effect for the listed categories on shop pages (If categories are enabled from WooCommerce settings).', 'theme-options', 'lafka'),
				'id' => 'enable_shop_cat_carousel',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Columns Number for Shop Categories', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Select the number of columns for the listed categories on shop pages (If categories are enabled from WooCommerce settings).', 'theme-options', 'lafka'),
				'id' => 'category_columns_num',
				'std' => '3',
				'type' => 'select',
				'class' => 'mini', //mini, tiny, small
				'options' => array(2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6)
		);
		$options[] = array(
				'name' => esc_html_x('Shop Pages Default Product Columns', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Select the default number of product columns on shop/category pages.', 'theme-options', 'lafka'),
				'id' => 'shop_default_product_columns',
				'std' => 'columns-3',
				'type' => 'select',
				'class' => 'mini', //mini, tiny, small
				'options' => $shop_default_product_columns_values
		);
		$options[] = array(
			'name' => esc_html_x('Number of Products per Page', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the number of products per page for product listings, like shop and product category pages.', 'theme-options', 'lafka'),
			'id' => 'products_per_page',
			'std' => 12,
			'class' => 'mini',
			'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Number of Related Products', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Set the number of related products shown on single product page. Set to -1 to display all.', 'theme-options', 'lafka'),
				'id' => 'number_related_products',
				'std' => 6,
				'class' => 'mini',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Enable Price Filter on Product Categories', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Show Price Filter.', 'theme-options', 'lafka'),
				'id' => 'show_pricefilter',
				'class' => 'expandable_option',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
			'name' => esc_html_x('Price Filter Slider Step', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the step at which the slider changes and rounds min and max price boundaries.', 'theme-options', 'lafka'),
			'id' => 'price_filter_widget_step',
			'std' => 10,
			'class' => 'mini show_pricefilter',
			'type' => 'text'
		);
		$options[] = array(
			'name' => esc_html_x('Enable Product Per Page on Product Categories', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Limit products on product category pages.', 'theme-options', 'lafka'),
			'id' => 'show_products_limit',
			'std' => 1,
			'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Use Countdown on Sales', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Show countdown meter for products on sale.', 'theme-options', 'lafka'),
				'id' => 'use_countdown',
				'std' => 'enabled',
				'type' => 'select',
				'class' => 'mini', //mini, tiny, small
				'options' => $enable_disable_array
		);
		$options[] = array(
				'name' => esc_html_x('"NEW" Label Active Period', 'theme-options', 'lafka'),
				'desc' => esc_html_x('in Days', 'theme-options', 'lafka'),
				'id' => 'new_label_period',
				'std' => 45,
				'type' => 'select',
				'class' => 'mini', //mini, tiny, small
				'options' => $new_label_period_array
		);
		$options[] = array(
			'name' => esc_html_x( 'Product Promo Info Tooltips', 'theme-options', 'lafka' ),
			'desc' => esc_html_x( 'Define up to three tooltips, triggered on hover. There are three predefined positions for single product view. And additional option to show tooltips in product listings.', 'theme-options', 'lafka' ),
			'type' => 'info'
		);
		$num_to_words = array(
			1 => esc_html_x( 'First', 'theme-options', 'lafka' ),
			2 => esc_html_x( 'Second', 'theme-options', 'lafka' ),
			3 => esc_html_x( 'Third', 'theme-options', 'lafka' ),
		);
		for ( $i = 1; $i <= 3; $i ++ ) {
			$options[] = array(
				'name' => $num_to_words[ $i ] . ' ' . esc_html_x( 'Tooltip', 'theme-options', 'lafka' ),
				'type' => 'start_section',
				'id'   => 'promo_tooltip_' . $i,
			);
			$options[] = array(
				'name'  => esc_html_x( 'Promo Text', 'theme-options', 'lafka' ),
				'id'    => 'promo_tooltip_' . $i . '_text',
				'std'   => '',
				'type'  => 'text',
				'class' => 'lafka-options-two-columns'
			);
			$options[] = array(
				'name'  => esc_html_x( 'Tooltip Trigger Text', 'theme-options', 'lafka' ),
				'desc'  => esc_html_x( 'When hovered, will show the tooltip content.', 'theme-options', 'lafka' ),
				'id'    => 'promo_tooltip_' . $i . '_trigger_text',
				'std'   => '',
				'type'  => 'text',
				'class' => 'lafka-options-two-columns'
			);
			$options[] = array(
				'name'    => esc_html_x( 'Position in Single Product', 'theme-options', 'lafka' ),
				'id'      => 'promo_tooltip_' . $i . '_position',
				'std'     => 'above_price',
				'type'    => 'select',
				'options' => $promo_tooltip_positions,
				'class'   => 'lafka-options-two-columns'
			);
			$options[] = array(
				'name'  => esc_html_x( 'Show Also in Product Listings', 'theme-options', 'lafka' ),
				'desc'  => esc_html_x( 'Check to show tooltips also in product listings, just below the price.', 'theme-options', 'lafka' ),
				'id'    => 'promo_tooltip_' . $i . '_show_in_listing',
				'std'   => 0,
				'type'  => 'checkbox',
				'class' => 'lafka-options-two-columns'
			);
			$options[] = array(
				'name' => esc_html_x( 'Tooltip Content', 'theme-options', 'lafka' ),
				'id'   => 'promo_tooltip_' . $i . '_content',
				'std'  => '',
				'type' => 'textarea'
			);
			$options[] = array(
				'type' => 'end_section'
			);
		}
		$options[] = array(
			'name' => esc_html_x('Single Product Page Custom Popup', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enter the link label and the content of the popup. The link will be shown in the single product page, in the short description area.', 'theme-options', 'lafka'),
			'id' => 'custom_product_popup_link',
			'std' => '',
			'type' => 'text'
		);
		$options[] = array(
			'id' => 'custom_product_popup_content',
			'media_buttons' => true,
			'std' => '',
			'type' => 'editor',
			'settings' => $wp_editor_settings
		);
		// Video background
		$options[] = array(
				'name' => esc_html_x('Enable YouTube video background for Shop page', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Check to enable below youtube video to be set as background on the Shop page.', 'theme-options', 'lafka'),
				'id' => 'show_shop_video_bckgr',
				'std' => 0,
				'type' => 'checkbox'
		);
		$options[] = array(
				'desc' => esc_html_x('Enable the video background for the whole shop area. NOTE: It will override all other video backgrounds in the shop area.', 'theme-options', 'lafka'),
				'id' => 'shopwide_video_bckgr',
				'std' => '0',
				'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('YouTube Video URL', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Paste the YouTube URL.', 'theme-options', 'lafka'),
				'id' => 'shop_video_bckgr_url',
				'std' => '',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Start Time', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Set the seconds the video should start at.', 'theme-options', 'lafka'),
				'id' => 'shop_video_bckgr_start',
				'class' => 'mini',
				'std' => '',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('End Time', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Set the seconds the video should stop at.', 'theme-options', 'lafka'),
				'id' => 'shop_video_bckgr_end',
				'class' => 'mini',
				'std' => '',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Loop', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Loops the movie once ended.', 'theme-options', 'lafka'),
				'id' => 'shop_video_bckgr_loop',
				'std' => 1,
				'type' => 'checkbox'
		);
		$options[] = array(
				'name' => esc_html_x('Mute', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Mute the audio.', 'theme-options', 'lafka'),
				'id' => 'shop_video_bckgr_mute',
				'std' => 1,
				'type' => 'checkbox'
		);
	}
	/*
	 * If The Events Calendar is activated show the Events options
	 */
	if (defined('LAFKA_IS_EVENTS') && LAFKA_IS_EVENTS) {
		/*
		 * EVENTS
		 */
		$options[] = array(
			'name' => esc_html_x('Events', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'events'
		);
		$options[] = array(
			'desc' => esc_html_x('Options specific to The Events Calendar plugin.', 'theme-options', 'lafka'),
			'type' => 'info'
		);
		$options[] = array(
			'name' => esc_html_x('Top Menu for Events Category View Pages', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose top menu for the Main Calendar Page, Calendar Category Pages, Main Events List, Category Events List.', 'theme-options', 'lafka'),
			'id' => 'events_top_menu',
			'std' => 'default',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $choose_menu_options
		);
		$options[] = array(
			'name' => esc_html_x('Header Style for Events Category View Pages', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose the header style for Main Calendar Page, Calendar Category Pages, Main Events List, Category Events List.', 'theme-options', 'lafka'),
			'id' => 'events_header_style',
			'std' => '',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $header_style_list
		);
		$options[] = array(
			'name' => esc_html_x('Events Title', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enter the title for Main Calendar Page, Main Events List. Use empty for default.', 'theme-options', 'lafka'),
			'id' => 'events_title',
			'std' => '',
			'type' => 'text'
		);
		$options[] = array(
			'name' => esc_html_x('Events Subtitle', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Subtitle for Main Calendar Page, Main Events List.', 'theme-options', 'lafka'),
			'id' => 'events_subtitle',
			'std' => '',
			'type' => 'text'
		);
		$options[] = array(
			'name' => esc_html_x('Title with Image Background for Events', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Use to manage / upload images', 'theme-options', 'lafka'),
			'id' => 'events_title_background_imgid',
			'std' => '',
			'type' => 'lafka_upload',
			'is_multiple' => false
		);
		$options[] = array(
			'desc' => esc_html_x('Title alignment', 'theme-options', 'lafka'),
			'id' => 'events_title_alignment',
			'std' => 'none',
			'type' => 'select',
			'options' => array(
				'left_title' => esc_html_x('Left', 'theme-options', 'lafka'),
				'centered_title' => esc_html_x('Center', 'theme-options', 'lafka'),
			)
		);
		$options[] = array(
			'name' => esc_html_x('Use Countdown on Events', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Show countdown meter for starting time for an Event. Visible on single event pages.', 'theme-options', 'lafka'),
			'id' => 'event_use_countdown',
			'std' => 1,
			'type' => 'checkbox'
		);
	}
	/*
	 * If bbPress is activated show the forum options
	 */
	if (defined('LAFKA_IS_BBPRESS') && LAFKA_IS_BBPRESS) {
		/*
		 * bbPress
		 */
		$options[] = array(
				'name' => esc_html_x('bbPress', 'theme-options', 'lafka'),
				'type' => 'heading',
				'tab_id' => 'bbpress'
		);
		$options[] = array(
				'name' => esc_html_x('Header Style for Forum Root Page', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Choose the header style for the forum root page.', 'theme-options', 'lafka'),
				'id' => 'forum_header_style',
				'std' => '',
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $header_style_list
		);
		$options[] = array(
				'name' => esc_html_x('Forum Root Page Top Menu', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Choose top menu for the forum root page.', 'theme-options', 'lafka'),
				'id' => 'forum_top_menu',
				'std' => 'default',
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $choose_menu_options
		);
		$options[] = array(
				'name' => esc_html_x('Subtitle', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Subtitle for Forum Root Page.', 'theme-options', 'lafka'),
				'id' => 'forum_subtitle',
				'std' => '',
				'type' => 'text'
		);
		$options[] = array(
				'name' => esc_html_x('Title with Image Background on Forum Root Page', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Use to manage / upload images.', 'theme-options', 'lafka'),
				'id' => 'forum_title_background_imgid',
				'std' => '',
				'type' => 'lafka_upload',
				'is_multiple' => false
		);
		$options[] = array(
				'desc' => esc_html_x('Title alignment.', 'theme-options', 'lafka'),
				'id' => 'forum_title_alignment',
				'std' => 'none',
				'type' => 'select',
				'options' => array(
						'left_title' => esc_html_x('Left', 'theme-options', 'lafka'),
						'centered_title' => esc_html_x('Center', 'theme-options', 'lafka'),
				)
		);
	}
	/*
	 * BLOG
	 */
	$options[] = array(
			'name' => esc_html_x('Blog', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'blog'
	);
	$options[] = array(
			'name' => esc_html_x('Blog Style', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose how the posts will appear on the Blog.', 'theme-options', 'lafka'),
			'id' => 'general_blog_style',
			'std' => '',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $general_blog_style_values
	);
	$options[] = array(
			'name' => esc_html_x('Top Menu for Blog page', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose top menu for Blog page.', 'theme-options', 'lafka'),
			'id' => 'blog_top_menu',
			'std' => 'default',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $choose_menu_options
	);
	$options[] = array(
			'name' => esc_html_x('Header Style for Blog page, Category, Tags and Search', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose the header style for Blog page, Category, Tags and Search.', 'theme-options', 'lafka'),
			'id' => 'blog_header_style',
			'std' => '',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $header_style_list
	);
	$options[] = array(
			'name' => esc_html_x('Show Title on the Blog page.', 'theme-options', 'lafka'),
			'desc' => esc_html_x('If selected, the page set as Blog page will have its title displayed.', 'theme-options', 'lafka'),
			'id' => 'show_blog_title',
			'std' => '1',
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Blog Title', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enter the title of the Blog page as set up in the Settings->Reading.', 'theme-options', 'lafka'),
			'id' => 'blog_title',
			'std' => 'Blog',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Blog Subtitle', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Subtitle for blog page.', 'theme-options', 'lafka'),
			'id' => 'blog_subtitle',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Title with Image Background on Blog page, Category, Tags and Search', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Use to manage / upload images', 'theme-options', 'lafka'),
			'id' => 'blog_title_background_imgid',
			'std' => '',
			'type' => 'lafka_upload',
			'is_multiple' => false
	);
	$options[] = array(
			'desc' => esc_html_x('Title alignment', 'theme-options', 'lafka'),
			'id' => 'blog_title_alignment',
			'std' => 'centered_title',
			'type' => 'select',
			'options' => array(
					'left_title' => esc_html_x('Left', 'theme-options', 'lafka'),
					'centered_title' => esc_html_x('Center', 'theme-options', 'lafka'),
			)
	);
	$options[] = array(
		'name'    => esc_html_x( 'Blog and Category Pages Width', 'theme-options', 'lafka' ),
		'desc'    => esc_html_x( 'Choose width for the blog and category pages.', 'theme-options', 'lafka' ),
		'id'      => 'blog_pages_width',
		'std'     => 'lafka-fullwidth-blog-pages',
		'type'    => 'select',
		'class'   => 'mini', //mini, tiny, small
		'options' => array( '' => esc_html_x( 'Standard', 'theme-options', 'lafka' ),
		                    'lafka-fullwidth-blog-pages' => esc_html_x( 'Fullwidth', 'theme-options', 'lafka')
		)
	);
	$options[] = array(
			'name' => esc_html_x('Show Related Posts', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Display random posts from the same categories on single post page.', 'theme-options', 'lafka'),
			'id' => 'show_related_posts',
			'class' => 'expandable_option',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Related Posts Carousel', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enable Carousel effect on related blog posts.', 'theme-options', 'lafka'),
			'id' => 'owl_carousel',
			'std' => 1,
			'type' => 'checkbox',
			'class' => 'show_related_posts'
	);
	$options[] = array(
		'name' => esc_html_x('Number of Related Posts', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Set the number of related posts shown on single post page. Set to -1 to display all.', 'theme-options', 'lafka'),
		'id' => 'number_related_posts',
		'std' => 6,
		'class' => 'mini',
		'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Show Author Info on Blog Posts', 'theme-options', 'lafka'),
			'desc' => esc_html_x('If selected, Author section will be displayed below the post.', 'theme-options', 'lafka'),
			'id' => 'show_author_info',
			'std' => '1',
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Show Author Avatar', 'theme-options', 'lafka'),
			'desc' => esc_html_x('If selected, Author avatar will be displayed on posts.', 'theme-options', 'lafka'),
			'id' => 'show_author_avatar',
			'std' => '1',
			'type' => 'checkbox'
	);
	// Video background
	$options[] = array(
			'name' => esc_html_x('Enable YouTube video background for Blog page', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Check to enable below youtube video to be set as background on the Blog page.', 'theme-options', 'lafka'),
			'id' => 'show_blog_video_bckgr',
			'std' => 0,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('YouTube Video URL', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Paste the YouTube URL.', 'theme-options', 'lafka'),
			'id' => 'blog_video_bckgr_url',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Start Time', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the seconds the video should start at.', 'theme-options', 'lafka'),
			'id' => 'blog_video_bckgr_start',
			'class' => 'mini',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('End Time', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Set the seconds the video should stop at.', 'theme-options', 'lafka'),
			'id' => 'blog_video_bckgr_end',
			'class' => 'mini',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Loop', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Loops the movie once ended.', 'theme-options', 'lafka'),
			'id' => 'blog_video_bckgr_loop',
			'std' => 1,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Mute', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Mute the audio.', 'theme-options', 'lafka'),
			'id' => 'blog_video_bckgr_mute',
			'std' => 1,
			'type' => 'checkbox'
	);
	/*
	 * SIDEBARS
	 */
	$options[] = array(
			'name' => esc_html_x('Sidebars', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'sidebars'
	);
	$options[] = array(
			'name' => esc_html_x('Sidebars Default Position', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Choose sitewide sidebars position. Can be changed in page/post edit.', 'theme-options', 'lafka'),
			'id' => 'sidebar_position',
			'std' => 'lafka-right-sidebar',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $sidebar_positions
	);
	$options[] = array(
			'name' => esc_html_x('Create new custom sidebar', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Enter the name of the custom sidebar.', 'theme-options', 'lafka'),
			'id' => 'sidebar_ids',
			'std' => '',
			'type' => 'sidebar'
	);
	$options[] = array(
			'name' => esc_html_x('Sidebar for Blog, Archive and Category Pages', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select sidebar to be displayed on Blog page and all post category, tag and search pages.', 'theme-options', 'lafka'),
			'id' => 'blog_categoty_sidebar',
			'std' => 'right_sidebar',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $registered_sidebars_array
	);
	$options[] = array(
		'desc' => esc_html_x('Position', 'theme-options', 'lafka'),
		'id' => 'blog_sidebar_position',
		'std' => 'default',
		'type' => 'select',
		'class' => '', //mini, tiny, small
		'options' => $sidebar_positions_with_default
	);
	$options[] = array(
			'name' => esc_html_x('Sidebar for Foodmenu Archive and Category Pages', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select sidebar to be displayed on all Foodmenu Archive and Category Pages.', 'theme-options', 'lafka'),
			'id' => 'foodmenu_categoty_sidebar',
			'std' => 'none',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $registered_sidebars_array
	);
	if (defined('LAFKA_IS_WOOCOMMERCE') && LAFKA_IS_WOOCOMMERCE) {
		$default_shop_sdbr = 'none';
		if (array_key_exists('shop', $registered_sidebars_array)) {
			$default_shop_sdbr = 'shop';
		}
		$options[] = array(
				'name' => esc_html_x('Sidebar for WooCommerce part', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Select sidebar to be displayed on all WooCommerce pages that can have sidebar', 'theme-options', 'lafka'),
				'id' => 'woocommerce_sidebar',
				'std' => $default_shop_sdbr,
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $registered_sidebars_array
		);
		$options[] = array(
			'desc' => esc_html_x('Show sidebar on Shop and category pages.', 'theme-options', 'lafka'),
			'id' => 'show_sidebar_shop',
			'std' => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
			'desc' => esc_html_x('Position for the Shop and category pages sidebar.', 'theme-options', 'lafka'),
			'id' => 'shop_sidebar_position',
			'std' => 'default',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $sidebar_positions_with_default
		);
		$options[] = array(
			'desc' => esc_html_x('Show sidebar on product pages.', 'theme-options', 'lafka'),
			'id' => 'show_sidebar_product',
			'std' => 0,
			'type' => 'checkbox'
		);
		$options[] = array(
			'desc' => esc_html_x('Position for the Product pages sidebar.', 'theme-options', 'lafka'),
			'id' => 'product_sidebar_position',
			'std' => 'default',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $sidebar_positions_with_default
		);
	}
	if (defined('LAFKA_IS_BBPRESS') && LAFKA_IS_BBPRESS) {
		$default_forum_sdbr = 'none';
		if (array_key_exists('lafka_forum', $registered_sidebars_array)) {
			$default_forum_sdbr = 'lafka_forum';
		}
		$options[] = array(
				'name' => esc_html_x('Sidebar for bbPress part', 'theme-options', 'lafka'),
				'desc' => esc_html_x('Select sidebar to be displayed by default on all bbPress pages. May be overridden on specific forums and topics.', 'theme-options', 'lafka'),
				'id' => 'bbpress_sidebar',
				'std' => $default_forum_sdbr,
				'type' => 'select',
				'class' => '', //mini, tiny, small
				'options' => $registered_sidebars_array
		);
	}
	if (defined('LAFKA_IS_EVENTS') && LAFKA_IS_EVENTS) {
		$default_events_sdbr = 'none';
		if (array_key_exists('right_sidebar', $registered_sidebars_array)) {
			$default_events_sdbr = 'right_sidebar';
		}
		$options[] = array(
			'name' => esc_html_x('Sidebar for Events Calendar part', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select sidebar to be displayed by default on all Events Calendar pages. May be overridden on specific Events.', 'theme-options', 'lafka'),
			'id' => 'events_sidebar',
			'std' => $default_events_sdbr,
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $registered_sidebars_array
		);
	}
	$options[] = array(
			'name' => esc_html_x('Off Canvas Sidebar', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select sidebar to be displayed off canvas.', 'theme-options', 'lafka'),
			'id' => 'offcanvas_sidebar',
			'std' => 'none',
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $registered_sidebars_array
	);
	$default_footer_sdbr = 'bottom_footer_sidebar';
	$options[] = array(
			'name' => esc_html_x('Footer Sidebar', 'theme-options', 'lafka'),
			'desc' => esc_html_x('Select sidebar to be displayed in footer. May be overriden for the specific pages, posts and custom post types.', 'theme-options', 'lafka'),
			'id' => 'footer_sidebar',
			'std' => $default_footer_sdbr,
			'type' => 'select',
			'class' => '', //mini, tiny, small
			'options' => $registered_sidebars_array
	);
	/*
	 * Social profiles settings
	 */
	$options[] = array(
			'name' => esc_html_x('Social Profiles', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'socialprofiles'
	);
	$options[] = array(
			'desc' => esc_html_x('Fill in your social profiles URLs.', 'theme-options', 'lafka'),
			'type' => 'info'
	);
	$options[] = array(
			'name' => esc_html_x('Show in Footer', 'theme-options', 'lafka'),
			'id' => 'social_in_footer',
			'desc' => esc_html_x('Show profiles in footer.', 'theme-options', 'lafka'),
			'std' => 0,
			'type' => 'checkbox'
	);
	$options[] = array(
			'name' => esc_html_x('Facebook Profile URL', 'theme-options', 'lafka'),
			'id' => 'facebook_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Twitter Profile URL', 'theme-options', 'lafka'),
			'id' => 'twitter_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('YouTube Profile URL', 'theme-options', 'lafka'),
			'id' => 'youtube_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Vimeo Profile URL', 'theme-options', 'lafka'),
			'id' => 'vimeo_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Dribbble Profile URL', 'theme-options', 'lafka'),
			'id' => 'dribbble_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('LinkedIn Profile URL', 'theme-options', 'lafka'),
			'id' => 'linkedin_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Flickr Profile URL', 'theme-options', 'lafka'),
			'id' => 'flicker_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Instagram Profile URL', 'theme-options', 'lafka'),
			'id' => 'instegram_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Pinterest Profile URL', 'theme-options', 'lafka'),
			'id' => 'pinterest_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('VKontakte Profile URL', 'theme-options', 'lafka'),
			'id' => 'vkontakte_profile',
			'std' => '',
			'type' => 'text'
	);
	$options[] = array(
			'name' => esc_html_x('Behance Profile URL', 'theme-options', 'lafka'),
			'id' => 'behance_profile',
			'std' => '',
			'type' => 'text'
	);
	/*
	 * Demo import
	 */
	$options[] = array(
			'name' => esc_html_x('Import Demo', 'theme-options', 'lafka'),
			'type' => 'heading',
			'tab_id' => 'importdemo'
	);
	$options[] = array(
			'desc' => '<p><b>' . esc_html__('NOTE THAT THE IMPORT CAN OVERRIDE YOUR DATA AND SETTINGS.', 'lafka') . '</b></p>' . sprintf(_x("<p><b>Make sure that the required plugins for the corresponding import are installed and activated.</b></p><p>Note also that the demo is using many images and takes longer to import. You may need to increase some of the PHP parameters, described here: %s . If for some reason is not possible to increase <i>max_execution_time</i> and still can't run the import, you may need to run it again, in order to import all the images.</p><p><b>Click the image of the desired demo to import.</b>The import can take several minutes. For best result use fresh WP installation.</p><p>You can use following plugin to reset WordPress: %s .</p>", 'theme-options', 'lafka'), '<a href="http://althemist.com/are-you-sure-you-want-to-do-this/" target="_blank">Recommended settings for successfull import</a>', '<a href="https://wordpress.org/plugins/wordpress-reset/" target="_blank">WordPress Reset</a>'),
			'type' => 'info'
	);
	$options[] = array(
			'name' => esc_html_x('Import Lafka Main Demo', 'theme-options', 'lafka'),
			'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p><p><b>Required plugins for the import:</b><br/><b><a target=\"_blank\" href=\"https://wordpress.org/plugins/woocommerce/\">WooCommerce</a></b></p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/fastfood/" >Lafka Main Demo</a>'),
			'id' => 'import_lafka0',
			'type' => 'images',
			'class' => 'import_lafka_demo',
			'options' => array(
					'lafka' => LAFKA_IMAGES_PATH . 'demo-image0.jpg'
			)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Burger Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p><p><b>Required plugins for the import:</b><br/><b><a target=\"_blank\" href=\"https://wordpress.org/plugins/woocommerce/\">WooCommerce</a></b></p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/burgers/" >Lafka Burgers</a>'),
		'id' => 'import_lafka1',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image1.jpg'
		)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Pizza Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p><p><b>Required plugins for the import:</b><br/><b><a target=\"_blank\" href=\"https://wordpress.org/plugins/woocommerce/\">WooCommerce</a></b></p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/pizza/" >Lafka Pizza</a>'),
		'id' => 'import_lafka2',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image2.jpg'
		)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Meraki Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p><p><b>Required plugins for the import:</b><br/><b><a target=\"_blank\" href=\"https://wordpress.org/plugins/woocommerce/\">WooCommerce</a></b></p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/meraki/" >Lafka Meraki Restaurant</a>'),
		'id' => 'import_lafka3',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image3.jpg'
		)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Food Truck Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/foodtruck/" >Lafka Food Truck</a>'),
		'id' => 'import_lafka4',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image4.jpg'
		)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Bakery Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p><p><b>Required plugins for the import:</b><br/><b><a target=\"_blank\" href=\"https://wordpress.org/plugins/woocommerce/\">WooCommerce</a></b></p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/bakery/" >Lafka Bakery</a>'),
		'id' => 'import_lafka5',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image5.jpg'
		)
	);
	$options[] = array(
		'name' => esc_html_x('Import Lafka Sushi Demo', 'theme-options', 'lafka'),
		'desc' => sprintf(_x("<p><b>Demo Location:</b> %s <br/><b>NOTE:</b> Usually takes less than 3 minutes, but depending on the server it may take up to 10.</p>", 'theme-options', 'lafka'), '<a target="_blank" href="https://lafka.althemist.com/sushi/" >Lafka Sushi</a>'),
		'id' => 'import_lafka6',
		'type' => 'images',
		'class' => 'import_lafka_demo',
		'options' => array(
			'lafka' => LAFKA_IMAGES_PATH . 'demo-image6.jpg'
		)
	);

	/*
	 * Lafka Updates
	 */
	$options[] = array(
		'name' => esc_html_x('Lafka Updates', 'theme-options', 'lafka'),
		'type' => 'heading',
		'tab_id' => 'lafkaupdates'
	);

	$options[] = array(
		'desc' => '<p>' . esc_html__( 'Lafka checks GitHub for theme and plugin updates automatically every 12 hours. To force a fresh check, visit Dashboard > Updates and click "Check Again".', 'lafka' ) . '</p>',
		'type' => 'info'
	);

	$options[] = array(
		'name' => esc_html_x('Enable Update Checks', 'theme-options', 'lafka'),
		'desc' => esc_html_x('Automatically check GitHub for theme and plugin updates.', 'theme-options', 'lafka'),
		'id' => 'lafka_github_updates_enabled',
		'std' => 1,
		'type' => 'checkbox'
	);

	$options[] = array(
		'name' => esc_html_x('GitHub Personal Access Token', 'theme-options', 'lafka'),
		'desc' => sprintf(
			_x( 'Optional. Increases the GitHub API rate limit from 60 to 5,000 requests/hour. Create a token at %s with no special permissions (public repo access only).', 'theme-options', 'lafka' ),
			'<a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a>'
		),
		'id' => 'lafka_github_token',
		'std' => '',
		'type' => 'text'
	);

	return $options;
}
