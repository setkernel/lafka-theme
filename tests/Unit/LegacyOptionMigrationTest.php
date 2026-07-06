<?php
declare(strict_types=1);

/**
 * LegacyOptionMigrationTest — NX1-02 one-time legacy-option → theme_mod copy.
 *
 * NX1-02 retires the theme's legacy Options Framework: each migration slice
 * re-points its readers at a `lafka_<key>` theme_mod, and the shared migration
 * map (incl/system/lafka-legacy-migrate.php) copies the operator's stored
 * legacy value into that new home so an UPGRADED install keeps its
 * customizations (invariant 2, pixel parity). This test locks the copy
 * function's contract:
 *   - the map contains every slice's key → theme_mod pair;
 *   - a stored legacy value is copied to its theme_mod;
 *   - the copy is idempotent AND never clobbers a value the operator already
 *     set in the new home (Customizer wins over legacy);
 *   - absent legacy keys and a non-array `lafka` option are safe no-ops.
 *
 * The WP shims (get_option / get_theme_mod / set_theme_mod) live in the GLOBAL
 * namespace — that is where the procedural migrate function resolves its calls.
 * Sibling test files define some of the same shims, so this class runs in a
 * SEPARATE PROCESS with global state discarded, guaranteeing THESE stateful
 * shims win.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 6.22.0 (NX1-02.logos-brand-pilot)
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			return array_key_exists( $name, $GLOBALS['lafka_mig_options'] ?? array() )
				? $GLOBALS['lafka_mig_options'][ $name ]
				: $default;
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return array_key_exists( $name, $GLOBALS['lafka_mig_mods'] ?? array() )
				? $GLOBALS['lafka_mig_mods'][ $name ]
				: $default;
		}
	}
	if ( ! function_exists( 'set_theme_mod' ) ) {
		function set_theme_mod( $name, $value ) {
			$GLOBALS['lafka_mig_mods'][ $name ] = $value;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-legacy-migrate.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class LegacyOptionMigrationTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_mig_options'] = array();
			$GLOBALS['lafka_mig_mods']    = array();
		}

		public function test_map_contains_logos_brand_pilot_keys(): void {
			$map = \lafka_legacy_migrate_map();
			$expected = array(
				'accent_color'            => 'lafka_accent_color',
				'brand_color'             => 'lafka_brand_color',
				'logo_background_color'   => 'lafka_logo_background_color',
				'mobile_theme_logo'       => 'lafka_mobile_theme_logo',
				'disable_logo_point_down' => 'lafka_disable_logo_point_down',
				'theme_logo'              => 'lafka_theme_logo',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_chrome_colors_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'header_top_bar_color'               => 'lafka_header_top_bar_color',
				'header_top_bar_border_color'        => 'lafka_header_top_bar_border_color',
				'top_bar_message_color'              => 'lafka_top_bar_message_color',
				'header_services_color'              => 'lafka_header_services_color',
				'top_bar_menu_links_color'           => 'lafka_top_bar_menu_links_color',
				'top_bar_menu_links_hover_color'     => 'lafka_top_bar_menu_links_hover_color',
				'transparent_header_dark_menu_color' => 'lafka_transparent_header_dark_menu_color',
				'collapsible_bckgr_color'            => 'lafka_collapsible_bckgr_color',
				'collapsible_titles_color'           => 'lafka_collapsible_titles_color',
				'collapsible_titles_border_color'    => 'lafka_collapsible_titles_border_color',
				'collapsible_links_color'            => 'lafka_collapsible_links_color',
				'main_menu_background_color'          => 'lafka_main_menu_background_color',
				'main_menu_links_color'              => 'lafka_main_menu_links_color',
				'main_menu_links_hover_color'        => 'lafka_main_menu_links_hover_color',
				'main_menu_links_bckgr_hover_color'  => 'lafka_main_menu_links_bckgr_hover_color',
				'main_menu_icons_color'              => 'lafka_main_menu_icons_color',
				'footer_titles_color'                => 'lafka_footer_titles_color',
				'footer_title_border_color'          => 'lafka_footer_title_border_color',
				'footer_copyright_bar_text_color'    => 'lafka_footer_copyright_bar_text_color',
				'footer_menu_links_color'            => 'lafka_footer_menu_links_color',
				'footer_links_color'                 => 'lafka_footer_links_color',
				'footer_text_color'                  => 'lafka_footer_text_color',
				'footer_copyright_bar_bckgr_color'   => 'lafka_footer_copyright_bar_bckgr_color',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_content_colors_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'links_color'                        => 'lafka_links_color',
				'links_hover_color'                  => 'lafka_links_hover_color',
				'sidebar_titles_color'               => 'lafka_sidebar_titles_color',
				'all_buttons_color'                  => 'lafka_all_buttons_color',
				'all_buttons_hover_color'            => 'lafka_all_buttons_hover_color',
				'new_label_color'                    => 'lafka_new_label_color',
				'sale_label_color'                   => 'lafka_sale_label_color',
				'page_title_color'                   => 'lafka_page_title_color',
				'page_subtitle_color'                => 'lafka_page_subtitle_color',
				'custom_page_title_color'            => 'lafka_custom_page_title_color',
				'page_title_bckgr_color'             => 'lafka_page_title_bckgr_color',
				'page_title_border_color'            => 'lafka_page_title_border_color',
				'add_to_cart_color'                  => 'lafka_add_to_cart_color',
				'price_color_in_listings'            => 'lafka_price_color_in_listings',
				'price_background_color_in_listings' => 'lafka_price_background_color_in_listings',
				'fancy_category_title_color'         => 'lafka_fancy_category_title_color',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_typography_backgrounds_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'main_menu_typography'           => 'lafka_main_menu_typography',
				'top_menu_typography'            => 'lafka_top_menu_typography',
				'body_font'                      => 'lafka_body_font',
				'text_logo_typography'           => 'lafka_text_logo_typography',
				'headings_font'                  => 'lafka_headings_font',
				'use_google_face_for'            => 'lafka_use_google_face_for',
				'google_subsets'                 => 'lafka_google_subsets',
				'h1_font'                        => 'lafka_h1_font',
				'h2_font'                        => 'lafka_h2_font',
				'h3_font'                        => 'lafka_h3_font',
				'h4_font'                        => 'lafka_h4_font',
				'h5_font'                        => 'lafka_h5_font',
				'h6_font'                        => 'lafka_h6_font',
				'header_background'              => 'lafka_header_background',
				'footer_background'              => 'lafka_footer_background',
				'page_title_default_bckgr_image' => 'lafka_page_title_default_bckgr_image',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}


		public function test_map_contains_layout_behaviour_toggles_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'is_responsive'                   => 'lafka_is_responsive',
				'show_preloader'                  => 'lafka_show_preloader',
				'general_layout'                  => 'lafka_general_layout',
				'sticky_header'                   => 'lafka_sticky_header',
				'header_width'                    => 'lafka_header_width',
				'enable_top_header'               => 'lafka_enable_top_header',
				'header_top_mobile_visibility'    => 'lafka_header_top_mobile_visibility',
				'main_menu_transf_to_uppercase'   => 'lafka_main_menu_transf_to_uppercase',
				'submenu_color_scheme'            => 'lafka_submenu_color_scheme',
				'footer_style'                    => 'lafka_footer_style',
				'footer_width'                    => 'lafka_footer_width',
				'all_buttons_style'               => 'lafka_all_buttons_style',
				'fancy_title_font'                => 'lafka_fancy_title_font',
				'uppercase_page_titles'           => 'lafka_uppercase_page_titles',
				'date_format'                     => 'lafka_date_format',
				'search_options'                  => 'lafka_search_options',
				'show_my_account'                 => 'lafka_show_my_account',
				'show_shopping_cart'              => 'lafka_show_shopping_cart',
				'shopping_cart_on_add'            => 'lafka_shopping_cart_on_add',
				'show_wish_in_header'             => 'lafka_show_wish_in_header',
				'add_to_cart_sound'               => 'lafka_add_to_cart_sound',
				'show_breadcrumb'                 => 'lafka_show_breadcrumb',
				'show_prev_next'                  => 'lafka_show_prev_next',
				'enable_smooth_scroll'            => 'lafka_enable_smooth_scroll',
				'show_searchform'                 => 'lafka_show_searchform',
				'shop_header_style'               => 'lafka_shop_header_style',
				'shop_top_menu'                   => 'lafka_shop_top_menu',
				'shop_subtitle'                   => 'lafka_shop_subtitle',
				'shop_title_background_imgid'     => 'lafka_shop_title_background_imgid',
				'shop_title_alignment'            => 'lafka_shop_title_alignment',
				'single_product_gallery_type'     => 'lafka_single_product_gallery_type',
				'enable_shop_infinite'            => 'lafka_enable_shop_infinite',
				'use_load_more_on_shop'           => 'lafka_use_load_more_on_shop',
				'show_refine_area'                => 'lafka_show_refine_area',
				'refine_area_state'               => 'lafka_refine_area_state',
				'use_product_filter_ajax'         => 'lafka_use_product_filter_ajax',
				'only_free_delivery'              => 'lafka_only_free_delivery',
				'ajax_to_cart_single'             => 'lafka_ajax_to_cart_single',
				'use_quickview'                   => 'lafka_use_quickview',
				'shop_pages_width'                => 'lafka_shop_pages_width',
				'product_columns_mobile'          => 'lafka_product_columns_mobile',
				'product_list_buttons_visibility' => 'lafka_product_list_buttons_visibility',
				'show_quantity_on_listing'        => 'lafka_show_quantity_on_listing',
				'product_hover_onproduct'         => 'lafka_product_hover_onproduct',
				'hide_product_price_on_zero'      => 'lafka_hide_product_price_on_zero',
				'categories_fancy'                => 'lafka_categories_fancy',
				'enable_shop_cat_carousel'        => 'lafka_enable_shop_cat_carousel',
				'category_columns_num'            => 'lafka_category_columns_num',
				'shop_default_product_columns'    => 'lafka_shop_default_product_columns',
				'products_per_page'               => 'lafka_products_per_page',
				'number_related_products'         => 'lafka_number_related_products',
				'show_pricefilter'                => 'lafka_show_pricefilter',
				'price_filter_widget_step'        => 'lafka_price_filter_widget_step',
				'show_products_limit'             => 'lafka_show_products_limit',
				'new_label_period'                => 'lafka_new_label_period',
				'show_shop_video_bckgr'           => 'lafka_show_shop_video_bckgr',
				'shopwide_video_bckgr'            => 'lafka_shopwide_video_bckgr',
				'shop_video_bckgr_url'            => 'lafka_shop_video_bckgr_url',
				'show_related_menu_entries'       => 'lafka_show_related_menu_entries',
				'show_light_menu_entries'         => 'lafka_show_light_menu_entries',
				'hide_foodmenu_images'            => 'lafka_hide_foodmenu_images',
				'foodmenu_simple_menu'            => 'lafka_foodmenu_simple_menu',
				'show_video_bckgr'                => 'lafka_show_video_bckgr',
				'video_bckgr_url'                 => 'lafka_video_bckgr_url',
				'general_blog_style'              => 'lafka_general_blog_style',
				'blog_top_menu'                   => 'lafka_blog_top_menu',
				'blog_header_style'               => 'lafka_blog_header_style',
				'show_blog_title'                 => 'lafka_show_blog_title',
				'blog_title'                      => 'lafka_blog_title',
				'blog_subtitle'                   => 'lafka_blog_subtitle',
				'blog_title_background_imgid'     => 'lafka_blog_title_background_imgid',
				'blog_title_alignment'            => 'lafka_blog_title_alignment',
				'blog_pages_width'                => 'lafka_blog_pages_width',
				'show_author_info'                => 'lafka_show_author_info',
				'show_author_avatar'              => 'lafka_show_author_avatar',
				'show_blog_video_bckgr'           => 'lafka_show_blog_video_bckgr',
				'blog_video_bckgr_url'            => 'lafka_blog_video_bckgr_url',
				'forum_header_style'              => 'lafka_forum_header_style',
				'forum_top_menu'                  => 'lafka_forum_top_menu',
				'forum_subtitle'                  => 'lafka_forum_subtitle',
				'forum_title_background_imgid'    => 'lafka_forum_title_background_imgid',
				'forum_title_alignment'           => 'lafka_forum_title_alignment',
				'events_top_menu'                 => 'lafka_events_top_menu',
				'events_header_style'             => 'lafka_events_header_style',
				'events_title'                    => 'lafka_events_title',
				'event_use_countdown'             => 'lafka_event_use_countdown',
				'sidebar_position'                => 'lafka_sidebar_position',
				'sidebar_ids'                     => 'lafka_sidebar_ids',
				'blog_categoty_sidebar'           => 'lafka_blog_categoty_sidebar',
				'blog_sidebar_position'           => 'lafka_blog_sidebar_position',
				'foodmenu_categoty_sidebar'       => 'lafka_foodmenu_categoty_sidebar',
				'woocommerce_sidebar'             => 'lafka_woocommerce_sidebar',
				'show_sidebar_shop'               => 'lafka_show_sidebar_shop',
				'shop_sidebar_position'           => 'lafka_shop_sidebar_position',
				'show_sidebar_product'            => 'lafka_show_sidebar_product',
				'product_sidebar_position'        => 'lafka_product_sidebar_position',
				'bbpress_sidebar'                 => 'lafka_bbpress_sidebar',
				'events_sidebar'                  => 'lafka_events_sidebar',
				'offcanvas_sidebar'               => 'lafka_offcanvas_sidebar',
				'lafka_github_updates_enabled'    => 'lafka_github_updates_enabled',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
			// The github SECRET token is deliberately NOT migrated (stays in the
			// `lafka` array, never an exportable theme_mod).
			$this->assertArrayNotHasKey( 'lafka_github_token', $map );
		}

		/**
		 * NX1-02.plugin-owned-confirm — the final slice migrates exactly ONE
		 * theme-owned key (the WooCommerce sale-countdown toggle) and confirms the
		 * remaining plugin-owned set stays in the `lafka` array (invariant 1): none
		 * of those keys may appear in the map, or the copy would fork a plugin flag
		 * into an exportable theme_mod and diverge from the plugin's flag storage.
		 */
		public function test_map_contains_use_countdown_and_excludes_plugin_owned_set(): void {
			$map = \lafka_legacy_migrate_map();

			// The lone theme-owned key in this slice migrates to a theme_mod.
			$this->assertArrayHasKey( 'use_countdown', $map, "Migration map missing 'use_countdown'." );
			$this->assertSame( 'lafka_use_countdown', $map['use_countdown'], "Migration map mis-homes 'use_countdown'." );

			// The plugin-owned set is NEVER copied out of the `lafka` array.
			$plugin_owned = array(
				'product_addons',
				'google_maps_api_key',
				'foodmenu_currency',
				'foodmenu_currency_position',
				'category_description_position',
				'custom_product_popup_link',
				'custom_product_popup_content',
				'promo_tooltip_1',
				'promo_tooltip_2',
				'promo_tooltip_3',
			);
			foreach ( $plugin_owned as $key ) {
				$this->assertArrayNotHasKey( $key, $map, "Plugin-owned '{$key}' must stay in the `lafka` array, not migrate to a theme_mod." );
			}
		}

		/**
		 * The copy migrates use_countdown verbatim while leaving every plugin-owned
		 * key untouched in the `lafka` array (never promoted to a theme_mod).
		 */
		public function test_copies_use_countdown_but_never_plugin_owned_keys(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array(
				'use_countdown'                 => 'disabled',
				// Plugin-owned keys that share the array must survive untouched.
				'product_addons'                => 'enabled',
				'google_maps_api_key'           => 'SECRET-KEY',
				'foodmenu_currency'             => '£',
				'foodmenu_currency_position'    => 'right',
				'category_description_position' => 'below',
			);

			$report = \lafka_legacy_migrate_run();

			// The theme-owned toggle lands in its theme_mod home.
			$this->assertSame( 'disabled', get_theme_mod( 'lafka_use_countdown' ) );
			$this->assertSame( 'disabled', $report['lafka_use_countdown'] );

			// No plugin-owned key ever becomes a theme_mod, and none is reported.
			$this->assertFalse( get_theme_mod( 'lafka_product_addons' ) );
			$this->assertFalse( get_theme_mod( 'lafka_google_maps_api_key' ) );
			$this->assertFalse( get_theme_mod( 'lafka_foodmenu_currency' ) );
			$this->assertFalse( get_theme_mod( 'lafka_foodmenu_currency_position' ) );
			$this->assertFalse( get_theme_mod( 'lafka_category_description_position' ) );
			$this->assertArrayNotHasKey( 'lafka_google_maps_api_key', $report );
			$this->assertArrayNotHasKey( 'lafka_foodmenu_currency', $report );
		}

		/**
		 * The composite typography + background arrays copy verbatim into their
		 * theme_mods — the migration is value-type-agnostic, so the JSON-encoded
		 * `style` sub-field and the background arrays keep their exact shape
		 * (Hazard 6). This locks that a slice-5 upgraded install renders identically.
		 */
		public function test_copies_composite_typography_and_background_arrays(): void {
			$body_font  = array(
				'face'  => 'Rubik',
				'size'  => '16px',
				'color' => '#5e5e5e',
			);
			$h1_font    = array(
				'face'  => 'Rubik',
				'size'  => '60px',
				'color' => '#22272d',
				'style' => '{"font-weight":"700","font-style":"normal"}',
			);
			$header_bg  = array(
				'color'      => '#ffffff',
				'image'      => 0,
				'repeat'     => '',
				'position'   => '',
				'attachment' => 'scroll',
			);
			$subsets    = array( 'latin' => '1' );

			$GLOBALS['lafka_mig_options']['lafka'] = array(
				'body_font'         => $body_font,
				'h1_font'           => $h1_font,
				'header_background'  => $header_bg,
				'google_subsets'    => $subsets,
			);

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( $body_font, get_theme_mod( 'lafka_body_font' ) );
			$this->assertSame( $h1_font, get_theme_mod( 'lafka_h1_font' ) );
			$this->assertSame( $header_bg, get_theme_mod( 'lafka_header_background' ) );
			$this->assertSame( $subsets, get_theme_mod( 'lafka_google_subsets' ) );
			// The JSON `style` sub-field survives verbatim for the renderer.
			$this->assertSame(
				'{"font-weight":"700","font-style":"normal"}',
				get_theme_mod( 'lafka_h1_font' )['style']
			);
			$this->assertSame( $body_font, $report['lafka_body_font'] );
		}

		public function test_map_is_pure_and_prefixes_every_destination(): void {
			// A pure data map: every destination is a namespaced lafka_ theme_mod
			// so NX1-05 export (which only bundles lafka_* theme_mods) picks them up.
			foreach ( \lafka_legacy_migrate_map() as $legacy_key => $mod_key ) {
				$this->assertIsString( $legacy_key );
				$this->assertStringStartsWith( 'lafka_', $mod_key, "Destination for '{$legacy_key}' must be a lafka_ theme_mod." );
			}
		}

		public function test_copies_stored_legacy_values_to_theme_mods(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array(
				'accent_color'            => '#0a58f3',
				'brand_color'             => '#88f6a6',
				'logo_background_color'   => '#123456',
				'mobile_theme_logo'       => 42,
				'disable_logo_point_down' => 1,
				'theme_logo'              => 7,
				// A plugin-owned flag that must NOT be migrated (invariant 1).
				'product_addons'          => 'enabled',
			);

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
			$this->assertSame( '#88f6a6', get_theme_mod( 'lafka_brand_color' ) );
			$this->assertSame( '#123456', get_theme_mod( 'lafka_logo_background_color' ) );
			$this->assertSame( 42, get_theme_mod( 'lafka_mobile_theme_logo' ) );
			$this->assertSame( 1, get_theme_mod( 'lafka_disable_logo_point_down' ) );
			$this->assertSame( 7, get_theme_mod( 'lafka_theme_logo' ) );

			// The plugin-owned flag never becomes a theme_mod.
			$this->assertFalse( get_theme_mod( 'lafka_product_addons' ) );
			$this->assertArrayNotHasKey( 'lafka_product_addons', $report );
			// Report lists exactly what was copied.
			$this->assertSame( '#0a58f3', $report['lafka_accent_color'] );
		}

		public function test_is_idempotent_across_repeat_runs(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

			$first = \lafka_legacy_migrate_run();
			$this->assertArrayHasKey( 'lafka_accent_color', $first );

			$second = \lafka_legacy_migrate_run();
			$this->assertSame( array(), $second, 'Second run must copy nothing — the theme_mod is already set.' );
			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
		}

		public function test_never_clobbers_an_operator_set_theme_mod(): void {
			// Operator already set a NEW-home value (e.g. via Customizer) that
			// differs from the stale legacy value: the migration must not overwrite it.
			set_theme_mod( 'lafka_accent_color', '#ffffff' );
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#000000' );

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( '#ffffff', get_theme_mod( 'lafka_accent_color' ), 'Customizer value must win over legacy.' );
			$this->assertArrayNotHasKey( 'lafka_accent_color', $report );
		}

		public function test_absent_legacy_keys_are_skipped(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

			\lafka_legacy_migrate_run();

			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
			// A key not present in the stored array is never written.
			$this->assertFalse( get_theme_mod( 'lafka_logo_background_color' ) );
		}

		public function test_non_array_option_is_a_safe_no_op(): void {
			// Fresh install: the `lafka` option does not exist yet.
			$report = \lafka_legacy_migrate_run();
			$this->assertSame( array(), $report );
		}
	}
}
