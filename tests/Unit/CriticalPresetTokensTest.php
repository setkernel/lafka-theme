<?php
declare(strict_types=1);

/**
 * GX4 A5: the inlined critical CSS follows the active preset when a counter
 * layout is on — the preset's above-fold tokens + a var()-only counter slice
 * (body face/size/ink, header + hero skeleton) — so first paint shows the right
 * font and palette with no layout shift. Classic layouts inline exactly the
 * pre-GX4 bundle.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-fonts.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-critical-css.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CriticalPresetTokensTest extends TestCase {

		protected function setUp(): void {
			\Lafka_Presets::reset();
			\add_filter(
				'lafka_presets',
				static function ( $presets ) {
					$presets['cp-counter'] = new \Lafka_Preset(
						array(
							'slug'     => 'cp-counter',
							'schema'   => 1,
							'tokens'   => array(
								'--lafka-color-surface-page'  => '#ffffff',
								'--lafka-color-text-primary'  => '#1f1b18',
								'--lafka-font-family-body'    => '"Atkinson Hyperlegible Next", verdana, sans-serif',
								'--lafka-color-surface-muted' => '#f6f3ee',
								'--lafka-radius-md'           => '8px', // not a critical key.
							),
							'variants' => array(
								'header_layout' => 'counter',
								'home_layout'   => 'counter',
							),
						)
					);
					return $presets;
				}
			);
		}

		protected function tearDown(): void {
			\Lafka_Presets::reset();
		}

		private function inline(): string {
			ob_start();
			\lafka_inline_critical_css();
			return (string) ob_get_clean();
		}

		public function test_classic_layout_inlines_the_unchanged_bundle(): void {
			$out = $this->inline();
			$this->assertStringStartsWith( "\n<style id=\"lafka-critical-css\">", $out );
			$this->assertStringNotContainsString( 'lafka-counter', $out );
			$this->assertSame( '', \lafka_critical_preset_css(), 'no counter layout -> no preset block' );
		}

		public function test_counter_layout_appends_preset_critical_tokens_and_slice(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'cp-counter';
			\Lafka_Presets::reset();

			$block = \lafka_critical_preset_css();
			$this->assertStringStartsWith( ':root{', $block );
			$this->assertStringContainsString( '--lafka-color-surface-page:#ffffff;', $block );
			$this->assertStringContainsString( '--lafka-color-surface-muted:#f6f3ee;', $block );
			$this->assertStringContainsString( '--lafka-font-family-body:"Atkinson Hyperlegible Next", verdana, sans-serif;', $block );
			$this->assertStringNotContainsString( '--lafka-radius-md', $block, 'only LAFKA_PRESET_CRITICAL_KEYS are inlined' );

			$out = $this->inline();
			$this->assertStringContainsString( $block, $out );
			$this->assertMatchesRegularExpression( '/body \{[^}]*font-family: var\(--lafka-font-family-body\)/', $out, 'the counter slice sets the body face from tokens' );
			$this->assertLessThan( 9 * 1024, strlen( $out ), 'critical budget: bundle + preset block + slice <= 9 KB' );
		}

		public function test_operator_can_turn_the_counter_off(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'cp-counter';
			$GLOBALS['lafka_test_theme_mods']['lafka_header_layout'] = 'classic';
			$GLOBALS['lafka_test_theme_mods']['lafka_home_layout']   = 'classic';
			\Lafka_Presets::reset();
			$this->assertSame( '', \lafka_critical_preset_css() );
		}

		public function test_new_critical_keys_are_whitelisted(): void {
			$this->assertContains( '--lafka-color-surface-muted', LAFKA_PRESET_CRITICAL_KEYS );
			$this->assertContains( '--lafka-color-success-500', LAFKA_PRESET_CRITICAL_KEYS );
		}

		public function test_counter_slice_uses_tokens_only(): void {
			$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/critical-counter.css' );
			$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
			$this->assertDoesNotMatchRegularExpression( '/#[0-9a-f]{3,8}\b/i', $css, 'critical-counter.css is var()-only (no colour literals)' );
		}
	}
}
