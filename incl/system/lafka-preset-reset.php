<?php
/**
 * GX4 (PRESET_ENGINE §7): "Reset appearance to preset".
 *
 * Operator theme_mods always beat a preset's defaults, so a site that carries
 * old appearance overrides (legacy accent, fonts, footer colours migrated from
 * the options framework) never shows a new preset's design. This removes ONLY
 * the appearance keys the preset engine owns —
 *   array_keys( active preset chrome ) ∪ LAFKA_PRESET_CHROME_WHITELIST ∪ { lafka_accent_color, lafka_brand_color }
 * — after backing them up to a non-autoloaded option, so it can be undone.
 * NAP / business info, functional settings, layouts, modules, checkout and KDS
 * keys are never touched.
 *
 * Surfaces:
 *   - Customizer → Lafka Settings → Design Preset → "Reset appearance to preset"
 *     (edit_theme_options + nonce, admin-ajax `lafka_preset_reset`)
 *   - WP-CLI: `wp lafka preset reset [--dry-run]`, `wp lafka preset restore <backup>`,
 *     `wp lafka preset backups`
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_preset_reset_capability' ) ) {
	/** Capability required to reset / restore appearance. */
	function lafka_preset_reset_capability(): string {
		return 'edit_theme_options';
	}
}

if ( ! function_exists( 'lafka_preset_reset_keys' ) ) {
	/**
	 * The theme_mod keys a reset may remove.
	 *
	 * @return string[]
	 */
	function lafka_preset_reset_keys(): array {
		$keys = defined( 'LAFKA_PRESET_CHROME_WHITELIST' ) ? LAFKA_PRESET_CHROME_WHITELIST : array();
		if ( function_exists( 'lafka_active_preset' ) ) {
			$keys = array_merge( $keys, array_keys( lafka_active_preset()->chrome() ) );
		}
		$keys = array_merge( $keys, array( 'lafka_accent_color', 'lafka_brand_color' ) );
		// Only ever appearance keys: a (filtered) preset cannot widen the set
		// beyond the chrome whitelist + accent/brand.
		$allowed = array_merge( defined( 'LAFKA_PRESET_CHROME_WHITELIST' ) ? LAFKA_PRESET_CHROME_WHITELIST : array(), array( 'lafka_accent_color', 'lafka_brand_color' ) );
		return array_values( array_unique( array_intersect( $keys, $allowed ) ) );
	}
}

if ( ! function_exists( 'lafka_preset_appearance_backups' ) ) {
	/**
	 * Names of the stored appearance backups, oldest first.
	 *
	 * @return string[]
	 */
	function lafka_preset_appearance_backups(): array {
		return array_values( array_filter( (array) get_option( 'lafka_appearance_backups', array() ), 'is_string' ) );
	}
}

if ( ! function_exists( 'lafka_preset_reset_appearance' ) ) {
	/**
	 * Remove the appearance overrides so the active preset's design shows.
	 *
	 * @param bool $dry_run Report only.
	 * @return array{removed:array<string,mixed>, backup:string} Removed key => old value, and the backup option name ('' when none written).
	 */
	function lafka_preset_reset_appearance( bool $dry_run = false ): array {
		$mods    = (array) get_theme_mods();
		$removed = array();
		foreach ( lafka_preset_reset_keys() as $key ) {
			if ( array_key_exists( $key, $mods ) ) {
				$removed[ $key ] = $mods[ $key ];
			}
		}
		// Stable, readable order: the order the mods were stored in.
		$removed = array_intersect_key( $mods, $removed );

		if ( $dry_run || ! $removed ) {
			return array(
				'removed' => $removed,
				'backup'  => '',
			);
		}

		$backups = lafka_preset_appearance_backups();
		$name    = 'lafka_appearance_backup_' . gmdate( 'YmdHis' );
		$suffix  = 1;
		while ( in_array( $name, $backups, true ) ) {
			$name = 'lafka_appearance_backup_' . gmdate( 'YmdHis' ) . '_' . ( ++$suffix );
		}
		update_option( $name, $removed, false );
		$backups[] = $name;
		update_option( 'lafka_appearance_backups', $backups, false );

		foreach ( array_keys( $removed ) as $key ) {
			remove_theme_mod( $key );
		}

		/**
		 * Fires after appearance overrides were removed.
		 *
		 * @param array<string,mixed> $removed Removed key => old value.
		 * @param string              $name    Backup option name.
		 */
		do_action( 'lafka_preset_appearance_reset', $removed, $name );

		return array(
			'removed' => $removed,
			'backup'  => $name,
		);
	}
}

if ( ! function_exists( 'lafka_preset_restore_appearance' ) ) {
	/**
	 * Put a backup's overrides back.
	 *
	 * @param string $backup A name from lafka_preset_appearance_backups().
	 * @return string[] Restored keys ([] for an unknown backup).
	 */
	function lafka_preset_restore_appearance( string $backup ): array {
		if ( ! in_array( $backup, lafka_preset_appearance_backups(), true ) ) {
			return array();
		}
		$values = get_option( $backup, array() );
		if ( ! is_array( $values ) ) {
			return array();
		}
		$allowed  = lafka_preset_reset_keys();
		$restored = array();
		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $allowed, true ) ) {
				set_theme_mod( $key, $value );
				$restored[] = (string) $key;
			}
		}
		return $restored;
	}
}

if ( ! function_exists( 'lafka_preset_reset_ajax' ) ) {
	/** admin-ajax `lafka_preset_reset` (the Customizer button). */
	function lafka_preset_reset_ajax(): void {
		if ( ! current_user_can( lafka_preset_reset_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change the design.', 'lafka' ) ), 403 );
		}
		check_ajax_referer( 'lafka_preset_reset', 'nonce' );
		$result = lafka_preset_reset_appearance();
		wp_send_json_success(
			array(
				'removed' => array_keys( $result['removed'] ),
				'backup'  => $result['backup'],
			)
		);
	}
}
add_action( 'wp_ajax_lafka_preset_reset', 'lafka_preset_reset_ajax' );

if ( ! function_exists( 'lafka_preset_reset_customize_register' ) ) {
	/**
	 * The "Reset appearance to preset" button in the Design Preset section.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 */
	function lafka_preset_reset_customize_register( $wp_customize ): void {
		if ( ! class_exists( 'WP_Customize_Control' ) ) {
			return;
		}
		if ( ! class_exists( 'Lafka_Customize_Preset_Reset_Control' ) ) {
			require_once __DIR__ . '/class-lafka-customize-preset-reset-control.php';
		}
		$wp_customize->add_control(
			new Lafka_Customize_Preset_Reset_Control(
				$wp_customize,
				'lafka_preset_reset',
				array(
					'section'    => 'lafka_design_preset',
					'settings'   => array(),
					'capability' => lafka_preset_reset_capability(),
					'priority'   => 50,
				)
			)
		);
	}
}
add_action( 'customize_register', 'lafka_preset_reset_customize_register', 20 );

if ( ! function_exists( 'lafka_preset_reset_controls_script' ) ) {
	/** The button's click handler (Customizer controls pane only). */
	function lafka_preset_reset_controls_script(): void {
		$config = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'lafka_preset_reset' ),
			'confirm' => __( "This removes your saved colour and font overrides so the preset's design shows. A backup is kept.", 'lafka' ),
			'done'    => __( 'Done. Reloading…', 'lafka' ),
			'failed'  => __( 'Could not reset. Please try again.', 'lafka' ),
		);
		$js     = '(function(c){document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest("[data-lafka-preset-reset]");if(!b){return;}e.preventDefault();if(!window.confirm(c.confirm)){return;}b.disabled=true;var d=new FormData();d.append("action","lafka_preset_reset");d.append("nonce",c.nonce);fetch(c.ajaxUrl,{method:"POST",credentials:"same-origin",body:d}).then(function(r){return r.json();}).then(function(r){var s=b.parentNode.querySelector("[data-lafka-preset-reset-status]");if(r&&r.success){if(s){s.textContent=c.done;}window.location.reload();}else{b.disabled=false;if(s){s.textContent=c.failed;}}}).catch(function(){b.disabled=false;});});})(' . wp_json_encode( $config ) . ');';
		wp_add_inline_script( 'customize-controls', $js );
	}
}
add_action( 'customize_controls_enqueue_scripts', 'lafka_preset_reset_controls_script' );

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	require_once __DIR__ . '/class-lafka-preset-cli-command.php';
	WP_CLI::add_command( 'lafka preset', 'Lafka_Preset_CLI_Command' );
}
