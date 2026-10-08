<?php
/**
 * Design-token values for PHP, and the editor presets that follow the active
 * preset.
 *
 * `styles/lafka-tokens.css` and the preset engine are the one source of the
 * design tokens. PHP reads the shipped values from `token-defaults.json` and the
 * block editor reads `theme.json`; both are GENERATED from that source by
 * `npm run build` (scripts/build-theme-json.mjs) and gated by
 * `npm run check-theme-json`. Nothing in PHP spells a design colour.
 *
 *   - lafka_token_default( $token )  the shipped value of a token.
 *   - lafka_color_setting( $mod, $token )  operator theme_mod, else the active
 *     preset's chrome default, else the token's shipped value.
 *   - lafka_theme_json_overlay()  lays the active preset's tokens and the
 *     operator's accent/brand over the generated editor presets, so the
 *     editor palette, font sizes and families match the front end.
 *
 * See docs/DESIGN_SYSTEM.md (Single source).
 *
 * @package Lafka
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_token_defaults' ) ) {

	/**
	 * The generated token values: `light` (the base :root block) and `dark`
	 * (the dark scaffold's overrides), each token name => value.
	 *
	 * @since 7.4.0
	 *
	 * @return array<string,array<string,string>>
	 */
	function lafka_token_defaults(): array {
		static $data = null;
		if ( null === $data ) {
			$data = array(
				'light' => array(),
				'dark'  => array(),
			);
			$json = lafka_read_file( get_template_directory() . '/incl/presets/token-defaults.json' );
			$read = is_string( $json ) ? json_decode( $json, true ) : null;
			if ( is_array( $read ) ) {
				$data['light'] = isset( $read['light'] ) && is_array( $read['light'] ) ? $read['light'] : array();
				$data['dark']  = isset( $read['dark'] ) && is_array( $read['dark'] ) ? $read['dark'] : array();
			}
		}
		return $data;
	}
}

if ( ! function_exists( 'lafka_token_default' ) ) {

	/**
	 * The shipped (base light) value of a design token.
	 *
	 * @since 7.4.0
	 *
	 * @param string $token    Custom property name, e.g. `--lafka-color-accent-500`.
	 * @param string $fallback Returned when the token has no literal value.
	 * @return string
	 */
	function lafka_token_default( string $token, string $fallback = '' ): string {
		$defaults = lafka_token_defaults();
		return isset( $defaults['light'][ $token ] ) ? (string) $defaults['light'][ $token ] : $fallback;
	}
}

if ( ! function_exists( 'lafka_color_setting' ) ) {

	/**
	 * A colour setting resolved the way the operator layer resolves it: the
	 * stored theme_mod wins, then the active preset's chrome default, then the
	 * token's shipped value.
	 *
	 * @since 7.4.0
	 *
	 * @param string $mod   `lafka_*` theme_mod key.
	 * @param string $token Token whose shipped value is the last default.
	 * @return string
	 */
	function lafka_color_setting( string $mod, string $token ): string {
		$default = lafka_token_default( $token );
		if ( function_exists( 'lafka_preset_default' ) ) {
			$default = lafka_preset_default( $mod, $default );
		}
		return (string) get_theme_mod( $mod, $default );
	}
}

if ( ! function_exists( 'lafka_theme_json_map' ) ) {

	/**
	 * The editor-preset map (slug => token per section), the same file the
	 * generator reads.
	 *
	 * @since 7.4.0
	 *
	 * @return array<string,mixed>
	 */
	function lafka_theme_json_map(): array {
		static $map = null;
		if ( null === $map ) {
			$json = lafka_read_file( get_template_directory() . '/incl/presets/theme-json-map.json' );
			$read = is_string( $json ) ? json_decode( $json, true ) : null;
			$map  = is_array( $read ) ? $read : array();
		}
		return $map;
	}
}

if ( ! function_exists( 'lafka_active_token_values' ) ) {

	/**
	 * Every literal token value the active design renders: the shipped base,
	 * the dark scaffold under a dark preset, the preset's own tokens, then the
	 * operator-fed accent and brand.
	 *
	 * @since 7.4.0
	 *
	 * @return array<string,string>
	 */
	function lafka_active_token_values(): array {
		$defaults = lafka_token_defaults();
		$values   = $defaults['light'];

		if ( function_exists( 'lafka_active_preset' ) ) {
			$preset = lafka_active_preset();
			if ( $preset->is_dark() ) {
				$values = array_merge( $values, $defaults['dark'] );
			}
			foreach ( $preset->tokens() as $name => $value ) {
				if ( is_scalar( $value ) ) {
					$values[ $name ] = function_exists( 'lafka_preset_css_value' ) ? lafka_preset_css_value( (string) $value ) : (string) $value;
				}
			}
		}

		$values['--lafka-color-accent-500'] = lafka_color_setting( 'lafka_accent_color', '--lafka-color-accent-500' );
		$values['--lafka-color-brand-500']  = lafka_color_setting( 'lafka_brand_color', '--lafka-color-brand-500' );

		return $values;
	}
}

if ( ! function_exists( 'lafka_theme_json_overlay' ) ) {

	/**
	 * Lay the active design over the generated editor presets. The generated
	 * theme.json carries the default preset's values; this makes every other
	 * preset, and an operator's accent or brand, reach the block editor's
	 * palette, font sizes and font families (and the `--wp--preset--*`
	 * variables the front end prints).
	 *
	 * @since 7.4.0
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme-layer data.
	 * @return WP_Theme_JSON_Data
	 */
	function lafka_theme_json_overlay( $theme_json ) {
		$map = lafka_theme_json_map();
		if ( empty( $map ) || ! is_object( $theme_json ) || ! method_exists( $theme_json, 'get_data' ) ) {
			return $theme_json;
		}

		$data   = $theme_json->get_data();
		$values = lafka_active_token_values();

		$sections = array(
			'palette'      => array( array( 'settings', 'color', 'palette' ), 'color' ),
			'fontSizes'    => array( array( 'settings', 'typography', 'fontSizes' ), 'size' ),
			'fontFamilies' => array( array( 'settings', 'typography', 'fontFamilies' ), 'fontFamily' ),
		);

		$update = array(
			'version'  => isset( $data['version'] ) ? $data['version'] : 3,
			'settings' => array(),
		);
		foreach ( $sections as $key => list( $path, $field ) ) {
			if ( empty( $map[ $key ] ) ) {
				continue;
			}
			$current = $data;
			foreach ( $path as $step ) {
				$current = isset( $current[ $step ] ) ? $current[ $step ] : array();
			}
			// Theme-layer presets are keyed by origin (`theme`) once WordPress has
			// merged them; a bare list is accepted too.
			$origin = is_array( $current ) && isset( $current['theme'] ) ? 'theme' : '';
			$list   = '' !== $origin ? $current['theme'] : $current;
			if ( ! is_array( $list ) || empty( $list ) ) {
				continue;
			}
			foreach ( $list as $i => $entry ) {
				$slug = isset( $entry['slug'] ) ? (string) $entry['slug'] : '';
				if ( '' === $slug || empty( $map[ $key ][ $slug ]['token'] ) ) {
					continue;
				}
				$token = $map[ $key ][ $slug ]['token'];
				if ( isset( $values[ $token ] ) ) {
					$list[ $i ][ $field ] = $values[ $token ];
				}
			}
			$update['settings'][ $path[1] ][ $path[2] ] = '' !== $origin ? array( $origin => $list ) : $list;
		}

		return $theme_json->update_with( $update );
	}
	add_filter( 'wp_theme_json_data_theme', 'lafka_theme_json_overlay' );
}

if ( ! function_exists( 'lafka_theme_json_flush_cache' ) ) {

	/**
	 * Drop WordPress's cached theme.json data when the stored design changes
	 * (active preset, accent, brand), so the editor presets are rebuilt with
	 * the new values, including behind a persistent object cache.
	 *
	 * @since 7.4.0
	 *
	 * @param string $option Option name that was just updated.
	 * @return void
	 */
	function lafka_theme_json_flush_cache( $option = '' ) {
		if ( ! is_string( $option ) || 0 !== strpos( $option, 'theme_mods_' ) ) {
			return;
		}
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) && is_callable( array( 'WP_Theme_JSON_Resolver', 'clean_cached_data' ) ) ) {
			WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}
	add_action( 'updated_option', 'lafka_theme_json_flush_cache' );
	add_action( 'added_option', 'lafka_theme_json_flush_cache' );
}
