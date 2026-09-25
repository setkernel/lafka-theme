<?php
declare(strict_types=1);

/**
 * GX4 D4 / PRESET_ENGINE §7: "Reset appearance to preset" removes ONLY the
 * appearance overrides the preset engine owns (the chrome whitelist + the
 * accent/brand pair), backs them up first, can be restored, and a dry run
 * changes nothing. NAP, functional, module, checkout and KDS settings are
 * never touched.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-preset-reset.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class PresetResetTest extends TestCase {

		/** Mods that must survive a reset (functional / NAP / layout / preset choice). */
		private const KEEP = array(
			'lafka_active_preset'             => 'peppery',
			'lafka_business_phone'            => '+15550100',
			'lafka_counter_deals_cat'         => 12,
			'lafka_home_layout'               => 'counter',
			'lafka_archive_quickadd_enabled'  => true,
			'custom_logo'                     => 44,
			'lafka_kds_token'                 => 'secret-token',
			'lafka_home_hero_headline'        => 'Our headline',
		);

		protected function setUp(): void {
			\Lafka_Presets::reset();
			$GLOBALS['lafka_test_theme_mods'] = self::KEEP + array(
				'lafka_accent_color'    => '#f2002d',
				'lafka_body_font'       => array(
					'face'  => 'Rubik',
					'size'  => '16px',
					'color' => '#888888',
				),
				'lafka_h1_font'         => array(
					'face'  => 'Arial',
					'size'  => '60px',
					'color' => '#222222',
				),
				'lafka_footer_background' => array( 'color' => '#2a2a2a' ),
			);
		}

		public function test_removes_only_whitelisted_appearance_keys(): void {
			$result = \lafka_preset_reset_appearance();
			$this->assertSame(
				array( 'lafka_accent_color', 'lafka_body_font', 'lafka_h1_font', 'lafka_footer_background' ),
				array_keys( $result['removed'] )
			);
			foreach ( self::KEEP as $key => $value ) {
				$this->assertSame( $value, $GLOBALS['lafka_test_theme_mods'][ $key ] ?? null, "{$key} must survive" );
			}
			$this->assertArrayNotHasKey( 'lafka_accent_color', $GLOBALS['lafka_test_theme_mods'] );
			$this->assertSame( '#B0271D', \get_theme_mod( 'lafka_accent_color', \lafka_preset_default( 'lafka_accent_color', '#dc2626' ) ), 'the preset now shows' );
		}

		public function test_backup_is_written_and_restorable(): void {
			$before = $GLOBALS['lafka_test_theme_mods'];
			$result = \lafka_preset_reset_appearance();
			$this->assertStringStartsWith( 'lafka_appearance_backup_', $result['backup'] );
			$this->assertSame( $result['removed'], \get_option( $result['backup'] ) );
			$this->assertContains( $result['backup'], \lafka_preset_appearance_backups() );

			$restored = \lafka_preset_restore_appearance( $result['backup'] );
			$this->assertSame( array_keys( $result['removed'] ), $restored );
			ksort( $before );
			$after = $GLOBALS['lafka_test_theme_mods'];
			ksort( $after );
			$this->assertSame( $before, $after, 'restore round-trips exactly' );
		}

		public function test_dry_run_changes_nothing(): void {
			$before = $GLOBALS['lafka_test_theme_mods'];
			$result = \lafka_preset_reset_appearance( true );
			$this->assertCount( 4, $result['removed'] );
			$this->assertSame( '', $result['backup'] );
			$this->assertSame( $before, $GLOBALS['lafka_test_theme_mods'] );
			$this->assertSame( array(), \lafka_preset_appearance_backups() );
		}

		public function test_nothing_to_reset_writes_no_backup(): void {
			$GLOBALS['lafka_test_theme_mods'] = self::KEEP;
			$result                           = \lafka_preset_reset_appearance();
			$this->assertSame( array(), $result['removed'] );
			$this->assertSame( '', $result['backup'] );
		}

		public function test_restore_rejects_unknown_backups(): void {
			$this->assertSame( array(), \lafka_preset_restore_appearance( 'lafka_business_phone' ) );
			$this->assertSame( array(), \lafka_preset_restore_appearance( 'lafka_appearance_backup_1' ) );
		}

		public function test_ajax_requires_capability_and_nonce(): void {
			$this->assertSame( 'edit_theme_options', \lafka_preset_reset_capability() );
			$this->assertTrue( (bool) \has_action( 'wp_ajax_lafka_preset_reset', 'lafka_preset_reset_ajax' ) );
		}
	}
}
