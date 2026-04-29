<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * P6-PERF-5 W3-T4 regression lock: critical CSS inline + non-critical
 * deferral must remain wired and respect the keep-blocking filter.
 *
 * These tests are intentionally structural (no WP bootstrap needed):
 * they verify that the module file exists, contains the expected hooks,
 * and is required from core-functions.php.  Any future refactor that
 * silently removes a hook will fail here.
 *
 * @package Lafka\Tests\Unit
 * @since   5.10.0 (W3-T4 P6-PERF-5)
 */
final class CriticalCssTest extends TestCase {

	private string $module;

	protected function setUp(): void {
		parent::setUp();
		$this->module = file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/system/lafka-critical-css.php'
		);
	}

	/** Module file is readable and non-empty. */
	public function test_module_exists(): void {
		$this->assertNotEmpty( $this->module );
	}

	/** Critical CSS flat-file exists in styles/ directory. */
	public function test_critical_css_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/styles/critical.css' );
	}

	/** Inline function is hooked to wp_head at priority 1. */
	public function test_inline_hooked_to_wp_head_priority_1(): void {
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*['\"]wp_head['\"]\s*,\s*['\"]lafka_inline_critical_css['\"]\s*,\s*1\s*\)/",
			$this->module
		);
	}

	/** Defer filter is hooked to style_loader_tag. */
	public function test_defer_filter_hooked_to_style_loader_tag(): void {
		$this->assertMatchesRegularExpression(
			"/add_filter\(\s*['\"]style_loader_tag['\"]\s*,\s*['\"]lafka_defer_non_critical_css['\"]/",
			$this->module
		);
	}

	/** Keep-blocking escape-valve filter is present in the module. */
	public function test_keep_blocking_filter_provided(): void {
		$this->assertStringContainsString( 'lafka_critical_css_keep_blocking', $this->module );
	}

	/** <noscript> fallback is emitted for non-JS clients. */
	public function test_noscript_fallback_emitted(): void {
		$this->assertStringContainsString( '<noscript>', $this->module );
	}

	/** Module is wired via require_once in core-functions.php. */
	public function test_module_required_from_core_functions(): void {
		$core = file_get_contents( dirname( __DIR__, 2 ) . '/incl/system/core-functions.php' );
		$this->assertStringContainsString( 'lafka-critical-css.php', $core );
	}

	/**
	 * Inline function rewrites relative url() refs to absolute URLs.
	 *
	 * Regression: critical.css uses url('../assets/fonts/rubik/...'),
	 * which when inlined into <head> resolves against the page URL
	 * (e.g. /menu/pizza/../assets/) → 404 on every front-end page.
	 * The inline emitter must rewrite these to absolute URLs before echo.
	 */
	public function test_inline_rewrites_relative_urls_to_absolute(): void {
		$this->assertStringContainsString( 'get_template_directory_uri', $this->module,
			'inline function must build the stylesheet base URL via get_template_directory_uri()'
		);
		$this->assertMatchesRegularExpression(
			'/preg_replace_callback[^;]*url\\\\\(/s',
			$this->module,
			'inline function must run a preg_replace_callback over url(...) refs to rewrite relative paths'
		);
	}
}
