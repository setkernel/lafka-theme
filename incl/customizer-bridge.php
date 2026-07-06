<?php
/**
 * Customizer bridge to legacy Theme Options storage (v6.0.0).
 *
 * The lafka theme historically shipped TWO config UIs:
 *   - Appearance → Customize  (WP-native, modern)
 *   - Appearance → Lafka Theme Options  (custom framework, 2014-era)
 *
 * These wrote to DIFFERENT DB locations:
 *   - Customizer  → wp_options.theme_mods_<stylesheet> (read via get_theme_mod)
 *   - Theme Opts  → wp_options.lafka (read via lafka_get_option)
 *
 * Operators had to learn which UI drove which field — high friction, low SSOT.
 * This bridge consolidates the visible operator surface into Customizer while
 * keeping the legacy storage intact so existing read paths (`lafka_get_option`)
 * keep working with zero data migration.
 *
 * Mechanism: a legacy-bridged Customizer setting uses `type => 'option'` with a
 * setting ID like `lafka[<key>]`. WP's Customizer maps that to the
 * `wp_options.lafka` row at sub-key `<key>` — the SAME storage Theme Options
 * writes to. The operator now has one UI; the codebase has one storage.
 *
 * NX1-02 (theme 7.0) retires that legacy storage slice by slice: a migrated
 * control instead uses `type => 'theme_mod'` with a `lafka_<key>` setting ID,
 * and its readers move to `get_theme_mod( 'lafka_<key>', <std> )`. The
 * per-control `$type` argument on the setting helpers selects which storage a
 * given control uses, so migrated and not-yet-migrated fields coexist here
 * during the migration. (logos-brand-pilot migrated accent/brand/logo-bg +
 * mobile logo + point-down.)
 *
 * This file is included from functions.php. Adding new bridges below is a
 * matter of calling `lafka_bridge_*()` with the legacy option key.
 *
 * @package Lafka
 * @since   6.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Customizer_Bridge' ) ) {

	/**
	 * Registers Customizer settings that read/write `wp_options.lafka` sub-keys.
	 */
	final class Lafka_Customizer_Bridge {

		/**
		 * Hook into customize_register at default priority.
		 */
		public static function init(): void {
			add_action( 'customize_register', array( __CLASS__, 'register' ) );
			add_action( 'admin_notices', array( __CLASS__, 'theme_options_notice' ) );
		}

		/**
		 * Register the consolidation panel + sections + bridged settings.
		 *
		 * @param WP_Customize_Manager $wp_customize
		 */
		public static function register( $wp_customize ): void {
			$wp_customize->add_panel(
				'lafka_settings',
				array(
					'title'       => esc_html__( 'Lafka — Site Settings', 'lafka' ),
					'description' => esc_html__( 'Logos, branding, integrations, and general site behavior. Migrated fields save to the Customizer (theme_mods); the rest still write to the legacy "Theme Options" storage until their NX1-02 slice lands — the only change is the editing UI is now here, in one place.', 'lafka' ),
					'priority'    => 30,
				)
			);

			self::register_logos_section( $wp_customize );
			self::register_brand_section( $wp_customize );
			self::register_header_colors_section( $wp_customize );
			self::register_menu_colors_section( $wp_customize );
			self::register_footer_colors_section( $wp_customize );
			self::register_content_colors_section( $wp_customize );
			self::register_page_title_colors_section( $wp_customize );
			self::register_listing_colors_section( $wp_customize );
			self::register_general_section( $wp_customize );
		}

		// ====================================================================
		// Section: Logos
		// ====================================================================

		private static function register_logos_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_logos',
				array(
					'title'       => esc_html__( 'Logos (extras)', 'lafka' ),
					/* translators: %s is the linked label "Site Identity → Logo". */
					'description' => sprintf(
						wp_kses(
							/* translators: %s — link to Customizer Site Identity → Logo */
							__( 'The main logo is now uploaded via the WP-native %s. This section only houses the mobile and footer variants — there are no WP-core controls for those.', 'lafka' ),
							array( 'a' => array( 'href' => array() ) )
						),
						'<a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ) . '">' . esc_html__( 'Site Identity → Logo', 'lafka' ) . '</a>'
					),
					'panel'       => 'lafka_settings',
					'priority'    => 10,
				)
			);

			// v6.4.0: removed `lafka[theme_logo]` bridge field. Main logo
			// is now WP's custom-logo theme_mod (see header.php's
			// custom_logo branch + add_theme_support in core-functions.php).
			// Operators upload via Customize → Site Identity → Logo.
			// Legacy data in lafka.theme_logo is still read as fallback
			// by header.php so we don't lose existing values.

			self::add_image(
				$wp_customize,
				'lafka_mobile_theme_logo',
				'lafka_settings_logos',
				__( 'Mobile logo (optional)', 'lafka' ),
				__( 'Alternate logo for ≤767px viewports. Also used in the sticky/condensed header. Leave empty to reuse the main logo from Site Identity.', 'lafka' ),
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_logo_background_color',
				'lafka_settings_logos',
				__( 'Logo background color', 'lafka' ),
				'#fccc4c',
				__( 'Applied behind the logo across all viewports. Leave the default for the legacy peach-yellow plate.', 'lafka' ),
				'theme_mod'
			);

			self::add_checkbox(
				$wp_customize,
				'lafka_disable_logo_point_down',
				'lafka_settings_logos',
				__( 'Disable the logo point-down accent', 'lafka' ),
				__( 'Removes the small triangle that hangs under the logo plate.', 'lafka' ),
				0,
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Brand & Colors
		// ====================================================================

		private static function register_brand_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_brand',
				array(
					'title'       => esc_html__( 'Brand & Colors', 'lafka' ),
					'description' => esc_html__( 'The accent color drives every primary CTA, link hover, and badge. It also flows through the handoff design tokens (since v5.96 SSOT alias) so legacy and rebuilt pages share one brand color.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 20,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_accent_color',
				'lafka_settings_brand',
				__( 'Accent color', 'lafka' ),
				'#dc2626',
				__( 'Primary brand color — CTAs, links, badges. Aliased to --lafka-color-accent-500 in modern components.', 'lafka' ),
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_brand_color',
				'lafka_settings_brand',
				__( 'Brand color', 'lafka' ),
				'#f59e0b',
				__( 'Secondary brand accent — drives the --lafka-color-brand-500 ramp (footer chrome, hero gradient, open-status dot). Defaults to the shipped pepper-yellow.', 'lafka' ),
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Header & Top Bar colors  (NX1-02.dyncss-chrome-colors)
		// ====================================================================

		/**
		 * Header chrome color controls migrated off the legacy Options Framework
		 * to `lafka_<key>` theme_mods. Defaults match the framework `std` (and the
		 * inline defaults in styles/dynamic-css.php) so a fresh install renders the
		 * shipped Peppery pixels unchanged. The two dynamic-css inline fallbacks
		 * `header_top_bar_border_color` / `main_menu_links_bckgr_hover_color` had no
		 * framework field and get no control — they keep their transparent default.
		 */
		private static function register_header_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_header_colors',
				array(
					'title'       => esc_html__( 'Header & Top Bar Colors', 'lafka' ),
					'description' => esc_html__( 'Colors for the top bar, header service icons, and the collapsible pre-header. These drive the header --lafka-* CSS custom properties.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 22,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_header_top_bar_color',
				'lafka_settings_header_colors',
				__( 'Header top bar background color', 'lafka' ),
				'#222222',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_top_bar_message_color',
				'lafka_settings_header_colors',
				__( 'Short header message color', 'lafka' ),
				'#4b4b4b',
				__( 'The optional short message in the header top bar.', 'lafka' ),
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_header_services_color',
				'lafka_settings_header_colors',
				__( 'Header service icons color', 'lafka' ),
				'#333333',
				__( 'My Account, Wishlist, Cart and related header icons.', 'lafka' ),
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_top_bar_menu_links_color',
				'lafka_settings_header_colors',
				__( 'Top bar menu links color', 'lafka' ),
				'#ffffff',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_top_bar_menu_links_hover_color',
				'lafka_settings_header_colors',
				__( 'Top bar menu links hover color', 'lafka' ),
				'#fccc4c',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_transparent_header_dark_menu_color',
				'lafka_settings_header_colors',
				__( 'Transparent header menu color (dark scheme)', 'lafka' ),
				'#22272d',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_collapsible_bckgr_color',
				'lafka_settings_header_colors',
				__( 'Collapsible pre-header background color', 'lafka' ),
				'#fcfcfc',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_collapsible_titles_color',
				'lafka_settings_header_colors',
				__( 'Collapsible pre-header titles color', 'lafka' ),
				'#22272d',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_collapsible_titles_border_color',
				'lafka_settings_header_colors',
				__( 'Collapsible pre-header titles border color', 'lafka' ),
				'#f1f1f1',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_collapsible_links_color',
				'lafka_settings_header_colors',
				__( 'Collapsible pre-header links color', 'lafka' ),
				'#22272d',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Main Menu colors  (NX1-02.dyncss-chrome-colors)
		// ====================================================================

		private static function register_menu_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_menu_colors',
				array(
					'title'       => esc_html__( 'Main Menu Colors', 'lafka' ),
					'description' => esc_html__( 'Background, link, and icon colors for the primary navigation menu.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 24,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_main_menu_background_color',
				'lafka_settings_menu_colors',
				__( 'Main menu background color', 'lafka' ),
				'#fccc4c',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_main_menu_links_color',
				'lafka_settings_menu_colors',
				__( 'Main menu links color', 'lafka' ),
				'#61443e',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_main_menu_links_hover_color',
				'lafka_settings_menu_colors',
				__( 'Main menu links hover color', 'lafka' ),
				'#22272d',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_main_menu_icons_color',
				'lafka_settings_menu_colors',
				__( 'Main menu icons color', 'lafka' ),
				'#ac8320',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Footer colors  (NX1-02.dyncss-chrome-colors)
		// ====================================================================

		private static function register_footer_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_footer_colors',
				array(
					'title'       => esc_html__( 'Footer Colors', 'lafka' ),
					'description' => esc_html__( 'Title, link, text, and copyright-bar colors for the site footer.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 26,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_titles_color',
				'lafka_settings_footer_colors',
				__( 'Footer titles color', 'lafka' ),
				'#ffffff',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_title_border_color',
				'lafka_settings_footer_colors',
				__( 'Footer titles border color', 'lafka' ),
				'#f1f1f1',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_menu_links_color',
				'lafka_settings_footer_colors',
				__( 'Footer menu links color', 'lafka' ),
				'#ffffff',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_links_color',
				'lafka_settings_footer_colors',
				__( 'Footer widget links color', 'lafka' ),
				'#f5f5f5',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_text_color',
				'lafka_settings_footer_colors',
				__( 'Footer text color', 'lafka' ),
				'#aeaeae',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_copyright_bar_bckgr_color',
				'lafka_settings_footer_colors',
				__( 'Footer copyright bar background color', 'lafka' ),
				'#222222',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_footer_copyright_bar_text_color',
				'lafka_settings_footer_colors',
				__( 'Footer copyright bar text color', 'lafka' ),
				'#aeaeae',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Content colors  (NX1-02.dyncss-content-colors)
		// ====================================================================

		private static function register_content_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_content_colors',
				array(
					'title'       => esc_html__( 'Content Colors', 'lafka' ),
					'description' => esc_html__( 'Links, sidebar widget titles, buttons, and the New / Sale product badges. These drive the content --lafka-* CSS custom properties.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 27,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_links_color',
				'lafka_settings_content_colors',
				__( 'Links color', 'lafka' ),
				'#dc2626',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_links_hover_color',
				'lafka_settings_content_colors',
				__( 'Links hover color', 'lafka' ),
				'#ce4f44',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_sidebar_titles_color',
				'lafka_settings_content_colors',
				__( 'Sidebar widget titles color', 'lafka' ),
				'#333333',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_all_buttons_color',
				'lafka_settings_content_colors',
				__( 'Buttons color', 'lafka' ),
				'#dc2626',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_all_buttons_hover_color',
				'lafka_settings_content_colors',
				__( 'Buttons hover color', 'lafka' ),
				'#b91c1c',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_new_label_color',
				'lafka_settings_content_colors',
				__( 'New product label color', 'lafka' ),
				'#047857',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_sale_label_color',
				'lafka_settings_content_colors',
				__( 'Sale product label color', 'lafka' ),
				'#dc2626',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Page title colors  (NX1-02.dyncss-content-colors)
		// ====================================================================

		private static function register_page_title_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_page_title_colors',
				array(
					'title'       => esc_html__( 'Page Title Colors', 'lafka' ),
					'description' => esc_html__( 'Colors for the page-title bar: title, subtitle, and — when a title background image is set — the overlaid custom title, plus the bar background and border.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 28,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_page_title_color',
				'lafka_settings_page_title_colors',
				__( 'Page title color', 'lafka' ),
				'#22272d',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_page_subtitle_color',
				'lafka_settings_page_title_colors',
				__( 'Page subtitle color', 'lafka' ),
				'#5e5e5e',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_custom_page_title_color',
				'lafka_settings_page_title_colors',
				__( 'Custom title color (over background image)', 'lafka' ),
				'#ffffff',
				__( 'Used for the page title when the title bar has a background image.', 'lafka' ),
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_page_title_bckgr_color',
				'lafka_settings_page_title_colors',
				__( 'Page title bar background color', 'lafka' ),
				'#f7f7f7',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_page_title_border_color',
				'lafka_settings_page_title_colors',
				__( 'Page title bar border color', 'lafka' ),
				'#f0f0f0',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: Product listing colors  (NX1-02.dyncss-content-colors)
		// ====================================================================

		private static function register_listing_colors_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_listing_colors',
				array(
					'title'       => esc_html__( 'Product Listing Colors', 'lafka' ),
					'description' => esc_html__( 'Add-to-cart button, price tag, and fancy category title colors used across the product grid and menu listings.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 29,
				)
			);

			self::add_color(
				$wp_customize,
				'lafka_add_to_cart_color',
				'lafka_settings_listing_colors',
				__( 'Add to cart button color', 'lafka' ),
				'#e4584b',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_price_color_in_listings',
				'lafka_settings_listing_colors',
				__( 'Listing price color', 'lafka' ),
				'#feda5e',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_price_background_color_in_listings',
				'lafka_settings_listing_colors',
				__( 'Listing price background color', 'lafka' ),
				'#4d2c21',
				'',
				'theme_mod'
			);

			self::add_color(
				$wp_customize,
				'lafka_fancy_category_title_color',
				'lafka_settings_listing_colors',
				__( 'Fancy category title color', 'lafka' ),
				'#dd3333',
				'',
				'theme_mod'
			);
		}

		// ====================================================================
		// Section: General
		// ====================================================================

		private static function register_general_section( $wp_customize ): void {
			$wp_customize->add_section(
				'lafka_settings_general',
				array(
					'title'       => esc_html__( 'General', 'lafka' ),
					'description' => esc_html__( 'Site-wide behavior toggles and integrations.', 'lafka' ),
					'panel'       => 'lafka_settings',
					'priority'    => 30,
				)
			);

			self::add_text(
				$wp_customize,
				'lafka[google_maps_api_key]',
				'lafka_settings_general',
				__( 'Google Maps API key', 'lafka' ),
				'',
				__( 'Required for the [lafka_map] shortcode and Shipping Areas (if enabled). Generate at https://console.cloud.google.com/google/maps-apis/.', 'lafka' )
			);

			self::add_checkbox(
				$wp_customize,
				'lafka[show_breadcrumb]',
				'lafka_settings_general',
				__( 'Show breadcrumb trail', 'lafka' ),
				__( 'Renders the breadcrumb on archive, single, and page templates that support it.', 'lafka' ),
				1
			);

			self::add_checkbox(
				$wp_customize,
				'lafka[show_prev_next]',
				'lafka_settings_general',
				__( 'Show previous / next post links', 'lafka' ),
				__( 'Appears at the bottom of blog posts and products.', 'lafka' ),
				1
			);

			self::add_checkbox(
				$wp_customize,
				'lafka[enable_smooth_scroll]',
				'lafka_settings_general',
				__( 'Enable smooth scroll for anchor links', 'lafka' ),
				__( 'Smoothly animates jumps to in-page anchors (e.g. one-pager layouts).', 'lafka' ),
				1
			);

			self::add_checkbox(
				$wp_customize,
				'lafka[show_searchform]',
				'lafka_settings_general',
				__( 'Show search form in header', 'lafka' ),
				__( 'Adds the search icon + form to the main navigation.', 'lafka' ),
				0
			);
		}

		// ====================================================================
		// Setting helpers — each writes to wp_options.lafka.<sub-key>
		// ====================================================================

		/**
		 * Add a text setting with a text-input control.
		 *
		 * @param WP_Customize_Manager $wp_customize
		 * @param string               $id          E.g. 'lafka[google_maps_api_key]'
		 * @param string               $section
		 * @param string               $label
		 * @param string               $default
		 * @param string               $description
		 * @param string               $type        'option' (legacy lafka[] storage)
		 *                                           or 'theme_mod' (migrated home).
		 */
		private static function add_text( $wp_customize, $id, $section, $label, $default = '', $description = '', $type = 'option' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => $type,
					'default'           => $default,
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'label'       => $label,
					'section'     => $section,
					'type'        => 'text',
					'description' => $description,
				)
			);
		}

		/**
		 * Add a color setting + WP_Customize_Color_Control.
		 *
		 * @param string $type 'option' (legacy lafka[] storage) or 'theme_mod'.
		 */
		private static function add_color( $wp_customize, $id, $section, $label, $default = '#000000', $description = '', $type = 'option' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => $type,
					'default'           => $default,
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => 'sanitize_hex_color',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$id,
					array(
						'label'       => $label,
						'section'     => $section,
						'description' => $description,
					)
				)
			);
		}

		/**
		 * Add an image-attachment-id setting + WP_Customize_Media_Control.
		 *
		 * Stores the attachment ID (int). Theme Options' `lafka_upload` field
		 * stored attachment IDs the same way, so this is a drop-in bridge —
		 * existing render code calling `lafka_get_option('theme_logo')` keeps
		 * working without any change.
		 *
		 * @param string $type 'option' (legacy lafka[] storage) or 'theme_mod'.
		 */
		private static function add_image( $wp_customize, $id, $section, $label, $description = '', $type = 'option' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => $type,
					'default'           => '',
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => 'absint',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					$id,
					array(
						'label'       => $label,
						'section'     => $section,
						'description' => $description,
						'mime_type'   => 'image',
					)
				)
			);
		}

		/**
		 * Add a boolean setting (stored as 1/0 to match Theme Options shape).
		 *
		 * @param string $type 'option' (legacy lafka[] storage) or 'theme_mod'.
		 */
		private static function add_checkbox( $wp_customize, $id, $section, $label, $description = '', $default = 0, $type = 'option' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => $type,
					'default'           => $default ? 1 : 0,
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'label'       => $label,
					'section'     => $section,
					'type'        => 'checkbox',
					'description' => $description,
				)
			);
		}

		/**
		 * Sanitize a checkbox value to the 1/0 shape Theme Options expects.
		 *
		 * @param mixed $value
		 * @return int
		 */
		public static function sanitize_checkbox( $value ): int {
			return ( ! empty( $value ) && '0' !== $value ) ? 1 : 0;
		}

		// ====================================================================
		// Admin notice on Theme Options page directing operators to Customizer
		// ====================================================================

		/**
		 * Show a one-time-per-pageload notice on the Theme Options admin page
		 * explaining that editing now happens in Customizer.
		 */
		public static function theme_options_notice(): void {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || ! isset( $screen->id ) ) {
				return;
			}
			// Theme Options framework uses the slug 'lafka_options' for its page.
			if ( false === strpos( (string) $screen->id, 'lafka_options' )
				&& false === strpos( (string) $screen->id, 'lafka-options' ) ) {
				return;
			}
			$customizer_url = admin_url( 'customize.php?autofocus[panel]=lafka_settings' );
			?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Editing moved to the Customizer.', 'lafka' ); ?></strong>
					<?php esc_html_e( 'The fields here still work, but the operator UI now lives in', 'lafka' ); ?>
					<a href="<?php echo esc_url( $customizer_url ); ?>"><strong><?php esc_html_e( 'Appearance → Customize → Lafka — Site Settings', 'lafka' ); ?></strong></a>.
					<?php esc_html_e( 'Both surfaces read/write the same DB row, so existing values are preserved either way.', 'lafka' ); ?>
				</p>
			</div>
			<?php
		}
	}

	Lafka_Customizer_Bridge::init();
}
