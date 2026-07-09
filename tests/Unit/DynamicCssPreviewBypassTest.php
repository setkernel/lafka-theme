<?php
declare(strict_types=1);

/**
 * NX2-04 Task 1: inside the Customizer preview, dynamic-css must be rebuilt
 * from the (previewed, unsaved) theme_mods on every request — never served
 * from the wp_cache/transient written under SAVED values. Without the bypass
 * every dynamic-css-backed control (accent, brand, menu colors …) shows
 * stale styles in the preview iframe.
 *
 * Global-namespace shims (theme convention); separate process so sibling
 * shims never interfere.
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
	if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
		define( 'WEEK_IN_SECONDS', 604800 );
	}

	$GLOBALS['lafka_test_state'] = array(
		'is_preview'      => false,
		'cache_reads'     => 0,
		'transient_reads' => 0,
		'cache_writes'    => 0,
		'inline'          => array(),
	);

	if ( ! function_exists( 'is_customize_preview' ) ) {
		function is_customize_preview() {
			return $GLOBALS['lafka_test_state']['is_preview'];
		}
	}
	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( 'get_locale' ) ) {
		function get_locale() {
			return 'en_US';
		}
	}
	if ( ! function_exists( 'get_template' ) ) {
		function get_template() {
			return 'lafka';
		}
	}
	if ( ! function_exists( 'wp_get_theme' ) ) {
		function wp_get_theme( $stylesheet = null ) {
			return new class() {
				public function get( $header ) {
					return '7.0.0';
				}
			};
		}
	}
	if ( ! function_exists( 'wp_cache_get' ) ) {
		function wp_cache_get( $key, $group = '' ) {
			++$GLOBALS['lafka_test_state']['cache_reads'];
			return 'CACHED-CSS-MUST-NOT-BE-SERVED';
		}
	}
	if ( ! function_exists( 'wp_cache_set' ) ) {
		function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) {
			++$GLOBALS['lafka_test_state']['cache_writes'];
			return true;
		}
	}
	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( $key ) {
			++$GLOBALS['lafka_test_state']['transient_reads'];
			return 'CACHED-CSS-MUST-NOT-BE-SERVED';
		}
	}
	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( $key, $value, $ttl = 0 ) {
			++$GLOBALS['lafka_test_state']['cache_writes'];
			return true;
		}
	}
	if ( ! function_exists( 'wp_add_inline_style' ) ) {
		function wp_add_inline_style( $handle, $css ) {
			$GLOBALS['lafka_test_state']['inline'][] = $css;
			return true;
		}
	}
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
			return true;
		}
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook, $value = null, ...$rest ) {
			return $value;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class DynamicCssPreviewBypassTest extends TestCase {

		private function reset_state( bool $is_preview ): void {
			$GLOBALS['lafka_test_state']['is_preview']      = $is_preview;
			$GLOBALS['lafka_test_state']['cache_reads']     = 0;
			$GLOBALS['lafka_test_state']['transient_reads'] = 0;
			$GLOBALS['lafka_test_state']['cache_writes']    = 0;
			$GLOBALS['lafka_test_state']['inline']          = array();
		}

		public function test_normal_request_serves_the_cache(): void {
			$this->reset_state( false );

			\lafka_add_custom_css();

			$this->assertGreaterThan( 0, $GLOBALS['lafka_test_state']['cache_reads'], 'Front-end requests must keep using the cache.' );
			$this->assertSame( array( 'CACHED-CSS-MUST-NOT-BE-SERVED' ), $GLOBALS['lafka_test_state']['inline'] );
		}

		public function test_customize_preview_rebuilds_and_never_touches_the_cache(): void {
			$this->reset_state( true );

			\lafka_add_custom_css();

			$this->assertSame( 0, $GLOBALS['lafka_test_state']['cache_reads'], 'Preview must not READ the cache (it was built from saved values).' );
			$this->assertSame( 0, $GLOBALS['lafka_test_state']['transient_reads'], 'Preview must not read the transient either.' );
			$this->assertSame( 0, $GLOBALS['lafka_test_state']['cache_writes'], 'Preview must not WRITE unsaved values into the shared cache.' );
			$this->assertCount( 1, $GLOBALS['lafka_test_state']['inline'] );
			$this->assertStringNotContainsString( 'CACHED-CSS-MUST-NOT-BE-SERVED', $GLOBALS['lafka_test_state']['inline'][0] );
			$this->assertStringContainsString( '--lafka-color-accent-500', $GLOBALS['lafka_test_state']['inline'][0], 'Preview must emit a freshly built :root block.' );
		}
	}
}
