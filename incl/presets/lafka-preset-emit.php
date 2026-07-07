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
		return array_key_exists( $key, $chrome )
			? lafka_preset_sanitize_chrome_value( $chrome[ $key ] )
			: $fallback;
	}
}

if ( ! function_exists( 'lafka_preset_sanitize_chrome_value' ) ) {
	/**
	 * Defence-in-depth sanitiser for a chrome (theme_mod-default) value — the
	 * counterpart of lafka_preset_css_value() for the TML layer. Chrome values
	 * flow into dynamic-css.php's :root{} block, where esc_attr() alone cannot
	 * stop a `red;}body{display:none` breakout from a hostile 3rd-party preset
	 * registered via the `lafka_presets` filter. Strings are stripped of
	 * rule-breaking characters; two composite shapes survive intact:
	 *   - arrays (composite typography / background) sanitise recursively;
	 *   - a JSON-object STRING (the legacy `style` sub-field) is decoded, its
	 *     leaves stripped, and re-encoded so its legitimate braces remain.
	 * Clean first-party values pass through byte-identical (parity-safe).
	 *
	 * @param mixed $value Chrome value supplied by a preset.
	 * @return mixed
	 */
	function lafka_preset_sanitize_chrome_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'lafka_preset_sanitize_chrome_value', $value );
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$trimmed = trim( $value );
		if ( '' !== $trimmed && '{' === $trimmed[0] ) {
			$decoded = json_decode( $trimmed, true );
			if ( is_array( $decoded ) ) {
				return (string) wp_json_encode( array_map( 'lafka_preset_sanitize_chrome_value', $decoded ) );
			}
		}
		return lafka_preset_css_value( $value );
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

		$is_dark = $preset->is_dark();

		if ( $is_dark ) {
			// accent-text is FORBIDDEN in preset tokens (operator-derived), so the
			// emitter supplies the dark value. Static fallback = the raw accent
			// (readable on a dark surface) for browsers without color-mix; the base
			// derivation DARKENS accent-text, wrong on dark, so the @supports block
			// below LIGHTENS it toward white. See PRESET_ENGINE.md §6.
			$decls .= '--lafka-color-accent-text:var(--lafka-color-accent-500);';
		}

		if ( '' === $decls ) {
			return '';
		}

		$selector = $is_dark ? ':root[data-theme="dark"]' : ':root';
		$css      = $selector . '{' . $decls . '}';

		if ( $is_dark ) {
			$css .= '@supports (color: color-mix(in srgb, red 50%, white)){'
				. ':root[data-theme="dark"]{--lafka-color-accent-text:color-mix(in srgb, var(--lafka-color-accent-500) 80%, #fff);}}';
		}

		return $css;
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

if ( ! function_exists( 'lafka_font_pool' ) ) {
	/**
	 * The curated font pool (slug => definition), filterable so a child theme or
	 * 3rd-party preset bundle can register additional self-hosted OFL families.
	 * See incl/presets/lafka-preset-fonts.php for the shape. NX2-03.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function lafka_font_pool(): array {
		$pool = defined( 'LAFKA_FONT_POOL' ) ? LAFKA_FONT_POOL : array();
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter the registered font pool.
			 *
			 * @param array<string,array<string,mixed>> $pool slug => definition.
			 */
			$pool = (array) apply_filters( 'lafka_font_pool', $pool );
		}
		return $pool;
	}
}

if ( ! function_exists( 'lafka_font_pool_slug' ) ) {
	/**
	 * Resolve a family NAME (as written in a preset's fonts{} block, e.g.
	 * "Space Grotesk") to its pool slug, or '' when the family is not pooled.
	 * Accepts either the display family name or the slug itself.
	 *
	 * @param string $family
	 */
	function lafka_font_pool_slug( string $family ): string {
		$family = trim( $family );
		if ( '' === $family ) {
			return '';
		}
		$pool = lafka_font_pool();
		foreach ( $pool as $slug => $entry ) {
			if ( isset( $entry['family'] ) && 0 === strcasecmp( (string) $entry['family'], $family ) ) {
				return (string) $slug;
			}
		}
		$key = function_exists( 'sanitize_key' ) ? sanitize_key( $family ) : strtolower( $family );
		return isset( $pool[ $key ] ) ? $key : '';
	}
}

if ( ! function_exists( 'lafka_preset_font_selection' ) ) {
	/**
	 * The active preset's resolved body + display font families. Returns a map
	 * keyed by role, each entry:
	 *   [ 'role' => 'body'|'display', 'family' => 'Rubik', 'source' => 'base'|'pool', 'slug' => '<pool slug>|'' ]
	 * These are the ONLY two families the active preset loads (peppery => Rubik +
	 * Fraunces). NX2-03.
	 *
	 * @param Lafka_Preset $preset
	 * @return array<string,array<string,string>>
	 */
	function lafka_preset_font_selection( Lafka_Preset $preset ): array {
		$fonts = $preset->fonts();
		$out   = array();
		foreach ( array( 'body', 'display' ) as $role ) {
			$decl   = isset( $fonts[ $role ] ) && is_array( $fonts[ $role ] ) ? $fonts[ $role ] : array();
			$family = isset( $decl['family'] ) ? (string) $decl['family'] : '';
			$source = isset( $decl['source'] ) ? (string) $decl['source'] : 'base';
			$out[ $role ] = array(
				'role'   => $role,
				'family' => $family,
				'source' => $source,
				'slug'   => 'pool' === $source ? lafka_font_pool_slug( $family ) : '',
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'lafka_font_face_css_for_slug' ) ) {
	/**
	 * @font-face CSS for ONE pool family (every weight × subset it ships). Base
	 * families return '' — Rubik/Fraunces are already declared in the static CSS,
	 * so re-emitting them would be a duplicate (and would break Peppery's
	 * byte/pixel identity). NX2-03.
	 *
	 * @param string $slug
	 * @return string
	 */
	function lafka_font_face_css_for_slug( string $slug ): string {
		$pool = lafka_font_pool();
		if ( ! isset( $pool[ $slug ] ) ) {
			return '';
		}
		$entry = $pool[ $slug ];
		$source = isset( $entry['source'] ) ? (string) $entry['source'] : 'pool';
		if ( 'pool' !== $source ) {
			return '';
		}

		$dir_uri = function_exists( 'get_template_directory_uri' )
			? get_template_directory_uri()
			: '..'; // isolated unit tests: relative marker, structure is what's asserted.
		$base    = $dir_uri . '/assets/fonts/' . ( isset( $entry['dir'] ) ? $entry['dir'] : $slug ) . '/';
		$family  = isset( $entry['family'] ) ? (string) $entry['family'] : $slug;
		$subsets = array(
			'latin'     => defined( 'LAFKA_FONT_RANGE_LATIN' ) ? LAFKA_FONT_RANGE_LATIN : '',
			'latin-ext' => defined( 'LAFKA_FONT_RANGE_LATIN_EXT' ) ? LAFKA_FONT_RANGE_LATIN_EXT : '',
		);

		$css = '';
		foreach ( (array) ( isset( $entry['weights'] ) ? $entry['weights'] : array() ) as $weight => $files ) {
			foreach ( $subsets as $subset => $range ) {
				if ( empty( $files[ $subset ] ) ) {
					continue;
				}
				$css .= '@font-face{'
					. 'font-family:"' . $family . '";'
					. 'font-style:normal;'
					. 'font-display:swap;'
					. 'font-weight:' . (int) $weight . ';'
					. 'src:url(' . $base . $files[ $subset ] . ') format("woff2");'
					. ( '' !== $range ? 'unicode-range:' . $range . ';' : '' )
					. '}';
			}
		}
		return $css;
	}
}

if ( ! function_exists( 'lafka_preset_font_face_css' ) ) {
	/**
	 * @font-face CSS for the active preset's POOL body + display families
	 * (deduped when both roles share a family). Peppery — both source:"base" —
	 * yields '', so the engine emits nothing and the goldens stay byte-identical.
	 * Only the ACTIVE preset's (at most two) pool families are ever emitted:
	 * conditional per-preset enqueue. NX2-03.
	 *
	 * @param Lafka_Preset $preset
	 * @return string
	 */
	function lafka_preset_font_face_css( Lafka_Preset $preset ): string {
		$slugs = array();
		foreach ( lafka_preset_font_selection( $preset ) as $sel ) {
			if ( 'pool' === $sel['source'] && '' !== $sel['slug'] ) {
				$slugs[ $sel['slug'] ] = true;
			}
		}
		$css = '';
		foreach ( array_keys( $slugs ) as $slug ) {
			$css .= lafka_font_face_css_for_slug( $slug );
		}
		return $css;
	}
}

if ( ! function_exists( 'lafka_preset_register_fonts' ) ) {
	/**
	 * Register the inline-only `lafka-preset-fonts` handle and attach the active
	 * preset's pool @font-face declarations. `src=false` → NO extra HTTP request
	 * for the CSS; the browser fetches ONLY the (at most two) woff2 families the
	 * preset references. Peppery (base fonts, already in static CSS) attaches no
	 * inline, so this adds zero bytes for the default preset — the always-on CSS
	 * budget is unchanged (pool fonts are strictly conditional). Called from
	 * lafka_enqueue_scripts_and_styles() alongside the PTL registration. NX2-03.
	 */
	function lafka_preset_register_fonts(): void {
		if ( ! function_exists( 'wp_register_style' ) ) {
			return;
		}
		$ver = ( function_exists( 'wp_get_theme' ) && function_exists( 'get_template' ) )
			? wp_get_theme( get_template() )->get( 'Version' )
			: false;

		wp_register_style( 'lafka-preset-fonts', false, array(), $ver );

		$css = lafka_preset_font_face_css( lafka_active_preset() );
		if ( '' !== $css && function_exists( 'wp_add_inline_style' ) ) {
			wp_add_inline_style( 'lafka-preset-fonts', $css );
		}
	}
}

if ( ! function_exists( 'lafka_preset_display_preload_href' ) ) {
	/**
	 * The URL of the active preset's POOL display font to `<link rel=preload>`
	 * (the heaviest shipped weight, latin subset — the above-fold heading face,
	 * the same intent as header.php's static Fraunces preload). Returns '' when
	 * the display font is source:"base" (Peppery included) — the static Fraunces
	 * preload already covers it, so the head stays byte-identical. The CALLER
	 * escapes with esc_url() in the template. NX2-03.
	 *
	 * @return string A font URL, or '' when there is nothing new to preload.
	 */
	function lafka_preset_display_preload_href(): string {
		$sel = lafka_preset_font_selection( lafka_active_preset() );
		if ( ! isset( $sel['display'] ) || 'pool' !== $sel['display']['source'] || '' === $sel['display']['slug'] ) {
			return '';
		}
		$pool = lafka_font_pool();
		$slug = $sel['display']['slug'];
		if ( empty( $pool[ $slug ]['weights'] ) ) {
			return '';
		}
		$weights  = $pool[ $slug ]['weights'];
		$heaviest = max( array_map( 'intval', array_keys( $weights ) ) );
		if ( empty( $weights[ $heaviest ]['latin'] ) ) {
			return '';
		}
		$dir_uri = function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : '..';
		return $dir_uri . '/assets/fonts/' . ( isset( $pool[ $slug ]['dir'] ) ? $pool[ $slug ]['dir'] : $slug ) . '/' . $weights[ $heaviest ]['latin'];
	}
}

if ( ! function_exists( 'lafka_preset_language_attributes' ) ) {
	/**
	 * Stamp preset markers on <html>. header.php emits
	 * `<html <?php language_attributes(); ?>>`.
	 *
	 *   - `data-lafka-preset="<slug>"` for any NON-default active preset, so
	 *     preset-scoped CSS can target it (NX2-08 uses it to lift the fixed
	 *     WooCommerce breadcrumb #767676 to the preset's muted ink on off-white
	 *     light surfaces where it dips below AA). Peppery is the zero-state — it
	 *     carries NO marker, so its markup + the 30 goldens stay identical (mirrors
	 *     Peppery's empty PTL).
	 *   - `data-theme="dark"` for a dark active preset, activating the
	 *     `:root[data-theme="dark"]` scaffold + `color-scheme` and the dark PTL. §6.
	 *
	 * @param string $output The language_attributes string.
	 * @return string
	 */
	function lafka_preset_language_attributes( $output ) {
		if ( ! function_exists( 'lafka_active_preset' ) ) {
			return $output;
		}
		$preset = lafka_active_preset();
		$slug   = $preset->slug();
		if (
			'' !== $slug && 'peppery' !== $slug
			&& false === strpos( (string) $output, 'data-lafka-preset' )
		) {
			$attr    = function_exists( 'esc_attr' ) ? esc_attr( $slug ) : $slug;
			$output .= ' data-lafka-preset="' . $attr . '"';
		}
		if (
			$preset->is_dark()
			&& false === strpos( (string) $output, 'data-theme' )
		) {
			$output .= ' data-theme="dark"';
		}
		return $output;
	}
}

if ( ! function_exists( 'lafka_preset_category_emoji' ) ) {
	/**
	 * Feed the active preset's `category_emoji` map into the `lafka_category_emoji`
	 * filter (partials/home-categories.php:102), so each preset can theme the
	 * "What are you craving?" category-tile glyphs. The map is keyed by product_cat
	 * term slug (e.g. pizzas/sides/salads/drinks); the resolved glyph is echoed as
	 * escaped text content downstream (esc_html) — a trusted preset literal.
	 *
	 * A preset with an EMPTY map (Peppery, Midnight) returns the incoming $emoji
	 * untouched, so those presets' markup + the 30 goldens stay byte-identical.
	 * Resolution mirrors the partial's own map (home-categories.php:96-100): an
	 * exact slug hit wins, else a fuzzy fallback where the term slug CONTAINS a map
	 * key or the term name contains it. An unmatched term keeps the passed glyph.
	 *
	 * @param string $emoji The glyph the partial resolved from its own default map.
	 * @param object $term  The product_cat term (WP_Term; reads ->slug and ->name).
	 * @return string
	 */
	function lafka_preset_category_emoji( $emoji, $term ) {
		if ( ! function_exists( 'lafka_active_preset' ) ) {
			return $emoji;
		}
		$map = lafka_active_preset()->category_emoji();
		if ( empty( $map ) || ! is_object( $term ) || ! isset( $term->slug ) ) {
			return $emoji;
		}
		$slug = strtolower( (string) $term->slug );
		if ( isset( $map[ $slug ] ) ) {
			return (string) $map[ $slug ];
		}
		$name = isset( $term->name ) ? (string) $term->name : '';
		foreach ( $map as $needle => $glyph ) {
			$needle = strtolower( (string) $needle );
			if ( '' !== $needle && ( false !== strpos( $slug, $needle ) || false !== stripos( $name, $needle ) ) ) {
				return (string) $glyph;
			}
		}
		return $emoji;
	}
}

// Include-time hook (matches dynamic-css.php's pattern); guarded so the isolated
// preset unit tests that don't shim add_filter don't fatal.
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'language_attributes', 'lafka_preset_language_attributes' );
	add_filter( 'lafka_category_emoji', 'lafka_preset_category_emoji', 10, 2 );
}
