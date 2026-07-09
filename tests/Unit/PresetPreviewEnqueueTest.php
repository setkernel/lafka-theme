<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX2-04 preview-side locks: the preview script must load only in the
 * preview iframe (customize_preview_init), carry every preset payload, and
 * swap the three server-emitted style blocks + data-theme — never compute
 * styles client-side. Accent/brand ride postMessage onto the exact custom
 * properties dynamic-css emits.
 */
final class PresetPreviewEnqueueTest extends TestCase {

	private string $customizer;
	private string $js;

	protected function setUp(): void {
		parent::setUp();
		$root             = dirname( __DIR__, 2 );
		$this->customizer = (string) file_get_contents( $root . '/incl/presets/lafka-preset-customizer.php' );
		$this->js         = (string) file_get_contents( $root . '/assets/customizer/lafka-preset-preview.js' );
	}

	public function test_preview_script_is_preview_scoped_and_localized(): void {
		$this->assertStringContainsString( "add_action( 'customize_preview_init'", $this->customizer );
		$this->assertStringContainsString( 'lafka_preset_preview_payloads()', $this->customizer );
		$this->assertStringContainsString( "'lafkaPresetPreview'", $this->customizer );
		$this->assertStringContainsString( "array( 'customize-preview' )", $this->customizer, 'Must depend on customize-preview.' );
	}

	public function test_all_pool_fonts_preload_in_preview_only(): void {
		$this->assertStringContainsString( 'lafka_preset_preview_preload_fonts', $this->customizer );
		$this->assertStringContainsString( 'is_customize_preview()', $this->customizer, 'Font preloading must never reach a normal front-end render (iron gate).' );
	}

	public function test_js_swaps_the_three_style_blocks_and_data_theme(): void {
		$this->assertStringContainsString( 'lafka-preset-inline-css', $this->js );
		$this->assertStringContainsString( 'lafka-preset-fonts-inline-css', $this->js );
		$this->assertStringContainsString( 'lafka-style-inline-css', $this->js );
		$this->assertStringContainsString( "setAttribute( 'data-theme', 'dark' )", $this->js );
		$this->assertStringContainsString( "removeAttribute( 'data-theme' )", $this->js );
	}

	public function test_js_binds_accent_and_brand_to_the_emitted_vars(): void {
		foreach ( array( 'lafka_accent_color', 'lafka_brand_color', '--lafka-color-accent-500', '--lafka-accent-color', '--lafka-color-brand-500' ) as $needle ) {
			$this->assertStringContainsString( $needle, $this->js );
		}
	}
}
