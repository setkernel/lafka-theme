<?php
declare(strict_types=1);

/**
 * GX4 A2: per-surface layout resolver. Resolution order is
 * operator theme_mod > active preset variant > 'classic'; an invalid stored
 * value falls back to the preset default; body classes are added only for
 * non-classic choices (so a classic install's markup is unchanged).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';

	if ( ! function_exists( 'add_theme_support' ) ) {
		function add_theme_support( $feature, ...$args ) {
			$GLOBALS['lafka_test_theme_supports'][ $feature ] = true;
			return true;
		}
	}
	if ( ! function_exists( 'remove_theme_support' ) ) {
		function remove_theme_support( $feature ) {
			unset( $GLOBALS['lafka_test_theme_supports'][ $feature ] );
			return true;
		}
	}
	if ( ! function_exists( 'current_theme_supports' ) ) {
		function current_theme_supports( $feature, ...$args ) {
			return ! empty( $GLOBALS['lafka_test_theme_supports'][ $feature ] );
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class LayoutResolverTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_theme_supports'] = array();
			\lafka_test_activate_identity_preset(); // No variants (GX4: Peppery is counter).
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function counter_preset(): void {
			\add_filter(
				'lafka_presets',
				static function ( $presets ) {
					$presets['lr-counter'] = new \Lafka_Preset(
						array(
							'slug'     => 'lr-counter',
							'schema'   => 1,
							'variants' => array(
								'header_layout' => 'counter',
								'home_layout'   => 'counter',
								'drawer_layout' => 'counter',
								'motif'         => 'check',
							),
						)
					);
					return $presets;
				}
			);
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'lr-counter';
			\Lafka_Presets::reset();
		}

		public function test_operator_beats_preset_beats_classic(): void {
			$this->assertSame( 'classic', \lafka_layout( 'home' ), 'no preset variant, no mod -> classic' );

			$this->counter_preset();
			$this->assertSame( 'counter', \lafka_layout( 'home' ), 'preset variant supplies the default' );

			$GLOBALS['lafka_test_theme_mods']['lafka_home_layout'] = 'classic';
			$this->assertSame( 'classic', \lafka_layout( 'home' ), 'the operator theme_mod wins over the preset' );
		}

		public function test_invalid_stored_value_gives_preset_default(): void {
			$this->counter_preset();
			$GLOBALS['lafka_test_theme_mods']['lafka_home_layout'] = 'grid';
			$this->assertSame( 'counter', \lafka_layout( 'home' ) );
			$this->assertSame( 'counter', \lafka_sanitize_layout( 'nope', 'counter' ) );
			$this->assertSame( 'classic', \lafka_sanitize_layout( 'nope', 'bogus' ) );
			$this->assertSame( 'counter', \lafka_sanitize_layout( 'counter' ) );
		}

		public function test_layout_is(): void {
			$this->counter_preset();
			$this->assertTrue( \lafka_layout_is( 'header', 'counter' ) );
			$this->assertFalse( \lafka_layout_is( 'menu', 'counter' ) );
			$this->assertTrue( \lafka_layout_is( 'menu', 'classic' ) );
			$this->assertFalse( \lafka_layout_is( 'nonsense', 'counter' ), 'an unknown surface is never counter' );
			$this->assertTrue( \lafka_any_counter_layout() );
		}

		public function test_motif_resolves_operator_then_preset(): void {
			$this->assertSame( 'none', \lafka_motif() );
			$this->counter_preset();
			$this->assertSame( 'check', \lafka_motif() );
			$GLOBALS['lafka_test_theme_mods']['lafka_motif'] = 'none';
			$this->assertSame( 'none', \lafka_motif() );
		}

		public function test_body_classes_only_for_counter_and_motif(): void {
			$this->assertSame( array( 'home' ), \lafka_layout_body_classes( array( 'home' ) ), 'classic install: body classes unchanged' );

			$this->counter_preset();
			$classes = \lafka_layout_body_classes( array( 'home' ) );
			$this->assertContains( 'lafka-layout-home-counter', $classes );
			$this->assertContains( 'lafka-layout-header-counter', $classes );
			$this->assertContains( 'lafka-motif-check', $classes );
			$this->assertNotContains( 'lafka-layout-menu-classic', $classes );
		}

		public function test_drawer_stepper_support_follows_the_drawer_layout(): void {
			\lafka_layout_theme_supports();
			$this->assertFalse( \current_theme_supports( 'lafka-drawer-stepper' ), 'classic drawer: plugin keeps today\'s row' );

			$this->counter_preset();
			\lafka_layout_theme_supports();
			$this->assertTrue( \current_theme_supports( 'lafka-drawer-stepper' ) );

			$GLOBALS['lafka_test_theme_mods']['lafka_drawer_layout'] = 'classic';
			\lafka_layout_theme_supports();
			$this->assertFalse( \current_theme_supports( 'lafka-drawer-stepper' ), 're-evaluation removes the support' );
		}
	}
}
