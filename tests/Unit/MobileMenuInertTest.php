<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * C-12 C-A11Y-Audit-2026-04-29: keyboard trap regression lock.
 *
 * The off-canvas #menu_mobile drawer was hidden purely by CSS transform
 * (left: -320px) but its links remained in the keyboard tab order, forcing
 * keyboard users through 26+ invisible off-screen links.
 *
 * Fix: `inert` attribute is set on the drawer by JS on DOM-ready and toggled
 * on every open/close. This test asserts:
 *   1. The JS sets `inert` as the initial default state.
 *   2. The open handler removes `inert`.
 *   3. The close handler restores `inert`.
 *   4. The audit provenance comment is present.
 */
final class MobileMenuInertTest extends TestCase {

	private string $js;

	protected function setUp(): void {
		parent::setUp();
		$path = dirname( __DIR__, 2 ) . '/js/lafka-front.js';
		$this->assertFileExists( $path, 'lafka-front.js not found' );
		$this->js = file_get_contents( $path );
	}

	/** JS must set `inert` on the drawer as its initial (closed) state. */
	public function test_inert_set_initially(): void {
		$this->assertMatchesRegularExpression(
			'/setAttribute\(\s*[\'"]inert[\'"]\s*,\s*[\'"][\'"]/',
			$this->js,
			'lafka-front.js must call setAttribute("inert", "") to close the drawer initially (C-12)'
		);
	}

	/** The open helper must remove the inert attribute. */
	public function test_inert_removed_on_open(): void {
		$this->assertMatchesRegularExpression(
			'/removeAttribute\(\s*[\'"]inert[\'"]\s*\)/',
			$this->js,
			'lafka-front.js must call removeAttribute("inert") when the mobile menu opens (C-12)'
		);
	}

	/** The toggle handler must branch on `.hasClass("active")` to decide open/close. */
	public function test_toggle_checks_active_class(): void {
		$this->assertMatchesRegularExpression(
			'/hasClass\(\s*[\'"]active[\'"]\s*\)/',
			$this->js,
			'Mobile menu toggle must check .hasClass("active") to branch inert set/remove (C-12)'
		);
	}

	/** Both open and close helpers must exist as named functions or inline branches. */
	public function test_open_and_close_helpers_present(): void {
		$this->assertStringContainsString(
			'lafkaMobileMenuOpen',
			$this->js,
			'lafka-front.js must define lafkaMobileMenuOpen (C-12)'
		);
		$this->assertStringContainsString(
			'lafkaMobileMenuClose',
			$this->js,
			'lafka-front.js must define lafkaMobileMenuClose (C-12)'
		);
	}

	/** Audit provenance comment must be present. */
	public function test_c12_audit_comment_present(): void {
		$this->assertStringContainsString(
			'C-A11Y-Audit-2026-04-29',
			$this->js,
			'lafka-front.js must carry C-A11Y-Audit-2026-04-29 provenance comment (C-12)'
		);
	}

	/** Verify the document-click close path also triggers lafkaMobileMenuClose. */
	public function test_document_click_close_path_restores_inert(): void {
		// The document click handler that dismisses the drawer on outside-click
		// must also call lafkaMobileMenuClose so inert is restored.
		$this->assertMatchesRegularExpression(
			'/lafkaMobileMenuClose\s*\(\s*\)/',
			$this->js,
			'Document click close path must call lafkaMobileMenuClose() (C-12)'
		);
	}
}
