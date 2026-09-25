<?php
/**
 * WP-CLI: `wp lafka preset …` (GX4). Loaded only under WP-CLI.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Preset_CLI_Command' ) ) {

	/**
	 * Reset / restore the appearance overrides that hide the active preset.
	 */
	class Lafka_Preset_CLI_Command {

		/**
		 * Remove saved colour/font overrides so the active preset shows (a backup is kept).
		 *
		 * ## OPTIONS
		 *
		 * [--dry-run]
		 * : List what would be removed; change nothing.
		 *
		 * ## EXAMPLES
		 *
		 *     wp lafka preset reset --dry-run
		 *     wp lafka preset reset
		 *
		 * @param array<int,string>    $args       Positional.
		 * @param array<string,string> $assoc_args Flags.
		 */
		public function reset( $args, $assoc_args ) {
			$dry    = ! empty( $assoc_args['dry-run'] );
			$result = lafka_preset_reset_appearance( $dry );
			if ( ! $result['removed'] ) {
				WP_CLI::success( 'Nothing to reset: no appearance overrides are stored.' );
				return;
			}
			foreach ( $result['removed'] as $key => $value ) {
				WP_CLI::log( sprintf( '%s %s = %s', $dry ? 'would remove' : 'removed', $key, is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ) );
			}
			if ( $dry ) {
				WP_CLI::success( sprintf( '%d override(s) would be removed (dry run).', count( $result['removed'] ) ) );
				return;
			}
			WP_CLI::success( sprintf( 'Removed %d override(s). Backup: %s (undo: wp lafka preset restore %s)', count( $result['removed'] ), $result['backup'], $result['backup'] ) );
		}

		/**
		 * Put a backup's overrides back.
		 *
		 * ## OPTIONS
		 *
		 * <backup>
		 * : Backup name (see `wp lafka preset backups`).
		 *
		 * @param array<int,string>    $args       Positional.
		 * @param array<string,string> $assoc_args Flags.
		 */
		public function restore( $args, $assoc_args ) {
			$restored = lafka_preset_restore_appearance( (string) ( $args[0] ?? '' ) );
			if ( ! $restored ) {
				WP_CLI::error( 'Unknown or empty backup. List them with: wp lafka preset backups' );
			}
			WP_CLI::success( sprintf( 'Restored %d override(s): %s', count( $restored ), implode( ', ', $restored ) ) );
		}

		/**
		 * List appearance backups.
		 *
		 * @param array<int,string>    $args       Positional.
		 * @param array<string,string> $assoc_args Flags.
		 */
		public function backups( $args, $assoc_args ) {
			$names = lafka_preset_appearance_backups();
			if ( ! $names ) {
				WP_CLI::log( 'No backups.' );
				return;
			}
			foreach ( $names as $name ) {
				WP_CLI::log( $name );
			}
		}
	}
}
