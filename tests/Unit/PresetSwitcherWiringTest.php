<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX2-04 wiring locks: the Design Preset section must sit at the TOP of the
 * Lafka — Site Settings panel, bind the lafka_active_preset theme_mod with
 * postMessage transport + registry-backed sanitization, and the control must
 * degrade to accent/brand swatches when a preset ships no preview.jpg.
 */
final class PresetSwitcherWiringTest extends TestCase {

	private string $customizer;
	private string $control;

	protected function setUp(): void {
		parent::setUp();
		$root             = dirname( __DIR__, 2 );
		$this->customizer = (string) file_get_contents( $root . '/incl/presets/lafka-preset-customizer.php' );
		$this->control    = (string) file_get_contents( $root . '/incl/presets/class-lafka-customize-preset-control.php' );
	}

	public function test_setting_uses_postmessage_and_registry_sanitizer(): void {
		$this->assertStringContainsString( "'lafka_active_preset'", $this->customizer );
		$this->assertStringContainsString( "'transport'         => 'postMessage'", $this->customizer );
		$this->assertStringContainsString( "'sanitize_callback' => 'lafka_sanitize_preset_slug'", $this->customizer );
		$this->assertStringContainsString( "'default'           => 'peppery'", $this->customizer );
	}

	public function test_section_tops_the_site_settings_panel(): void {
		$this->assertStringContainsString( "'lafka_design_preset'", $this->customizer );
		$this->assertStringContainsString( "'panel'    => 'lafka_settings'", $this->customizer );
		$this->assertStringContainsString( "'priority' => 5,", $this->customizer, 'Must sort above the Logos section (priority 10).' );
	}

	public function test_control_renders_thumbnails_with_swatch_fallback(): void {
		$this->assertStringContainsString( 'preview.jpg', $this->control );
		$this->assertStringContainsString( 'file_exists', $this->control, 'Missing preview.jpg must not render a broken <img>.' );
		$this->assertStringContainsString( 'lafka-preset-card__swatch', $this->control, 'Fallback swatch markup required for image-less (e.g. 3rd-party) presets.' );
		$this->assertStringContainsString( 'esc_attr', $this->control );
		$this->assertStringContainsString( 'checked(', $this->control );
	}

	public function test_controls_css_is_enqueued_on_the_controls_screen_only(): void {
		$this->assertStringContainsString( "add_action( 'customize_controls_enqueue_scripts'", $this->customizer );
		$this->assertStringContainsString( 'lafka-preset-control.css', $this->customizer );
	}
}
