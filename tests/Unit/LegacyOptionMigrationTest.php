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
 *   - the map contains every slice's key → theme_mod pair;
 *   - a stored legacy value is copied to its theme_mod;
 *   - the copy is idempotent AND never clobbers a value the operator already
 *     set in the new home (Customizer wins over legacy);
 *   - absent legacy keys and a non-array `lafka` option are safe no-ops.
 *
 * The WP shims (get_option / get_theme_mod / set_theme_mod) live in the GLOBAL
 * namespace — that is where the procedural migrate function resolves its calls.
 * Sibling test files define some of the same shims, so this class runs in a
 * SEPARATE PROCESS with global state discarded, guaranteeing THESE stateful
 * shims win.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 6.22.0 (NX1-02.logos-brand-pilot)
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			return array_key_exists( $name, $GLOBALS['lafka_mig_options'] ?? array() )
				? $GLOBALS['lafka_mig_options'][ $name ]
				: $default;
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return array_key_exists( $name, $GLOBALS['lafka_mig_mods'] ?? array() )
				? $GLOBALS['lafka_mig_mods'][ $name ]
				: $default;
		}
	}
	if ( ! function_exists( 'set_theme_mod' ) ) {
		function set_theme_mod( $name, $value ) {
			$GLOBALS['lafka_mig_mods'][ $name ] = $value;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-legacy-migrate.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class LegacyOptionMigrationTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_mig_options'] = array();
			$GLOBALS['lafka_mig_mods']    = array();
		}

		public function test_map_contains_logos_brand_pilot_keys(): void {
			$map = \lafka_legacy_migrate_map();
			$expected = array(
				'accent_color'            => 'lafka_accent_color',
				'brand_color'             => 'lafka_brand_color',
				'logo_background_color'   => 'lafka_logo_background_color',
				'mobile_theme_logo'       => 'lafka_mobile_theme_logo',
				'disable_logo_point_down' => 'lafka_disable_logo_point_down',
				'theme_logo'              => 'lafka_theme_logo',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_chrome_colors_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'header_top_bar_color'               => 'lafka_header_top_bar_color',
				'header_top_bar_border_color'        => 'lafka_header_top_bar_border_color',
				'top_bar_message_color'              => 'lafka_top_bar_message_color',
				'header_services_color'              => 'lafka_header_services_color',
				'top_bar_menu_links_color'           => 'lafka_top_bar_menu_links_color',
				'top_bar_menu_links_hover_color'     => 'lafka_top_bar_menu_links_hover_color',
				'transparent_header_dark_menu_color' => 'lafka_transparent_header_dark_menu_color',
				'collapsible_bckgr_color'            => 'lafka_collapsible_bckgr_color',
				'collapsible_titles_color'           => 'lafka_collapsible_titles_color',
				'collapsible_titles_border_color'    => 'lafka_collapsible_titles_border_color',
				'collapsible_links_color'            => 'lafka_collapsible_links_color',
				'main_menu_background_color'          => 'lafka_main_menu_background_color',
				'main_menu_links_color'              => 'lafka_main_menu_links_color',
				'main_menu_links_hover_color'        => 'lafka_main_menu_links_hover_color',
				'main_menu_links_bckgr_hover_color'  => 'lafka_main_menu_links_bckgr_hover_color',
				'main_menu_icons_color'              => 'lafka_main_menu_icons_color',
				'footer_titles_color'                => 'lafka_footer_titles_color',
				'footer_title_border_color'          => 'lafka_footer_title_border_color',
				'footer_copyright_bar_text_color'    => 'lafka_footer_copyright_bar_text_color',
				'footer_menu_links_color'            => 'lafka_footer_menu_links_color',
				'footer_links_color'                 => 'lafka_footer_links_color',
				'footer_text_color'                  => 'lafka_footer_text_color',
				'footer_copyright_bar_bckgr_color'   => 'lafka_footer_copyright_bar_bckgr_color',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_content_colors_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'links_color'                        => 'lafka_links_color',
				'links_hover_color'                  => 'lafka_links_hover_color',
				'sidebar_titles_color'               => 'lafka_sidebar_titles_color',
				'all_buttons_color'                  => 'lafka_all_buttons_color',
				'all_buttons_hover_color'            => 'lafka_all_buttons_hover_color',
				'new_label_color'                    => 'lafka_new_label_color',
				'sale_label_color'                   => 'lafka_sale_label_color',
				'page_title_color'                   => 'lafka_page_title_color',
				'page_subtitle_color'                => 'lafka_page_subtitle_color',
				'custom_page_title_color'            => 'lafka_custom_page_title_color',
				'page_title_bckgr_color'             => 'lafka_page_title_bckgr_color',
				'page_title_border_color'            => 'lafka_page_title_border_color',
				'add_to_cart_color'                  => 'lafka_add_to_cart_color',
				'price_color_in_listings'            => 'lafka_price_color_in_listings',
				'price_background_color_in_listings' => 'lafka_price_background_color_in_listings',
				'fancy_category_title_color'         => 'lafka_fancy_category_title_color',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
		}

		public function test_map_contains_dyncss_typography_backgrounds_keys(): void {
			$map      = \lafka_legacy_migrate_map();
			$expected = array(
				'main_menu_typography'           => 'lafka_main_menu_typography',
				'top_menu_typography'            => 'lafka_top_menu_typography',
				'body_font'                      => 'lafka_body_font',
				'text_logo_typography'           => 'lafka_text_logo_typography',
				'headings_font'                  => 'lafka_headings_font',
				'use_google_face_for'            => 'lafka_use_google_face_for',
				'google_subsets'                 => 'lafka_google_subsets',
				'h1_font'                        => 'lafka_h1_font',
				'h2_font'                        => 'lafka_h2_font',
				'h3_font'                        => 'lafka_h3_font',
				'h4_font'                        => 'lafka_h4_font',
				'h5_font'                        => 'lafka_h5_font',
				'h6_font'                        => 'lafka_h6_font',
				'header_background'              => 'lafka_header_background',
				'footer_background'              => 'lafka_footer_background',
				'page_title_default_bckgr_image' => 'lafka_page_title_default_bckgr_image',
			);
			foreach ( $expected as $legacy_key => $mod_key ) {
				$this->assertArrayHasKey( $legacy_key, $map, "Migration map missing '{$legacy_key}'." );
				$this->assertSame( $mod_key, $map[ $legacy_key ], "Migration map mis-homes '{$legacy_key}'." );
			}
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

			$GLOBALS['lafka_mig_options']['lafka'] = array(
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
				$this->assertStringStartsWith( 'lafka_', $mod_key, "Destination for '{$legacy_key}' must be a lafka_ theme_mod." );
			}
		}

		public function test_copies_stored_legacy_values_to_theme_mods(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array(
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
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

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
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#000000' );

			$report = \lafka_legacy_migrate_run();

			$this->assertSame( '#ffffff', get_theme_mod( 'lafka_accent_color' ), 'Customizer value must win over legacy.' );
			$this->assertArrayNotHasKey( 'lafka_accent_color', $report );
		}

		public function test_absent_legacy_keys_are_skipped(): void {
			$GLOBALS['lafka_mig_options']['lafka'] = array( 'accent_color' => '#0a58f3' );

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
	}
}
