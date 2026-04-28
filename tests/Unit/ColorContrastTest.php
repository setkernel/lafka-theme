<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-A11Y-2 W2-T4 regression lock: WCAG AA contrast palette must be respected.
 */
final class ColorContrastTest extends TestCase {

    private string $style_css;
    private string $theme_json;

    protected function setUp(): void {
        parent::setUp();
        $this->style_css  = file_get_contents( dirname( __DIR__, 2 ) . '/style.css' );
        $this->theme_json = file_get_contents( dirname( __DIR__, 2 ) . '/theme.json' );
    }

    public function test_theme_json_text_muted_is_wcag_compliant(): void {
        // Find the "muted" or "text-muted" color entry, assert it's NOT one
        // of the failing values.
        $forbidden = [ '#999', '#999999', '#888', '#888888' ];
        $palette_section = '';
        if ( preg_match( '/"palette"\s*:\s*\[(.*?)\]\s*,/s', $this->theme_json, $m ) ) {
            $palette_section = $m[1];
        }
        $this->assertNotEmpty( $palette_section, 'theme.json palette section not found' );

        foreach ( $forbidden as $hex ) {
            $this->assertStringNotContainsString(
                '"color": "' . $hex . '"',
                $palette_section,
                "theme.json palette must not include $hex (fails WCAG 4.5:1)"
            );
        }
    }

    public function test_style_css_documents_a11y_color_changes(): void {
        // The fix should leave behind /* P6-A11Y-2: */ markers on each edited rule.
        $this->assertMatchesRegularExpression(
            '/\/\*\s*P6-A11Y-2/',
            $this->style_css,
            'style.css should document P6-A11Y-2 changes with comments'
        );
    }

    public function test_brand_orange_button_has_bold_weight(): void {
        // The orange CTA button must qualify as "large/bold text" via
        // font-weight: 700 (or higher).
        $this->assertMatchesRegularExpression(
            '/vc_btn3-style-custom[^}]*font-weight\s*:\s*(?:bold|700|800|900)/s',
            $this->style_css,
            'WPBakery custom button must use font-weight 700+ for AA 3:1 large-text contrast'
        );
    }
}
