<?php
/**
 * One-shot Customizer reset migration (v5.93.0).
 *
 * Disposable migration that runs ONCE after deploy: wipes all theme_mods
 * for the active theme so the new defaults across v5.74–v5.92 take effect
 * (hero stats reordered, "Hot food, three taps away.", "Hungry?", new
 * step copy, etc.). The migration takes a backup before wiping so the
 * operation is reversible via WP-CLI:
 *
 *   wp option get lafka_customizer_backup_pre_v5_93 --format=json
 *   wp option update theme_mods_<stylesheet> '<backup-json>' --format=json
 *
 * @package Lafka\Migrations
 * @since   5.93.0
 * @remove-in 5.94.0 — delete this file + the require_once in functions.php
 *                    once the operator has confirmed defaults look correct.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_migration_v5_93_run' ) ) {
	/**
	 * Idempotent one-shot. Bails immediately if the marker option is set.
	 * Safe to keep loading on every request — short-circuits via option_get.
	 */
	function lafka_migration_v5_93_run() {
		$marker_key = 'lafka_customizer_reset_v5_93_done';
		if ( '1' === (string) get_option( $marker_key, '' ) ) {
			return;
		}

		// Capture a complete backup of every theme_mod for the active
		// stylesheet — operator can restore via WP-CLI if defaults
		// don't match expectations.
		$current_mods = get_theme_mods();
		if ( is_array( $current_mods ) && ! empty( $current_mods ) ) {
			update_option(
				'lafka_customizer_backup_pre_v5_93',
				$current_mods,
				false
			);
		}

		// Wipe all theme_mods for the active theme. The new code defaults
		// from v5.85 / v5.86 / v5.87 / v5.90 / v5.92 will now apply for
		// every operator-tunable field (hero stats, step copy, closer
		// headline, contact CTAs, hours footnote, etc.).
		remove_theme_mods();

		// Mark complete so we don't re-wipe on the next request.
		update_option( $marker_key, '1', false );

		// Best-effort log for ops visibility. Silenced if no error_log.
		if ( function_exists( 'error_log' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Lafka v5.93: Customizer theme_mods cleared. Backup at lafka_customizer_backup_pre_v5_93.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

// Fire on init so the user's first request after deploy triggers the
// reset. Priority 1 so we run BEFORE any code that reads theme_mods
// for output (avoids a half-state on the request that wipes).
add_action( 'init', 'lafka_migration_v5_93_run', 1 );
