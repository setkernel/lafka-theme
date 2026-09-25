<?php
declare(strict_types=1);

/**
 * GX4: the counter design's Customizer wiring — layout selects default to the
 * active preset's variant and sanitize through the layout whitelist.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/customizer-counter.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterCustomizerWiringTest extends TestCase {

		protected function setUp(): void {
			\Lafka_Presets::reset();
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function register(): \WP_Customize_Manager {
			$manager = new \WP_Customize_Manager();
			foreach ( \lafka_test_callbacks( 'customize_register' ) as $callback ) {
				if ( is_string( $callback ) && str_starts_with( $callback, 'lafka_counter_' ) ) {
					$callback( $manager );
				}
			}
			return $manager;
		}

		public function test_layout_selects_are_registered_with_the_whitelist_sanitizer(): void {
			$manager = $this->register();
			foreach ( array( 'header', 'home', 'menu', 'footer', 'drawer' ) as $surface ) {
				$id = 'lafka_' . $surface . '_layout';
				$this->assertArrayHasKey( $id, $manager->settings );
				$this->assertSame( 'lafka_sanitize_layout', $manager->settings[ $id ]['sanitize_callback'] );
				$this->assertSame( 'refresh', $manager->settings[ $id ]['transport'] );
				$this->assertSame( 'classic', $manager->settings[ $id ]['default'], 'no preset variant -> classic default' );
				$this->assertSame( 'lafka_layouts', $manager->controls[ $id ]['section'] );
				$this->assertSame( array( 'classic', 'counter' ), array_keys( $manager->controls[ $id ]['choices'] ) );
			}
			$this->assertSame( 'lafka_sanitize_motif', $manager->settings['lafka_motif']['sanitize_callback'] );
			$this->assertSame( 'lafka_settings', $manager->sections['lafka_layouts']['panel'] );
		}

		public function test_defaults_follow_the_active_preset_variant(): void {
			\add_filter(
				'lafka_presets',
				static function ( $presets ) {
					$presets['cw-counter'] = new \Lafka_Preset(
						array(
							'slug'     => 'cw-counter',
							'schema'   => 1,
							'variants' => array(
								'home_layout' => 'counter',
								'motif'       => 'check',
							),
						)
					);
					return $presets;
				}
			);
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'cw-counter';
			\Lafka_Presets::reset();

			$manager = $this->register();
			$this->assertSame( 'counter', $manager->settings['lafka_home_layout']['default'] );
			$this->assertSame( 'classic', $manager->settings['lafka_header_layout']['default'] );
			$this->assertSame( 'check', $manager->settings['lafka_motif']['default'] );
		}

		public function test_counter_home_settings(): void {
			$manager  = $this->register();
			$expected = array(
				'lafka_counter_costar_a'       => array( 0, 'absint' ),
				'lafka_counter_costar_b'       => array( 0, 'absint' ),
				'lafka_counter_hero_product_a' => array( 0, 'absint' ),
				'lafka_counter_hero_product_b' => array( 0, 'absint' ),
				'lafka_counter_deals_cat'      => array( 0, 'absint' ),
				'lafka_counter_featured_deal'  => array( 0, 'absint' ),
				'lafka_counter_deals_heading'  => array( "Today's deals", 'sanitize_text_field' ),
				'lafka_counter_deals_lead'     => array( '', 'sanitize_text_field' ),
				'lafka_counter_deals_limit'    => array( 6, 'lafka_counter_sanitize_limit_12' ),
				'lafka_counter_costar_limit'   => array( 3, 'lafka_counter_sanitize_limit_12' ),
				'lafka_counter_menu_limit'     => array( 6, 'lafka_counter_sanitize_limit_24' ),
				'lafka_counter_menu_style'     => array( 'compact', 'lafka_counter_sanitize_menu_style' ),
				'lafka_counter_menu_thumbs'    => array( true, 'rest_sanitize_boolean' ),
				'lafka_counter_menu_heading'   => array( 'More from our menu', 'sanitize_text_field' ),
				'lafka_counter_jump_links'     => array( true, 'rest_sanitize_boolean' ),
				'lafka_counter_show_find_us'   => array( true, 'rest_sanitize_boolean' ),
			);
			foreach ( $expected as $id => list( $default, $sanitize ) ) {
				$this->assertSame( $default, $manager->settings[ $id ]['default'], $id );
				$this->assertSame( $sanitize, $manager->settings[ $id ]['sanitize_callback'], $id );
				$this->assertSame( 'lafka_home_counter', $manager->controls[ $id ]['section'], $id );
			}
			$this->assertSame( 'lafka_home', $manager->sections['lafka_home_counter']['panel'] );
			$this->assertSame( 'lafka_counter_home_is_active', $manager->sections['lafka_home_counter']['active_callback'] );
			$this->assertSame( 'Automatic', $manager->controls['lafka_counter_deals_cat']['choices'][0] );

			$this->assertSame( 12, \lafka_counter_sanitize_limit_12( 12 ) );
			$this->assertSame( 6, \lafka_counter_sanitize_limit_12( 99 ) );
			$this->assertSame( 24, \lafka_counter_sanitize_limit_24( 24 ) );
			$this->assertSame( 'compact', \lafka_counter_sanitize_menu_style( 'grid' ) );
		}

		public function test_sanitizer_uses_the_setting_default_as_fallback(): void {
			$setting          = new \stdClass();
			$setting->default = 'counter';
			$this->assertSame( 'counter', \lafka_sanitize_layout( '<script>', $setting ) );
			$this->assertSame( 'classic', \lafka_sanitize_layout( 'classic', $setting ) );
		}
	}
}
