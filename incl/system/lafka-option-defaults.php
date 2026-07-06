<?php
/**
 * NX1-02 (theme 7.0): slim successor to the retired Options-Framework defaults.
 *
 * The legacy Options Framework registered ~234 field defaults and exposed them
 * through `lafka_get_default_values()` — the create-only source the theme's
 * (now-removed) admin seeder AND the plugin's activation seeder / the shared
 * `Lafka_Options::get()` defaults layer both read. NX1-02 retires that registry:
 * the theme's OWN appearance keys now default INLINE at each `get_theme_mod()`
 * reader (migrated by the NX1-02 slices), so this file provides the ONLY defaults
 * the retired registry still owed to code that survives it — the plugin-owned
 * feature flags and functional-shared keys that stay in the `lafka` array
 * (invariant 1) and resolve through the `lafka_get_option()` shim.
 *
 * Every value here is the EXACT Options-Framework `std` it replaces, so both a
 * fresh plugin activation (which seeds the `lafka` array from this array) and the
 * `Lafka_Options::get()` defaults-fallback stay byte-identical to a
 * pre-retirement install (e.g. product_addons defaults ON, foodmenu currency is
 * still "$"). The theme's dropped appearance keys are deliberately ABSENT: a
 * fresh install no longer seeds the legacy appearance array — it renders the
 * shipped pixels from the theme_mod inline defaults (invariant 3, and the
 * NX1-02 accept criterion "fresh install never writes the legacy array").
 *
 * @package Lafka
 * @since   7.0.0 (NX1-02 framework retirement)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_get_default_values' ) ) {
	/**
	 * Default values for the plugin-owned / functional-shared keys that live in
	 * the `lafka` option array.
	 *
	 * Registers them with the shared Lafka_Options helper (when the plugin is
	 * active) so `Lafka_Options::get()` / `::is_enabled()` resolve a feature flag
	 * through the same defaults layer the retired registry used to feed. Returns
	 * the same array so the plugin's create-only activation seeder can persist it.
	 *
	 * @return array<string,mixed> Map of `lafka` sub-key => default value.
	 */
	function lafka_get_default_values() {
		$defaults = array(
			// Gated feature-module flags (NX1-01 registry / is_lafka_*() gates).
			// product_addons ships ON; the rest ship OFF ('' / not 'enabled').
			'product_addons'                => 'enabled',
			'shipping_areas'                => '',
			'order_hours'                   => '',
			'kitchen_display'               => '',
			// NX1-08b browser push for new orders (checkbox, OFF).
			'order_notifications'           => 0,

			// Functional-shared keys read by BOTH repos through the shim; they
			// stay in the `lafka` array (never forked into a theme_mod).
			'google_maps_api_key'           => '',
			'foodmenu_currency'             => '$',
			'foodmenu_currency_position'    => 'left',
			'category_description_position' => '',
			'custom_product_popup_link'     => '',
			'custom_product_popup_content'  => '',

			// Theme-read NAP site value (operator-configurable phone in the
			// trust strip); kept '' so a fresh install renders no literal.
			'top_bar_message_phone'         => '',
		);

		// Promo-tooltip feature (plugin; three independent tooltips). Only
		// `_position` carries a non-empty std; the copy mirrors the registry.
		for ( $i = 1; $i <= 3; $i++ ) {
			$defaults[ 'promo_tooltip_' . $i . '_text' ]            = '';
			$defaults[ 'promo_tooltip_' . $i . '_trigger_text' ]    = '';
			$defaults[ 'promo_tooltip_' . $i . '_position' ]        = 'above_price';
			$defaults[ 'promo_tooltip_' . $i . '_show_in_listing' ] = 0;
			$defaults[ 'promo_tooltip_' . $i . '_content' ]         = '';
		}

		/**
		 * Filter the plugin-owned `lafka` option defaults.
		 *
		 * @since 7.0.0
		 * @param array<string,mixed> $defaults Default values keyed by `lafka` sub-key.
		 */
		if ( function_exists( 'apply_filters' ) ) {
			$defaults = apply_filters( 'lafka_default_option_values', $defaults );
		}

		// Register with the shared helper so Lafka_Options::get() falls back to
		// these defaults exactly as it did against the retired registry.
		if ( class_exists( 'Lafka_Options' ) ) {
			Lafka_Options::set_defaults( $defaults );
		}

		return $defaults;
	}
}
