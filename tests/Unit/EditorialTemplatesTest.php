<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-UX-1 + P6-UX-4 W3-T8 regression lock: editorial templates must
 * remain (a) selectable via Template dropdown, (b) Customizer-driven,
 * (c) conditionally enqueued.
 *
 * Tests run from lafka-theme/ but path-traverse into lafka-child/ via
 * dirname(__DIR__, 3) which resolves to the shared parent of both repos.
 */
final class EditorialTemplatesTest extends TestCase {

    private string $child_dir;

    protected function setUp(): void {
        parent::setUp();
        $this->child_dir = dirname( __DIR__, 3 ) . '/lafka-child';
    }

    public function test_home_template_exists_and_has_template_name_header(): void {
        $path = $this->child_dir . '/page-templates/template-editorial-home.php';
        $this->assertFileExists( $path );
        $contents = file_get_contents( $path );
        $this->assertMatchesRegularExpression(
            '/^\s*\*\s*Template Name:\s*Editorial Home/m',
            $contents,
            'template-editorial-home.php must declare "Template Name: Editorial Home (Lafka)" so WP exposes it in the Page Attributes dropdown'
        );
    }

    public function test_contact_template_exists_and_has_template_name_header(): void {
        $path = $this->child_dir . '/page-templates/template-editorial-contact.php';
        $this->assertFileExists( $path );
        $this->assertMatchesRegularExpression(
            '/^\s*\*\s*Template Name:\s*Editorial Contact/m',
            file_get_contents( $path )
        );
    }

    public function test_editorial_css_exists(): void {
        $this->assertFileExists( $this->child_dir . '/styles/editorial.css' );
    }

    public function test_fraunces_font_files_present(): void {
        // At least one woff2 must be present
        $glob = glob( $this->child_dir . '/assets/fonts/fraunces/*.woff2' );
        $this->assertNotEmpty(
            $glob,
            'Fraunces font woff2 files must be self-hosted under lafka-child/assets/fonts/fraunces/'
        );
    }

    public function test_assets_are_conditionally_enqueued(): void {
        $functions = file_get_contents( $this->child_dir . '/functions.php' );
        $this->assertStringContainsString( 'is_page_template', $functions );
        $this->assertStringContainsString( 'template-editorial-home.php', $functions );
        $this->assertStringContainsString( 'template-editorial-contact.php', $functions );
    }

    public function test_customizer_panels_registered(): void {
        // Look at lafka-child's customizer file (path may vary; locate it)
        $customizer_files = glob( $this->child_dir . '/inc/customizer*.php' );
        $this->assertNotEmpty(
            $customizer_files,
            'Customizer file for editorial panels must exist in lafka-child/inc/'
        );
        $combined = '';
        foreach ( $customizer_files as $f ) {
            $combined .= file_get_contents( $f );
        }
        $this->assertStringContainsString( 'lafka_editorial_home_hero_eyebrow', $combined );
        $this->assertStringContainsString( 'lafka_editorial_contact_h1', $combined );
        $this->assertStringContainsString( 'lafka_editorial_contact_cf7_form_id', $combined );
    }

    public function test_home_template_uses_get_restaurant_info_for_visit_section(): void {
        $partials_dir = $this->child_dir . '/partials';
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

    public function test_no_hardcoded_peppery_strings(): void {
        // OSS-safety: per the architectural feedback, no Peppery-specific strings
        // should appear in the OSS code. Operator content flows through Customizer.
        $files_to_check = array_merge(
            glob( $this->child_dir . '/page-templates/template-editorial-*.php' ) ?: array(),
            glob( $this->child_dir . '/partials/editorial-*.php' ) ?: array(),
            glob( $this->child_dir . '/inc/customizer-editorial.php' ) ?: array(),
            glob( $this->child_dir . '/styles/editorial.css' ) ?: array()
        );
        foreach ( $files_to_check as $f ) {
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
