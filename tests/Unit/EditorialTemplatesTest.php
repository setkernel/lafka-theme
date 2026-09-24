<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-UX-1 + P6-UX-4 W3-T8 regression lock: the editorial templates must stay
 * selectable via the Template dropdown, ship their stylesheet + self-hosted
 * Fraunces files, read NAP/hours from the restaurant-info helper, and stay
 * free of operator-specific literals.
 *
 * Customizer registration and the template-gated enqueue are locked by
 * EditorialCustomizerTest.
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

    public function test_no_hardcoded_peppery_strings(): void {
        // OSS-safety: per the architectural feedback, no Peppery-specific strings
        // should appear in the OSS code. Operator content flows through Customizer.
        // This also globs js/*.js so brand-namespaced literals (e.g. a
        // 'peppery.fulfilment' localStorage key) can't silently ship in the
        // public theme's scripts.
        // NB: this scan must NOT include tests/ — the test body itself contains
        // 'Peppery' / 'Sackville Drive' as the banned literals to assert against.
        $files_to_check = array_merge(
            glob( $this->theme_dir . '/page_templates/template-editorial-*.php' ) ?: array(),
            glob( $this->theme_dir . '/partials/editorial-*.php' ) ?: array(),
            glob( $this->theme_dir . '/incl/customizer-editorial.php' ) ?: array(),
            glob( $this->theme_dir . '/styles/editorial.css' ) ?: array(),
            glob( $this->theme_dir . '/js/*.js' ) ?: array()
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
            // Case-insensitive so it also catches the lowercase brand
            // namespace that leaked into the JS controllers (the
            // 'peppery.fulfilment' localStorage key), not just the
            // capitalised display name.
            $this->assertStringNotContainsStringIgnoringCase(
                'peppery',
                $contents,
                "$f contains the hardcoded 'peppery' brand namespace — should come from get_bloginfo('name'), Customizer, or a brand-neutral key/filter"
            );
        }
    }
}
