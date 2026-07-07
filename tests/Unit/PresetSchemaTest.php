<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use PHPUnit\Framework\Attributes\DataProvider;

	/**
	 * NX2-01 preset SCHEMA gate.
	 *
	 * Every shipped `presets/*​/preset.json` must:
	 *   - decode to a valid, schema-1 definition (Lafka_Preset::validate() clean);
	 *   - list only `tokens` keys in LAFKA_PRESET_TOKEN_WHITELIST;
	 *   - list only `chrome` keys in LAFKA_PRESET_CHROME_WHITELIST;
	 *   - carry no operator-fed / derived / structural / legacy-alias token.
	 *
	 * It also locks the two whitelists against drift: the chrome whitelist must
	 * be EXACTLY the set of `get_theme_mod( 'lafka_*' )` design-token reads in
	 * styles/dynamic-css.php (so a new chrome reader forces a whitelist update),
	 * and the critical-keys subset must live inside the token whitelist.
	 *
	 * @package Lafka\Tests
	 */
	final class PresetSchemaTest extends TestCase {

		private static function root(): string {
			return dirname( __DIR__, 2 );
		}

		public static function setUpBeforeClass(): void {
			require_once self::root() . '/incl/presets/lafka-preset-tokens.php';
			require_once self::root() . '/incl/presets/class-lafka-preset.php';
		}

		/** @return array<string,array{0:string,1:string}> label => [slug, path] */
		public static function shippedPresetProvider(): array {
			$out = array();
			foreach ( (array) glob( self::root() . '/presets/*/preset.json' ) as $file ) {
				$slug         = basename( dirname( $file ) );
				$out[ $slug ] = array( $slug, $file );
			}
			return $out;
		}

		public function test_at_least_peppery_is_shipped(): void {
			$slugs = array_keys( self::shippedPresetProvider() );
			$this->assertContains( 'peppery', $slugs, 'presets/peppery/preset.json must ship as preset #1.' );
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_preset_json_decodes( string $slug, string $path ): void {
			$data = json_decode( (string) file_get_contents( $path ), true );
			$this->assertIsArray( $data, "presets/{$slug}/preset.json is not valid JSON." );
			$this->assertSame( $slug, basename( dirname( $path ) ), 'directory name is the authoritative slug' );
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_preset_validates_clean( string $slug, string $path ): void {
			$data          = json_decode( (string) file_get_contents( $path ), true );
			$data['slug']  = $slug;
			$preset        = new \Lafka_Preset( (array) $data );
			$this->assertSame(
				array(),
				$preset->validate(),
				"presets/{$slug}/preset.json failed schema validation."
			);
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_tokens_all_whitelisted( string $slug, string $path ): void {
			$data   = json_decode( (string) file_get_contents( $path ), true );
			$tokens = isset( $data['tokens'] ) && is_array( $data['tokens'] ) ? $data['tokens'] : array();
			$this->assertIsArray( $tokens, "presets/{$slug}: tokens{} must be an object/array." );
			foreach ( array_keys( $tokens ) as $key ) {
				$this->assertContains(
					$key,
					LAFKA_PRESET_TOKEN_WHITELIST,
					"presets/{$slug}: token '{$key}' is not in LAFKA_PRESET_TOKEN_WHITELIST."
				);
			}
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_chrome_all_whitelisted( string $slug, string $path ): void {
			$data   = json_decode( (string) file_get_contents( $path ), true );
			$chrome = isset( $data['chrome'] ) && is_array( $data['chrome'] ) ? $data['chrome'] : array();
			$this->assertIsArray( $chrome, "presets/{$slug}: chrome{} must be an object/array." );
			foreach ( array_keys( $chrome ) as $key ) {
				$this->assertContains(
					$key,
					LAFKA_PRESET_CHROME_WHITELIST,
					"presets/{$slug}: chrome key '{$key}' is not in LAFKA_PRESET_CHROME_WHITELIST."
				);
			}
		}

		#[DataProvider( 'shippedPresetProvider' )]
		public function test_no_forbidden_tokens( string $slug, string $path ): void {
			$data   = json_decode( (string) file_get_contents( $path ), true );
			$tokens = isset( $data['tokens'] ) && is_array( $data['tokens'] ) ? $data['tokens'] : array();
			$forbidden = array(
				'--lafka-color-accent-500',
				'--lafka-color-brand-500',
				'--lafka-color-accent-text',
			);
			foreach ( $forbidden as $key ) {
				$this->assertArrayNotHasKey(
					$key,
					$tokens,
					"presets/{$slug}: '{$key}' is operator-fed/derived and FORBIDDEN in a preset's tokens{}."
				);
			}
		}

		/** The forbidden operator/derived colours must never enter the token whitelist. */
		public function test_whitelist_excludes_operator_and_derived_colours(): void {
			foreach ( array( '--lafka-color-accent-500', '--lafka-color-brand-500', '--lafka-color-accent-text' ) as $key ) {
				$this->assertNotContains(
					$key,
					LAFKA_PRESET_TOKEN_WHITELIST,
					"{$key} is operator-fed/derived and must NOT be in LAFKA_PRESET_TOKEN_WHITELIST."
				);
			}
		}

		/** The token whitelist must exclude structural + legacy-alias tokens. */
		public function test_whitelist_excludes_structural_tokens(): void {
			$banned = array(
				'--lafka-space-4',
				'--lafka-gap-card',
				'--lafka-z-header',
				'--lafka-container-max',
				'--lafka-gutter-mobile',
				'--lafka-header-h',
				'--lafka-tap-target',
				'--lafka-font-sans',       // legacy var() alias
				'--lafka-color-border-focus', // var() alias -> accent-500
			);
			foreach ( $banned as $key ) {
				$this->assertNotContains(
					$key,
					LAFKA_PRESET_TOKEN_WHITELIST,
					"{$key} is structural/alias and must NOT be in LAFKA_PRESET_TOKEN_WHITELIST."
				);
			}
		}

		/** LAFKA_PRESET_CRITICAL_KEYS must be a subset of the token whitelist. */
		public function test_critical_keys_are_whitelisted_tokens(): void {
			foreach ( LAFKA_PRESET_CRITICAL_KEYS as $key ) {
				$this->assertContains(
					$key,
					LAFKA_PRESET_TOKEN_WHITELIST,
					"critical key {$key} must also be in LAFKA_PRESET_TOKEN_WHITELIST."
				);
			}
		}

		/**
		 * The chrome whitelist must equal EXACTLY the `get_theme_mod( 'lafka_*' )`
		 * design-token reads in dynamic-css.php (h-loop expanded). This is the
		 * drift lock: adding a chrome reader without whitelisting it (or vice
		 * versa) fails here.
		 */
		public function test_chrome_whitelist_matches_dynamic_css_reads(): void {
			$src = (string) file_get_contents( self::root() . '/styles/dynamic-css.php' );
			preg_match_all( "/get_theme_mod\(\s*'(lafka_[a-z0-9_]+)'/", $src, $m );
			$keys = array_values( array_unique( $m[1] ) );

			// Expand the heading loop literal 'lafka_h' -> lafka_h1_font..h6_font,
			// and drop the non-design-token active-preset selector read.
			$keys = array_values( array_diff( $keys, array( 'lafka_h', 'lafka_active_preset' ) ) );
			for ( $i = 1; $i <= 6; $i++ ) {
				$keys[] = 'lafka_h' . $i . '_font';
			}
			sort( $keys );

			$whitelist = LAFKA_PRESET_CHROME_WHITELIST;
			sort( $whitelist );

			$this->assertSame(
				$keys,
				$whitelist,
				'LAFKA_PRESET_CHROME_WHITELIST drifted from the get_theme_mod(lafka_*) reads in dynamic-css.php.'
			);
		}
	}
}
