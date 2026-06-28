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

	/** The drawer must be declared a true modal dialog. */
	public function test_drawer_is_aria_modal_true(): void {
		$this->assertMatchesRegularExpression(
			'/aria-modal="true"/',
			$this->php,
			'.lafka-cart-drawer must declare aria-modal="true" (f092)'
		);
	}

	/** The stale aria-modal="false" must be gone. */
	public function test_drawer_is_not_aria_modal_false(): void {
		$this->assertDoesNotMatchRegularExpression(
			'/aria-modal="false"/',
			$this->php,
			'.lafka-cart-drawer must NOT declare aria-modal="false" (f092)'
		);
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

	/** Background isolation must provide an aria-hidden fallback for older AT. */
	public function test_js_has_aria_hidden_fallback(): void {
		$this->assertMatchesRegularExpression(
			"/setAttribute\\(\\s*'aria-hidden'\\s*,\\s*'true'\\s*\\)/",
			$this->js,
			'The background helper must fall back to aria-hidden for AT without inert support (f092)'
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

	/** It must NOT inert document.body — that would disable the drawer too. */
	public function test_js_does_not_inert_document_body(): void {
		$this->assertDoesNotMatchRegularExpression(
			'/document\.body\.(inert|setAttribute\(\s*[\'"]inert)/',
			$this->js,
			'document.body must never be inerted — the drawer lives inside body (f092)'
		);
	}
}
