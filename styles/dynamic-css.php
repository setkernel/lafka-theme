<?php defined( 'ABSPATH' ) || exit; ?>
<?php
/**
 * Generate CSS custom properties from theme options.
 * All selectors live in style.css for browser caching.
 * Only ~100 lines of :root{} declarations are inlined per page.
 */
add_action( 'wp_enqueue_scripts', 'lafka_add_custom_css', 99 );

// Bust the dynamic-css cache whenever the design-token sources change:
//  - the legacy `lafka` option (Options Framework + plugin flags), and
//  - the active theme's `theme_mods_<stylesheet>` row. NX1-02 migrates the
//    design-token readers onto `lafka_<key>` theme_mods, so a Customizer
//    publish (or the one-time legacy->theme_mod copy) writes that theme_mods
//    option — without this branch the operator would see stale cached CSS.
add_action( 'updated_option', 'lafka_dynamic_css_bust_on_options_save', 10, 1 );
add_action( 'added_option', 'lafka_dynamic_css_bust_on_options_save', 10, 1 );
if ( ! function_exists( 'lafka_dynamic_css_bust_on_options_save' ) ) {
	function lafka_dynamic_css_bust_on_options_save( $option_name ) {
		if ( $option_name === 'lafka' || $option_name === 'theme_mods_' . get_option( 'stylesheet' ) ) {
			update_option( 'lafka_dynamic_css_version', (string) time(), false );
		}
	}
}

if ( ! function_exists( 'lafka_dynamic_css_style_pair' ) ) {
	/**
	 * Decode a composite typography `style` sub-field into [ weight, style ].
	 *
	 * The legacy Options-Framework shape stores `style` as a JSON STRING
	 * ('{"font-weight":"600","font-style":"normal"}'), but preset.json chrome
	 * and `lafka_presets`-filter values may (reasonably) supply a plain array
	 * — and json_decode() on an array throws an uncaught TypeError, which
	 * would fatal every front-end request from inside wp_enqueue_scripts.
	 * Accept both shapes; anything else falls back to normal/normal.
	 *
	 * @param mixed $typography Composite typography value ({ size, style, … }).
	 * @return array{0:string,1:string} [ font-weight, font-style ].
	 */
	function lafka_dynamic_css_style_pair( $typography ): array {
		$style = is_array( $typography ) && isset( $typography['style'] ) ? $typography['style'] : null;
		if ( is_string( $style ) && '' !== $style ) {
			$style = json_decode( $style, true );
		}
		if ( ! is_array( $style ) ) {
			return array( 'normal', 'normal' );
		}

		return array(
			isset( $style['font-weight'] ) ? esc_attr( (string) $style['font-weight'] ) : 'normal',
			isset( $style['font-style'] ) ? esc_attr( (string) $style['font-style'] ) : 'normal',
		);
	}
}

if ( ! function_exists( 'lafka_add_custom_css' ) ) {

	function lafka_add_custom_css() {
		// Cache key includes:
		// - options-version: bumped by lafka_dynamic_css_bust_on_options_save when
		//   the operator saves theme options.
		// - theme version: bumped on every theme upgrade so dynamic-css.php code
		//   changes invalidate stale transients (v5.45.0). Without this, any
		//   PHP-level edit to the dynamic-css builder silently no-ops until the
		//   transient expires (was up to a week).
		$opts_version  = get_option( 'lafka_dynamic_css_version', '0' );
		$theme_version = wp_get_theme( get_template() )->get( 'Version' );
		// NX2-01: fold the active preset slug into the cache key. dynamic-css now
		// resolves its ~57 defaults through the active preset (lafka_preset_default),
		// so two presets must never share a cache entry — a preset switch writes
		// only the lafka_active_preset theme_mod, and this makes that switch
		// cache-correct by construction (independent of the option-save bust hook).
		$active_preset = function_exists( 'lafka_get_active_preset_slug' ) ? lafka_get_active_preset_slug() : 'peppery';
		$cache_key     = 'lafka_dyncss_v' . $opts_version . '_t' . $theme_version . '_' . get_locale() . '_p' . $active_preset;

		// NX2-04: inside the Customizer preview the theme_mods are the
		// operator's UNSAVED values — the cache was built from saved ones, so
		// serving it would show stale styles for every dynamic-css-backed
		// control (and writing here would poison the shared cache with
		// unsaved values). Always rebuild, never cache, in preview.
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			wp_add_inline_style( 'lafka-style', lafka_dynamic_css_build() );
			return;
		}

		$custom_css = wp_cache_get( $cache_key, 'lafka' );
		if ( $custom_css === false ) {
			$custom_css = get_transient( $cache_key );
		}
		if ( $custom_css === false ) {
			$custom_css = lafka_dynamic_css_build();
			wp_cache_set( $cache_key, $custom_css, 'lafka', DAY_IN_SECONDS );
			set_transient( $cache_key, $custom_css, WEEK_IN_SECONDS );
		}

		wp_add_inline_style( 'lafka-style', $custom_css );
	}
}

if ( ! function_exists( 'lafka_dynamic_css_build' ) ) {

	/**
	 * Build the dynamic-css string from current theme options. Pure: no side effects.
	 * Pulled out of lafka_add_custom_css() so the result is cacheable.
	 *
	 * @return string CSS string ready for wp_add_inline_style.
	 */
	function lafka_dynamic_css_build() {
		// Gather all theme options.
		// NX1-02.logos-brand-pilot: accent/brand/logo-bg read from `lafka_<key>`
		// theme_mods (migrated off the legacy `lafka` option); inline defaults
		// reproduce the registry `std` so fresh installs still render the
		// shipped Peppery pixels.
		$accent_color                    = esc_attr( get_theme_mod( 'lafka_accent_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_accent_color', '#dc2626' ) : '#dc2626' ) );
		// f074: brand-ramp anchor. Default #f59e0b matches the shipped
		// pepper-yellow in lafka-tokens.css so the out-of-box ramp is
		// unchanged; operators who set a brand color drive the handoff
		// `--lafka-color-brand-500` consumers (footer chrome, hero gradient,
		// open-status dot, etc.) instead of that token being fixed in CSS.
		$brand_color                     = esc_attr( get_theme_mod( 'lafka_brand_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_brand_color', '#f59e0b' ) : '#f59e0b' ) );
		$logo_bg_color                   = esc_attr( get_theme_mod( 'lafka_logo_background_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_logo_background_color', '#fccc4c' ) : '#fccc4c' ) );
		// NX1-02.dyncss-content-colors: content color tokens (links, sidebar
		// titles, all-buttons, new/sale labels, page title/subtitle) read from
		// `lafka_<key>` theme_mods (migrated off the legacy `lafka` option).
		// Inline defaults reproduce the Options-Framework `std` so fresh installs
		// still render the shipped Peppery pixels.
		$links_color                     = esc_attr( get_theme_mod( 'lafka_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_links_color', '#dc2626' ) : '#dc2626' ) );
		$links_hover_color               = esc_attr( get_theme_mod( 'lafka_links_hover_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_links_hover_color', '#ce4f44' ) : '#ce4f44' ) );
		$sidebar_titles_color            = esc_attr( get_theme_mod( 'lafka_sidebar_titles_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_sidebar_titles_color', '#333333' ) : '#333333' ) );
		$all_buttons_color               = esc_attr( get_theme_mod( 'lafka_all_buttons_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_all_buttons_color', '#dc2626' ) : '#dc2626' ) );
		$all_buttons_hover_color         = esc_attr( get_theme_mod( 'lafka_all_buttons_hover_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_all_buttons_hover_color', '#b91c1c' ) : '#b91c1c' ) );
		$new_label_color                 = esc_attr( get_theme_mod( 'lafka_new_label_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_new_label_color', '#047857' ) : '#047857' ) );
		$sale_label_color                = esc_attr( get_theme_mod( 'lafka_sale_label_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_sale_label_color', '#dc2626' ) : '#dc2626' ) );
		$page_title_color                = esc_attr( get_theme_mod( 'lafka_page_title_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_page_title_color', '#22272d' ) : '#22272d' ) );
		$page_subtitle_color             = esc_attr( get_theme_mod( 'lafka_page_subtitle_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_page_subtitle_color', '#5e5e5e' ) : '#5e5e5e' ) );
		$custom_page_title_color         = esc_attr( get_theme_mod( 'lafka_custom_page_title_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_custom_page_title_color', '#ffffff' ) : '#ffffff' ) );
		// NX1-02.dyncss-chrome-colors: header / top-bar / collapsible / footer
		// color tokens read from `lafka_<key>` theme_mods (migrated off the
		// legacy `lafka` option). Inline defaults reproduce the Options-Framework
		// `std` so fresh installs still render the shipped Peppery pixels; the
		// two unregistered fallbacks (header_top_bar_border_color,
		// main_menu_links_bckgr_hover_color) keep their '' default so the ternary
		// resolves to `transparent` when unset, exactly as before. The interleaved
		// page-title background/border keys were migrated in
		// NX1-02.dyncss-content-colors (below).
		$transparent_dark_menu_color     = esc_attr( get_theme_mod( 'lafka_transparent_header_dark_menu_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_transparent_header_dark_menu_color', '#22272d' ) : '#22272d' ) );
		$page_title_bg_color             = esc_attr( get_theme_mod( 'lafka_page_title_bckgr_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_page_title_bckgr_color', '#f7f7f7' ) : '#f7f7f7' ) );
		$page_title_border_color         = esc_attr( get_theme_mod( 'lafka_page_title_border_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_page_title_border_color', '#f0f0f0' ) : '#f0f0f0' ) );
		$header_top_bar_color            = esc_attr( get_theme_mod( 'lafka_header_top_bar_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_header_top_bar_color', '#222222' ) : '#222222' ) );
		$header_top_bar_border_raw       = get_theme_mod( 'lafka_header_top_bar_border_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_header_top_bar_border_color', '' ) : '' );
		$header_top_bar_border_color     = $header_top_bar_border_raw ? esc_attr( $header_top_bar_border_raw ) : 'transparent';
		$top_bar_message_color           = esc_attr( get_theme_mod( 'lafka_top_bar_message_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_top_bar_message_color', '#4b4b4b' ) : '#4b4b4b' ) );
		$header_services_color           = esc_attr( get_theme_mod( 'lafka_header_services_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_header_services_color', '#333333' ) : '#333333' ) );
		$top_bar_menu_links_color        = esc_attr( get_theme_mod( 'lafka_top_bar_menu_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_top_bar_menu_links_color', '#ffffff' ) : '#ffffff' ) );
		$top_bar_menu_links_hover_color  = esc_attr( get_theme_mod( 'lafka_top_bar_menu_links_hover_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_top_bar_menu_links_hover_color', '#fccc4c' ) : '#fccc4c' ) );
		$collapsible_bg_color            = esc_attr( get_theme_mod( 'lafka_collapsible_bckgr_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_collapsible_bckgr_color', '#fcfcfc' ) : '#fcfcfc' ) );
		$collapsible_titles_color        = esc_attr( get_theme_mod( 'lafka_collapsible_titles_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_collapsible_titles_color', '#22272d' ) : '#22272d' ) );
		$collapsible_titles_border_color = esc_attr( get_theme_mod( 'lafka_collapsible_titles_border_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_collapsible_titles_border_color', '#f1f1f1' ) : '#f1f1f1' ) );
		$collapsible_links_color         = esc_attr( get_theme_mod( 'lafka_collapsible_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_collapsible_links_color', '#22272d' ) : '#22272d' ) );
		$footer_titles_color             = esc_attr( get_theme_mod( 'lafka_footer_titles_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_titles_color', '#ffffff' ) : '#ffffff' ) );
		$footer_title_border_color       = esc_attr( get_theme_mod( 'lafka_footer_title_border_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_title_border_color', '#f1f1f1' ) : '#f1f1f1' ) );
		$footer_copyright_text_color     = esc_attr( get_theme_mod( 'lafka_footer_copyright_bar_text_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_copyright_bar_text_color', '#aeaeae' ) : '#aeaeae' ) );
		$footer_menu_links_color         = esc_attr( get_theme_mod( 'lafka_footer_menu_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_menu_links_color', '#ffffff' ) : '#ffffff' ) );
		$footer_links_color              = esc_attr( get_theme_mod( 'lafka_footer_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_links_color', '#f5f5f5' ) : '#f5f5f5' ) );
		$footer_text_color               = esc_attr( get_theme_mod( 'lafka_footer_text_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_text_color', '#aeaeae' ) : '#aeaeae' ) );
		$footer_copyright_bg_raw         = get_theme_mod( 'lafka_footer_copyright_bar_bckgr_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_footer_copyright_bar_bckgr_color', '#222222' ) : '#222222' );
		$footer_copyright_bg_color       = $footer_copyright_bg_raw ? esc_attr( $footer_copyright_bg_raw ) : 'transparent';
		// NX1-02.dyncss-content-colors: product-listing color tokens (add-to-cart
		// button, listing price fg/bg, fancy category title) read from theme_mods.
		$add_to_cart_color               = esc_attr( get_theme_mod( 'lafka_add_to_cart_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_add_to_cart_color', '#e4584b' ) : '#e4584b' ) );
		$price_color                     = esc_attr( get_theme_mod( 'lafka_price_color_in_listings', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_price_color_in_listings', '#feda5e' ) : '#feda5e' ) );
		$price_bg_color                  = esc_attr( get_theme_mod( 'lafka_price_background_color_in_listings', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_price_background_color_in_listings', '#4d2c21' ) : '#4d2c21' ) );
		$fancy_category_title_color      = esc_attr( get_theme_mod( 'lafka_fancy_category_title_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_fancy_category_title_color', '#dd3333' ) : '#dd3333' ) );

		// Main menu (NX1-02.dyncss-chrome-colors: theme_mods; inline defaults
		// reproduce the Options-Framework std. main_menu_links_bckgr_hover_color
		// was never a registered field, so its '' default keeps the ternary
		// transparent/accent fallback identical to the legacy behaviour).
		$menu_bg_color             = esc_attr( get_theme_mod( 'lafka_main_menu_background_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_main_menu_background_color', '#fccc4c' ) : '#fccc4c' ) );
		$menu_links_color          = esc_attr( get_theme_mod( 'lafka_main_menu_links_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_main_menu_links_color', '#61443e' ) : '#61443e' ) );
		$menu_links_hover_color    = esc_attr( get_theme_mod( 'lafka_main_menu_links_hover_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_main_menu_links_hover_color', '#22272d' ) : '#22272d' ) );
		$menu_links_bg_hover_raw   = get_theme_mod( 'lafka_main_menu_links_bckgr_hover_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_main_menu_links_bckgr_hover_color', '' ) : '' );
		$menu_links_bg_hover_color = $menu_links_bg_hover_raw ? esc_attr( $menu_links_bg_hover_raw ) : 'transparent';
		$menu_highlight_bg_color   = $menu_links_bg_hover_raw ? esc_attr( $menu_links_bg_hover_raw ) : $accent_color;
		$menu_icons_color_raw      = get_theme_mod( 'lafka_main_menu_icons_color', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_main_menu_icons_color', '#ac8320' ) : '#ac8320' );
		$menu_icons_color          = $menu_icons_color_raw ? esc_attr( $menu_icons_color_raw ) : 'inherit';

		// NX1-02.dyncss-typography-backgrounds: the menu/logo/body/heading
		// typography arrays + header/footer backgrounds + the default title
		// background image read from `lafka_<key>` theme_mods (migrated off the
		// legacy `lafka` option). After this slice styles/dynamic-css.php has ZERO
		// legacy Options-Framework reads (NX1-02 accept criterion). The composite array
		// SHAPE — a JSON-encoded `style` sub-field decoded below — is preserved by
		// the theme_mod sanitizers registered in incl/customizer-bridge.php; the
		// inline defaults reproduce the Options-Framework `std` so a fresh install
		// still renders the shipped Peppery pixels.

		// Main menu typography
		$main_menu_typography_default = array(
			'size'  => '15px',
			'style' => '{"font-weight":"600","font-style":"normal"}',
		);
		$main_menu_typography = get_theme_mod(
			'lafka_main_menu_typography',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_main_menu_typography', $main_menu_typography_default )
				: $main_menu_typography_default
		);
		list( $menu_font_weight, $menu_font_style ) = lafka_dynamic_css_style_pair( $main_menu_typography );
		$menu_font_size = esc_attr( $main_menu_typography['size'] );

		// Top menu typography
		$top_menu_typography_default = array(
			'size'  => '13px',
			'style' => '{"font-weight":"500","font-style":"normal"}',
		);
		$top_menu_typography  = get_theme_mod(
			'lafka_top_menu_typography',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_top_menu_typography', $top_menu_typography_default )
				: $top_menu_typography_default
		);
		list( $top_menu_font_weight, $top_menu_font_style ) = lafka_dynamic_css_style_pair( $top_menu_typography );
		$top_menu_font_size = esc_attr( $top_menu_typography['size'] );

		// Body font
		$body_font_default = array(
			'face'  => 'Rubik',
			'size'  => '16px',
			'color' => '#5e5e5e',
		);
		$body_font        = get_theme_mod(
			'lafka_body_font',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_body_font', $body_font_default )
				: $body_font_default
		);
		$body_font_family = ! empty( $body_font['face'] ) ? '"' . esc_attr( $body_font['face'] ) . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' : '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
		$body_font_size   = esc_attr( $body_font['size'] );
		$body_font_color  = esc_attr( $body_font['color'] );

		// Text logo typography
		$text_logo_typography_default = array(
			'size'  => '21px',
			'style' => '{"font-weight":"700","font-style":"normal"}',
			'color' => '#ffffff',
		);
		$text_logo_typography = get_theme_mod(
			'lafka_text_logo_typography',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_text_logo_typography', $text_logo_typography_default )
				: $text_logo_typography_default
		);
		list( $logo_font_weight, $logo_font_style ) = lafka_dynamic_css_style_pair( $text_logo_typography );
		$logo_font_color = esc_attr( $text_logo_typography['color'] );
		$logo_font_size  = esc_attr( $text_logo_typography['size'] );

		// Headings font.
		// v5.44.0: the legacy theme-options "Headings Font" picker is now
		// inert — design system defines h1/h2 typography via
		// --lafka-font-display (Fraunces, see DESIGN_SYSTEM.md). Operators
		// wanting custom heading typography override --lafka-font-display
		// in a child-theme stylesheet. The legacy CSS variable name
		// `--lafka-headings-font-family` is kept (style.css:21214 still
		// references it via that name across thousands of selectors), but
		// routed through the design token so a single source of truth wins.
		$headings_font_family = 'var(--lafka-font-display)';

		// H1-H6 fonts. Per-level inline defaults reproduce the Options-Framework
		// std — face 'Rubik' and color '#22272d' are shared across levels; only
		// the size + font-weight differ (see incl/lafka-options-framework/
		// lafka-options.php $hN_font_default). Indexed h1..h6.
		$h_sizes   = array( '60px', '44px', '30px', '24px', '21px', '19px' );
		$h_weights = array( '700', '700', '700', '600', '500', '500' );
		$h_vars    = '';
		for ( $i = 1; $i <= 6; $i++ ) {
			$h_font_default = array(
				'face'  => 'Rubik',
				'size'  => $h_sizes[ $i - 1 ],
				'color' => '#22272d',
				'style' => '{"font-weight":"' . $h_weights[ $i - 1 ] . '","font-style":"normal"}',
			);
			$h_font  = get_theme_mod(
				'lafka_h' . $i . '_font',
				function_exists( 'lafka_preset_default' )
					? lafka_preset_default( 'lafka_h' . $i . '_font', $h_font_default )
					: $h_font_default
			);
			list( $h_weight, $h_slant ) = lafka_dynamic_css_style_pair( $h_font );
			$h_vars .= '--lafka-h' . $i . '-color:' . esc_attr( $h_font['color'] ) . ';';
			$h_vars .= '--lafka-h' . $i . '-size:' . esc_attr( $h_font['size'] ) . ';';
			$h_vars .= '--lafka-h' . $i . '-weight:' . $h_weight . ';';
			$h_vars .= '--lafka-h' . $i . '-style:' . $h_slant . ';';
		}

		// Header background
		$header_backgr_default = array(
			'color'      => '#ffffff',
			'image'      => '',
			'repeat'     => '',
			'position'   => '',
			'attachment' => 'scroll',
		);
		$header_backgr        = get_theme_mod(
			'lafka_header_background',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_header_background', $header_backgr_default )
				: $header_backgr_default
		);
		$header_bg_color      = esc_attr( $header_backgr['color'] );
		$header_bg_image      = 'none';
		$header_bg_position   = 'center center';
		$header_bg_repeat     = 'no-repeat';
		$header_bg_attachment = 'scroll';
		if ( $header_backgr['image'] ) {
			$header_bg_image      = 'url("' . esc_url( wp_get_attachment_image_url( $header_backgr['image'], 'full' ) ) . '")';
			$header_bg_position   = esc_attr( $header_backgr['position'] );
			$header_bg_repeat     = esc_attr( $header_backgr['repeat'] );
			$header_bg_attachment = esc_attr( $header_backgr['attachment'] );
		}

		// Footer background
		$footer_backgr_default = array(
			'color'      => '#242424',
			'image'      => '',
			'repeat'     => '',
			'position'   => '',
			'attachment' => 'scroll',
		);
		$footer_backgr        = get_theme_mod(
			'lafka_footer_background',
			function_exists( 'lafka_preset_default' )
				? lafka_preset_default( 'lafka_footer_background', $footer_backgr_default )
				: $footer_backgr_default
		);
		$footer_bg_color      = esc_attr( $footer_backgr['color'] );
		$footer_bg_image      = 'none';
		$footer_bg_position   = 'center center';
		$footer_bg_repeat     = 'no-repeat';
		$footer_bg_attachment = 'scroll';
		$footer_bg_size       = 'auto';
		if ( $footer_backgr['image'] ) {
			$footer_bg_image      = 'url("' . esc_url( wp_get_attachment_image_url( $footer_backgr['image'], 'full' ) ) . '")';
			$footer_bg_position   = esc_attr( $footer_backgr['position'] );
			$footer_bg_repeat     = esc_attr( $footer_backgr['repeat'] );
			$footer_bg_attachment = esc_attr( $footer_backgr['attachment'] );
			if ( $footer_backgr['repeat'] === 'no-repeat' ) {
				$footer_bg_size = 'cover';
			}
		}

		// Title background image
		$title_backgr       = get_theme_mod( 'lafka_page_title_default_bckgr_image', function_exists( 'lafka_preset_default' ) ? lafka_preset_default( 'lafka_page_title_default_bckgr_image', '' ) : '' );
		$title_bg_image     = 'none';
		$title_bg_font_size = 'inherit';
		if ( $title_backgr ) {
			$title_bg_image     = 'url("' . esc_url( wp_get_attachment_image_url( $title_backgr, 'full' ) ) . '")';
			$title_bg_font_size = '64px';
		}

		$custom_css  = ':root{';
		$custom_css .= '--lafka-accent-color:' . $accent_color . ';';
		// v5.96.0: SSOT — Customizer accent_color drives BOTH the legacy
		// `--lafka-accent-color` (consumed by WPBakery surfaces + dynamic
		// rules below) AND the handoff token `--lafka-color-accent-500`
		// (consumed by every rebuilt page since v5.59.0). Without this
		// alias, operators who set their brand color in Customizer
		// see the legacy surfaces change but the handoff pages stay
		// on the shipped #dc2626 — colour drift across the site.
		$custom_css .= '--lafka-color-accent-500:' . $accent_color . ';';
		// f074: SSOT — bridge the Customizer brand_color into the handoff
		// brand ramp anchor (--lafka-color-brand-500). Without this the brand
		// ramp was fixed at the shipped pepper-yellow with no operator feed,
		// so a rebrand never reached the brand-token consumers. The accent
		// ramp is already mirrored above (v5.96.0); this gives the brand ramp
		// the same operator hook.
		$custom_css .= '--lafka-color-brand-500:' . $brand_color . ';';
		$custom_css .= '--lafka-logo-bg-color:' . $logo_bg_color . ';';
		$custom_css .= '--lafka-link-color:' . $links_color . ';';
		$custom_css .= '--lafka-link-hover-color:' . $links_hover_color . ';';
		$custom_css .= '--lafka-sidebar-title-color:' . $sidebar_titles_color . ';';
		$custom_css .= '--lafka-button-color:' . $all_buttons_color . ';';
		$custom_css .= '--lafka-button-hover-color:' . $all_buttons_hover_color . ';';
		$custom_css .= '--lafka-new-label-color:' . $new_label_color . ';';
		$custom_css .= '--lafka-sale-label-color:' . $sale_label_color . ';';
		$custom_css .= '--lafka-page-title-color:' . $page_title_color . ';';
		$custom_css .= '--lafka-page-subtitle-color:' . $page_subtitle_color . ';';
		$custom_css .= '--lafka-custom-page-title-color:' . $custom_page_title_color . ';';
		$custom_css .= '--lafka-transparent-dark-menu-color:' . $transparent_dark_menu_color . ';';
		$custom_css .= '--lafka-page-title-bg-color:' . $page_title_bg_color . ';';
		$custom_css .= '--lafka-page-title-border-color:' . $page_title_border_color . ';';
		$custom_css .= '--lafka-header-top-bar-color:' . $header_top_bar_color . ';';
		$custom_css .= '--lafka-header-top-bar-border-color:' . $header_top_bar_border_color . ';';
		$custom_css .= '--lafka-menu-bg-color:' . $menu_bg_color . ';';
		$custom_css .= '--lafka-menu-link-color:' . $menu_links_color . ';';
		$custom_css .= '--lafka-menu-link-hover-color:' . $menu_links_hover_color . ';';
		$custom_css .= '--lafka-menu-link-bg-hover-color:' . $menu_links_bg_hover_color . ';';
		$custom_css .= '--lafka-menu-highlight-bg-color:' . $menu_highlight_bg_color . ';';
		$custom_css .= '--lafka-menu-icon-color:' . $menu_icons_color . ';';
		$custom_css .= '--lafka-top-bar-message-color:' . $top_bar_message_color . ';';
		$custom_css .= '--lafka-header-services-color:' . $header_services_color . ';';
		$custom_css .= '--lafka-top-menu-link-color:' . $top_bar_menu_links_color . ';';
		$custom_css .= '--lafka-top-menu-link-hover-color:' . $top_bar_menu_links_hover_color . ';';
		$custom_css .= '--lafka-collapsible-bg-color:' . $collapsible_bg_color . ';';
		$custom_css .= '--lafka-collapsible-title-color:' . $collapsible_titles_color . ';';
		$custom_css .= '--lafka-collapsible-title-border-color:' . $collapsible_titles_border_color . ';';
		$custom_css .= '--lafka-collapsible-link-color:' . $collapsible_links_color . ';';
		$custom_css .= '--lafka-footer-title-color:' . $footer_titles_color . ';';
		$custom_css .= '--lafka-footer-title-border-color:' . $footer_title_border_color . ';';
		$custom_css .= '--lafka-footer-copyright-text-color:' . $footer_copyright_text_color . ';';
		$custom_css .= '--lafka-footer-menu-link-color:' . $footer_menu_links_color . ';';
		$custom_css .= '--lafka-footer-link-color:' . $footer_links_color . ';';
		$custom_css .= '--lafka-footer-text-color:' . $footer_text_color . ';';
		$custom_css .= '--lafka-footer-copyright-bg-color:' . $footer_copyright_bg_color . ';';
		$custom_css .= '--lafka-add-to-cart-color:' . $add_to_cart_color . ';';
		$custom_css .= '--lafka-price-color:' . $price_color . ';';
		$custom_css .= '--lafka-price-bg-color:' . $price_bg_color . ';';
		$custom_css .= '--lafka-fancy-category-title-color:' . $fancy_category_title_color . ';';
		// Typography
		$custom_css .= '--lafka-body-font-family:' . $body_font_family . ';';
		$custom_css .= '--lafka-body-font-size:' . $body_font_size . ';';
		$custom_css .= '--lafka-body-font-color:' . $body_font_color . ';';
		$custom_css .= '--lafka-headings-font-family:' . $headings_font_family . ';';
		$custom_css .= '--lafka-logo-font-color:' . $logo_font_color . ';';
		$custom_css .= '--lafka-logo-font-size:' . $logo_font_size . ';';
		$custom_css .= '--lafka-logo-font-weight:' . $logo_font_weight . ';';
		$custom_css .= '--lafka-logo-font-style:' . $logo_font_style . ';';
		$custom_css .= '--lafka-menu-font-size:' . $menu_font_size . ';';
		$custom_css .= '--lafka-menu-font-weight:' . $menu_font_weight . ';';
		$custom_css .= '--lafka-menu-font-style:' . $menu_font_style . ';';
		$custom_css .= '--lafka-top-menu-font-size:' . $top_menu_font_size . ';';
		$custom_css .= '--lafka-top-menu-font-weight:' . $top_menu_font_weight . ';';
		$custom_css .= '--lafka-top-menu-font-style:' . $top_menu_font_style . ';';
		$custom_css .= $h_vars;
		// Backgrounds
		$custom_css .= '--lafka-header-bg-color:' . $header_bg_color . ';';
		$custom_css .= '--lafka-header-bg-image:' . $header_bg_image . ';';
		$custom_css .= '--lafka-header-bg-position:' . $header_bg_position . ';';
		$custom_css .= '--lafka-header-bg-repeat:' . $header_bg_repeat . ';';
		$custom_css .= '--lafka-header-bg-attachment:' . $header_bg_attachment . ';';
		$custom_css .= '--lafka-footer-bg-color:' . $footer_bg_color . ';';
		$custom_css .= '--lafka-footer-bg-image:' . $footer_bg_image . ';';
		$custom_css .= '--lafka-footer-bg-position:' . $footer_bg_position . ';';
		$custom_css .= '--lafka-footer-bg-repeat:' . $footer_bg_repeat . ';';
		$custom_css .= '--lafka-footer-bg-attachment:' . $footer_bg_attachment . ';';
		$custom_css .= '--lafka-footer-bg-size:' . $footer_bg_size . ';';
		$custom_css .= '--lafka-title-bg-image:' . $title_bg_image . ';';
		$custom_css .= '--lafka-title-bg-font-size:' . $title_bg_font_size . ';';
		$custom_css .= '}';

		// Breadcrumb base color (WCAG AA compliant)
		$custom_css .= '.breadcrumb{color:#767676}';

		// Compare table — always hide quickview/compare on compare page
		$custom_css .= 'table.compare-list .add-to-cart td a.lafka-quick-view-link,table.compare-list .add-to-cart td a.compare.button{display:none !important}';

		return $custom_css;
	}

}
