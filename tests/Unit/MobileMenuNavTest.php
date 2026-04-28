<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-A11Y-3 W2-T3 regression lock: mobile menu tab widget must use correct
 * ARIA semantics.  The mobile menu IS a real tab widget (Menu / My Account
 * panels switch on click), so role="tablist" and role="tab" are correct —
 * but role="tab" must be on the focusable <a>, NOT on the <li>.
 *
 * Lighthouse violations fixed:
 *   • aria-required-children: <ul role="tablist"> must contain [role="tab"]
 *     elements — satisfied by placing role="tab" on <a> inside each <li>.
 *   • listitem: <li> inside a tablist must not itself carry role="tab" —
 *     fixed by moving the role to the <a>.
 */
final class MobileMenuNavTest extends TestCase {

    private string $functions;

    protected function setUp(): void {
        parent::setUp();
        $this->functions = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
    }

    /** The <ul> must declare role="tablist" for the widget container. */
    public function test_mobile_menu_ul_has_role_tablist(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<ul[^>]+class="lafka-mobile-menu-tabs"[^>]+role="tablist"/',
            $body,
            '<ul class="lafka-mobile-menu-tabs"> must carry role="tablist"'
        );
    }

    /** The tablist <ul> must carry an aria-label for landmark purposes. */
    public function test_mobile_menu_ul_has_aria_label(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<ul[^>]+class="lafka-mobile-menu-tabs"[^>]+aria-label=/',
            $body,
            '<ul class="lafka-mobile-menu-tabs"> must carry aria-label'
        );
    }

    /** role="tab" must appear on <a> elements, NOT on <li> elements.
     *  Lighthouse aria-required-children looks down the tree, so role="tab"
     *  on an <a> inside an <li> satisfies the tablist requirement. */
    public function test_role_tab_on_anchor_not_li(): void {
        $body = $this->get_function_body();

        // <a ...role="tab"...> must exist
        $this->assertMatchesRegularExpression(
            '/<a\b[^>]+role="tab"/',
            $body,
            'role="tab" must be on an <a> element'
        );

        // <li ...role="tab"...> must NOT exist
        $this->assertDoesNotMatchRegularExpression(
            '/<li\b[^>]+role="tab"/',
            $body,
            'role="tab" must NOT be placed on a <li> element'
        );
    }

    /** Tab <a> elements must carry aria-controls pointing to a panel. */
    public function test_tab_anchors_have_aria_controls(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<a\b[^>]+role="tab"[^>]+aria-controls=/',
            $body,
            'role="tab" <a> elements must have aria-controls'
        );
    }

    /** Tab <a> elements must carry aria-selected. */
    public function test_tab_anchors_have_aria_selected(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<a\b[^>]+role="tab"[^>]+aria-selected=/',
            $body,
            'role="tab" <a> elements must have aria-selected'
        );
    }

    /** Panel divs must declare role="tabpanel". */
    public function test_panel_divs_have_role_tabpanel(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<div\b[^>]+id="lafka_mobile_menu_tab"[^>]+role="tabpanel"/',
            $body,
            '#lafka_mobile_menu_tab must carry role="tabpanel"'
        );
    }

    /** Panel divs must declare aria-labelledby linking back to a tab. */
    public function test_panel_divs_have_aria_labelledby(): void {
        $body = $this->get_function_body();
        $this->assertMatchesRegularExpression(
            '/<div\b[^>]+id="lafka_mobile_menu_tab"[^>]+aria-labelledby=/',
            $body,
            '#lafka_mobile_menu_tab must carry aria-labelledby'
        );
    }

    // ------------------------------------------------------------------

    private function get_function_body(): string {
        $body = $this->extract_function_body( 'lafka_build_mobile_menu_items_wrap' );
        if ( '' === $body ) {
            $body = $this->extract_function_body( 'lafka_top_mobile_menu' );
        }
        $this->assertNotEmpty( $body, 'Mobile menu builder function not found — adjust test name' );
        return $body;
    }

    private function extract_function_body( string $function_name ): string {
        if ( ! preg_match(
            '/function\s+' . preg_quote( $function_name, '/' ) . '\s*\([^)]*\)\s*\{/',
            $this->functions,
            $m,
            PREG_OFFSET_CAPTURE
        ) ) {
            return '';
        }
        $start = $m[0][1] + strlen( $m[0][0] );
        $depth = 1;
        $i     = $start;
        $len   = strlen( $this->functions );
        while ( $i < $len && $depth > 0 ) {
            $c = $this->functions[ $i ];
            if ( $c === '{' ) {
                $depth++;
            } elseif ( $c === '}' ) {
                $depth--;
            }
            $i++;
        }
        return substr( $this->functions, $start, $i - $start - 1 );
    }
}
