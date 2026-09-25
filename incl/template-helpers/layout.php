<?php
/**
 * GX4: per-surface layout resolver.
 *
 * Five storefront surfaces (header, home, menu, footer, drawer) each render in
 * one of two layouts: `classic` (the handoff design every preset shipped with
 * before GX4) or `counter` (design direction C, "The counter"). A decorative
 * motif (`none` | `check`) rides alongside.
 *
 * Resolution, per surface:  operator theme_mod  >  active preset variant  >  classic
 *
 *  - theme_mod `lafka_<surface>_layout` / `lafka_motif` — the Customizer selects
 *    (incl/customizer-counter.php), whose default IS the preset variant, so an
 *    operator who never touches them follows the preset;
 *  - the preset's `variants{}` block via lafka_preset_variant() (GX4 A1);
 *  - `classic`.
 *
 * Templates branch with lafka_layout_is( 'home', 'counter' ). Body classes are
 * added for non-classic choices only, so a classic install's markup is unchanged.
 *
 * Filter surface:
 *   lafka_layout( string $layout, string $surface ) — last-word override
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_layout_surfaces' ) ) {
	/**
	 * The surfaces that have a layout choice.
	 *
	 * @return string[]
	 */
	function lafka_layout_surfaces(): array {
		return array( 'header', 'home', 'menu', 'footer', 'drawer' );
	}
}

if ( ! function_exists( 'lafka_layout_values' ) ) {
	/**
	 * The allowed layout values.
	 *
	 * @return string[]
	 */
	function lafka_layout_values(): array {
		return array( 'classic', 'counter' );
	}
}

if ( ! function_exists( 'lafka_sanitize_layout' ) ) {
	/**
	 * Sanitize a layout value. Doubles as a Customizer sanitize_callback: WP
	 * passes the WP_Customize_Setting as the 2nd argument, whose default (the
	 * preset variant) is then the fallback.
	 *
	 * @param mixed        $value    Candidate value.
	 * @param string|object $fallback Fallback layout, or a WP_Customize_Setting.
	 */
	function lafka_sanitize_layout( $value, $fallback = 'classic' ): string {
		if ( is_object( $fallback ) ) {
			$fallback = isset( $fallback->default ) ? (string) $fallback->default : 'classic';
		}
		$allowed = lafka_layout_values();
		if ( ! in_array( $fallback, $allowed, true ) ) {
			$fallback = 'classic';
		}
		return ( is_string( $value ) && in_array( $value, $allowed, true ) ) ? $value : $fallback;
	}
}

if ( ! function_exists( 'lafka_sanitize_motif' ) ) {
	/**
	 * Sanitize the motif value (`none` | `check`).
	 *
	 * @param mixed         $value    Candidate value.
	 * @param string|object $fallback Fallback, or a WP_Customize_Setting.
	 */
	function lafka_sanitize_motif( $value, $fallback = 'none' ): string {
		if ( is_object( $fallback ) ) {
			$fallback = isset( $fallback->default ) ? (string) $fallback->default : 'none';
		}
		$allowed = array( 'none', 'check' );
		if ( ! in_array( $fallback, $allowed, true ) ) {
			$fallback = 'none';
		}
		return ( is_string( $value ) && in_array( $value, $allowed, true ) ) ? $value : $fallback;
	}
}

if ( ! function_exists( 'lafka_layout_default' ) ) {
	/**
	 * The active preset's default layout for a surface (before any operator choice).
	 *
	 * @param string $surface One of lafka_layout_surfaces().
	 */
	function lafka_layout_default( string $surface ): string {
		return function_exists( 'lafka_preset_variant' )
			? lafka_sanitize_layout( lafka_preset_variant( $surface . '_layout', 'classic' ) )
			: 'classic';
	}
}

if ( ! function_exists( 'lafka_layout' ) ) {
	/**
	 * The resolved layout for a surface: operator > preset > classic.
	 *
	 * @param string $surface One of lafka_layout_surfaces().
	 */
	function lafka_layout( string $surface ): string {
		if ( ! in_array( $surface, lafka_layout_surfaces(), true ) ) {
			return 'classic';
		}
		$default = lafka_layout_default( $surface );
		$layout  = lafka_sanitize_layout( get_theme_mod( 'lafka_' . $surface . '_layout', $default ), $default );

		/**
		 * Filter the resolved layout of a surface.
		 *
		 * @param string $layout  `classic` | `counter`.
		 * @param string $surface header|home|menu|footer|drawer.
		 */
		return lafka_sanitize_layout( apply_filters( 'lafka_layout', $layout, $surface ), $layout );
	}
}

if ( ! function_exists( 'lafka_layout_is' ) ) {
	/**
	 * Whether a surface renders in the given layout.
	 *
	 * @param string $surface Surface.
	 * @param string $value   Layout value.
	 */
	function lafka_layout_is( string $surface, string $value ): bool {
		return lafka_layout( $surface ) === $value;
	}
}

if ( ! function_exists( 'lafka_any_counter_layout' ) ) {
	/**
	 * True when at least one surface renders the counter layout (drives the
	 * conditional lafka-counter stylesheet + scripts).
	 */
	function lafka_any_counter_layout(): bool {
		foreach ( lafka_layout_surfaces() as $surface ) {
			if ( lafka_layout_is( $surface, 'counter' ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'lafka_motif' ) ) {
	/**
	 * The resolved decorative motif: operator > preset > none.
	 */
	function lafka_motif(): string {
		$default = function_exists( 'lafka_preset_variant' ) ? lafka_sanitize_motif( lafka_preset_variant( 'motif', 'none' ) ) : 'none';
		return lafka_sanitize_motif( get_theme_mod( 'lafka_motif', $default ), $default );
	}
}

if ( ! function_exists( 'lafka_layout_body_classes' ) ) {
	/**
	 * Add `lafka-layout-<surface>-counter` + `lafka-motif-<motif>` body classes.
	 * Classic choices add nothing, so classic markup stays byte-identical.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	function lafka_layout_body_classes( $classes ) {
		$classes = (array) $classes;
		foreach ( lafka_layout_surfaces() as $surface ) {
			$layout = lafka_layout( $surface );
			if ( 'classic' !== $layout ) {
				$classes[] = 'lafka-layout-' . $surface . '-' . $layout;
			}
		}
		$motif = lafka_motif();
		if ( 'none' !== $motif ) {
			$classes[] = 'lafka-motif-' . $motif;
		}
		return $classes;
	}
}
add_filter( 'body_class', 'lafka_layout_body_classes' );

if ( ! function_exists( 'lafka_layout_theme_supports' ) ) {
	/**
	 * Declare `lafka-drawer-stepper` while the drawer is counter. lafka-plugin
	 * reads current_theme_supports( 'lafka-drawer-stepper' ) to render its
	 * quantity-stepper drawer row; any other theme / classic drawer keeps
	 * today's row. Evaluated at after_setup_theme (every request, including
	 * wc-ajax fragment refreshes) and re-evaluated at wp_loaded once Customizer
	 * previewed values are applied.
	 */
	function lafka_layout_theme_supports(): void {
		if ( lafka_layout_is( 'drawer', 'counter' ) ) {
			add_theme_support( 'lafka-drawer-stepper' );
		} elseif ( function_exists( 'remove_theme_support' ) ) {
			remove_theme_support( 'lafka-drawer-stepper' );
		}
	}
}
add_action( 'after_setup_theme', 'lafka_layout_theme_supports', 20 );
add_action( 'wp_loaded', 'lafka_layout_theme_supports', 20 );
