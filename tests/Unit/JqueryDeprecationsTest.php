<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-7 W3-T7 regression lock: lafka first-party JS must remain
 * jQuery-Migrate-clean. Catches reintroduction of the deprecated patterns.
 */
final class JqueryDeprecationsTest extends TestCase {

    private array $files;

    protected function setUp(): void {
        parent::setUp();
        $this->files = array_filter( array_map( 'file_get_contents', array(
            dirname( __DIR__, 2 ) . '/js/lafka-front.js',
            dirname( __DIR__, 2 ) . '/js/lafka-libs-config.js',
        ) ) );
    }

    public function test_no_blur_focus_resize_event_shorthands(): void {
        foreach ( $this->files as $i => $js ) {
            // .blur(function ...) / .focus(function ...) / .resize(function ...) — event handlers
            $this->assertDoesNotMatchRegularExpression(
                '/\.(blur|focus|resize)\(\s*function/',
                $js,
                "File index $i: deprecated jQuery event-shorthand handler found"
            );
        }
    }

    public function test_no_jquery_type_calls(): void {
        foreach ( $this->files as $i => $js ) {
            $this->assertDoesNotMatchRegularExpression(
                '/(jQuery|\$)\.type\(/',
                $js,
                "File index $i: deprecated jQuery.type() call found"
            );
        }
    }

    public function test_window_on_load_uses_readystate_guard(): void {
        foreach ( $this->files as $i => $js ) {
            // Allow $(window).on('load', ...) ONLY if there's a nearby
            // readyState guard. Simple heuristic: if the file has the pattern,
            // it must also reference 'readyState' OR have a P6-PERF-7 marker.
            if ( preg_match( "/\(window\)\.on\(\s*['\"]load['\"]/", $js ) ) {
                $has_guard  = false !== strpos( $js, 'readyState' );
                $has_marker = false !== strpos( $js, 'P6-PERF-7' );
                $this->assertTrue( $has_guard || $has_marker,
                    "File index $i: \$(window).on('load',...) without readyState guard or P6-PERF-7 marker" );
            } else {
                $this->assertTrue( true );
            }
        }
    }
}
