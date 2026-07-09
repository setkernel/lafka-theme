<?php
/**
 * NX2-04 — Customizer-side preset surface: preview payloads + sanitizer.
 *
 * The live preview never does client-side style math: for each preset this
 * builder produces the EXACT three CSS strings the front end would emit with
 * that preset active (PTL, font-faces, dynamic-css) by resolving the slug
 * through the `lafka_active_preset_slug` filter and re-running the real
 * emitters. Operator theme_mods keep winning inside every payload by
 * get_theme_mod() semantics — identical to a real render by construction.
 *
 * Loaded unconditionally (cheap: functions only); the expensive payload
 * build only runs from customize_controls / preview enqueues (Task 4/5).
 *
 * @package Lafka\Theme\Presets
 * @since   7.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_sanitize_preset_slug' ) ) {
	/**
	 * Sanitize a preset slug against the registry (unknown → 'peppery').
	 *
	 * @param mixed $slug Raw setting value.
	 * @return string
	 */
	function lafka_sanitize_preset_slug( $slug ): string {
		$slug = sanitize_key( (string) $slug );
		if ( function_exists( 'lafka_presets' ) && array_key_exists( $slug, lafka_presets()->all() ) ) {
			return $slug;
		}
		return 'peppery';
	}
}

if ( ! function_exists( 'lafka_preset_preview_payloads' ) ) {
	/**
	 * label/description/dark + the three swap-ready CSS strings, per preset.
	 *
	 * @return array<string, array{label:string,description:string,dark:bool,ptl:string,fonts:string,dynamicCss:string}>
	 */
	function lafka_preset_preview_payloads(): array {
		$payloads = array();

		foreach ( lafka_presets()->all() as $slug => $preset ) {
			// Force every slug-resolving reader onto THIS preset for the
			// duration of one build. Priority 999 so it wins over any
			// third-party slug filter. No reset() needed between iterations:
			// Lafka_Presets::active() re-resolves the slug on EVERY call via
			// lafka_get_active_preset_slug() (which applies this filter) —
			// the registry caches only the discovered preset SET, never the
			// active resolution.
			$force = static function () use ( $slug ) {
				return $slug;
			};
			add_filter( 'lafka_active_preset_slug', $force, 999 );

			$payloads[ $slug ] = array(
				'label'       => $preset->label(),
				'description' => $preset->description(),
				'dark'        => $preset->is_dark(),
				'ptl'         => lafka_preset_ptl_css( $preset ),
				'fonts'       => lafka_preset_fonts_css_for_preview( $preset ),
				'dynamicCss'  => lafka_dynamic_css_build(),
			);

			remove_filter( 'lafka_active_preset_slug', $force, 999 );
		}

		return $payloads;
	}
}

if ( ! function_exists( 'lafka_preset_fonts_css_for_preview' ) ) {
	/**
	 * The @font-face block for ONE preset. Thin adapter around the NX2-03
	 * fonts builder — lafka_preset_font_face_css() takes the preset value
	 * object explicitly (it does not resolve the active slug internally), so
	 * the payload loop passes each preset straight through. Returns '' when
	 * the builder is unavailable (isolated loads).
	 *
	 * @param Lafka_Preset $preset The preset whose pool @font-face CSS to build.
	 * @return string
	 */
	function lafka_preset_fonts_css_for_preview( Lafka_Preset $preset ): string {
		if ( ! function_exists( 'lafka_preset_font_face_css' ) ) {
			return '';
		}
		return (string) lafka_preset_font_face_css( $preset );
	}
}
