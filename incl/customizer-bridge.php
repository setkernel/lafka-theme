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
			self::register_typography_settings( $wp_customize );
			self::register_layout_behaviour_settings( $wp_customize );
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
					'title'       => esc_html__( 'Page Title', 'lafka' ),
					'description' => esc_html__( 'The page-title bar: title, subtitle, and — when a title background image is set — the overlaid custom title, plus the bar background and border, and the default background image used on pages without their own.', 'lafka' ),
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

			// NX1-02.dyncss-typography-backgrounds: the default title background
			// image (scalar attachment id) — the one composite-slice key with a
			// natural native control.
			self::add_image(
				$wp_customize,
				'lafka_page_title_default_bckgr_image',
				'lafka_settings_page_title_colors',
				__( 'Default page title background image', 'lafka' ),
				__( 'Shown behind the page-title bar on any page without its own title image. Leave empty for the flat background color above.', 'lafka' ),
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
		// Typography + backgrounds settings  (NX1-02.dyncss-typography-backgrounds)
		// ====================================================================

		/**
		 * Register the composite typography + background theme_mod settings the
		 * dynamic-css typography/backgrounds slice migrated off the legacy Options
		 * Framework. Each is a first-class `lafka_<key>` theme_mod with the SAME
		 * Options-Framework `std` default and a shape-preserving sanitizer, so a
		 * preset apply / NX1-05 config-bundle import / future control round-trips
		 * through a real Customizer setting and the emitted `--lafka-*` tokens stay
		 * byte-identical (invariants 2 + 3).
		 *
		 * These values are composite arrays — typography carries a JSON-encoded
		 * `style` sub-field (font-weight/font-style), backgrounds carry
		 * color/image/position/repeat/attachment, and the two Google-font settings
		 * are multichecks. WordPress has no native single-input control for such a
		 * shape (the retired framework used bespoke font/background pickers), so
		 * these settings are registered WITHOUT controls; a modern granular
		 * typography editing UI is deferred to the NX2 preset/typography work. The
		 * one scalar key with a natural control — the default title background
		 * image — gets a media control in the Page Title section above.
		 *
		 * @param WP_Customize_Manager $wp_customize
		 */
		private static function register_typography_settings( $wp_customize ): void {
			$typography = array(
				'lafka_main_menu_typography'  => array(
					'size'  => '15px',
					'style' => '{"font-weight":"600","font-style":"normal"}',
				),
				'lafka_top_menu_typography'   => array(
					'size'  => '13px',
					'style' => '{"font-weight":"500","font-style":"normal"}',
				),
				'lafka_body_font'             => array(
					'face'  => 'Rubik',
					'size'  => '16px',
					'color' => '#5e5e5e',
				),
				'lafka_text_logo_typography'  => array(
					'size'  => '21px',
					'style' => '{"font-weight":"700","font-style":"normal"}',
					'color' => '#ffffff',
				),
				'lafka_headings_font'         => array( 'face' => 'Rubik' ),
				'lafka_h1_font'               => array(
					'face'  => 'Rubik',
					'size'  => '60px',
					'color' => '#22272d',
					'style' => '{"font-weight":"700","font-style":"normal"}',
				),
				'lafka_h2_font'               => array(
					'face'  => 'Rubik',
					'size'  => '44px',
					'color' => '#22272d',
					'style' => '{"font-weight":"700","font-style":"normal"}',
				),
				'lafka_h3_font'               => array(
					'face'  => 'Rubik',
					'size'  => '30px',
					'color' => '#22272d',
					'style' => '{"font-weight":"700","font-style":"normal"}',
				),
				'lafka_h4_font'               => array(
					'face'  => 'Rubik',
					'size'  => '24px',
					'color' => '#22272d',
					'style' => '{"font-weight":"600","font-style":"normal"}',
				),
				'lafka_h5_font'               => array(
					'face'  => 'Rubik',
					'size'  => '21px',
					'color' => '#22272d',
					'style' => '{"font-weight":"500","font-style":"normal"}',
				),
				'lafka_h6_font'               => array(
					'face'  => 'Rubik',
					'size'  => '19px',
					'color' => '#22272d',
					'style' => '{"font-weight":"500","font-style":"normal"}',
				),
			);
			foreach ( $typography as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_typography' ),
						'transport'         => 'refresh',
					)
				);
			}

			$backgrounds = array(
				'lafka_header_background' => array(
					'color'      => '#ffffff',
					'image'      => '',
					'repeat'     => '',
					'position'   => '',
					'attachment' => 'scroll',
				),
				'lafka_footer_background' => array(
					'color'      => '#242424',
					'image'      => '',
					'repeat'     => '',
					'position'   => '',
					'attachment' => 'scroll',
				),
			);
			foreach ( $backgrounds as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_background' ),
						'transport'         => 'refresh',
					)
				);
			}

			$multicheck = array(
				'lafka_use_google_face_for' => array(
					'main_menu' => 1,
					'buttons'   => 1,
				),
				'lafka_google_subsets'      => array( 'latin' => '1' ),
			);
			foreach ( $multicheck as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_multicheck' ),
						'transport'         => 'refresh',
					)
				);
			}
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

			// NX1-02.layout-behaviour-toggles: these four general toggles migrated
			// from the legacy `lafka[<key>]` option storage to `lafka_<key>`
			// theme_mods. Their readers now use get_theme_mod(), so the control
			// storage flips in the same commit (Hazard 7 — never leave two
			// controls writing two stores). show_searchform's default is corrected
			// to 1: the framework `std` was 1 (search shown) and its readers always
			// resolved to that, so 1 is the true shipped default.
			self::add_checkbox(
				$wp_customize,
				'lafka_show_breadcrumb',
				'lafka_settings_general',
				__( 'Show breadcrumb trail', 'lafka' ),
				__( 'Renders the breadcrumb on archive, single, and page templates that support it.', 'lafka' ),
				1,
				'theme_mod'
			);

			self::add_checkbox(
				$wp_customize,
				'lafka_show_prev_next',
				'lafka_settings_general',
				__( 'Show previous / next post links', 'lafka' ),
				__( 'Appears at the bottom of blog posts and products.', 'lafka' ),
				1,
				'theme_mod'
			);

			self::add_checkbox(
				$wp_customize,
				'lafka_enable_smooth_scroll',
				'lafka_settings_general',
				__( 'Enable smooth scroll for anchor links', 'lafka' ),
				__( 'Smoothly animates jumps to in-page anchors (e.g. one-pager layouts).', 'lafka' ),
				1,
				'theme_mod'
			);

			self::add_checkbox(
				$wp_customize,
				'lafka_show_searchform',
				'lafka_settings_general',
				__( 'Show search form in header', 'lafka' ),
				__( 'Adds the search icon + form to the main navigation.', 'lafka' ),
				1,
				'theme_mod'
			);
		}

		// ====================================================================
		// Layout & behaviour settings  (NX1-02.layout-behaviour-toggles)
		// ====================================================================

		/**
		 * Register the layout/behaviour theme_mod SETTINGS this slice migrated off
		 * the legacy Options Framework: the site-wide selects + checkboxes that
		 * drive body classes (functions.php), the enqueue/localize + sidebar /
		 * menu / video-background resolvers (core-functions.php), the WooCommerce
		 * shop layout (woocommerce-functions.php), and the shop / product /
		 * foodmenu / blog / forum / events / sidebar templates.
		 *
		 * Each is a first-class `lafka_<key>` theme_mod carrying the SAME
		 * Options-Framework `std` default and a shape-appropriate sanitizer, so a
		 * preset apply / NX1-05 config-bundle round-trip flows through a real
		 * Customizer setting and a fresh install renders the shipped Peppery
		 * pixels unchanged (invariants 2 + 3). As with the typography slice these
		 * are registered WITHOUT bespoke controls — a granular operator UI for the
		 * ~90 internal layout toggles is deferred to the NX2 settings work. The
		 * four general on/off toggles that already had a bridged control
		 * (breadcrumb / prev-next / smooth-scroll / header search) keep their
		 * checkbox control in the General section, now writing to their theme_mod.
		 *
		 * SECRET: `lafka_github_token` is intentionally NOT registered here — it
		 * stays in the `lafka` array (read via lafka_get_option) and never becomes
		 * an exportable `lafka_*` theme_mod, so the config bundle cannot leak it.
		 *
		 * @param WP_Customize_Manager $wp_customize
		 */
		private static function register_layout_behaviour_settings( $wp_customize ): void {

			$checkbox_defaults = array(
				'lafka_is_responsive'                  => 1,
				'lafka_show_preloader'                 => 1,
				'lafka_sticky_header'                  => 1,
				'lafka_enable_top_header'              => 1,
				'lafka_header_top_mobile_visibility'   => 1,
				'lafka_main_menu_transf_to_uppercase'  => 1,
				'lafka_fancy_title_font'               => 0,
				'lafka_uppercase_page_titles'          => 1,
				'lafka_show_my_account'                => 1,
				'lafka_show_shopping_cart'             => 1,
				'lafka_shopping_cart_on_add'           => 1,
				'lafka_show_wish_in_header'            => 1,
				'lafka_add_to_cart_sound'              => 1,
				'lafka_enable_shop_infinite'           => 1,
				'lafka_use_load_more_on_shop'          => 0,
				'lafka_show_refine_area'               => 1,
				'lafka_use_product_filter_ajax'        => 1,
				'lafka_only_free_delivery'             => 0,
				'lafka_ajax_to_cart_single'            => 1,
				'lafka_use_quickview'                  => 1,
				'lafka_show_quantity_on_listing'       => 0,
				'lafka_hide_product_price_on_zero'     => 0,
				'lafka_categories_fancy'               => 0,
				'lafka_enable_shop_cat_carousel'       => 1,
				'lafka_show_pricefilter'               => 1,
				'lafka_show_products_limit'            => 1,
				'lafka_show_shop_video_bckgr'          => 0,
				'lafka_show_related_menu_entries'      => 1,
				'lafka_show_light_menu_entries'        => 1,
				'lafka_hide_foodmenu_images'           => 0,
				'lafka_foodmenu_simple_menu'           => 0,
				'lafka_show_video_bckgr'               => 0,
				'lafka_show_blog_title'                => 1,
				'lafka_show_author_info'               => 1,
				'lafka_show_author_avatar'             => 1,
				'lafka_show_blog_video_bckgr'          => 0,
				'lafka_event_use_countdown'            => 1,
				'lafka_show_sidebar_shop'              => 0,
				'lafka_show_sidebar_product'           => 0,
				'lafka_github_updates_enabled'         => 1,
			);
			foreach ( $checkbox_defaults as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
						'transport'         => 'refresh',
					)
				);
			}

			$string_defaults = array(
				'lafka_general_layout'                   => 'lafka_fullwidth',
				'lafka_header_width'                     => '',
				'lafka_submenu_color_scheme'             => '',
				'lafka_footer_style'                     => '',
				'lafka_footer_width'                     => '',
				'lafka_all_buttons_style'                => 'round',
				'lafka_date_format'                      => 'default',
				'lafka_shop_header_style'                => '',
				'lafka_shop_top_menu'                    => 'default',
				'lafka_shop_subtitle'                    => '',
				'lafka_shop_title_alignment'             => 'centered_title',
				'lafka_single_product_gallery_type'      => 'woo_default',
				'lafka_refine_area_state'                => 'opened',
				'lafka_shop_pages_width'                 => '',
				'lafka_product_columns_mobile'           => '1',
				'lafka_product_list_buttons_visibility'  => 'lafka-visible-buttons',
				'lafka_product_hover_onproduct'          => 'lafka-prodhover-zoom',
				// NX1-02.plugin-owned-confirm — the WooCommerce sale-countdown
				// toggle (legacy 'use_countdown' select; std 'enabled'). The only
				// theme-owned key migrated in that otherwise plugin-owned slice.
				'lafka_use_countdown'                    => 'enabled',
				'lafka_category_columns_num'             => '3',
				'lafka_shop_default_product_columns'     => 'columns-3',
				'lafka_shopwide_video_bckgr'             => '0',
				'lafka_shop_video_bckgr_url'             => '',
				'lafka_video_bckgr_url'                  => '',
				'lafka_general_blog_style'               => '',
				'lafka_blog_top_menu'                    => 'default',
				'lafka_blog_header_style'                => '',
				'lafka_blog_title'                       => 'Blog',
				'lafka_blog_subtitle'                    => '',
				'lafka_blog_title_alignment'             => 'centered_title',
				'lafka_blog_pages_width'                 => 'lafka-fullwidth-blog-pages',
				'lafka_blog_video_bckgr_url'             => '',
				'lafka_forum_header_style'               => '',
				'lafka_forum_top_menu'                   => 'default',
				'lafka_forum_subtitle'                   => '',
				'lafka_forum_title_alignment'            => 'none',
				'lafka_events_top_menu'                  => 'default',
				'lafka_events_header_style'              => '',
				'lafka_events_title'                     => '',
				'lafka_sidebar_position'                 => 'lafka-right-sidebar',
				'lafka_sidebar_ids'                      => '',
				'lafka_blog_categoty_sidebar'            => 'right_sidebar',
				'lafka_blog_sidebar_position'            => 'default',
				'lafka_foodmenu_categoty_sidebar'        => 'none',
				'lafka_shop_sidebar_position'            => 'default',
				'lafka_product_sidebar_position'         => 'default',
				'lafka_offcanvas_sidebar'                => 'none',
			);
			foreach ( $string_defaults as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);
			}

			$int_defaults = array(
				'lafka_products_per_page'         => 12,
				'lafka_number_related_products'   => 6,
				'lafka_price_filter_widget_step'  => 10,
				'lafka_new_label_period'          => 45,
			);
			foreach ( $int_defaults as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => 'absint',
						'transport'         => 'refresh',
					)
				);
			}

			$image_id_defaults = array(
				'lafka_shop_title_background_imgid'   => '',
				'lafka_blog_title_background_imgid'   => '',
				'lafka_forum_title_background_imgid'  => '',
			);
			foreach ( $image_id_defaults as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => 'absint',
						'transport'         => 'refresh',
					)
				);
			}

			$dyn_sidebar_defaults = array(
				'lafka_woocommerce_sidebar'  => lafka_registered_sidebar_default( 'shop' ),
				'lafka_bbpress_sidebar'      => lafka_registered_sidebar_default( 'lafka_forum' ),
				'lafka_events_sidebar'       => lafka_registered_sidebar_default( 'right_sidebar' ),
			);
			foreach ( $dyn_sidebar_defaults as $id => $default ) {
				$wp_customize->add_setting(
					$id,
					array(
						'type'              => 'theme_mod',
						'default'           => $default,
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);
			}

			// search_options is a multicheck (use_ajax / only_products) — reuse the
			// typography-slice multicheck sanitizer to normalise its 1/0 flags.
			$wp_customize->add_setting(
				'lafka_search_options',
				array(
					'type'              => 'theme_mod',
					'default'           => array(
						'use_ajax'      => '1',
						'only_products' => '1',
					),
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => array( __CLASS__, 'sanitize_multicheck' ),
					'transport'         => 'refresh',
				)
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

		/**
		 * Sanitize a composite typography array, preserving the shape
		 * styles/dynamic-css.php consumes: an optional `face`, `size`, `color`
		 * and a JSON-encoded `style` ({"font-weight":…,"font-style":…}) that the
		 * renderer json_decode()s. Only the sub-keys present in the input survive,
		 * so a partial value (e.g. a size-only menu typography) round-trips.
		 *
		 * @param mixed $value
		 * @return array<string,string>
		 */
		public static function sanitize_typography( $value ): array {
			if ( ! is_array( $value ) ) {
				return array();
			}
			$clean = array();
			if ( isset( $value['face'] ) ) {
				$clean['face'] = sanitize_text_field( $value['face'] );
			}
			if ( isset( $value['size'] ) ) {
				$clean['size'] = sanitize_text_field( $value['size'] );
			}
			if ( isset( $value['color'] ) ) {
				$clean['color'] = '' === $value['color'] ? '' : (string) sanitize_hex_color( $value['color'] );
			}
			if ( isset( $value['style'] ) ) {
				// Re-encode from the decoded weight/style pair so only a
				// well-formed JSON object survives (Hazard 6: the exact JSON
				// shape must hold or the renderer's json_decode breaks).
				$decoded = is_string( $value['style'] ) ? json_decode( $value['style'], true ) : null;
				if ( is_array( $decoded ) ) {
					$clean['style'] = (string) wp_json_encode(
						array(
							'font-weight' => isset( $decoded['font-weight'] ) ? sanitize_text_field( $decoded['font-weight'] ) : 'normal',
							'font-style'  => isset( $decoded['font-style'] ) ? sanitize_text_field( $decoded['font-style'] ) : 'normal',
						)
					);
				} else {
					$clean['style'] = '';
				}
			}
			return $clean;
		}

		/**
		 * Sanitize a composite background array, preserving the shape
		 * styles/dynamic-css.php consumes: `color` (hex), `image` (attachment id),
		 * and `position` / `repeat` / `attachment` CSS keywords.
		 *
		 * @param mixed $value
		 * @return array<string,mixed>
		 */
		public static function sanitize_background( $value ): array {
			if ( ! is_array( $value ) ) {
				return array();
			}
			$clean = array();
			if ( isset( $value['color'] ) ) {
				$clean['color'] = '' === $value['color'] ? '' : (string) sanitize_hex_color( $value['color'] );
			}
			if ( isset( $value['image'] ) ) {
				$clean['image'] = '' === $value['image'] ? '' : absint( $value['image'] );
			}
			foreach ( array( 'repeat', 'position', 'attachment' ) as $sub_key ) {
				if ( isset( $value[ $sub_key ] ) ) {
					$clean[ $sub_key ] = sanitize_text_field( $value[ $sub_key ] );
				}
			}
			return $clean;
		}

		/**
		 * Sanitize a multicheck array (use_google_face_for / google_subsets):
		 * a map of option-key => truthy flag, normalised to 1/0.
		 *
		 * @param mixed $value
		 * @return array<string,int>
		 */
		public static function sanitize_multicheck( $value ): array {
			if ( ! is_array( $value ) ) {
				return array();
			}
			$clean = array();
			foreach ( $value as $sub_key => $flag ) {
				$clean[ sanitize_key( $sub_key ) ] = ( ! empty( $flag ) && '0' !== $flag ) ? 1 : 0;
			}
			return $clean;
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
