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

if ( ! function_exists( 'lafka_preset_css_value' ) ) {
	/**
	 * Defence-in-depth sanitiser for a PTL token value. Presets are
	 * theme-shipped JSON (no operator input reaches here), but a 3rd-party
	 * preset registered via the `lafka_presets` filter could carry hostile
	 * strings — strip the characters that could break out of the inline
	 * `<style>` declaration/rule or close the tag.
	 *
	 * @param string $value
	 */
	function lafka_preset_css_value( string $value ): string {
		$value = (string) preg_replace( '/[<>{};]/', '', $value );
		return trim( $value );
	}
}

if ( ! function_exists( 'lafka_preset_ptl_css' ) ) {
	/**
	 * Build the Preset-Token Layer CSS for a preset: a single `:root{}` (light)
	 * or `:root[data-theme="dark"]{}` (dark) block carrying only the preset's
	 * whitelisted `--lafka-*` token overrides. Out-of-whitelist / forbidden keys
	 * are dropped (WP_DEBUG log). Peppery (empty tokens) yields '' — the engine
	 * emits nothing, so dynamic-css stays byte-identical.
	 *
	 * @param Lafka_Preset $preset
	 * @return string CSS (may be empty).
	 */
	function lafka_preset_ptl_css( Lafka_Preset $preset ): string {
		$whitelist = defined( 'LAFKA_PRESET_TOKEN_WHITELIST' ) ? LAFKA_PRESET_TOKEN_WHITELIST : array();

		$decls = '';
		foreach ( $preset->tokens() as $key => $value ) {
			if ( ! in_array( $key, $whitelist, true ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
					error_log( "[lafka] preset '{$preset->slug()}' dropped non-whitelisted token '{$key}'" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
				}
				continue;
			}
			$decls .= $key . ':' . lafka_preset_css_value( (string) $value ) . ';';
		}

		if ( '' === $decls ) {
			return '';
		}

		$selector = $preset->is_dark() ? ':root[data-theme="dark"]' : ':root';
		return $selector . '{' . $decls . '}';
	}
}

if ( ! function_exists( 'lafka_preset_register_ptl' ) ) {
	/**
	 * Register the inline-only `lafka-preset` handle and attach the active
	 * preset's PTL. `src=false` means NO extra HTTP request; `deps=['lafka-tokens']`
	 * is a dependency EDGE (not print-order luck) forcing the PTL to print AFTER
	 * the base tokens. Called from lafka_enqueue_scripts_and_styles() right before
	 * lafka-style is enqueued (lafka-style then depends on lafka-preset so the
	 * operator's dynamic-css inline still prints last). See PRESET_ENGINE.md §4.
	 */
	function lafka_preset_register_ptl(): void {
		if ( ! function_exists( 'wp_register_style' ) ) {
			return;
		}
		$ver = ( function_exists( 'wp_get_theme' ) && function_exists( 'get_template' ) )
			? wp_get_theme( get_template() )->get( 'Version' )
			: false;

		wp_register_style( 'lafka-preset', false, array( 'lafka-tokens' ), $ver );

		$css = lafka_preset_ptl_css( lafka_active_preset() );
		if ( '' !== $css && function_exists( 'wp_add_inline_style' ) ) {
			wp_add_inline_style( 'lafka-preset', $css );
		}
	}
}
