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
 * Mechanism: each Customizer setting uses `type => 'option'` with a setting
 * ID like `lafka[<key>]`. WP's Customizer maps that to the `wp_options.lafka`
 * row at sub-key `<key>` — the SAME storage Theme Options writes to. The
 * operator now has one UI; the codebase has one storage.
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
					'description' => esc_html__( 'Logos, branding, integrations, and general site behavior. These fields write to the legacy "Theme Options" storage so existing code keeps working — the only change is the editing UI is now here, in one place.', 'lafka' ),
					'priority'    => 30,
				)
			);

			self::register_logos_section( $wp_customize );
			self::register_brand_section( $wp_customize );
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
				'lafka[mobile_theme_logo]',
				'lafka_settings_logos',
				__( 'Mobile logo (optional)', 'lafka' ),
				__( 'Alternate logo for ≤767px viewports. Also used in the sticky/condensed header. Leave empty to reuse the main logo from Site Identity.', 'lafka' )
			);

			self::add_image(
				$wp_customize,
				'lafka[footer_logo]',
				'lafka_settings_logos',
				__( 'Footer logo (optional)', 'lafka' ),
				__( 'Shown in the footer brand column when "Show logo in footer" is enabled.', 'lafka' )
			);

			self::add_color(
				$wp_customize,
				'lafka[logo_background_color]',
				'lafka_settings_logos',
				__( 'Logo background color', 'lafka' ),
				'#fccc4c',
				__( 'Applied behind the logo across all viewports. Leave the default for the legacy peach-yellow plate.', 'lafka' )
			);

			self::add_checkbox(
				$wp_customize,
				'lafka[disable_logo_point_down]',
				'lafka_settings_logos',
				__( 'Disable the logo point-down accent', 'lafka' ),
				__( 'Removes the small triangle that hangs under the logo plate.', 'lafka' )
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
				'lafka[accent_color]',
				'lafka_settings_brand',
				__( 'Accent color', 'lafka' ),
				'#dc2626',
				__( 'Primary brand color — CTAs, links, badges. Aliased to --lafka-color-accent-500 in modern components.', 'lafka' )
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
		 */
		private static function add_text( $wp_customize, $id, $section, $label, $default = '', $description = '' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
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
		 */
		private static function add_color( $wp_customize, $id, $section, $label, $default = '#000000', $description = '' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
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
		 */
		private static function add_image( $wp_customize, $id, $section, $label, $description = '' ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
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
		 */
		private static function add_checkbox( $wp_customize, $id, $section, $label, $description = '', $default = 0 ): void {
			$wp_customize->add_setting(
				$id,
				array(
					'type'              => 'option',
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
