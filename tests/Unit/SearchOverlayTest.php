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

	public function test_header_trigger_hook_present(): void {
		$header = file_get_contents( $this->root . '/header.php' );
		$this->assertStringContainsString(
			'data-lafka-search-toggle',
			$header,
			'The header search trigger must expose the data-lafka-search-toggle hook.'
		);
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

	public function test_handler_script_exists_and_wires_dialog(): void {
		$js_path = $this->root . '/js/lafka-search.js';
		$this->assertFileExists( $js_path );
		$js = file_get_contents( $js_path );
		$this->assertStringContainsString( 'data-lafka-search-toggle', $js, 'Handler must bind the trigger.' );
		$this->assertStringContainsString( 'showModal', $js, 'Handler must open the native dialog via showModal().' );
		$this->assertStringContainsString( 'lafka-search-dialog', $js, 'Handler must target the search dialog.' );
	}

	public function test_assets_enqueued_only_when_search_enabled(): void {
		$core = file_get_contents( $this->root . '/incl/system/core-functions.php' );
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_script\(\s*'lafka-search'/",
			$core,
			'The search handler must be enqueued.'
		);
		// The enqueue must be guarded by the show_searchform option.
		$pos = strpos( $core, "wp_enqueue_script(\n\t\t\t\t'lafka-search'" );
		if ( false === $pos ) {
			$pos = strpos( $core, "'lafka-search'," );
		}
		$this->assertNotFalse( $pos );
		$window = substr( $core, max( 0, $pos - 400 ), 600 );
		$this->assertStringContainsString(
			'show_searchform',
			$window,
			'Search assets must be enqueued conditionally on show_searchform.'
		);
	}
}
