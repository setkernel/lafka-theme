<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Audit #97 (theme half): every "browse the menu" CTA in the theme must resolve
 * the canonical /menu/ URL through ONE guarded helper — lafka_theme_menu_url()
 * in incl/template-helpers/menu-url.php — rather than repeating the
 * `function_exists( 'lafka_get_menu_url' ) ? … : home_url( '/menu/' )` ternary
 * (and the bare home_url( '/menu/' ) literal) at each call site.
 *
 * The helper delegates to the plugin's lafka_get_menu_url() SSOT when present
 * and otherwise falls back to trailingslashit( home_url( '/menu/' ) ).
 *
 * This test locks the helper shape AND scans the theme so no bare
 * home_url( '/menu/' ) literal survives anywhere except inside the helper.
 */
final class ThemeMenuUrlTest extends TestCase {

	private const HELPER_REL = 'incl/template-helpers/menu-url.php';

	private static function theme_root(): string {
		return dirname( __DIR__, 2 );
	}

	private static function helper_src(): string {
		return (string) file_get_contents( self::theme_root() . '/' . self::HELPER_REL );
	}

	public function test_helper_file_exists_and_defines_resolver(): void {
		$this->assertFileExists( self::theme_root() . '/' . self::HELPER_REL );
		$this->assertStringContainsString(
			'function lafka_theme_menu_url',
			self::helper_src(),
			'The theme must expose a single lafka_theme_menu_url() resolver.'
		);
	}

	public function test_helper_delegates_to_plugin_resolver_when_present(): void {
		$src = self::helper_src();
		$this->assertStringContainsString(
			"function_exists( 'lafka_get_menu_url' )",
			$src,
			'The helper must prefer the plugin SSOT lafka_get_menu_url() when it is loaded.'
		);
		$this->assertMatchesRegularExpression(
			'/return\s+\(string\)\s+lafka_get_menu_url\(\)/',
			$src,
			'The helper must return the plugin resolver value when present.'
		);
	}

	public function test_helper_fallback_targets_the_menu_page(): void {
		$this->assertStringContainsString(
			"trailingslashit( home_url( '/menu/' ) )",
			self::helper_src(),
			'The plugin-absent fallback must resolve trailingslashit( home_url( \'/menu/\' ) ).'
		);
	}

	public function test_no_bare_menu_url_literals_outside_the_helper(): void {
		$offenders = array();
		foreach ( self::theme_php_files() as $file ) {
			$rel = ltrim( str_replace( self::theme_root(), '', $file ), '/' );
			if ( self::HELPER_REL === $rel ) {
				continue; // The ONE sanctioned home_url( '/menu/' ) site.
			}
			$contents = (string) file_get_contents( $file );
			if ( preg_match( "#home_url\(\s*['\"]/menu/['\"]\s*\)#", $contents ) ) {
				$offenders[] = $rel;
			}
		}
		$this->assertSame(
			array(),
			$offenders,
			"Bare home_url( '/menu/' ) literals must route through lafka_theme_menu_url(); offenders: " . implode( ', ', $offenders )
		);
	}

	/**
	 * All theme-owned PHP files (excluding vendor, node_modules, tests, .git).
	 *
	 * @return string[]
	 */
	private static function theme_php_files(): array {
		$root = self::theme_root();
		$skip = array(
			$root . '/vendor',
			$root . '/node_modules',
			$root . '/tests',
			$root . '/.git',
		);

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveCallbackFilterIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
				static function ( $current ) use ( $skip ): bool {
					$path = $current->getPathname();
					foreach ( $skip as $dir ) {
						if ( $path === $dir || 0 === strpos( $path, $dir . '/' ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$files = array();
		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}
		return $files;
	}
}
