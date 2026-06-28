<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for the v5.56.0 mobile slide-out nav drawer
 * (partials/mobile-nav.php + js/lafka-mobile-nav.js).
 *
 * Audit f019 (MEDIUM / a11y): the drawer was declared `aria-modal="false"`
 * and shipped NO Tab focus trap and NO background inerting, so keyboard focus
 * and the screen-reader virtual cursor could escape behind the full-screen
 * scrim onto the obscured page (WCAG 2.4.3 Focus Order / 2.4.7 Focus Visible).
 *
 * This test asserts:
 *   1. The drawer container declares aria-modal="true".
 *   2. The JS implements a Tab focus trap scoped to the drawer panel, wrapping
 *      first<->last with preventDefault().
 *   3. The JS removes the background from the a11y tree via `inert`
 *      (with an aria-hidden fallback) on open and restores it on close —
 *      WITHOUT inerting document.body (which would disable the drawer itself).
 */
final class MobileNavFocusTrapTest extends TestCase {

	private string $php;
	private string $js;

	protected function setUp(): void {
		parent::setUp();

		$php_path = dirname( __DIR__, 2 ) . '/partials/mobile-nav.php';
		$js_path  = dirname( __DIR__, 2 ) . '/js/lafka-mobile-nav.js';

		$this->assertFileExists( $php_path, 'partials/mobile-nav.php not found' );
		$this->assertFileExists( $js_path, 'js/lafka-mobile-nav.js not found' );

		$this->php = (string) file_get_contents( $php_path );
		$this->js  = (string) file_get_contents( $js_path );
	}

	/** The drawer must be a true modal dialog. */
	public function test_drawer_is_aria_modal_true(): void {
		$this->assertMatchesRegularExpression(
			'/aria-modal="true"/',
			$this->php,
			'#lafka-mobile-nav must declare aria-modal="true" (f019)'
		);
	}

	/** The stale aria-modal="false" must be gone. */
	public function test_drawer_is_not_aria_modal_false(): void {
		$this->assertDoesNotMatchRegularExpression(
			'/aria-modal="false"/',
			$this->php,
			'#lafka-mobile-nav must NOT declare aria-modal="false" (f019)'
		);
	}

	/** The JS must trap the Tab key while the drawer is open. */
	public function test_js_traps_tab_key(): void {
		$this->assertMatchesRegularExpression(
			"/e\\.key\\s*===\\s*'Tab'/",
			$this->js,
			'lafka-mobile-nav.js must branch on the Tab key for the focus trap (f019)'
		);
	}

	/** The focus trap must be scoped to the drawer panel, not the whole page. */
	public function test_focus_trap_scoped_to_panel(): void {
		$this->assertMatchesRegularExpression(
			'/panel\.querySelectorAll\(/',
			$this->js,
			'The focus trap must query focusables within .lafka-mobile-nav__panel (f019)'
		);
		$this->assertStringContainsString(
			'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
			$this->js,
			'The focus trap must use the audited focusable selector (f019)'
		);
	}

	/** The trap must wrap focus first<->last using preventDefault(). */
	public function test_focus_trap_wraps_with_prevent_default(): void {
		$this->assertMatchesRegularExpression(
			'/e\.preventDefault\(\s*\)/',
			$this->js,
			'The focus trap must call preventDefault() when wrapping focus (f019)'
		);
		$this->assertMatchesRegularExpression(
			'/last\.focus\(\s*\)/',
			$this->js,
			'shift+Tab from the first element must wrap to last.focus() (f019)'
		);
		$this->assertMatchesRegularExpression(
			'/first\.focus\(\s*\)/',
			$this->js,
			'Tab from the last element must wrap to first.focus() (f019)'
		);
	}

	/** The JS must remove the background from the a11y tree via inert. */
	public function test_js_inerts_background(): void {
		$this->assertMatchesRegularExpression(
			'/setBackgroundInert\(\s*true\s*\)/',
			$this->js,
			'open() must inert the background page wrappers (f019)'
		);
		$this->assertMatchesRegularExpression(
			'/setBackgroundInert\(\s*false\s*\)/',
			$this->js,
			'close() must restore the background page wrappers (f019)'
		);
		$this->assertMatchesRegularExpression(
			'/\.inert\s*=\s*true/',
			$this->js,
			'The background helper must set the inert property (f019)'
		);
	}

	/** Background inerting must provide an aria-hidden fallback. */
	public function test_inert_has_aria_hidden_fallback(): void {
		$this->assertMatchesRegularExpression(
			"/setAttribute\\(\\s*'aria-hidden'\\s*,\\s*'true'\\s*\\)/",
			$this->js,
			'The background helper must fall back to aria-hidden when inert is unsupported (f019)'
		);
	}

	/** It must NOT inert document.body — that would disable the drawer too. */
	public function test_does_not_inert_document_body(): void {
		$this->assertDoesNotMatchRegularExpression(
			'/document\.body\.inert/',
			$this->js,
			'document.body must never be inerted — the drawer lives inside body (f019)'
		);
	}
}
