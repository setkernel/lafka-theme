<?php
/**
 * NX2-01 preset-engine token whitelists (pure-data constants).
 *
 * The preset engine has two independent "operator always wins" layers, each
 * with its own allow-list. These constants are the SINGLE source both the
 * emitter (incl/presets/lafka-preset-emit.php) and the validator
 * (tests/Unit/PresetSchemaTest.php) read, mirroring the `lafka_legacy_migrate_map()`
 * pure-data idiom. See docs/PRESET_ENGINE.md §3.
 *
 *  - LAFKA_PRESET_TOKEN_WHITELIST — the ~200 `--lafka-*` design tokens that have
 *    NO operator feed (surfaces, borders, text, semantics, radii, shadows,
 *    motion, type scale/family). A preset's `tokens{}` block may set any of
 *    these into the Preset-Token Layer (PTL). Deliberately EXCLUDES the
 *    operator-fed / derived colours (`--lafka-color-accent-500`, `-brand-500`,
 *    `-accent-text`), every structural token (spacing/gap, z-index,
 *    container/gutter/header-h, tap-target), and the legacy `var()`-alias
 *    forwards — those are FORBIDDEN in a preset and fail PresetSchemaTest.
 *
 *  - LAFKA_PRESET_CHROME_WHITELIST — `lafka_accent_color` + `lafka_brand_color`
 *    plus the ~55 appearance theme_mods that `styles/dynamic-css.php` emits
 *    (the `lafka_legacy_migrate_map()` destinations). A preset's `chrome{}`
 *    block supplies the DEFAULT value for these via the theme_mod-default
 *    layer (TML, see lafka_preset_default()); an operator-set theme_mod always
 *    beats it by get_theme_mod() semantics.
 *
 *  - LAFKA_PRESET_CRITICAL_KEYS — the above-fold subset of the token whitelist
 *    (inert this wave; consumed by the NX2-04.1 critical.css preset-awareness).
 *
 * @package Lafka
 * @since   7.1.0 (NX2-01)
 */

defined( 'ABSPATH' ) || exit;

/**
 * PTL token allow-list. Every key a preset lists under `tokens{}` MUST appear
 * here. Keys map 1:1 to `--lafka-*` custom properties declared in
 * styles/lafka-tokens.css that have no operator feed.
 */
if ( ! defined( 'LAFKA_PRESET_TOKEN_WHITELIST' ) ) {
	define(
		'LAFKA_PRESET_TOKEN_WHITELIST',
		array(
			// ---- Colour: brand ramp (EXCLUDES -500 — operator-fed via chrome) --
			'--lafka-color-brand-50',
			'--lafka-color-brand-100',
			'--lafka-color-brand-300',
			'--lafka-color-brand-600',
			'--lafka-color-brand-700',
			'--lafka-color-brand-900',

			// ---- Colour: accent ramp (EXCLUDES -500 operator-fed + -text derived)
			'--lafka-color-accent-50',
			'--lafka-color-accent-600',
			'--lafka-color-accent-700',
			'--lafka-color-accent-contrast',

			// ---- Colour: text --------------------------------------------------
			'--lafka-color-text-primary',
			'--lafka-color-text-secondary',
			'--lafka-color-text-muted',
			'--lafka-color-text-inverse',
			'--lafka-color-text-on-accent',

			// ---- Colour: surface ----------------------------------------------
			'--lafka-color-surface-page',
			'--lafka-color-surface-raised',
			'--lafka-color-surface-sunken',
			'--lafka-color-surface-muted',
			'--lafka-color-surface-hover',
			'--lafka-color-surface-active',
			'--lafka-color-surface-overlay',
			'--lafka-color-surface-dark',
			'--lafka-color-surface-cream',
			'--lafka-color-scrim-bottom',
			'--lafka-color-scrim-left',

			// ---- Colour: border (EXCLUDES -focus — var() alias to accent-500) --
			'--lafka-color-border-subtle',
			'--lafka-color-border-default',
			'--lafka-color-border-strong',

			// ---- Colour: semantic ---------------------------------------------
			'--lafka-color-success-500',
			'--lafka-color-success-50',
			'--lafka-color-error-500',
			'--lafka-color-error-50',
			'--lafka-color-warning-500',
			'--lafka-color-warning-50',
			'--lafka-color-info-500',
			'--lafka-color-info-50',

			// ---- Typography: families -----------------------------------------
			'--lafka-font-family-body',
			'--lafka-font-family-display',
			'--lafka-font-family-mono',

			// ---- Typography: size scale (mobile + desktop) --------------------
			'--lafka-font-size-caption',
			'--lafka-font-size-body-sm',
			'--lafka-font-size-body',
			'--lafka-font-size-body-lg',
			'--lafka-font-size-h4',
			'--lafka-font-size-h3',
			'--lafka-font-size-h2',
			'--lafka-font-size-h1',
			'--lafka-font-size-display',
			'--lafka-font-size-body-lg-desk',
			'--lafka-font-size-h4-desk',
			'--lafka-font-size-h3-desk',
			'--lafka-font-size-h2-desk',
			'--lafka-font-size-h1-desk',
			'--lafka-font-size-display-desk',

			// ---- Typography: weight -------------------------------------------
			'--lafka-font-weight-regular',
			'--lafka-font-weight-medium',
			'--lafka-font-weight-semibold',
			'--lafka-font-weight-bold',
			'--lafka-font-weight-display',

			// ---- Typography: line-height (handoff-native names) ---------------
			'--lafka-line-display',
			'--lafka-line-heading',
			'--lafka-line-body',
			'--lafka-line-small',

			// ---- Typography: letter-spacing -----------------------------------
			'--lafka-letter-spacing-tight',
			'--lafka-letter-spacing-snug',
			'--lafka-letter-spacing-normal',
			'--lafka-letter-spacing-wide',

			// ---- Radii ---------------------------------------------------------
			'--lafka-radius-xs',
			'--lafka-radius-sm',
			'--lafka-radius-md',
			'--lafka-radius-lg',
			'--lafka-radius-xl',
			'--lafka-radius-pill',

			// ---- Shadows / elevation ------------------------------------------
			'--lafka-shadow-0',
			'--lafka-shadow-1',
			'--lafka-shadow-2',
			'--lafka-shadow-3',
			'--lafka-shadow-4',
			'--lafka-shadow-focus',

			// ---- Motion (literal tokens, not the var() aliases) ---------------
			'--lafka-motion-duration-fast',
			'--lafka-motion-duration-base',
			'--lafka-motion-duration-slow',
			'--lafka-motion-ease-out',
			'--lafka-motion-ease-in-out',
			'--lafka-duration-instant',
			'--lafka-ease-in',
			'--lafka-ease-spring',
		)
	);
}

/**
 * TML chrome allow-list. Every key a preset lists under `chrome{}` MUST appear
 * here. These are exactly the `get_theme_mod( 'lafka_*' )` design-token reads in
 * styles/dynamic-css.php (the `lafka_legacy_migrate_map()` destinations).
 */
if ( ! defined( 'LAFKA_PRESET_CHROME_WHITELIST' ) ) {
	define(
		'LAFKA_PRESET_CHROME_WHITELIST',
		array(
			// Brand + logo.
			'lafka_accent_color',
			'lafka_brand_color',
			'lafka_logo_background_color',

			// Content colours.
			'lafka_links_color',
			'lafka_links_hover_color',
			'lafka_sidebar_titles_color',
			'lafka_all_buttons_color',
			'lafka_all_buttons_hover_color',
			'lafka_new_label_color',
			'lafka_sale_label_color',
			'lafka_page_title_color',
			'lafka_page_subtitle_color',
			'lafka_custom_page_title_color',
			'lafka_page_title_bckgr_color',
			'lafka_page_title_border_color',
			'lafka_add_to_cart_color',
			'lafka_price_color_in_listings',
			'lafka_price_background_color_in_listings',
			'lafka_fancy_category_title_color',

			// Header / top-bar / collapsible / main-menu / footer chrome.
			'lafka_transparent_header_dark_menu_color',
			'lafka_header_top_bar_color',
			'lafka_header_top_bar_border_color',
			'lafka_top_bar_message_color',
			'lafka_header_services_color',
			'lafka_top_bar_menu_links_color',
			'lafka_top_bar_menu_links_hover_color',
			'lafka_collapsible_bckgr_color',
			'lafka_collapsible_titles_color',
			'lafka_collapsible_titles_border_color',
			'lafka_collapsible_links_color',
			'lafka_main_menu_background_color',
			'lafka_main_menu_links_color',
			'lafka_main_menu_links_hover_color',
			'lafka_main_menu_links_bckgr_hover_color',
			'lafka_main_menu_icons_color',
			'lafka_footer_titles_color',
			'lafka_footer_title_border_color',
			'lafka_footer_copyright_bar_text_color',
			'lafka_footer_menu_links_color',
			'lafka_footer_links_color',
			'lafka_footer_text_color',
			'lafka_footer_copyright_bar_bckgr_color',

			// Composite typography arrays (routed by lafka_preset_default's
			// untyped return — the shape matches dynamic-css.php's defaults).
			'lafka_main_menu_typography',
			'lafka_top_menu_typography',
			'lafka_body_font',
			'lafka_text_logo_typography',
			'lafka_h1_font',
			'lafka_h2_font',
			'lafka_h3_font',
			'lafka_h4_font',
			'lafka_h5_font',
			'lafka_h6_font',

			// Composite background arrays + default title background image.
			'lafka_header_background',
			'lafka_footer_background',
			'lafka_page_title_default_bckgr_image',
		)
	);
}

/**
 * Above-fold subset of LAFKA_PRESET_TOKEN_WHITELIST. Inert this wave; the
 * NX2-04.1 critical.css preset-awareness will read it so first-paint on a
 * non-default preset reflects the preset's key surfaces/type. MUST stay a
 * subset of LAFKA_PRESET_TOKEN_WHITELIST (asserted by PresetSchemaTest).
 */
if ( ! defined( 'LAFKA_PRESET_CRITICAL_KEYS' ) ) {
	define(
		'LAFKA_PRESET_CRITICAL_KEYS',
		array(
			'--lafka-color-surface-page',
			'--lafka-color-surface-raised',
			'--lafka-color-text-primary',
			'--lafka-color-text-secondary',
			'--lafka-color-border-subtle',
			'--lafka-color-accent-600',
			'--lafka-color-accent-700',
			'--lafka-font-family-body',
			'--lafka-font-family-display',
		)
	);
}
