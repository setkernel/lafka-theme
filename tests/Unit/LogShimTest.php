<?php
declare(strict_types=1);

/**
 * GX1 / A3: lafka_theme_log() forwards to the plugin through the `lafka_log`
 * action (no hard dependency) and only falls back to error_log() when nothing
 * listens AND the fallback is on (WP_DEBUG by default, filterable).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-log-shim.php';
}

namespace Lafka\Tests\Unit {
	use PHPUnit\Framework\TestCase;

	final class LogShimTest extends TestCase {

		public function test_forwards_level_channel_message_and_context_to_the_lafka_log_action(): void {
			$received = array();
			add_action(
				'lafka_log',
				static function ( ...$args ) use ( &$received ) {
					$received[] = $args;
				},
				10,
				4
			);

			$sent = \lafka_theme_log( 'warning', 'Updater: GitHub returned HTTP 500', array( 'repo' => 'lafka-theme' ) );

			$this->assertTrue( $sent );
			$this->assertSame(
				array( array( 'warning', 'theme', 'Updater: GitHub returned HTTP 500', array( 'repo' => 'lafka-theme' ) ) ),
				$received
			);
			// A listener means no error_log() write — PHPUnit fails the test on
			// any unexpected error_log() output.
		}

		public function test_child_theme_can_pick_its_own_channel(): void {
			$channels = array();
			add_action(
				'lafka_log',
				static function ( $level, $channel ) use ( &$channels ) {
					$channels[] = $channel;
				},
				10,
				4
			);

			\lafka_theme_log( 'error', 'override failed', array(), 'child' );

			$this->assertSame( array( 'child' ), $channels );
		}

		public function test_without_a_listener_it_stays_silent_when_the_fallback_is_off(): void {
			// Nothing listens and WP_DEBUG is off in the suite: no write at all
			// (PHPUnit fails the test on any unexpected error_log() output).
			$this->assertFalse( \lafka_theme_log( 'warning', 'nobody listens' ) );
		}

		public function test_without_a_listener_the_fallback_writes_to_the_php_error_log(): void {
			add_filter( 'lafka_theme_log_fallback', '__return_true' );
			$this->expectErrorLog();

			$this->assertTrue( \lafka_theme_log( 'warning', 'debugging without the plugin' ) );
		}
	}
}
