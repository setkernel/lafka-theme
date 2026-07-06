<?php
/**
 * NX1-02 dynamic-css BYTE-PARITY fixture (committed contract; do NOT edit by hand).
 *
 * A complete, DELIBERATELY NON-DEFAULT snapshot of every legacy theme-option
 * key that styles/dynamic-css.php consumes. tests/Unit/DynamicCssParityTest.php
 * feeds this SAME array to BOTH lafka_get_option() and get_theme_mod() so that,
 * as NX1-02 slices re-point each reader from the legacy layer to theme_mods, a
 * byte-identical emitted CSS proves the rewiring is lossless. Every value is
 * unique so a swapped/dropped key shows up as a diff against the golden.
 *
 * Regenerate ONLY on an intentional dynamic-css contract change:
 *   php scripts/... (see scratch generator) then refresh the golden with
 *   LAFKA_UPDATE_DYNCSS_GOLDEN=1 vendor/bin/phpunit --filter DynamicCssParityTest
 *
 * @package Lafka\Tests
 */

return array(
	'accent_color'                       => '#ce2266',
	'add_to_cart_color'                  => '#9b0c69',
	'all_buttons_color'                  => '#7eb7d5',
	'all_buttons_hover_color'            => '#38f742',
	'brand_color'                        => '#88f6a6',
	'collapsible_bckgr_color'            => '#fa64cc',
	'collapsible_links_color'            => '#0bba0e',
	'collapsible_titles_border_color'    => '#8047db',
	'collapsible_titles_color'           => '#2950ce',
	'custom_page_title_color'            => '#b4c8db',
	'fancy_category_title_color'         => '#bbaedd',
	'footer_copyright_bar_bckgr_color'   => '#3a827f',
	'footer_copyright_bar_text_color'    => '#880b3e',
	'footer_links_color'                 => '#6d4f96',
	'footer_menu_links_color'            => '#6ca926',
	'footer_text_color'                  => '#6024b2',
	'footer_title_border_color'          => '#9cb73e',
	'footer_titles_color'                => '#1ae252',
	'header_services_color'              => '#b0b97f',
	'header_top_bar_border_color'        => '#6a4600',
	'header_top_bar_color'               => '#b945c4',
	'links_color'                        => '#5b05c9',
	'links_hover_color'                  => '#021622',
	'logo_background_color'              => '#59bb35',
	'main_menu_background_color'         => '#45298c',
	'main_menu_icons_color'              => '#a50227',
	'main_menu_links_bckgr_hover_color'  => '#0a58f3',
	'main_menu_links_color'              => '#3d55bf',
	'main_menu_links_hover_color'        => '#d85287',
	'new_label_color'                    => '#87c511',
	'page_subtitle_color'                => '#136ba7',
	'page_title_bckgr_color'             => '#d50974',
	'page_title_border_color'            => '#dfe449',
	'page_title_color'                   => '#296325',
	'price_background_color_in_listings' => '#62bbc5',
	'price_color_in_listings'            => '#0423a6',
	'sale_label_color'                   => '#a78216',
	'sidebar_titles_color'               => '#9f6c36',
	'top_bar_menu_links_color'           => '#79374e',
	'top_bar_menu_links_hover_color'     => '#8e5e4f',
	'top_bar_message_color'              => '#4746c3',
	'transparent_header_dark_menu_color' => '#eb117f',

	// Main-menu typography: reads ['style'] (JSON) + ['size'].
	'main_menu_typography' => array(
		'size'  => '17px',
		'style' => '{"font-weight":"620","font-style":"italic"}',
	),

	// Top-menu typography: reads ['style'] (JSON) + ['size'].
	'top_menu_typography' => array(
		'size'  => '14px',
		'style' => '{"font-weight":"540","font-style":"normal"}',
	),

	// Body font: reads ['face'] + ['size'] + ['color'].
	'body_font' => array(
		'face'  => 'Fixture Sans',
		'size'  => '16.5px',
		'color' => '#2b2b31',
	),

	// Text-logo typography: reads ['style'] (JSON) + ['color'] + ['size'].
	'text_logo_typography' => array(
		'size'  => '23px',
		'style' => '{"font-weight":"770","font-style":"normal"}',
		'color' => '#f4f4f2',
	),

	// Heading fonts h1..h6: each reads ['style'] (JSON) + ['color'] + ['size'].
	'h1_font' => array(
		'size'  => '41px',
		'style' => '{"font-weight":"800","font-style":"normal"}',
		'color' => '#640a02',
	),
	'h2_font' => array(
		'size'  => '33px',
		'style' => '{"font-weight":"750","font-style":"italic"}',
		'color' => '#dcd2c5',
	),
	'h3_font' => array(
		'size'  => '27px',
		'style' => '{"font-weight":"700","font-style":"normal"}',
		'color' => '#c2884a',
	),
	'h4_font' => array(
		'size'  => '22px',
		'style' => '{"font-weight":"650","font-style":"italic"}',
		'color' => '#9b3ce9',
	),
	'h5_font' => array(
		'size'  => '18px',
		'style' => '{"font-weight":"600","font-style":"normal"}',
		'color' => '#475bc9',
	),
	'h6_font' => array(
		'size'  => '15px',
		'style' => '{"font-weight":"550","font-style":"italic"}',
		'color' => '#ccbeb4',
	),

	// Header background: reads ['color'] + ['image'] (+ position/repeat/attachment when image set).
	'header_background' => array(
		'color'      => '#101018',
		'image'      => 101,
		'position'   => 'left top',
		'repeat'     => 'repeat-x',
		'attachment' => 'fixed',
	),

	// Footer background: repeat='no-repeat' exercises the bg-size:cover branch.
	'footer_background' => array(
		'color'      => '#181820',
		'image'      => 102,
		'position'   => 'right bottom',
		'repeat'     => 'no-repeat',
		'attachment' => 'scroll',
	),

	// Page-title default background image: scalar attachment id (truthy → 64px title).
	'page_title_default_bckgr_image' => 103,
);
