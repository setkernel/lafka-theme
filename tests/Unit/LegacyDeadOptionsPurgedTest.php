<?php
/**
 * LegacyDeadOptionsPurgedTest — NX1-02 framework-retirement regression guard.
 *
 * The NX1-02.dead-purge slice removed 48 unread legacy field registrations plus
 * the orphaned partials/social-profiles.php. The final NX1-02 retire phase then
 * deleted the entire Options-Framework admin panel — its field registry
 * (lafka-options.php, where those dead keys lived), the interface/sanitize
 * layers, and the Settings-API save/validate handler that was the "saving the
 * panel rebuilds the whole `lafka` array" hazard.
 *
 * This source-level guard locks that retirement: the registry file can never
 * re-register a dead key because the file itself is gone, the rebuild-hazard
 * register_setting() call is gone, and the orphaned social partial stays
 * deleted. The two genuinely-used helpers (the Google-font list + the media
 * library uploader) must SURVIVE, so their absence would also fail here.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 6.21.0 (NX1-02 dead-purge); 7.0.0 (framework retirement)
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LegacyDeadOptionsPurgedTest extends TestCase {

	private function theme_root(): string {
		return dirname( __DIR__, 2 );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function retiredFileProvider(): array {
		$files = array(
			// The field registry (where the 48 dead keys lived) — deleting it
			// makes re-registering any of them impossible by construction.
			'incl/lafka-options-framework/lafka-options.php',
			// The admin panel: menu page, enqueue, tabs/fields renderer.
			'incl/lafka-options-framework/lafka-options-framework.php',
			'incl/lafka-options-framework/lafka-options-interface.php',
			// The Settings-API sanitize layer used only by the panel/validate.
			'incl/lafka-options-framework/lafka-options-sanitize.php',
			// The v6.1 menu-hide/redirect shim, redundant once the menu is gone.
			'incl/customizer-bridge-deprecate-theme-options.php',
			// The orphaned (never-included) reader of the dead *_profile keys.
			'partials/social-profiles.php',
		);
		$provider = array();
		foreach ( $files as $file ) {
			$provider[ $file ] = array( $file );
		}
		return $provider;
	}

	#[DataProvider('retiredFileProvider')]
	public function test_retired_framework_file_is_deleted( string $relative ): void {
		$this->assertFileDoesNotExist(
			$this->theme_root() . '/' . $relative,
			"NX1-02 retired '{$relative}' — it must stay deleted."
		);
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function survivingHelperProvider(): array {
		$files = array(
			// Google-font list helper — read by the front-end font enqueuer.
			'incl/lafka-options-framework/lafka-options-functions.php',
			// Media Library uploader — used by the mega-menu editor.
			'incl/lafka-options-framework/lafka-options-medialibrary-uploader.php',
			// The slim successor to the registry defaults (plugin-owned keys).
			'incl/system/lafka-option-defaults.php',
		);
		$provider = array();
		foreach ( $files as $file ) {
			$provider[ $file ] = array( $file );
		}
		return $provider;
	}

	#[DataProvider('survivingHelperProvider')]
	public function test_surviving_helper_file_still_exists( string $relative ): void {
		$this->assertFileExists(
			$this->theme_root() . '/' . $relative,
			"'{$relative}' is still consumed by live code and must survive the retirement."
		);
	}

	public function test_settings_api_rebuild_hazard_is_gone(): void {
		// The "saving the panel rebuilds the whole `lafka` array from registered
		// fields, clobbering plugin-written keys" hazard lived in the framework's
		// register_setting( 'lafka-optionsframework', 'lafka', ... ) validate call.
		// No theme source may register it any more.
		$hits = array();
		$it   = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->theme_root(), \FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( 'php' !== strtolower( $file->getExtension() ) ) {
				continue;
			}
			$path = $file->getPathname();
			if ( str_contains( $path, '/tests/' ) || str_contains( $path, '/vendor/' ) ) {
				continue;
			}
			$src = (string) file_get_contents( $path );
			if ( preg_match( "/register_setting\(\s*'lafka-optionsframework'/", $src ) ) {
				$hits[] = $path;
			}
		}
		$this->assertSame(
			array(),
			$hits,
			"The Options-Framework register_setting() rebuild hazard is still present in: \n" . implode( "\n", $hits )
		);
	}
}
