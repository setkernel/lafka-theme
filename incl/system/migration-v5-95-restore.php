<?php
/**
 * One-shot Customizer restore migration (v5.95.0).
 *
 * v5.93.0 wiped ALL theme_mods to let new handoff defaults apply, but
 * the wipe was too aggressive — it also killed operator-owned data:
 * phone, address, business hours, social links, accent color, theme
 * logo, etc. None of those have hardcoded defaults, so the contact
 * page lost its phone CTA + hours card, the footer lost its phone,
 * and the announce bar lost its contact strip.
 *
 * This migration:
 *
 * 1. Reads the pre-wipe backup at option
 *    `lafka_customizer_backup_pre_v5_93`.
 * 2. Selectively restores OPERATIONAL fields (NAP, hours, social,
 *    images, accent, service ETAs, gateway config, etc.) — anything
 *    the operator might have set that doesn't have a code default.
 * 3. Leaves the COPY/STYLE fields cleared so the new v5.85–v5.94
 *    handoff defaults still apply for hero stats, how-it-works
 *    headline + step copy, closer headline, etc.
 *
 * @package Lafka\Migrations
 * @since   5.95.0
 * @remove-in 5.96.0 — delete this file + the require in functions.php
 *                    once the operator has confirmed restore took.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_migration_v5_95_run' ) ) {
	function lafka_migration_v5_95_run() {
		$marker_key = 'lafka_customizer_restore_v5_95_done';
		if ( '1' === (string) get_option( $marker_key, '' ) ) {
			return;
		}

		$backup = get_option( 'lafka_customizer_backup_pre_v5_93', null );
		if ( ! is_array( $backup ) || empty( $backup ) ) {
			// No backup → nothing to do. Mark done so we don't keep
			// checking on every request.
			update_option( $marker_key, '1', false );
			return;
		}

		// Copy/style fields the v5.93 wipe was supposed to reset.
		// These STAY cleared so the new handoff defaults apply.
		$lafka_copy_fields_to_keep_cleared = array(
			// Home hero stats — handoff reorder rating/time/delivery.
			'lafka_home_hero_stat_1_value',
			'lafka_home_hero_stat_1_label',
			'lafka_home_hero_stat_2_value',
			'lafka_home_hero_stat_2_label',
			'lafka_home_hero_stat_3_value',
			'lafka_home_hero_stat_3_label',

			// Home how-it-works — new copy + step titles.
			'lafka_home_how_headline',
			'lafka_home_how_1_title',
			'lafka_home_how_1_body',
			'lafka_home_how_2_title',
			'lafka_home_how_2_body',
			'lafka_home_how_3_title',
			'lafka_home_how_3_body',

			// Closer — "Hungry?" instead of "Hungry yet?".
			'lafka_home_closer_headline',

			// Menu archive — handoff title + lead.
			'lafka_menu_archive_title',
			'lafka_menu_archive_lead',
		);

		// Allow operator/child theme to extend the cleared-list before
		// the restore runs, e.g. to add their own style fields.
		$lafka_copy_fields_to_keep_cleared = (array) apply_filters(
			'lafka_migration_v5_95_clear_keys',
			$lafka_copy_fields_to_keep_cleared
		);

		// Build the restored theme_mods array: backup minus the
		// cleared-list keys.
		$restore = $backup;
		foreach ( $lafka_copy_fields_to_keep_cleared as $cleared_key ) {
			unset( $restore[ $cleared_key ] );
		}

		// Write the restored set back to the theme_mods option for the
		// active stylesheet. WP's set_theme_mod() walks one key at a
		// time, but a single update_option() is atomic + fast.
		$stylesheet = get_option( 'stylesheet' );
		if ( $stylesheet && is_string( $stylesheet ) ) {
			update_option( 'theme_mods_' . $stylesheet, $restore );
		}

		update_option( $marker_key, '1', false );

		if ( function_exists( 'error_log' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$restored_count = count( $restore );
			$cleared_count  = count( $lafka_copy_fields_to_keep_cleared );
			error_log( sprintf( 'Lafka v5.95: restored %d theme_mods from backup, kept %d copy fields cleared.', $restored_count, $cleared_count ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

add_action( 'init', 'lafka_migration_v5_95_run', 1 );
