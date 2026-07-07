<?php
/**
 * NX2-01 preset engine — public surface + emission wiring.
 *
 * Public helpers (all `function_exists`-guarded, `lafka_`-prefixed):
 *   - lafka_presets()               -> Lafka_Presets registry.
 *   - lafka_active_preset()         -> Lafka_Preset for the active slug.
 *   - lafka_get_active_preset_slug()-> stored slug (theme_mod, default peppery).
 *   - lafka_preset_default($k,$fb)  -> active preset's chrome default for $k, else $fb.
 *
 * See docs/PRESET_ENGINE.md §2, §4, §5, §6.
 *
 * @package Lafka
 * @since   7.1.0 (NX2-01)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_presets' ) ) {
	/**
	 * The preset registry (discovery + cache + active()).
	 */
	function lafka_presets(): Lafka_Presets {
		return Lafka_Presets::instance();
	}
}

if ( ! function_exists( 'lafka_get_active_preset_slug' ) ) {
	/**
	 * The stored active-preset slug. Per-stylesheet theme_mod (child-active-safe,
	 * the NX1-02 trap), default `peppery`. Filterable via `lafka_active_preset_slug`.
	 */
	function lafka_get_active_preset_slug(): string {
		$slug = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'lafka_active_preset', 'peppery' ) : 'peppery';
		if ( ! is_string( $slug ) || '' === $slug ) {
			$slug = 'peppery';
		}
		if ( function_exists( 'sanitize_key' ) ) {
			$slug = sanitize_key( $slug );
		}
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter the resolved active-preset slug.
			 *
			 * @param string $slug Sanitized slug.
			 */
			$slug = (string) apply_filters( 'lafka_active_preset_slug', $slug );
		}
		return '' !== $slug ? $slug : 'peppery';
	}
}

if ( ! function_exists( 'lafka_active_preset' ) ) {
	/**
	 * The active preset value object (falls back to peppery).
	 */
	function lafka_active_preset(): Lafka_Preset {
		return lafka_presets()->active();
	}
}

if ( ! function_exists( 'lafka_preset_default' ) ) {
	/**
	 * The active preset's chrome default for a theme_mod key, else the caller's
	 * literal fallback. UNTYPED return so it routes composite typography arrays
	 * (e.g. `lafka_h1_font`, `lafka_body_font`), not just scalars.
	 *
	 * Wrapped as the DEFAULT argument of the `get_theme_mod()` reads in
	 * styles/dynamic-css.php, so an operator-set theme_mod always wins by
	 * get_theme_mod() semantics — the preset only supplies the unset default.
	 *
	 * @param string $key      A `lafka_*` chrome theme_mod key.
	 * @param mixed  $fallback The shipped literal default at the reader.
	 * @return mixed
	 */
	function lafka_preset_default( $key, $fallback ) {
		if ( ! function_exists( 'lafka_active_preset' ) ) {
			return $fallback;
		}
		$chrome = lafka_active_preset()->chrome();
		return array_key_exists( $key, $chrome ) ? $chrome[ $key ] : $fallback;
	}
}
