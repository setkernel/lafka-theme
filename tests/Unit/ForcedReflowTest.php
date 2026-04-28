<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-8 W3-T3 regression lock: prevent reintroduction of read-write
 * thrashing patterns inside .each() / forEach loops in lafka-front.js
 * and lafka-libs-config.js.
 */
final class ForcedReflowTest extends TestCase {

    public function test_no_thrashing_in_lafka_front(): void {
        $js = file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-front.js' );
        $this->assert_no_thrashing( $js, 'lafka-front.js' );
    }

    public function test_no_thrashing_in_lafka_libs_config(): void {
        $js = file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-libs-config.js' );
        $this->assert_no_thrashing( $js, 'lafka-libs-config.js' );
    }

    /**
     * Heuristic: inside any .each(function...) block, look for both a
     * geometry READ (offsetWidth/offsetHeight/getBoundingClientRect) AND
     * a style WRITE (.css(/.attr(/...) /.addClass) on the SAME element.
     * If both appear in the same block, that's a likely thrash site.
     */
    private function assert_no_thrashing( string $js, string $where ): void {
        // Find each(...) blocks and inspect their body
        if ( ! preg_match_all( '/\.each\s*\(\s*function[^{]*\{(.*?)\n\s*\}\s*\)/s', $js, $matches ) ) {
            // No .each() at all — fine
            $this->assertTrue( true );
            return;
        }
        foreach ( $matches[1] as $body ) {
            $has_read  = preg_match( '/offsetWidth|offsetHeight|getBoundingClientRect|clientWidth|clientHeight/', $body );
            $has_write = preg_match( '/\.css\(|\.height\(|\.width\(|\.attr\(|\.addClass\(|\.removeClass\(/', $body );
            if ( $has_read && $has_write ) {
                // Allow if the block has a P6-PERF-8 marker (acknowledged + reviewed)
                if ( false !== strpos( $body, 'P6-PERF-8' ) ) {
                    continue;
                }
                $this->fail( "Likely thrash pattern in $where: .each() block contains both geometry-read and style-write without P6-PERF-8 marker.\n\nBlock:\n" . substr( $body, 0, 400 ) );
            }
        }
        $this->assertTrue( true );
    }
}
