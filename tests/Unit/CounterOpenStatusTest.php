<?php
declare(strict_types=1);

/**
 * GX4 A7 / D10: the open/closed status follows the ORDER GATE, not just the
 * schedule. A store the operator force-opened must never read "Closed", and a
 * holiday / force-closed store must never read "Open now".
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/open-status.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/hours-display.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterOpenStatusTest extends TestCase {

		private const HOURS = array(
			'Sunday'    => '11:00-23:00',
			'Monday'    => '11:00-23:00',
			'Tuesday'   => '11:00-23:00',
			'Wednesday' => '11:00-23:00',
			'Thursday'  => '11:00-23:00',
			'Friday'    => '11:00-00:00',
			'Saturday'  => '11:00-00:00',
		);

		protected function setUp(): void {
			$GLOBALS['lafka_test_restaurant_info'] = array( 'hours' => self::HOURS );
		}

		/** A Wednesday at $hour:00 (local time, as lafka_open_status() reads it). */
		private static function wednesday( int $hour ): int {
			$ts = mktime( $hour, 0, 0, 7, 8, 2026 );
			while ( 3 !== (int) date( 'w', $ts ) ) { // phpcs:ignore
				$ts += 86400;
			}
			return $ts;
		}

		public function test_schedule_open_without_gate(): void {
			$s = \lafka_counter_open_status( self::wednesday( 20 ) );
			$this->assertTrue( $s['is_open'] );
			$this->assertSame( 'Open now', $s['strong'] );
			$this->assertSame( 'until 11 pm', $s['rest'] );
			$this->assertSame( 'Open now · until 11 pm', $s['label'] );
			$this->assertSame( 'schedule', $s['gate'] );
		}

		public function test_schedule_closed_says_when_it_opens(): void {
			$s = \lafka_counter_open_status( self::wednesday( 8 ) );
			$this->assertFalse( $s['is_open'] );
			$this->assertSame( 'Closed · opens today at 11 am', $s['label'] );
			$this->assertSame( 'schedule', $s['gate'] );
		}

		public function test_force_open_gate_wins_over_a_closed_schedule(): void {
			$GLOBALS['lafka_test_order_hours_on']                           = true;
			\Lafka_Order_Hours::$shop_open                                  = true;
			\Lafka_Order_Hours::$lafka_order_hours_force_override_check     = true;

			$s = \lafka_counter_open_status( self::wednesday( 8 ) ); // schedule says closed.
			$this->assertTrue( $s['is_open'], 'force-opened store must not read Closed' );
			$this->assertSame( 'Open now', $s['label'] );
			$this->assertSame( 'override', $s['gate'], 'the client must not refresh it back to the schedule' );
		}

		public function test_closed_gate_override_says_closed_and_next_opening(): void {
			$GLOBALS['lafka_test_order_hours_on'] = true;
			\Lafka_Order_Hours::$shop_open        = false; // e.g. a holiday closure.
			\Lafka_Order_Hours::$next_open_human  = 'Thursday at 11:00 AM';

			$s = \lafka_counter_open_status( self::wednesday( 20 ) ); // schedule says open.
			$this->assertFalse( $s['is_open'] );
			$this->assertSame( 'Closed · opens Thursday at 11:00 AM', $s['label'] );
			$this->assertSame( 'override', $s['gate'] );
		}

		public function test_gate_agreeing_with_the_schedule_keeps_schedule_refresh(): void {
			$GLOBALS['lafka_test_order_hours_on'] = true;
			\Lafka_Order_Hours::$shop_open        = true;
			$s = \lafka_counter_open_status( self::wednesday( 20 ) );
			$this->assertSame( 'schedule', $s['gate'] );
			$this->assertSame( 'Open now · until 11 pm', $s['label'] );
		}

		public function test_module_off_ignores_the_class(): void {
			\Lafka_Order_Hours::$shop_open = false; // class exists, module off.
			$s = \lafka_counter_open_status( self::wednesday( 20 ) );
			$this->assertTrue( $s['is_open'] );
		}

		public function test_announce_bar_status_is_gated_too(): void {
			$GLOBALS['lafka_test_order_hours_on']                       = true;
			\Lafka_Order_Hours::$shop_open                              = true;
			\Lafka_Order_Hours::$lafka_order_hours_force_override_check = true;
			$s = \lafka_gated_open_status( self::wednesday( 8 ) );
			$this->assertTrue( $s['is_open'] );
			$this->assertSame( 'override', $s['gate'] );
			$this->assertSame( 'var(--lafka-color-success-500)', $s['dot_color'] );
		}

		public function test_no_hours_and_no_gate_says_nothing(): void {
			$GLOBALS['lafka_test_restaurant_info'] = array();
			$this->assertNull( \lafka_counter_open_status( self::wednesday( 20 ) ) );
		}
	}
}
