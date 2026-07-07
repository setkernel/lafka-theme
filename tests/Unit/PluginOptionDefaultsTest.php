<?php
/**
 * PluginOptionDefaultsTest — NX1-02 slim defaults successor.
 *
 * The retired Options-Framework registry used to own ~234 defaults via
 * lafka_get_default_values(). NX1-02 slims that function to ONLY the
 * plugin-owned / functional-shared keys that stay in the `lafka` array and
 * resolve through the lafka_get_option() shim + Lafka_Options::get() defaults
 * layer. This locks:
 *   - every surviving key keeps the EXACT Options-Framework `std` (so flag
 *     defaults + currency etc. render byte-identically after retirement);
 *   - the theme's migrated APPEARANCE keys are ABSENT (a fresh install no longer
 *     seeds the legacy appearance array — it renders from theme_mod defaults).
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 7.0.0 (NX1-02 framework retirement)
 */

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-option-defaults.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PluginOptionDefaultsTest extends TestCase {

		public function test_flag_and_shared_defaults_match_legacy_std(): void {
			$defaults = \lafka_get_default_values();

			$expected = array(
				'product_addons'                => 'enabled',
				'shipping_areas'                => '',
				'order_hours'                   => '',
				'kitchen_display'               => '',
				'order_notifications'           => 0,
				'google_maps_api_key'           => '',
				'foodmenu_currency'             => '$',
				'foodmenu_currency_position'    => 'left',
				'category_description_position' => '',
				'custom_product_popup_link'     => '',
				'custom_product_popup_content'  => '',
				'top_bar_message_phone'         => '',
			);
			foreach ( $expected as $key => $value ) {
				$this->assertArrayHasKey( $key, $defaults, "Slim defaults missing plugin-owned key '{$key}'." );
				$this->assertSame( $value, $defaults[ $key ], "Slim default for '{$key}' diverged from the Options-Framework std." );
			}
		}

		public function test_promo_tooltip_defaults_match_legacy_std(): void {
			$defaults = \lafka_get_default_values();
			for ( $i = 1; $i <= 3; $i++ ) {
				$this->assertSame( '', $defaults[ 'promo_tooltip_' . $i . '_text' ] );
				$this->assertSame( '', $defaults[ 'promo_tooltip_' . $i . '_trigger_text' ] );
				$this->assertSame( 'above_price', $defaults[ 'promo_tooltip_' . $i . '_position' ] );
				$this->assertSame( 0, $defaults[ 'promo_tooltip_' . $i . '_show_in_listing' ] );
				$this->assertSame( '', $defaults[ 'promo_tooltip_' . $i . '_content' ] );
			}
		}

		public function test_migrated_appearance_keys_are_absent(): void {
			$defaults = \lafka_get_default_values();
			// A representative slice of keys that moved to theme_mods: they must
			// NOT be seeded into the legacy `lafka` array on a fresh install.
			foreach ( array( 'accent_color', 'body_font', 'header_top_bar_color', 'is_responsive', 'use_countdown', 'main_menu_typography', 'header_background' ) as $migrated ) {
				$this->assertArrayNotHasKey(
					$migrated,
					$defaults,
					"Migrated appearance key '{$migrated}' must not be a plugin-owned default (it lives in a theme_mod now)."
				);
			}
		}

		/**
		 * Every plugin-owned key excluded from the migration map must have a slim
		 * default, so the shim's fallback for that key never regresses to false.
		 */
		public function test_shared_keys_read_via_shim_all_have_defaults(): void {
			$defaults = \lafka_get_default_values();
			$shared   = array(
				'product_addons',
				'foodmenu_currency',
				'foodmenu_currency_position',
				'category_description_position',
				'custom_product_popup_link',
				'custom_product_popup_content',
				'google_maps_api_key',
			);
			foreach ( $shared as $key ) {
				$this->assertArrayHasKey( $key, $defaults );
			}
		}
	}
}
