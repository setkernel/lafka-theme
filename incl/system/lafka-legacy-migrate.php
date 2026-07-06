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
 *  - It does not register the one-time upgrade trigger. Wiring the run onto an
 *    upgrade hook is the NX1-02 Retire phase's job; this file only provides the
 *    map + copy so a slice's readers have a migrated home to read from, and so
 *    the copy is unit-covered (LegacyOptionMigrationTest) ahead of that wiring.
 *
 * @package Lafka
 * @since   6.22.0 (NX1-02.logos-brand-pilot)
 */

defined( 'ABSPATH' ) || exit;

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
