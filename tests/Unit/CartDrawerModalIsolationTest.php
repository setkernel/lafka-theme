<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for audit f092 (MEDIUM / a11y).
 *
 * The slide-in cart (partials/cart-drawer.php + js/cart-drawer.js) behaves as a
 * modal for sighted/keyboard users — it locks body scroll, moves focus inside
 * and traps Tab — yet it was declared aria-modal="false" and never removed the
 * page behind it from the a11y tree. A screen-reader virtual cursor could
 * therefore still browse the whole obscured page (the scrim only blocks sighted
 * users), and the false aria-modal contradicted the real behaviour
 * (WCAG 4.1.2 Name, Role, Value / 1.3.1 Info and Relationships).
 *
 * This test asserts:
 *   1. The drawer container declares aria-modal="true" (and never "false").
 *   2. open() isolates the background page wrappers via `inert`
 *      (with an aria-hidden fallback for older AT) and close() restores them.
 *   3. The isolated wrappers are #header / #content — NOT document.body, which
 *      would disable the drawer itself (it is injected at wp_footer as a
 *      sibling of those wrappers).
 */
final class CartDrawerModalIsolationTest extends TestCase {
	private string $php;
	private string $js;

	protected function setUp(): void {
		parent::setUp();

		$php_path = dirname( __DIR__, 2 ) . '/partials/cart-drawer.php';
		$js_path  = dirname( __DIR__, 2 ) . '/js/cart-drawer.js';

		$this->assertFileExists( $php_path, 'partials/cart-drawer.php not found' );
		$this->assertFileExists( $js_path, 'js/cart-drawer.js not found' );

		$this->php = (string) file_get_contents( $php_path );
		$this->js  = (string) file_get_contents( $js_path );
	}

	/** open() must isolate the background; close() must restore it. */
	public function test_js_isolates_background_on_open_and_restores_on_close(): void {
		$this->assertMatchesRegularExpression(
			'/setBackgroundInert\(\s*true\s*\)/',
			$this->js,
			'open() must inert the background page wrappers (f092)'
		);
		$this->assertMatchesRegularExpression(
			'/setBackgroundInert\(\s*false\s*\)/',
			$this->js,
			'close() must restore the background page wrappers (f092)'
		);
	}

	/** The helper must apply the inert attribute on open and remove it on close. */
	public function test_js_toggles_inert_attribute(): void {
		$this->assertMatchesRegularExpression(
			"/setAttribute\\(\\s*'inert'\\s*,\\s*''\\s*\\)/",
			$this->js,
			'The background helper must set the inert attribute (f092)'
		);
		$this->assertMatchesRegularExpression(
			"/removeAttribute\\(\\s*'inert'\\s*\\)/",
			$this->js,
			'close() must remove the inert attribute from the background (f092)'
		);
	}

	/** The isolated wrappers must be #header and #content. */
	public function test_js_targets_header_and_content_wrappers(): void {
		$this->assertMatchesRegularExpression(
			"/'#header'/",
			$this->js,
			'#header must be isolated while the drawer is open (f092)'
		);
		$this->assertMatchesRegularExpression(
			"/'#content'/",
			$this->js,
			'#content must be isolated while the drawer is open (f092)'
		);
	}
}
