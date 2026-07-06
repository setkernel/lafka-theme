<?php
/**
 * NX1-02 legacy Options Framework → Customizer theme_mod migration map.
 *
 * NX1-02 retires the theme's legacy Options Framework (the single
 * `wp_options.lafka` array, read via `lafka_get_option()`). Each migration
 * slice re-points its appearance readers at a namespaced `lafka_<key>`
 * theme_mod. This file is the shared, idempotent copy step that moves an
 * UPGRADED install's stored legacy values into their new theme_mod homes so
 * the storefront renders byte-identically before and after (invariant 2).
 *
 * DESIGN
 * ------
 *  - `lafka_legacy_migrate_map()` is a PURE data map: legacy `lafka` sub-key →
 *    destination theme_mod key. Later NX1-02 slices APPEND their pairs here.
 *  - `lafka_legacy_migrate_run()` copies each mapped legacy value into its
 *    theme_mod. It is idempotent and NON-destructive: a destination that is
 *    already set (e.g. the operator edited it in the Customizer) is never
 *    clobbered, so Customizer always wins over stale legacy data.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 *  - It does not delete or rewrite the `lafka` array. That array SURVIVES —
 *    it is the plugin's flag storage (module registry, order_notifications,
 *    functional-shared secrets). Only the theme's appearance keys are copied
 *    out; plugin-owned keys are absent from the map (invariant 1).
 *  - It does not delete or rewrite the `lafka` array (see above).
 *
 * ONE-TIME UPGRADE TRIGGER (NX1-02 Retire phase)
 * ----------------------------------------------
 *  - `lafka_legacy_migrate_maybe_run()` runs the copy exactly ONCE per install,
 *    gated by the `lafka_legacy_migration_version` option flag (NOT the theme
 *    version — an operator can re-save the theme version without re-migrating,
 *    and a theme downgrade/upgrade cycle must not re-copy stale legacy data over
 *    an operator's newer Customizer edits). It writes a summary to the
 *    `lafka_legacy_migration_log` option for supportability, and is hooked on
 *    `after_setup_theme` so an upgraded install migrates on its first request
 *    (front-end or admin) before dynamic-css / templates read the new homes. The
 *    copy writes to the ACTIVE stylesheet's theme_mods (child on prod, parent on
 *    wp-env), because set_theme_mod() is active-theme aware (Hazard 1).
 *
 * @package Lafka
 * @since   6.22.0 (NX1-02.logos-brand-pilot)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Migration schema version. Bump ONLY when a new slice appends map pairs that an
 * already-migrated install must pick up on its next load. Bumping re-runs the
 * (idempotent, non-clobbering) copy so newly-mapped keys land while every
 * previously-copied / operator-edited theme_mod is left untouched.
 */
if ( ! defined( 'LAFKA_LEGACY_MIGRATION_VERSION' ) ) {
	define( 'LAFKA_LEGACY_MIGRATION_VERSION', 1 );
}

if ( ! function_exists( 'lafka_legacy_migrate_map' ) ) {
	/**
	 * The legacy-key → theme_mod-key migration map.
	 *
	 * Pure data. Every destination is a `lafka_`-namespaced theme_mod so the
	 * NX1-05 config bundle (which only exports `lafka_*` theme_mods) carries it.
	 * Later NX1-02 slices append their migrated appearance keys here.
	 *
	 * @return array<string,string> Map of legacy `lafka` sub-key => theme_mod key.
	 */
	function lafka_legacy_migrate_map() {
		return array(
			// NX1-02.logos-brand-pilot — brand + logo appearance keys.
			'accent_color'            => 'lafka_accent_color',
			'brand_color'             => 'lafka_brand_color',
			'logo_background_color'   => 'lafka_logo_background_color',
			'mobile_theme_logo'       => 'lafka_mobile_theme_logo',
			'disable_logo_point_down' => 'lafka_disable_logo_point_down',
			'theme_logo'              => 'lafka_theme_logo',

			// NX1-02.dyncss-chrome-colors — header / top-bar / collapsible /
			// main-menu / footer color tokens (dynamic-css.php's largest single
			// color block). `header_top_bar_border_color` and
			// `main_menu_links_bckgr_hover_color` were never registered
			// Options-Framework fields (no UI ever wrote them), so their entries
			// here are copy no-ops on any real install — kept for a complete
			// record of the slice's migrated readers.
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
			'main_menu_background_color'         => 'lafka_main_menu_background_color',
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

			// NX1-02.dyncss-content-colors — content color tokens
			// (page-title / buttons / links / labels / product-listing) that
			// dynamic-css.php (+ its gutenberg-editor twins) emit. Each carries
			// the same Options-Framework `std` as its theme_mod default.
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

			// NX1-02.dyncss-typography-backgrounds — the menu/logo/body/heading
			// typography arrays, the accent+headings Google-font settings, and the
			// header/footer background arrays + default title background image that
			// dynamic-css.php (+ its gutenberg-editor twins + the Google-font
			// enqueuer + body-class builder) emit. This slice takes
			// styles/dynamic-css.php to ZERO legacy reads. Each value is a
			// composite array (typography carries a JSON-encoded `style` sub-field;
			// backgrounds carry color/image/position/repeat/attachment) whose exact
			// shape is preserved by the copy below and the theme_mod sanitizers.
			// `headings_font` is inert in dynamic-css.php (headings resolve to
			// --lafka-font-display) but is still read by the editor CSS + the font
			// enqueuer, so it migrates here with the rest of the typography.
			'main_menu_typography'               => 'lafka_main_menu_typography',
			'top_menu_typography'                => 'lafka_top_menu_typography',
			'body_font'                          => 'lafka_body_font',
			'text_logo_typography'               => 'lafka_text_logo_typography',
			'headings_font'                      => 'lafka_headings_font',
			'use_google_face_for'                => 'lafka_use_google_face_for',
			'google_subsets'                     => 'lafka_google_subsets',
			'h1_font'                            => 'lafka_h1_font',
			'h2_font'                            => 'lafka_h2_font',
			'h3_font'                            => 'lafka_h3_font',
			'h4_font'                            => 'lafka_h4_font',
			'h5_font'                            => 'lafka_h5_font',
			'h6_font'                            => 'lafka_h6_font',
			'header_background'                  => 'lafka_header_background',
			'footer_background'                  => 'lafka_footer_background',
			'page_title_default_bckgr_image'     => 'lafka_page_title_default_bckgr_image',

			// NX1-02.layout-behaviour-toggles — the widest non-CSS batch: the
			// layout/behaviour selects + checkboxes that drive body classes
			// (functions.php), the enqueue/localize + sidebar/menu/video-bg
			// resolvers (core-functions.php), the WooCommerce shop layout
			// (woocommerce-functions.php), and the shop / product / foodmenu /
			// blog / forum / events / sidebar templates. Each destination keeps
			// the Options-Framework `std` as its theme_mod default at every
			// reader so a fresh install renders identically and an upgraded
			// install's copied value takes over here. The two dynamic-default
			// sidebar keys (woocommerce_sidebar / bbpress_sidebar /
			// events_sidebar) resolve their default at read time via
			// lafka_registered_sidebar_default(); the copy below is value-verbatim.
			//
			// NOT in this map (deliberately): `lafka_github_token` is a SECRET —
			// it stays in the `lafka` array (read via lafka_get_option) and is
			// never promoted to an exportable `lafka_*` theme_mod, so the NX1-05
			// config bundle can never leak it. `lafka_github_updates_enabled`
			// (a plain on/off toggle) DOES migrate; its key is already
			// `lafka_`-prefixed so its theme_mod home is the identity name.
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

			// NX1-02.plugin-owned-confirm — the WooCommerce sale-countdown toggle
			// is the SINGLE theme-owned key in an otherwise plugin-owned set; it
			// migrates to a theme_mod (readers: incl/woocommerce-functions.php's
			// product-list + single-product countdowns).
			//
			// NOT in this map (deliberately — invariant 1): the plugin-owned keys
			// product_addons, google_maps_api_key, foodmenu_currency,
			// foodmenu_currency_position, category_description_position,
			// custom_product_popup_link, custom_product_popup_content and
			// promo_tooltip_{1..3}_* stay in the `lafka` array (the plugin's flag /
			// functional-shared storage) and are read via the lafka_get_option shim.
			// Absent from the map, the copy never forks them into an exportable
			// theme_mod. The theme's product_addons reads route through the plugin's
			// is_lafka_product_addons() gate rather than a duplicated legacy read.
			'use_countdown'                   => 'lafka_use_countdown',
		);
	}
}

if ( ! function_exists( 'lafka_legacy_migrate_run' ) ) {
	/**
	 * Copy stored legacy `lafka` values into their theme_mod homes.
	 *
	 * Idempotent and non-destructive: only copies a mapped key that is PRESENT
	 * in the stored `lafka` array AND whose destination theme_mod is not already
	 * set. Re-running is a no-op; an operator-set theme_mod is never clobbered.
	 *
	 * @return array<string,mixed> Report of theme_mod key => copied value for
	 *                              the keys this run migrated (empty when none).
	 */
	function lafka_legacy_migrate_run() {
		$report = array();

		$legacy = get_option( 'lafka' );
		if ( ! is_array( $legacy ) ) {
			return $report;
		}

		// A sentinel distinguishes "theme_mod unset" from a stored falsey value.
		$sentinel = '__lafka_legacy_migrate_unset__';

		foreach ( lafka_legacy_migrate_map() as $legacy_key => $mod_key ) {
			if ( ! array_key_exists( $legacy_key, $legacy ) ) {
				continue;
			}
			if ( get_theme_mod( $mod_key, $sentinel ) !== $sentinel ) {
				// Destination already populated (operator edit / prior run).
				continue;
			}
			set_theme_mod( $mod_key, $legacy[ $legacy_key ] );
			$report[ $mod_key ] = $legacy[ $legacy_key ];
		}

		return $report;
	}
}

if ( ! function_exists( 'lafka_legacy_migrate_maybe_run' ) ) {
	/**
	 * Run the legacy → theme_mod copy exactly once per install (per schema bump).
	 *
	 * Gated by the `lafka_legacy_migration_version` option so the copy fires at
	 * most once per LAFKA_LEGACY_MIGRATION_VERSION: on a fresh install the flag is
	 * written immediately (with an empty report — nothing to copy), so this is a
	 * single cheap get_option() on every subsequent request. On an upgraded
	 * install the copy runs, then the flag is stamped and a summary logged.
	 *
	 * @return array<string,mixed>|null Report of copied theme_mods, or null when
	 *                                  this version already migrated (no-op).
	 */
	function lafka_legacy_migrate_maybe_run() {
		$done = (int) get_option( 'lafka_legacy_migration_version', 0 );
		if ( $done >= LAFKA_LEGACY_MIGRATION_VERSION ) {
			return null;
		}

		$report = lafka_legacy_migrate_run();

		// Stamp the flag FIRST so a fatal in the logging below can never cause a
		// re-copy (the copy itself is idempotent, but the invariant is one run).
		update_option( 'lafka_legacy_migration_version', LAFKA_LEGACY_MIGRATION_VERSION, false );

		// Supportability: record what this run did without storing the values
		// (they already live in the theme_mods) — just the audit trail.
		update_option(
			'lafka_legacy_migration_log',
			array(
				'migration_version' => LAFKA_LEGACY_MIGRATION_VERSION,
				'ran_at_gmt'        => function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' ),
				'stylesheet'        => function_exists( 'get_stylesheet' ) ? get_stylesheet() : '',
				'copied_count'      => count( $report ),
				'copied_theme_mods' => array_keys( $report ),
			),
			false
		);

		return $report;
	}
}

// Run the one-time upgrade copy on the first request after an upgrade. Guarded so
// the file can be required by unit tests (which stub get_option/set_theme_mod but
// not add_action) without side effects at include time.
if ( function_exists( 'add_action' ) ) {
	add_action( 'after_setup_theme', 'lafka_legacy_migrate_maybe_run', 99 );
}
