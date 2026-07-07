<?php
declare(strict_types=1);

/**
 * NX2-01 preset CASCADE proof (DOM-free, in code not prose).
 *
 * Proves Base < PTL < Operator and "operator always wins", for a light and a
 * dark preset, by exercising the REAL registry + lafka_preset_default +
 * lafka_preset_ptl_css against a controllable theme_mod store. See
 * docs/PRESET_ENGINE.md §4-6, §9.
 *
 * ISOLATION: the WP shims (get_theme_mod / add_filter / apply_filters /
 * sanitize_key) live in the GLOBAL namespace where the preset code resolves
 * them; sibling test files define some of the same shims, so this class runs in
 * a SEPARATE PROCESS with global state discarded so THESE shims win.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			$store = isset( $GLOBALS['lafka_test_theme_mods'] ) ? $GLOBALS['lafka_test_theme_mods'] : array();
			return array_key_exists( $name, $store ) ? $store[ $name ] : $default;
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
			$GLOBALS['lafka_test_filters'][ $hook ][] = $cb;
			return true;
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook, $value = null, ...$rest ) {
			if ( ! empty( $GLOBALS['lafka_test_filters'][ $hook ] ) ) {
				foreach ( $GLOBALS['lafka_test_filters'][ $hook ] as $cb ) {
					$value = $cb( $value, ...$rest );
				}
			}
			return $value;
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetCascadeTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_filters']    = array();
			$GLOBALS['lafka_test_theme_mods'] = array();
			\Lafka_Presets::reset();

			// Register two synthetic presets carrying chrome + PTL tokens so the
			// registry has a light and a dark preset with real defaults to resolve.
			\add_filter(
				'lafka_presets',
				static function ( $presets ) {
					$presets['ct-light'] = new \Lafka_Preset(
						array(
							'slug'   => 'ct-light',
							'schema' => 1,
							'dark'   => false,
							'tokens' => array( '--lafka-color-surface-page' => '#fffdf7' ),
							'chrome' => array( 'lafka_accent_color' => '#0ea5e9' ),
						)
					);
					$presets['ct-dark'] = new \Lafka_Preset(
						array(
							'slug'   => 'ct-dark',
							'schema' => 1,
							'dark'   => true,
							'tokens' => array( '--lafka-color-surface-page' => '#0a0a0a' ),
							'chrome' => array(
								'lafka_accent_color' => '#22d3ee',
								'lafka_brand_color'  => '#a3e635',
							),
						)
					);
					return $presets;
				}
			);
		}

		/**
		 * The dynamic-css wrap, reproduced exactly: the operator's stored
		 * theme_mod wins; otherwise the active preset's chrome default (from the
		 * REAL lafka_preset_default) supplies the value.
		 */
		private function effective_chrome( string $key, $literal ) {
			return \get_theme_mod( $key, \lafka_preset_default( $key, $literal ) );
		}

		/**
		 * STRUCTURAL operator-wins proof: the PTL token set (whitelist) and the
		 * operator token set (dynamic-css :root) are DISJOINT, so a PTL
		 * declaration can never collide with — let alone out-rank — the operator
		 * layer, and vice-versa.
		 */
		public function test_ptl_and_operator_token_sets_are_disjoint(): void {
			$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/dynamic-css.php' );
			preg_match_all( "/'(--lafka-[a-z0-9-]+)\s*:/", $src, $m );
			$operator = array_values( array_unique( $m[1] ) );
			$this->assertNotEmpty( $operator, 'failed to parse operator tokens from dynamic-css.php' );

			$overlap = array_values( array_intersect( $operator, LAFKA_PRESET_TOKEN_WHITELIST ) );
			$this->assertSame(
				array(),
				$overlap,
				'PTL whitelist overlaps the operator (dynamic-css) token set — the two layers must be disjoint.'
			);
		}

		/** Light preset PTL targets bare :root (beats base by source order). */
		public function test_light_preset_ptl_targets_root(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'l',
					'schema' => 1,
					'dark'   => false,
					'tokens' => array( '--lafka-color-surface-page' => '#fffdf7' ),
				)
			);
			$css = \lafka_preset_ptl_css( $preset );
			$this->assertStringStartsWith( ':root{', $css );
			$this->assertStringNotContainsString( 'data-theme', $css );
			$this->assertStringContainsString( '--lafka-color-surface-page:#fffdf7;', $css );
		}

		/** Dark preset PTL targets :root[data-theme="dark"] (0,2,0 supersedes scaffold). */
		public function test_dark_preset_ptl_targets_dark_root(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'd',
					'schema' => 1,
					'dark'   => true,
					'tokens' => array( '--lafka-color-surface-page' => '#0a0a0a' ),
				)
			);
			$css = \lafka_preset_ptl_css( $preset );
			$this->assertStringStartsWith( ':root[data-theme="dark"]{', $css );
			$this->assertStringContainsString( '--lafka-color-surface-page:#0a0a0a;', $css );
		}

		/** Forbidden + out-of-whitelist tokens are dropped at emit time. */
		public function test_ptl_drops_forbidden_and_unknown_tokens(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'x',
					'schema' => 1,
					'tokens' => array(
						'--lafka-color-accent-500'   => '#00ff00', // forbidden (operator-fed)
						'--lafka-color-accent-text'  => '#00ff00', // forbidden (derived)
						'--lafka-color-brand-500'    => '#00ff00', // forbidden (operator-fed)
						'--lafka-not-a-real-token'   => 'x',       // unknown
						'--lafka-color-surface-page' => '#0a0a0a', // valid
					),
				)
			);
			$css = \lafka_preset_ptl_css( $preset );
			$this->assertStringNotContainsString( 'accent-500', $css );
			$this->assertStringNotContainsString( 'accent-text', $css );
			$this->assertStringNotContainsString( 'brand-500', $css );
			$this->assertStringNotContainsString( 'not-a-real-token', $css );
			$this->assertStringContainsString( '--lafka-color-surface-page:#0a0a0a;', $css );
		}

		/** A hostile value cannot break out of the inline <style> declaration. */
		public function test_ptl_value_is_sanitised(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'   => 'h',
					'schema' => 1,
					'tokens' => array( '--lafka-color-surface-page' => '#000;} </style><script>x' ),
				)
			);
			$css = \lafka_preset_ptl_css( $preset );
			$this->assertStringNotContainsString( '</style>', $css );
			$this->assertStringNotContainsString( '<script>', $css );
			$this->assertStringNotContainsString( '}', substr( $css, 0, strlen( $css ) - 1 ) );
		}

		/** Peppery (shipped, empty tokens) is a provable no-op: empty PTL. */
		public function test_peppery_ptl_is_empty(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'peppery';
			$this->assertSame( '', \lafka_preset_ptl_css( \lafka_active_preset() ) );
		}

		/**
		 * THE KEY INVARIANT, in code: for both a light and a dark preset, the
		 * preset's chrome default fills an unset theme_mod, an operator-set
		 * theme_mod WINS over it, and that operator value SURVIVES a preset switch.
		 *
		 * @return array<string,array{0:string,1:string}> slug => [slug, expected preset-default accent]
		 */
		public static function activePresetProvider(): array {
			return array(
				'light preset' => array( 'ct-light', '#0ea5e9' ),
				'dark preset'  => array( 'ct-dark', '#22d3ee' ),
			);
		}

		#[\PHPUnit\Framework\Attributes\DataProvider( 'activePresetProvider' )]
		public function test_chrome_default_then_operator_wins_and_survives_switch( string $slug, string $preset_accent ): void {
			$key     = 'lafka_accent_color';
			$literal = '#dc2626';

			// (1) No operator override, preset active -> the preset's chrome default.
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = $slug;
			$this->assertSame(
				$preset_accent,
				$this->effective_chrome( $key, $literal ),
				'the active preset chrome must supply the accent when the operator has set none'
			);

			// (2) Operator sets a distinct accent -> operator WINS over the preset default.
			$GLOBALS['lafka_test_theme_mods'][ $key ] = '#00ff00';
			$this->assertSame(
				'#00ff00',
				$this->effective_chrome( $key, $literal ),
				'an operator theme_mod must win over the preset default'
			);

			// (3) Switch preset to peppery (empty chrome) -> operator SURVIVES + still wins.
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'peppery';
			$this->assertSame(
				'#00ff00',
				$this->effective_chrome( $key, $literal ),
				'the operator override must survive a preset switch and keep winning'
			);

			// (4) Remove the override on peppery -> falls back to the shipped literal.
			unset( $GLOBALS['lafka_test_theme_mods'][ $key ] );
			$this->assertSame(
				$literal,
				$this->effective_chrome( $key, $literal ),
				'peppery supplies no chrome default, so an unset key resolves to the shipped literal'
			);
		}

		/** lafka_preset_default routes composite typography arrays, not just scalars. */
		public function test_preset_default_routes_composite_arrays(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'peppery';
			$fallback = array( 'face' => 'Rubik', 'size' => '16px' );
			$this->assertSame(
				$fallback,
				\lafka_preset_default( 'lafka_body_font', $fallback ),
				'peppery has no chrome override, so the composite fallback array is returned verbatim'
			);
		}
	}
}
