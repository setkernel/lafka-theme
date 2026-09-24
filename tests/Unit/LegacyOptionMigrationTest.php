<?php
declare(strict_types=1);

/**
 * LegacyOptionMigrationTest — NX1-02 one-time legacy-option → theme_mod copy.
 *
 * NX1-02 retires the theme's legacy Options Framework: each migration slice
 * re-points its readers at a `lafka_<key>` theme_mod, and the shared migration
 * map (incl/system/lafka-legacy-migrate.php) copies the operator's stored
 * legacy value into that new home so an UPGRADED install keeps its
 * customizations (invariant 2, pixel parity). This test locks the copy
 * function's contract:
 *   - every destination is the legacy key's lafka_-prefixed theme_mod;
 *   - a stored legacy value is copied to its theme_mod;
 *   - the copy is idempotent AND never clobbers a value the operator already
 *     set in the new home (Customizer wins over legacy);
 *   - absent legacy keys and a non-array `lafka` option are safe no-ops.
 *
 * get_option / get_theme_mod / set_theme_mod come from the shared store-backed
 * shims (tests/support/wp-shims.php), reset before every test.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 6.22.0 (NX1-02.logos-brand-pilot)
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-legacy-migrate.php';
}

namespace Lafka\Tests\Unit {
	use PHPUnit\Framework\TestCase;

	final class LegacyOptionMigrationTest extends TestCase {
		/**
		 * NX1-02.plugin-owned-confirm — the final slice migrates exactly ONE
		 * theme-owned key (the WooCommerce sale-countdown toggle) and confirms the
		 * remaining plugin-owned set stays in the `lafka` array (invariant 1): none
		 * of those keys may appear in the map, or the copy would fork a plugin flag
		 * into an exportable theme_mod and diverge from the plugin's flag storage.
		 */
		public function test_map_contains_use_countdown_and_excludes_plugin_owned_set(): void {
			$map = \lafka_legacy_migrate_map();

			// The lone theme-owned key in this slice migrates to a theme_mod.
			$this->assertArrayHasKey( 'use_countdown', $map, "Migration map missing 'use_countdown'." );
			$this->assertSame( 'lafka_use_countdown', $map['use_countdown'], "Migration map mis-homes 'use_countdown'." );

			// The plugin-owned set is NEVER copied out of the `lafka` array.
			$plugin_owned = array(
				'product_addons',
				'google_maps_api_key',
				'foodmenu_currency',
				'foodmenu_currency_position',
				'category_description_position',
				'custom_product_popup_link',
				'custom_product_popup_content',
				'promo_tooltip_1',
				'promo_tooltip_2',
				'promo_tooltip_3',
			);
			foreach ( $plugin_owned as $key ) {
				$this->assertArrayNotHasKey( $key, $map, "Plugin-owned '{$key}' must stay in the `lafka` array, not migrate to a theme_mod." );
			}
		}

		/**
		 * The copy migrates use_countdown verbatim while leaving every plugin-owned
		 * key untouched in the `lafka` array (never promoted to a theme_mod).
		 */
		public function test_copies_use_countdown_but_never_plugin_owned_keys(): void {
			$GLOBALS['lafka_test_options']['lafka'] = array(
				'use_countdown'                 => 'disabled',
				// Plugin-owned keys that share the array must survive untouched.
				'product_addons'                => 'enabled',
				'google_maps_api_key'           => 'SECRET-KEY',
				'foodmenu_currency'             => '£',
				'foodmenu_currency_position'    => 'right',
				'category_description_position' => 'below',
			);

			$report = \lafka_legacy_migrate_run();

			// The theme-owned toggle lands in its theme_mod home.
			$this->assertSame( 'disabled', get_theme_mod( 'lafka_use_countdown' ) );
			$this->assertSame( 'disabled', $report['lafka_use_countdown'] );

			// No plugin-owned key ever becomes a theme_mod, and none is reported.
			$this->assertFalse( get_theme_mod( 'lafka_product_addons' ) );
			$this->assertFalse( get_theme_mod( 'lafka_google_maps_api_key' ) );
			$this->assertFalse( get_theme_mod( 'lafka_foodmenu_currency' ) );
			$this->assertFalse( get_theme_mod( 'lafka_foodmenu_currency_position' ) );
			$this->assertFalse( get_theme_mod( 'lafka_category_description_position' ) );
			$this->assertArrayNotHasKey( 'lafka_google_maps_api_key', $report );
			$this->assertArrayNotHasKey( 'lafka_foodmenu_currency', $report );
		}

		/**
		 * The composite typography + background arrays copy verbatim into their
		 * theme_mods — the migration is value-type-agnostic, so the JSON-encoded
		 * `style` sub-field and the background arrays keep their exact shape
		 * (Hazard 6). This locks that a slice-5 upgraded install renders identically.
		 */
		public function test_copies_composite_typography_and_background_arrays(): void {
			$body_font  = array(
				'face'  => 'Rubik',
				'size'  => '16px',
				'color' => '#5e5e5e',
			);
			$h1_font    = array(
				'face'  => 'Rubik',
				'size'  => '60px',
				'color' => '#22272d',
				'style' => '{"font-weight":"700","font-style":"normal"}',
			);
			$header_bg  = array(
				'color'      => '#ffffff',
				'image'      => 0,
				'repeat'     => '',
				'position'   => '',
				'attachment' => 'scroll',
			);
			$subsets    = array( 'latin' => '1' );

			$GLOBALS['lafka_test_options']['lafka'] = array(
				'body_font'         => $body_font,
				'h1_font'           => $h1_font,
				'header_background'  => $header_bg,
				'google_subsets'    => $subsets,
			);

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( $body_font, get_theme_mod( 'lafka_body_font' ) );
			$this->assertSame( $h1_font, get_theme_mod( 'lafka_h1_font' ) );
			$this->assertSame( $header_bg, get_theme_mod( 'lafka_header_background' ) );
			$this->assertSame( $subsets, get_theme_mod( 'lafka_google_subsets' ) );
			// The JSON `style` sub-field survives verbatim for the renderer.
			$this->assertSame(
				'{"font-weight":"700","font-style":"normal"}',
				get_theme_mod( 'lafka_h1_font' )['style']
			);
			$this->assertSame( $body_font, $report['lafka_body_font'] );
		}

		public function test_map_is_pure_and_prefixes_every_destination(): void {
			// A pure data map: every destination is a namespaced lafka_ theme_mod
			// so NX1-05 export (which only bundles lafka_* theme_mods) picks them up.
			foreach ( \lafka_legacy_migrate_map() as $legacy_key => $mod_key ) {
				$this->assertIsString( $legacy_key );
				$expected = str_starts_with( $legacy_key, 'lafka_' ) ? $legacy_key : 'lafka_' . $legacy_key;
				$this->assertSame( $expected, $mod_key, "Legacy key '{$legacy_key}' must migrate to its lafka_-prefixed theme_mod." );
			}
		}

		public function test_copies_stored_legacy_values_to_theme_mods(): void {
			$GLOBALS['lafka_test_options']['lafka'] = array(
				'accent_color'            => '#0a58f3',
				'brand_color'             => '#88f6a6',
				'logo_background_color'   => '#123456',
				'mobile_theme_logo'       => 42,
				'disable_logo_point_down' => 1,
				'theme_logo'              => 7,
				// A plugin-owned flag that must NOT be migrated (invariant 1).
				'product_addons'          => 'enabled',
			);

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
			$this->assertSame( '#88f6a6', get_theme_mod( 'lafka_brand_color' ) );
			$this->assertSame( '#123456', get_theme_mod( 'lafka_logo_background_color' ) );
			$this->assertSame( 42, get_theme_mod( 'lafka_mobile_theme_logo' ) );
			$this->assertSame( 1, get_theme_mod( 'lafka_disable_logo_point_down' ) );
			$this->assertSame( 7, get_theme_mod( 'lafka_theme_logo' ) );

			// The plugin-owned flag never becomes a theme_mod.
			$this->assertFalse( get_theme_mod( 'lafka_product_addons' ) );
			$this->assertArrayNotHasKey( 'lafka_product_addons', $report );
			// Report lists exactly what was copied.
			$this->assertSame( '#0a58f3', $report['lafka_accent_color'] );
		}

		public function test_is_idempotent_across_repeat_runs(): void {
			$GLOBALS['lafka_test_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

			$first = \lafka_legacy_migrate_run();
			$this->assertArrayHasKey( 'lafka_accent_color', $first );

			$second = \lafka_legacy_migrate_run();
			$this->assertSame( array(), $second, 'Second run must copy nothing — the theme_mod is already set.' );
			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
		}

		public function test_never_clobbers_an_operator_set_theme_mod(): void {
			// Operator already set a NEW-home value (e.g. via Customizer) that
			// differs from the stale legacy value: the migration must not overwrite it.
			set_theme_mod( 'lafka_accent_color', '#ffffff' );
			$GLOBALS['lafka_test_options']['lafka'] = array( 'accent_color' => '#000000' );

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( '#ffffff', get_theme_mod( 'lafka_accent_color' ), 'Customizer value must win over legacy.' );
			$this->assertArrayNotHasKey( 'lafka_accent_color', $report );
		}

		public function test_absent_legacy_keys_are_skipped(): void {
			$GLOBALS['lafka_test_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

			\lafka_legacy_migrate_run();

			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );
			// A key not present in the stored array is never written.
			$this->assertFalse( get_theme_mod( 'lafka_logo_background_color' ) );
		}

		public function test_non_array_option_is_a_safe_no_op(): void {
			// Fresh install: the `lafka` option does not exist yet.
			$report = \lafka_legacy_migrate_run();
			$this->assertSame( array(), $report );
		}

		/**
		 * UPGRADE SIMULATION (NX1-02 Retire phase): an existing install whose
		 * operator customised the theme via the legacy panel is upgraded. The
		 * one-time trigger copies EVERY mapped legacy value into its theme_mod
		 * home, leaves the plugin-owned keys untouched in the `lafka` array,
		 * stamps the migration flag, logs a summary, and is a no-op on re-run.
		 *
		 * The legacy array is reconstructed from the committed dynamic-css parity
		 * fixture VALUES (reverse-mapped to their bare legacy key names), so this
		 * exercises the real appearance keys — including the composite typography
		 * and background arrays — with the exact values the byte-parity gate uses.
		 */
		public function test_upgrade_run_copies_every_mapped_key_and_leaves_plugin_keys(): void {
			$map     = \lafka_legacy_migrate_map();
			$reverse = array_flip( $map );

			$fixture = require dirname( __DIR__ ) . '/fixtures/dynamic-css-fixture.php';

			// Reverse-map the fixture's `lafka_<key>` entries to bare legacy keys.
			$legacy = array();
			foreach ( $fixture as $mod_key => $value ) {
				if ( isset( $reverse[ $mod_key ] ) ) {
					$legacy[ $reverse[ $mod_key ] ] = $value;
				}
			}
			$this->assertNotEmpty( $legacy, 'Fixture failed to reverse-map onto legacy keys.' );

			// Plugin-owned keys that share the array and MUST survive untouched.
			$legacy['product_addons']      = 'enabled';
			$legacy['google_maps_api_key'] = 'SECRET-KEY';
			$legacy['foodmenu_currency']   = '£';

			$GLOBALS['lafka_test_options']['lafka'] = $legacy;

			$report = \lafka_legacy_migrate_maybe_run();
			$this->assertIsArray( $report, 'First upgrade run must copy and report.' );

			// Every mapped legacy value landed verbatim in its theme_mod home.
			foreach ( $legacy as $legacy_key => $value ) {
				if ( ! isset( $map[ $legacy_key ] ) ) {
					continue;
				}
				$this->assertSame(
					$value,
					get_theme_mod( $map[ $legacy_key ] ),
					"Upgrade copy lost/mangled '{$legacy_key}'."
				);
				$this->assertArrayHasKey( $map[ $legacy_key ], $report );
			}

			// Plugin-owned keys were never promoted to a theme_mod...
			$this->assertFalse( get_theme_mod( 'lafka_product_addons' ) );
			$this->assertFalse( get_theme_mod( 'lafka_google_maps_api_key' ) );
			$this->assertFalse( get_theme_mod( 'lafka_foodmenu_currency' ) );
			// ...and remain byte-intact in the `lafka` array (invariant 1).
			$this->assertSame( 'enabled', $GLOBALS['lafka_test_options']['lafka']['product_addons'] );
			$this->assertSame( 'SECRET-KEY', $GLOBALS['lafka_test_options']['lafka']['google_maps_api_key'] );
			$this->assertSame( '£', $GLOBALS['lafka_test_options']['lafka']['foodmenu_currency'] );

			// The flag is stamped and a supportability summary is logged.
			$this->assertSame( \LAFKA_LEGACY_MIGRATION_VERSION, (int) get_option( 'lafka_legacy_migration_version' ) );
			$log = get_option( 'lafka_legacy_migration_log' );
			$this->assertIsArray( $log );
			$this->assertSame( \LAFKA_LEGACY_MIGRATION_VERSION, $log['migration_version'] );
			$this->assertSame( count( $report ), $log['copied_count'] );
			$this->assertSame( array_keys( $report ), $log['copied_theme_mods'] );
		}

		public function test_upgrade_run_is_a_no_op_on_second_pass(): void {
			$GLOBALS['lafka_test_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

			$first = \lafka_legacy_migrate_maybe_run();
			$this->assertIsArray( $first );
			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ) );

			// Even if a NEW legacy value appears, the stamped flag blocks re-copy.
			$GLOBALS['lafka_test_options']['lafka']['accent_color'] = '#000000';
			$second = \lafka_legacy_migrate_maybe_run();
			$this->assertNull( $second, 'A second maybe_run at the same schema version must be a no-op.' );
			$this->assertSame( '#0a58f3', get_theme_mod( 'lafka_accent_color' ), 'Migrated theme_mod must not be re-copied.' );
		}

		public function test_fresh_install_stamps_flag_without_copying(): void {
			// No `lafka` array at all: the trigger still stamps the flag so it
			// never re-runs, and logs a zero-copy summary.
			$report = \lafka_legacy_migrate_maybe_run();
			$this->assertSame( array(), $report );
			$this->assertSame( \LAFKA_LEGACY_MIGRATION_VERSION, (int) get_option( 'lafka_legacy_migration_version' ) );
			$log = get_option( 'lafka_legacy_migration_log' );
			$this->assertSame( 0, $log['copied_count'] );
		}
	}
}
