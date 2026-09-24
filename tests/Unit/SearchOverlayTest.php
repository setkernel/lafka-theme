<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for the header search overlay.
 *
 * Audit 2026-06-27 #3: header.php rendered a search trigger
 * (.lafka-header__search[data-lafka-search-toggle], href="#search") but the
 * rebuilt theme emitted no #search overlay and bound no JS, so the icon was
 * dead. The fix wires it to a native <dialog id="lafka-search-dialog"> with a
 * small vanilla handler, enqueued only when the search icon is enabled.
 */
final class SearchOverlayTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname( __DIR__, 2 );
	}

	public function test_dialog_markup_rendered_and_gated(): void {
		$footer = file_get_contents( $this->root . '/footer.php' );
		$this->assertStringContainsString(
			'id="lafka-search-dialog"',
			$footer,
			'A <dialog id="lafka-search-dialog"> overlay must be rendered for the trigger to open.'
		);
		$this->assertStringContainsString(
			'show_searchform',
			$footer,
			'The dialog must be gated on the show_searchform option, matching the trigger.'
		);
		$this->assertMatchesRegularExpression(
			"/name=['\"]s['\"]/",
			$footer,
			'The overlay must contain a WordPress search field (name="s").'
		);
	}
}
