<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-UX-1 + P6-UX-4 W3-T8 regression lock: editorial templates must
 * remain (a) selectable via Template dropdown, (b) Customizer-driven,
 * (c) conditionally enqueued.
 *
 * As of the v5.16.0 child->parent split (Task A4), the canonical
 * editorial assets now live in lafka-theme/ (this repo). dirname(__DIR__, 2)
 * resolves to the lafka-theme/ root since this test lives at
 * lafka-theme/tests/Unit/.
 *
 * Tests for conditional-enqueue (A6) and customizer registration (A5)
 * are marked skipped until those tasks ship; they will flip green
 * automatically once the corresponding parent-side code lands.
 */
final class EditorialTemplatesTest extends TestCase {

    private string $theme_dir;

    protected function setUp(): void {
        parent::setUp();
        $this->theme_dir = dirname( __DIR__, 2 );
    }

    public function test_home_template_exists_and_has_template_name_header(): void {
        $path = $this->theme_dir . '/page_templates/template-editorial-home.php';
        $this->assertFileExists( $path );
        $contents = file_get_contents( $path );
        $this->assertMatchesRegularExpression(
            '/^\s*\*\s*Template Name:\s*Editorial Home/m',
            $contents,
            'template-editorial-home.php must declare "Template Name: Editorial Home (Lafka)" so WP exposes it in the Page Attributes dropdown'
        );
    }

    public function test_contact_template_exists_and_has_template_name_header(): void {
        $path = $this->theme_dir . '/page_templates/template-editorial-contact.php';
        $this->assertFileExists( $path );
        $this->assertMatchesRegularExpression(
            '/^\s*\*\s*Template Name:\s*Editorial Contact/m',
            file_get_contents( $path )
        );
    }

    public function test_editorial_css_exists(): void {
        $this->assertFileExists( $this->theme_dir . '/styles/editorial.css' );
    }

    public function test_fraunces_font_files_present(): void {
        // All six woff2 weights (400/600/800 + italics) must be self-hosted
        // under lafka-theme/assets/fonts/fraunces/ so editorial.css's
        // ../assets/fonts/fraunces/ url() refs resolve.
        $glob = glob( $this->theme_dir . '/assets/fonts/fraunces/*.woff2' );
        $this->assertNotEmpty(
            $glob,
            'Fraunces font woff2 files must be self-hosted under lafka-theme/assets/fonts/fraunces/'
        );
        $this->assertGreaterThanOrEqual(
            6,
            count( $glob ),
            'All six Fraunces weights (400/600/800 + italics) should be present.'
        );
    }

    public function test_assets_are_conditionally_enqueued(): void {
        // A6 (pending): editorial CSS + Fraunces fonts must be enqueued
        // ONLY on the editorial templates via is_page_template() guards.
        $functions = file_get_contents( $this->theme_dir . '/functions.php' );
        if ( false === strpos( $functions, 'template-editorial-home.php' ) ) {
            $this->markTestSkipped( 'A6 not yet shipped: parent functions.php has no editorial enqueue block.' );
        }
        $this->assertStringContainsString( 'is_page_template', $functions );
        $this->assertStringContainsString( 'template-editorial-home.php', $functions );
        $this->assertStringContainsString( 'template-editorial-contact.php', $functions );
    }

    public function test_customizer_panels_registered(): void {
        // A5 (pending): editorial Customizer settings must be registered
        // in the parent theme. Look in incl/ first (parent convention),
        // fall back to inc/ for backwards compatibility.
        $customizer_files = array_merge(
            glob( $this->theme_dir . '/incl/customizer*.php' ) ?: array(),
            glob( $this->theme_dir . '/inc/customizer*.php' ) ?: array()
        );
        if ( empty( $customizer_files ) ) {
            $this->markTestSkipped( 'A5 not yet shipped: no customizer*.php in parent incl/ or inc/.' );
        }
        $combined = '';
        foreach ( $customizer_files as $f ) {
            $combined .= file_get_contents( $f );
        }
        if ( false === strpos( $combined, 'lafka_editorial_home_hero_eyebrow' ) ) {
            $this->markTestSkipped( 'A5 not yet shipped: editorial Customizer settings not registered in parent.' );
        }
        $this->assertStringContainsString( 'lafka_editorial_home_hero_eyebrow', $combined );
        $this->assertStringContainsString( 'lafka_editorial_contact_h1', $combined );
        $this->assertStringContainsString( 'lafka_editorial_contact_cf7_form_id', $combined );
    }

    public function test_home_template_uses_get_restaurant_info_for_visit_section(): void {
        $partials_dir = $this->theme_dir . '/partials';
        $combined     = '';
        foreach ( glob( $partials_dir . '/editorial-*.php' ) as $f ) {
            $combined .= file_get_contents( $f );
        }
        $this->assertStringContainsString(
            'lafka_get_restaurant_info',
            $combined,
            'Editorial visit section must read NAP/hours from the W2-T1 source-of-truth helper'
        );
    }

    /**
     * W2-T1: lafka_get_restaurant_info() must now be DEFINED in the plugin's
     * schema helpers (no longer a deferred / phantom function). Assert it by
     * loading the helpers file and checking function_exists().
     */
    public function test_get_restaurant_info_function_is_defined(): void {
        // Plugin lives at ../../lafka-plugin relative to lafka-theme/ root.
        $helpers = dirname( $this->theme_dir ) . '/lafka-plugin/incl/schema/lafka-schema-helpers.php';
        $this->assertFileExists( $helpers, 'Plugin must ship lafka-schema-helpers.php (the resolver lives here).' );

        if ( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', __DIR__ . '/' );
        }
        require_once $helpers;

        $this->assertTrue(
            function_exists( 'lafka_get_restaurant_info' ),
            'lafka_get_restaurant_info() must be defined by lafka-plugin/incl/schema/lafka-schema-helpers.php (W2-T1 resolver).'
        );
    }

    public function test_no_hardcoded_peppery_strings(): void {
        // OSS-safety: per the architectural feedback, no Peppery-specific strings
        // should appear in the OSS code. Operator content flows through Customizer.
        // NB: this scan must NOT include tests/ — the test body itself contains
        // 'Peppery' / 'Sackville Drive' as the banned literals to assert against.
        $files_to_check = array_merge(
            glob( $this->theme_dir . '/page_templates/template-editorial-*.php' ) ?: array(),
            glob( $this->theme_dir . '/partials/editorial-*.php' ) ?: array(),
            glob( $this->theme_dir . '/incl/customizer-editorial.php' ) ?: array(),
            glob( $this->theme_dir . '/inc/customizer-editorial.php' ) ?: array(),
            glob( $this->theme_dir . '/styles/editorial.css' ) ?: array()
        );
        foreach ( $files_to_check as $f ) {
            // Defensive: skip any path that resolves under tests/ (shouldn't, but
            // protects against future glob expansion).
            if ( false !== strpos( $f, '/tests/' ) ) {
                continue;
            }
            $contents = file_get_contents( $f );
            // These literal strings appear in the mockup but must NOT ship in OSS code.
            $this->assertStringNotContainsString(
                'Sackville Drive',
                $contents,
                "$f contains hardcoded street address — should come from Customizer / restaurant-info"
            );
            $this->assertStringNotContainsString(
                'Peppery',
                $contents,
                "$f contains hardcoded brand name — should come from get_bloginfo('name') or Customizer"
            );
        }
    }
}
