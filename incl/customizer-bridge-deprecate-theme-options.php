<?php
/**
 * Retire the Theme Options admin menu (v6.1.0).
 *
 * Phase 3 of the Customizer consolidation. Phase 1 (v6.0.0) added the
 * Customizer bridge so operators have one editing UI. This phase hides
 * the legacy "Appearance → Theme Options" entry point entirely.
 *
 * What stays:
 *  - The framework's storage layer (wp_options.lafka) is untouched
 *  - lafka_get_option() and lafka_get_default_values() keep working
 *  - The framework's sanitize / interface PHP is loaded so any code that
 *    consumes its helpers (font preview, color picker, etc.) still has
 *    them available
 *
 * What disappears:
 *  - Appearance → Theme Options menu item (removed via remove_submenu_page)
 *  - Direct hits to ?page=lafka-optionsframework get redirected to the
 *    Customizer panel
 *  - The v6.0.0 admin notice that pointed legacy-page visitors to the
 *    Customizer (no longer needed — they can't reach the legacy page)
 *
 * Rollback: if a bridged field turns out to be unreachable from
 * Customizer, delete this file's require_once line in functions.php
 * and the menu comes back.
 *
 * @package Lafka
 * @since   6.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_retire_theme_options_menu' ) ) {
	/**
	 * Remove the Appearance → Theme Options submenu after the framework
	 * registers it. Runs at priority 999 so it fires AFTER
	 * lafka_optionsframework_add_page (priority 10 default).
	 */
	function lafka_retire_theme_options_menu(): void {
		remove_submenu_page( 'themes.php', 'lafka-optionsframework' );
	}
}
add_action( 'admin_menu', 'lafka_retire_theme_options_menu', 999 );

if ( ! function_exists( 'lafka_redirect_theme_options_to_customizer' ) ) {
	/**
	 * If anyone hits the legacy URL directly (bookmark, deep link, etc.),
	 * 302 them to the Customizer panel that replaced it.
	 *
	 * Uses 302 (not 301) so a future re-enable doesn't get permanently
	 * cached by the browser.
	 */
	function lafka_redirect_theme_options_to_customizer(): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( 'lafka-optionsframework' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$target = admin_url( 'customize.php?autofocus[panel]=lafka_settings' );
		wp_safe_redirect( $target, 302 );
		exit;
	}
}
add_action( 'admin_init', 'lafka_redirect_theme_options_to_customizer', 1 );

if ( ! function_exists( 'lafka_suppress_legacy_admin_notice' ) ) {
	/**
	 * The v6.0.0 bridge added an admin notice on the Theme Options page
	 * pointing operators to the Customizer. With the menu gone, that
	 * notice can never appear. Remove it to keep functions.php tidy
	 * after the menu retirement is verified safe.
	 *
	 * Implemented as a no-op for v6.1 — the notice in customizer-bridge.php
	 * already checks the screen ID and silently returns when not on the
	 * legacy page. Future cleanup can delete the method outright in v6.2.
	 */
	function lafka_suppress_legacy_admin_notice(): void {
		// Intentional no-op. The notice self-suppresses via screen-ID check.
	}
}
