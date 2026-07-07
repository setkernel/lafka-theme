<?php
declare(strict_types=1);

/**
 * NX2-02 preset CONTRAST gate.
 *
 * For EVERY registered preset this resolves the EFFECTIVE palette — base tokens
 * (styles/lafka-tokens.css :root, plus the :root[data-theme="dark"] scaffold for a
 * dark preset) (+) the Preset-Token Layer (the preset's whitelisted tokens{}) (+)
 * the chrome layer (lafka_accent_color -> --lafka-color-accent-500, the NEW/SALE
 * label fills, and the color-mix-derived accent-as-text) — and asserts the
 * conversion-critical colour pairs clear WCAG AA via the real ratio maths in
 * Lafka_Color_Contrast:
 *
 *   - body / secondary text on the page surface           (>= 4.5)
 *   - muted text on the busiest card surface              (>= 4.5, audited waiver)
 *   - accent-as-text on the page surface                  (>= 4.5)
 *   - button text on the accent fill (both on-accent      (>= 4.5)
 *     tokens the theme actually renders)
 *   - the focus-ring colour on the page surface           (>= 3.0, non-text 1.4.11)
 *   - NEW / SALE badge text on their fills                (>= 4.5)
 *
 * Audited waivers: a pair listed in a preset's contrast_exceptions is allowed to
 * miss AA, but only down to the AA-large floor (3.0) — a waiver can never
 * grandfather genuinely invisible text. Peppery's muted-on-card pair (4.40:1) is
 * the one shipped waiver, and it is genuinely exercised here.
 *
 * TEETH: presets/__fixtures__/lowcontrast (never registered — nested under
 * __fixtures__) must FAIL the gate; that failure is asserted via a data provider
 * that EXPECTS it, so the suite stays green while proving the gate rejects real
 * failures. peppery + midnight MUST pass, including a dark-surface audit for
 * midnight that the light-only FocusRingContrastTest cannot cover.
 *
 * The two whitelist-coverage gates (every tokens{} key in the token whitelist,
 * every chrome{} key in the chrome whitelist, the chrome whitelist == the
 * dynamic-css reads, critical keys a subset of the token whitelist) already live
 * in PresetSchemaTest, so they are not duplicated here.
 *
 * ISOLATION: minimal WP shims live in the GLOBAL namespace (guarded); sibling
 * test files declare the same shims, so this class runs in a SEPARATE PROCESS
 * with global state discarded. See docs/PRESET_ENGINE.md §9.
 *
 * @package Lafka\Tests
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook, $value = null, ...$rest ) {
			return $value;
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-color-contrast.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class PresetContrastTest extends TestCase {

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		// -----------------------------------------------------------------
		// Lafka_Color_Contrast — the WCAG maths (verify against a known pair)
		// -----------------------------------------------------------------

		/**
		 * The canonical calibration pair: #767676 on #fff is the WCAG AA boundary
		 * for normal text (~4.54:1). Locks the helper's luminance/ratio maths.
		 */
		public function test_known_pair_ratio(): void {
			$ratio = \Lafka_Color_Contrast::ratio( '#767676', '#ffffff' );
			$this->assertEqualsWithDelta( 4.54, $ratio, 0.02, '#767676 on #fff must be ~4.54:1' );
			$this->assertTrue( \Lafka_Color_Contrast::meets( '#767676', '#ffffff', 4.5 ), '#767676 on #fff must clear AA normal text' );
		}

		/** Luminance bounds + symmetry + shorthand/rgb parsing. */
		public function test_helper_luminance_and_parsing(): void {
			$this->assertEqualsWithDelta( 1.0, \Lafka_Color_Contrast::relative_luminance( '#ffffff' ), 0.0001 );
			$this->assertEqualsWithDelta( 0.0, \Lafka_Color_Contrast::relative_luminance( '#000000' ), 0.0001 );
			$this->assertEqualsWithDelta( 21.0, \Lafka_Color_Contrast::ratio( '#000', '#fff' ), 0.001, 'black/white must be 21:1' );
			$this->assertEqualsWithDelta( 21.0, \Lafka_Color_Contrast::ratio( '#fff', '#000' ), 0.001, 'ratio must be symmetric' );
			$this->assertEqualsWithDelta(
				\Lafka_Color_Contrast::relative_luminance( '#3366cc' ),
				\Lafka_Color_Contrast::relative_luminance( 'rgb(51, 102, 204)' ),
				0.0001,
				'hex and rgb() forms of the same colour must resolve identically'
			);
		}

		/** An unparseable colour is a hard error, never a silent 0-luminance. */
		public function test_helper_rejects_unparseable_colour(): void {
			$this->expectException( \InvalidArgumentException::class );
			\Lafka_Color_Contrast::relative_luminance( 'not-a-colour' );
		}

		// -----------------------------------------------------------------
		// Every registered preset must clear AA (honouring audited waivers)
		// -----------------------------------------------------------------

		/** @return array<string,array{0:string}> slug => [slug] over discovered presets. */
		public static function registeredPresetProvider(): array {
			$out = array();
			foreach ( \Lafka_Presets::from_dirs( array( self::root() . '/presets' ) )->slugs() as $slug ) {
				$out[ $slug ] = array( $slug );
			}
			return $out;
		}

		#[DataProvider( 'registeredPresetProvider' )]
		public function test_registered_preset_meets_aa( string $slug ): void {
			$preset = \Lafka_Presets::from_dirs( array( self::root() . '/presets' ) )->get( $slug );
			$this->assertInstanceOf( \Lafka_Preset::class, $preset, "preset '{$slug}' must be registered" );

			$failures = $this->unwaived_failures( $preset );
			$this->assertSame(
				array(),
				$failures,
				"preset '{$slug}' has WCAG AA contrast failures (pair => ratio): " . $this->describe( $failures )
			);
		}

		/** peppery + midnight are the shipped acceptance set — both must be present + pass. */
		public function test_shipped_presets_are_registered(): void {
			$slugs = array_keys( self::registeredPresetProvider() );
			$this->assertContains( 'peppery', $slugs );
			$this->assertContains( 'midnight', $slugs );
		}

		/**
		 * Peppery's one shipped waiver must be doing REAL work: the muted-text-on-
		 * card pair genuinely misses AA (so the waiver is not decorative), the pair
		 * id is actually declared in contrast_exceptions, it still clears the
		 * AA-large floor, and the waiver makes the preset pass overall.
		 */
		public function test_peppery_muted_waiver_is_genuinely_exercised(): void {
			$peppery = \Lafka_Presets::from_dirs( array( self::root() . '/presets' ) )->get( 'peppery' );
			$pal     = $this->effective_palette( $peppery );
			$pairs   = $this->critical_pairs( $pal );

			$this->assertArrayHasKey( 'text-muted-on-surface', $pairs );
			[ $fg, $bg, $min ] = $pairs['text-muted-on-surface'];
			$ratio             = \Lafka_Color_Contrast::ratio( $fg, $bg );

			$this->assertFalse(
				\Lafka_Color_Contrast::meets( $fg, $bg, $min ),
				'peppery muted-on-card is expected to MISS AA (the waiver must be exercised, not decorative)'
			);
			$this->assertContains(
				'text-muted-on-surface',
				$peppery->contrast_exceptions(),
				'peppery must declare the text-muted-on-surface waiver it relies on'
			);
			$this->assertGreaterThanOrEqual(
				\Lafka_Color_Contrast::AA_LARGE,
				$ratio,
				'even the waived muted pair must clear the AA-large floor (3.0)'
			);
			$this->assertSame(
				array(),
				$this->unwaived_failures( $peppery ),
				'with its audited waiver applied, peppery must pass the gate overall'
			);
		}

		/**
		 * Dark-surface audit for midnight — the existing FocusRingContrastTest only
		 * checks the LIGHT hex. Here the whole dark palette is exercised: body text
		 * on the near-black surface AND button text on the neon accent fill (the
		 * pair that would be white-on-cyan without midnight's on-accent override).
		 */
		public function test_midnight_dark_surface_audit(): void {
			$midnight = \Lafka_Presets::from_dirs( array( self::root() . '/presets' ) )->get( 'midnight' );
			$this->assertTrue( $midnight->is_dark(), 'midnight must be a dark preset' );

			$pal = $this->effective_palette( $midnight );

			$this->assertTrue(
				\Lafka_Color_Contrast::meets( $pal['text-primary'], $pal['surface-page'], \Lafka_Color_Contrast::AA_NORMAL ),
				'midnight body text must clear AA on the dark page surface'
			);
			$this->assertTrue(
				\Lafka_Color_Contrast::meets( $pal['text-on-accent'], $pal['accent-500'], \Lafka_Color_Contrast::AA_NORMAL ),
				'midnight button text must clear AA on the neon accent fill (dark text on cyan, not white)'
			);
			$this->assertTrue(
				\Lafka_Color_Contrast::meets( $pal['accent-contrast'], $pal['accent-500'], \Lafka_Color_Contrast::AA_NORMAL ),
				'midnight accent-contrast must clear AA on the accent fill'
			);
			$this->assertSame( array(), $this->unwaived_failures( $midnight ), 'midnight must pass the whole gate' );
		}

		// -----------------------------------------------------------------
		// TEETH — the negative fixture must be rejected (expected failure)
		// -----------------------------------------------------------------

		/** @return array<string,array{0:string}> label => [path] of expected-FAIL fixtures. */
		public static function negativeFixtureProvider(): array {
			return array(
				'lowcontrast' => array( self::root() . '/presets/__fixtures__/lowcontrast/preset.json' ),
			);
		}

		#[DataProvider( 'negativeFixtureProvider' )]
		public function test_negative_fixture_is_rejected( string $path ): void {
			$this->assertFileExists( $path, 'the low-contrast negative fixture must exist' );
			$data          = json_decode( (string) file_get_contents( $path ), true );
			$this->assertIsArray( $data, 'the fixture must be valid JSON' );
			$data['slug']  = basename( dirname( $path ) );
			$preset        = new \Lafka_Preset( $data );

			$failures = $this->unwaived_failures( $preset );
			$this->assertNotEmpty(
				$failures,
				'the low-contrast fixture MUST fail the gate — otherwise the gate has no teeth'
			);
			$this->assertArrayHasKey(
				'body-text-on-surface',
				$failures,
				'the fixture fails specifically on body text vs the page surface'
			);
			$this->assertFalse(
				\Lafka_Presets::from_dirs( array( self::root() . '/presets' ) )->has( 'lowcontrast' ),
				'the fixture must NOT be discoverable as a registered preset (it is nested under __fixtures__)'
			);
		}

		/**
		 * A waiver must NOT rescue an egregious failure: a pair below the AA-large
		 * floor (3.0) stays failed even when its id is listed in contrast_exceptions.
		 */
		public function test_waiver_cannot_rescue_sub_floor_failure(): void {
			$preset = new \Lafka_Preset(
				array(
					'slug'                 => 'floor',
					'schema'               => 1,
					'dark'                 => false,
					'tokens'               => array(
						'--lafka-color-surface-muted' => '#ffffff',
						'--lafka-color-text-muted'    => '#cccccc', // ~1.6:1 on white — far below the 3.0 floor.
					),
					'contrast_exceptions'  => array( 'text-muted-on-surface' ),
				)
			);
			$failures = $this->unwaived_failures( $preset );
			$this->assertArrayHasKey(
				'text-muted-on-surface',
				$failures,
				'a waiver cannot grandfather a pair that falls below the AA-large floor'
			);
		}

		// -----------------------------------------------------------------
		// Effective-palette resolution + critical pairs + gate evaluation
		// -----------------------------------------------------------------

		/**
		 * Gate evaluation: returns the map of pair-id => ratio for every critical
		 * pair that fails AA and is NOT covered by an in-floor audited waiver.
		 * Empty array == the preset passes.
		 *
		 * @return array<string,float>
		 */
		private function unwaived_failures( \Lafka_Preset $preset ): array {
			$pal        = $this->effective_palette( $preset );
			$exceptions = $preset->contrast_exceptions();
			$failures   = array();

			foreach ( $this->critical_pairs( $pal ) as $id => $pair ) {
				[ $fg, $bg, $min ] = $pair;
				if ( \Lafka_Color_Contrast::meets( $fg, $bg, $min ) ) {
					continue;
				}
				$ratio = \Lafka_Color_Contrast::ratio( $fg, $bg );
				// An audited waiver forgives a sub-AA pair, but never one that also
				// falls below the AA-large floor (3.0).
				if ( in_array( $id, $exceptions, true ) && $ratio >= \Lafka_Color_Contrast::AA_LARGE ) {
					continue;
				}
				$failures[ $id ] = round( $ratio, 3 );
			}

			return $failures;
		}

		/**
		 * The conversion-critical foreground/background pairs, resolved to concrete
		 * colours, each with its required ratio (AA normal text = 4.5; focus ring is
		 * non-text = 3.0).
		 *
		 * @param array<string,string> $pal
		 * @return array<string,array{0:string,1:string,2:float}>
		 */
		private function critical_pairs( array $pal ): array {
			$aa    = \Lafka_Color_Contrast::AA_NORMAL;
			$large = \Lafka_Color_Contrast::AA_LARGE;

			return array(
				'body-text-on-surface'      => array( $pal['text-primary'], $pal['surface-page'], $aa ),
				'text-secondary-on-surface' => array( $pal['text-secondary'], $pal['surface-page'], $aa ),
				// Muted text sits on the busiest card/chip surface (surface-muted),
				// its worst realistic case — this is the pair Peppery grandfathers.
				'text-muted-on-surface'     => array( $pal['text-muted'], $pal['surface-muted'], $aa ),
				'accent-text-on-surface'    => array( $pal['accent-text'], $pal['surface-page'], $aa ),
				// Both on-accent tokens the theme renders as button text on the fill.
				'button-text-on-accent'     => array( $pal['accent-contrast'], $pal['accent-500'], $aa ),
				'on-accent-text-on-accent'  => array( $pal['text-on-accent'], $pal['accent-500'], $aa ),
				// Focus ring (solid accent-700 layer) vs the page surface — non-text.
				'focus-ring-on-surface'     => array( $pal['accent-700'], $pal['surface-page'], $large ),
				// NEW / SALE badges render white text on their fills.
				'badge-new-on-fill'         => array( '#ffffff', $pal['badge-new'], $aa ),
				'badge-sale-on-fill'        => array( '#ffffff', $pal['badge-sale'], $aa ),
			);
		}

		/**
		 * Resolve a preset's effective palette to the concrete colours the gate
		 * needs. base (:root, + dark scaffold if dark) <- PTL (whitelisted tokens)
		 * <- chrome (accent fill, label fills) <- derived accent-as-text.
		 *
		 * @return array<string,string>
		 */
		private function effective_palette( \Lafka_Preset $preset ): array {
			$base = $this->base_token_map();
			$map  = $base['light'];
			if ( $preset->is_dark() ) {
				$map = array_merge( $map, $base['dark'] ); // dark scaffold overlays base.
			}
			// PTL overlays — only whitelisted tokens reach CSS (mirror the emitter).
			foreach ( $preset->tokens() as $key => $value ) {
				if ( in_array( $key, LAFKA_PRESET_TOKEN_WHITELIST, true ) ) {
					$map[ $key ] = (string) $value;
				}
			}

			$chrome = $preset->chrome();
			$accent = isset( $chrome['lafka_accent_color'] )
				? (string) $chrome['lafka_accent_color']
				: ( $map['--lafka-color-accent-500'] ?? '#dc2626' );

			// accent-as-text is color-mix-derived (never a literal PTL token): the base
			// derivation DARKENS for light surfaces; the dark emitter LIGHTENS it.
			$accent_text = $preset->is_dark()
				? $this->mix( $accent, '#ffffff', 0.80 )  // color-mix(in srgb, accent 80%, #fff)
				: $this->mix( $accent, '#000000', 0.85 ); // color-mix(in srgb, accent 85%, #000)

			return array(
				'surface-page'    => $map['--lafka-color-surface-page'],
				'surface-muted'   => $map['--lafka-color-surface-muted'],
				'text-primary'    => $map['--lafka-color-text-primary'],
				'text-secondary'  => $map['--lafka-color-text-secondary'],
				'text-muted'      => $map['--lafka-color-text-muted'],
				'accent-500'      => $accent,
				'accent-700'      => $map['--lafka-color-accent-700'],
				'accent-contrast' => $map['--lafka-color-accent-contrast'],
				'text-on-accent'  => $map['--lafka-color-text-on-accent'],
				'accent-text'     => $accent_text,
				'badge-new'       => isset( $chrome['lafka_new_label_color'] )
					? (string) $chrome['lafka_new_label_color']
					: $this->shipped_chrome_default( 'lafka_new_label_color' ),
				'badge-sale'      => isset( $chrome['lafka_sale_label_color'] )
					? (string) $chrome['lafka_sale_label_color']
					: $this->shipped_chrome_default( 'lafka_sale_label_color' ),
			);
		}

		/**
		 * Parse the base :root and :root[data-theme="dark"] token blocks of
		 * styles/lafka-tokens.css into simple maps (--lafka-* => raw value string).
		 *
		 * @return array{light:array<string,string>,dark:array<string,string>}
		 */
		private function base_token_map(): array {
			$css = (string) file_get_contents( self::root() . '/styles/lafka-tokens.css' );
			return array(
				// ':root {' — the '\s*\{' guard excludes ':root[data-theme="dark"] {'.
				'light' => $this->parse_block( $css, '/:root\s*\{(.*?)\}/s' ),
				'dark'  => $this->parse_block( $css, '/:root\[data-theme="dark"\]\s*\{(.*?)\}/s' ),
			);
		}

		/**
		 * @return array<string,string> --lafka-* => trimmed value (first block only).
		 */
		private function parse_block( string $css, string $selector_regex ): array {
			if ( ! preg_match( $selector_regex, $css, $m ) ) {
				return array();
			}
			$out = array();
			if ( preg_match_all( '/(--lafka-[a-z0-9-]+)\s*:\s*([^;]+);/', $m[1], $decls, PREG_SET_ORDER ) ) {
				foreach ( $decls as $d ) {
					$out[ $d[1] ] = trim( $d[2] );
				}
			}
			return $out;
		}

		/**
		 * The shipped literal default a chrome key falls back to in dynamic-css.php
		 * (the second arg of its lafka_preset_default( 'key', '#literal' ) wrap) —
		 * parsed, not hard-coded, so a default change can't silently desync the gate.
		 */
		private function shipped_chrome_default( string $key ): string {
			$src = (string) file_get_contents( self::root() . '/styles/dynamic-css.php' );
			if ( preg_match( "/lafka_preset_default\(\s*'" . preg_quote( $key, '/' ) . "',\s*'([^']+)'/", $src, $m ) ) {
				return $m[1];
			}
			$this->fail( "could not parse the shipped default for chrome key '{$key}' from dynamic-css.php" );
		}

		/**
		 * sRGB color-mix(in srgb, $a $pa%, $b) → a #rrggbb string. Matches the
		 * theme's accent-text derivation (a linear per-channel blend).
		 */
		private function mix( string $a, string $b, float $pa ): string {
			$ca = \Lafka_Color_Contrast::parse( $a );
			$cb = \Lafka_Color_Contrast::parse( $b );
			$this->assertNotNull( $ca, "unparseable colour in mix(): {$a}" );
			$this->assertNotNull( $cb, "unparseable colour in mix(): {$b}" );
			return sprintf(
				'#%02x%02x%02x',
				(int) round( $ca[0] * $pa + $cb[0] * ( 1 - $pa ) ),
				(int) round( $ca[1] * $pa + $cb[1] * ( 1 - $pa ) ),
				(int) round( $ca[2] * $pa + $cb[2] * ( 1 - $pa ) )
			);
		}

		/** @param array<string,float> $failures */
		private function describe( array $failures ): string {
			$parts = array();
			foreach ( $failures as $id => $ratio ) {
				$parts[] = "{$id}={$ratio}:1";
			}
			return '' === implode( ', ', $parts ) ? '(none)' : implode( ', ', $parts );
		}
	}
}
