<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression lock for audit f039 (LOW / perf):
 * exit-intent and push-subscribe each bound a document-level
 * `keydown` listener but removed it ONLY inside the Escape branch of
 * onKeydown. When the toast/prompt was dismissed via the close /
 * maybe-later / accept buttons, the listener (and its closure) survived
 * after the element was removed — a dangling listener.
 *
 * The fix detaches the keydown listener on EVERY close path:
 *   - exit-intent.js: removeEventListener inside dismiss(); the redundant
 *     removal inside onKeydown is dropped.
 *   - push-subscribe.js: a single teardown() removes the listener and is
 *     called by dismiss() AND the accept handler (which never routes through
 *     dismiss()); the redundant inline removal inside onKeydown is dropped.
 *
 * Asserted purely against the JS source text (same approach as
 * MobileNavFocusTrapTest), so no JS runtime is required.
 */
final class PromptKeydownTeardownTest extends TestCase {

	private string $exit;
	private string $push;

	protected function setUp(): void {
		parent::setUp();

		$exit_path = dirname( __DIR__, 2 ) . '/js/lafka-exit-intent.js';
		$push_path = dirname( __DIR__, 2 ) . '/js/lafka-push-subscribe.js';

		$this->assertFileExists( $exit_path, 'js/lafka-exit-intent.js not found' );
		$this->assertFileExists( $push_path, 'js/lafka-push-subscribe.js not found' );

		$this->exit = (string) file_get_contents( $exit_path );
		$this->push = (string) file_get_contents( $push_path );
	}

	/**
	 * Both files must still ADD the keydown listener — the fix detaches it,
	 * it does not remove the Escape-to-close feature.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function provide_files(): array {
		return array(
			'exit-intent' => array( 'exit' ),
			'push'        => array( 'push' ),
		);
	}

	#[DataProvider( 'provide_files' )]
	public function test_keydown_listener_is_still_added( string $which ): void {
		$src = $this->$which;
		$this->assertMatchesRegularExpression(
			"/addEventListener\\(\\s*'keydown'\\s*,\\s*onKeydown\\s*\\)/",
			$src,
			"$which must still bind the keydown listener (Escape-to-close) (f039)"
		);
	}

	/** exit-intent.js must detach the keydown listener inside dismiss(). */
	public function test_exit_intent_dismiss_detaches_keydown(): void {
		$dismiss = $this->extract_block( $this->exit, 'function dismiss(' );
		$this->assertMatchesRegularExpression(
			"/removeEventListener\\(\\s*'keydown'\\s*,\\s*onKeydown\\s*\\)/",
			$dismiss,
			'exit-intent dismiss() must removeEventListener keydown on every close path (f039)'
		);
	}

	/** exit-intent.js onKeydown must NOT carry the now-redundant removal. */
	public function test_exit_intent_onkeydown_drops_redundant_removal(): void {
		$on_keydown = $this->extract_block( $this->exit, 'function onKeydown(' );
		$this->assertDoesNotMatchRegularExpression(
			"/removeEventListener\\(\\s*'keydown'/",
			$on_keydown,
			'exit-intent onKeydown() must drop the redundant removeEventListener (handled by dismiss()) (f039)'
		);
	}

	/** push-subscribe.js must define a single teardown() helper. */
	public function test_push_defines_teardown_helper(): void {
		$this->assertMatchesRegularExpression(
			'/function\s+teardown\s*\(\s*\)\s*\{/',
			$this->push,
			'push-subscribe must define a teardown() helper (f039)'
		);
		$teardown = $this->extract_block( $this->push, 'function teardown(' );
		$this->assertMatchesRegularExpression(
			"/removeEventListener\\(\\s*'keydown'\\s*,\\s*onKeydown\\s*\\)/",
			$teardown,
			'push-subscribe teardown() must removeEventListener keydown (f039)'
		);
	}

	/** push-subscribe.js dismiss() must call teardown(). */
	public function test_push_dismiss_calls_teardown(): void {
		$dismiss = $this->extract_block( $this->push, 'function dismiss(' );
		$this->assertMatchesRegularExpression(
			'/teardown\(\s*\)\s*;/',
			$dismiss,
			'push-subscribe dismiss() must call teardown() (f039)'
		);
	}

	/**
	 * The accept handler never routes through dismiss(), so it must call
	 * teardown() itself — otherwise the listener leaks and a later Escape
	 * fires a false push_prompt_deny.
	 */
	public function test_push_accept_handler_calls_teardown(): void {
		// The accept handler is the click listener that calls subscribe().
		$this->assertMatchesRegularExpression(
			'/teardown\(\s*\)\s*;\s*subscribe\(\s*\)/s',
			$this->push,
			'push-subscribe accept handler must call teardown() before subscribe() (f039)'
		);
	}

	/** push-subscribe.js onKeydown must NOT carry the now-redundant removal. */
	public function test_push_onkeydown_drops_redundant_removal(): void {
		$on_keydown = $this->extract_block( $this->push, 'function onKeydown(' );
		$this->assertDoesNotMatchRegularExpression(
			"/removeEventListener\\(\\s*'keydown'/",
			$on_keydown,
			'push-subscribe onKeydown() must drop the redundant removeEventListener (handled by dismiss()/teardown()) (f039)'
		);
	}

	/**
	 * Extract a brace-balanced function body starting at the first occurrence
	 * of $needle (which should include the `function name(` prefix). Returns
	 * the text from the needle through the matching closing brace.
	 */
	private function extract_block( string $src, string $needle ): string {
		$start = strpos( $src, $needle );
		$this->assertNotFalse( $start, "Could not locate '$needle' in source (f039)" );

		$brace = strpos( $src, '{', $start );
		$this->assertNotFalse( $brace, "Could not locate opening brace for '$needle' (f039)" );

		$depth = 0;
		$len   = strlen( $src );
		for ( $i = $brace; $i < $len; $i++ ) {
			$ch = $src[ $i ];
			if ( '{' === $ch ) {
				$depth++;
			} elseif ( '}' === $ch ) {
				$depth--;
				if ( 0 === $depth ) {
					return substr( $src, $start, $i - $start + 1 );
				}
			}
		}

		$this->fail( "Unbalanced braces while extracting '$needle' (f039)" );
	}
}
