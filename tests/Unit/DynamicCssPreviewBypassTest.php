<?php
declare(strict_types=1);

/**
 * NX2-04 Task 1: inside the Customizer preview, dynamic-css must be rebuilt
 * from the (previewed, unsaved) theme_mods on every request — never served
 * from the wp_cache/transient written under SAVED values. Without the bypass
 * every dynamic-css-backed control (accent, brand, menu colors …) shows
 * stale styles in the preview iframe.
 */

namespace {
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
	if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
		define( 'WEEK_IN_SECONDS', 604800 );
	}

	if ( ! function_exists( 'lafka_dynamic_css_build' ) ) {
		ob_start();
		require dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
		ob_end_clean();
	}
}

namespace Lafka\Tests\Unit {
	use PHPUnit\Framework\TestCase;

	final class DynamicCssPreviewBypassTest extends TestCase {
		private const SENTINEL = 'CACHED-CSS-MUST-NOT-BE-SERVED';

		/**
		 * Warm the cache the way a real front-end request does, then swap the
		 * cached value for a sentinel so a later cache hit is detectable.
		 */
		protected function setUp(): void {
			\lafka_add_custom_css();
			foreach ( array_keys( $GLOBALS['lafka_test_cache']['lafka'] ?? array() ) as $key ) {
				$GLOBALS['lafka_test_cache']['lafka'][ $key ] = self::SENTINEL;
			}
			$this->assertNotEmpty( $GLOBALS['lafka_test_cache']['lafka'] ?? array(), 'The front-end build must have cached its CSS.' );
			$GLOBALS['lafka_test_counters'] = array();
			$GLOBALS['lafka_test_inline']   = array();
		}

		public function test_normal_request_serves_the_cache(): void {
			\lafka_add_custom_css();

			$this->assertGreaterThan( 0, $GLOBALS['lafka_test_counters']['cache_get'] ?? 0, 'Front-end requests must keep using the cache.' );
			$this->assertSame( array( self::SENTINEL ), $GLOBALS['lafka_test_inline']['lafka-style'] );
		}

		public function test_customize_preview_rebuilds_and_never_touches_the_cache(): void {
			$GLOBALS['lafka_test_is_preview'] = true;

			\lafka_add_custom_css();

			$this->assertSame( array(), $GLOBALS['lafka_test_counters'], 'Preview must not read or write the cache or the transient.' );
			$this->assertCount( 1, $GLOBALS['lafka_test_inline']['lafka-style'] );
			$this->assertStringNotContainsString( self::SENTINEL, $GLOBALS['lafka_test_inline']['lafka-style'][0] );
			$this->assertStringContainsString( '--lafka-color-accent-500', $GLOBALS['lafka_test_inline']['lafka-style'][0], 'Preview must emit a freshly built :root block.' );
		}
	}
}
