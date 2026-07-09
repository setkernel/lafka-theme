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
		// Memoized: a single preview load calls this twice (localize + font
		// preload). Payloads are deterministic within one request, so the
		// second call returns the built set free of charge.
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$payloads = array();

		// In a live customize session WP has previewed every registered
		// setting, pinning each flat theme_mod read to the value captured
		// with the CURRENT preset active — which would collapse all ten
		// dynamicCss payloads into clones of the active preset's. Suspend
		// those pins for the build; posted (changeset) values keep winning.
		$suspension = lafka_preset_payloads_unpin_theme_mods();

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

		lafka_preset_payloads_repin_theme_mods( $suspension );

		$cache = $payloads;

		return $cache;
	}
}

if ( ! function_exists( 'lafka_preset_payloads_unpin_theme_mods' ) ) {
	/**
	 * Suspend the customize-preview theme_mod pins for a forced-slug payload build.
	 *
	 * In a customize session (preview iframe or controls request) WordPress
	 * previews every registered setting: each FLAT theme_mod setting gets a
	 * `theme_mod_{$id}` filter (WP_Customize_Setting::_preview_filter) that
	 * REPLACES the incoming value with either the operator's posted value or
	 * the value captured when preview() ran. That capture resolved preset
	 * defaults against the preset active at load, so under the pins a
	 * forced-slug lafka_dynamic_css_build() reads the ACTIVE preset's chrome
	 * for every registered key — all ten payloads collapse into clones of the
	 * active one. (PTL/fonts take the preset object explicitly and never
	 * suffered; unit/CLI builds have no manager, which is why only a real
	 * customize session exposed this.)
	 *
	 * Removing a pin outright would also drop the operator's UNSAVED
	 * (changeset) value for that key, so each removed pin is replaced for the
	 * build's duration by a stand-in that keeps the posted value winning but
	 * lets get_theme_mod() defaults — the forced preset's chrome — through
	 * untouched. Multidimensional settings (core's nav_menu_locations[...])
	 * keep their aggregated pin: dynamic-css reads none of them and their
	 * capture is shared state that must not be disturbed.
	 *
	 * @since 7.1.0
	 *
	 * @return array{pins: array<int, array{string, callable}>, overrides: array<int, array{string, callable}>}
	 *         Handles for lafka_preset_payloads_repin_theme_mods().
	 */
	function lafka_preset_payloads_unpin_theme_mods(): array {
		$suspension = array(
			'pins'      => array(),
			'overrides' => array(),
		);
		$manager    = isset( $GLOBALS['wp_customize'] ) ? $GLOBALS['wp_customize'] : null;
		if ( ! $manager instanceof WP_Customize_Manager ) {
			return $suspension;
		}
		foreach ( $manager->settings() as $setting ) {
			if ( 'theme_mod' !== $setting->type || false !== strpos( $setting->id, '[' ) ) {
				continue;
			}
			$hook = 'theme_mod_' . $setting->id;
			$pin  = array( $setting, '_preview_filter' );
			if ( ! remove_filter( $hook, $pin ) ) {
				continue; // Setting not previewed in this request — nothing pinned.
			}
			$suspension['pins'][] = array( $hook, $pin );

			$override = static function ( $value ) use ( $setting ) {
				$unset = new stdClass();
				$post  = $setting->post_value( $unset );
				return $unset === $post ? $value : $post;
			};
			add_filter( $hook, $override );
			$suspension['overrides'][] = array( $hook, $override );
		}
		return $suspension;
	}
}

if ( ! function_exists( 'lafka_preset_payloads_repin_theme_mods' ) ) {
	/**
	 * Restore the pins suspended by lafka_preset_payloads_unpin_theme_mods().
	 *
	 * @since 7.1.0
	 *
	 * @param array{pins: array<int, array{string, callable}>, overrides: array<int, array{string, callable}>} $suspension Suspension handles.
	 */
	function lafka_preset_payloads_repin_theme_mods( array $suspension ): void {
		foreach ( $suspension['overrides'] as $pair ) {
			remove_filter( $pair[0], $pair[1] );
		}
		foreach ( $suspension['pins'] as $pair ) {
			add_filter( $pair[0], $pair[1] );
		}
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

if ( ! function_exists( 'lafka_preset_customize_register' ) ) {
	/**
	 * Setting + section + control. Section priority 5 tops the panel
	 * (Logos sits at 10) — the preset choice is the first decision.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 */
	function lafka_preset_customize_register( $wp_customize ): void {
		require_once __DIR__ . '/class-lafka-customize-preset-control.php';

		$wp_customize->add_section(
			'lafka_design_preset',
			array(
				'title'    => esc_html__( 'Design Preset', 'lafka' ),
				'description' => esc_html__( 'Ten complete restaurant identities — colors, typography, dark/light. Your own Customizer overrides always win over the preset.', 'lafka' ),
				'panel'    => 'lafka_settings',
				'priority' => 5,
			)
		);

		$wp_customize->add_setting(
			'lafka_active_preset',
			array(
				'default'           => 'peppery',
				'type'              => 'theme_mod',
				'sanitize_callback' => 'lafka_sanitize_preset_slug',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new Lafka_Customize_Preset_Control(
				$wp_customize,
				'lafka_active_preset',
				array(
					'label'   => esc_html__( 'Preset', 'lafka' ),
					'section' => 'lafka_design_preset',
				)
			)
		);
	}
	add_action( 'customize_register', 'lafka_preset_customize_register' );
}

if ( ! function_exists( 'lafka_preset_controls_css' ) ) {
	/**
	 * Card-grid styling for the controls pane (controls screen only —
	 * nothing loads on the front end).
	 */
	function lafka_preset_controls_css(): void {
		wp_enqueue_style(
			'lafka-preset-control',
			get_template_directory_uri() . '/assets/customizer/lafka-preset-control.css',
			array(),
			wp_get_theme( get_template() )->get( 'Version' )
		);
	}
	add_action( 'customize_controls_enqueue_scripts', 'lafka_preset_controls_css' );
}

if ( ! function_exists( 'lafka_preset_preview_enqueue' ) ) {
	/**
	 * Preview-iframe script: payloads for every preset, swap-on-message.
	 * Fires only via customize_preview_init — never on a real front end.
	 */
	function lafka_preset_preview_enqueue(): void {
		wp_enqueue_script(
			'lafka-preset-preview',
			get_template_directory_uri() . '/assets/customizer/lafka-preset-preview.js',
			array( 'customize-preview' ),
			wp_get_theme( get_template() )->get( 'Version' ),
			true
		);
		wp_localize_script(
			'lafka-preset-preview',
			'lafkaPresetPreview',
			array( 'payloads' => lafka_preset_preview_payloads() )
		);
	}
	add_action( 'customize_preview_init', 'lafka_preset_preview_enqueue' );
}

if ( ! function_exists( 'lafka_preset_preview_preload_fonts' ) ) {
	/**
	 * Inside the preview iframe, print EVERY pool family's @font-face block
	 * so a preset swap only changes font-family custom properties against
	 * already-loaded families ("hot swap"). Guarded by is_customize_preview()
	 * — a normal render keeps the strict 2-families-per-page budget (the
	 * NX2-03 accept criterion / iron gate).
	 */
	function lafka_preset_preview_preload_fonts(): void {
		if ( ! function_exists( 'is_customize_preview' ) || ! is_customize_preview() ) {
			return;
		}
		$css = '';
		foreach ( lafka_preset_preview_payloads() as $payload ) {
			$css .= $payload['fonts'];
		}
		if ( '' !== $css ) {
			// Duplicate @font-face rules across payloads are harmless — the
			// browser dedupes identical family/style/weight/src rules.
			wp_register_style( 'lafka-preset-fonts-preview', false, array(), wp_get_theme( get_template() )->get( 'Version' ) );
			wp_enqueue_style( 'lafka-preset-fonts-preview' );
			wp_add_inline_style( 'lafka-preset-fonts-preview', $css );
		}
	}
	add_action( 'wp_enqueue_scripts', 'lafka_preset_preview_preload_fonts', 20 );
}
