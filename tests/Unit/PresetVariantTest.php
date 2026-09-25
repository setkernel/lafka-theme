<?php
declare(strict_types=1);

/**
 * GX4 A1: preset `variants` are live — a whitelisted per-surface layout map
 * (header/home/menu/footer/drawer ∈ classic|counter, motif ∈ none|check) that
 * lafka_preset_variant() resolves from the ACTIVE preset.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PresetVariantTest extends TestCase {

		protected function setUp(): void {
			\Lafka_Presets::reset();
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function register( array $variants ): void {
			\add_filter(
				'lafka_presets',
				static function ( $presets ) use ( $variants ) {
					$presets['vt-fixture'] = new \Lafka_Preset(
						array(
							'slug'     => 'vt-fixture',
							'schema'   => 1,
							'variants' => $variants,
						)
					);
					return $presets;
				}
			);
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'vt-fixture';
			\Lafka_Presets::reset();
		}

		public function test_variant_resolves_from_active_preset(): void {
			$this->register(
				array(
					'home_layout' => 'counter',
					'motif'       => 'check',
				)
			);
			$this->assertSame( 'counter', \lafka_preset_variant( 'home_layout', 'classic' ) );
			$this->assertSame( 'check', \lafka_preset_variant( 'motif', 'none' ) );
			$this->assertSame( 'classic', \lafka_preset_variant( 'header_layout', 'classic' ), 'an unset surface falls back' );
			$this->assertSame( 'counter', \lafka_active_preset()->variant( 'home_layout' ) );
			$this->assertNull( \lafka_active_preset()->variant( 'footer_layout' ) );
		}

		public function test_unknown_key_or_value_is_rejected(): void {
			$bad_value = new \Lafka_Preset(
				array(
					'slug'     => 'x',
					'schema'   => 1,
					'variants' => array( 'home_layout' => 'grid' ),
				)
			);
			$this->assertNotSame( array(), $bad_value->validate(), 'variants.home_layout = "grid" must fail validation' );

			$bad_key = new \Lafka_Preset(
				array(
					'slug'     => 'x',
					'schema'   => 1,
					'variants' => array( 'foo' => 'counter' ),
				)
			);
			$this->assertNotSame( array(), $bad_key->validate(), 'an unknown variant key must fail validation' );
		}

		public function test_a_non_whitelisted_value_never_resolves(): void {
			// A 3rd-party preset registered via the filter bypasses validate();
			// the resolver must still refuse a value outside the whitelist.
			$this->register( array( 'home_layout' => 'grid' ) );
			$this->assertSame( 'classic', \lafka_preset_variant( 'home_layout', 'classic' ) );
			$this->assertSame( 'classic', \lafka_preset_variant( 'no_such_key', 'classic' ) );
		}

		public function test_empty_variants_fall_back(): void {
			$this->register( array() );
			$this->assertSame( 'classic', \lafka_preset_variant( 'home_layout', 'classic' ) );
			$this->assertSame( 'none', \lafka_preset_variant( 'motif', 'none' ) );
		}

		public function test_all_shipped_variants_are_whitelisted(): void {
			$this->assertSame(
				array( 'header_layout', 'home_layout', 'menu_layout', 'footer_layout', 'drawer_layout', 'motif' ),
				array_keys( LAFKA_PRESET_VARIANT_WHITELIST )
			);
			foreach ( (array) glob( dirname( __DIR__, 2 ) . '/presets/*/preset.json' ) as $file ) {
				$data = json_decode( (string) file_get_contents( $file ), true );
				foreach ( (array) ( $data['variants'] ?? array() ) as $key => $value ) {
					$this->assertArrayHasKey( $key, LAFKA_PRESET_VARIANT_WHITELIST, basename( dirname( $file ) ) . ": variant key {$key}" );
					$this->assertContains( $value, LAFKA_PRESET_VARIANT_WHITELIST[ $key ], basename( dirname( $file ) ) . ": variant {$key}={$value}" );
				}
			}
		}
	}
}
