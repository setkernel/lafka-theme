<?php
declare(strict_types=1);

/**
 * The open/closed status strip follows the plugin's order gate for "now".
 *
 * Click-test finding: with the store force-opened by the operator
 * (Lafka_Order_Hours force override) the announce bar still said
 * "Closed · opens today at 11:00 am", because it read only the weekly hours.
 * The gate (Lafka_Order_Hours::is_shop_open(): force override, holidays,
 * branch) now wins when it disagrees, and the result is marked locked so the
 * schedule-only client refresh keeps it.
 *
 * The shared shim clock (current_time) resolves to a moment just after
 * midnight, so an 11:00-23:00 schedule reads "closed" and a 00:00-23:59 one
 * reads "open".
 */

namespace {
	if ( ! function_exists( 'date_i18n' ) ) {
		function date_i18n( $format, $timestamp = null ) {
			return gmdate( $format, (int) $timestamp );
		}
	}
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/open-status.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class OpenStatusOrderGateTest extends TestCase {

		private function hours( string $range ): void {
			$GLOBALS['lafka_test_restaurant_info'] = array(
				'hours' => array_fill_keys( array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ), $range ),
			);
		}

		protected function tearDown(): void {
			\Lafka_Order_Hours::$shop_open       = true;
			\Lafka_Order_Hours::$next_open_human = '';
		}

		public function test_a_force_opened_store_reads_open_now(): void {
			$this->hours( '11:00-23:00' );
			\Lafka_Order_Hours::$shop_open = true;

			$status = \lafka_open_status();

			$this->assertTrue( $status['is_open'] );
			$this->assertSame( 'Open now', $status['label'] );
			$this->assertTrue( $status['locked'] );
		}

		public function test_a_holiday_closure_reads_closed_with_the_next_opening(): void {
			$this->hours( '00:00-23:59' );
			\Lafka_Order_Hours::$shop_open       = false;
			\Lafka_Order_Hours::$next_open_human = 'Saturday at 11:00 AM';

			$status = \lafka_open_status();

			$this->assertFalse( $status['is_open'] );
			$this->assertSame( 'Closed · opens Saturday at 11:00 AM', $status['label'] );
			$this->assertTrue( $status['locked'] );
		}

		public function test_when_gate_and_schedule_agree_the_schedule_label_stays(): void {
			$this->hours( '11:00-23:00' );
			\Lafka_Order_Hours::$shop_open = false;

			$status = \lafka_open_status();

			$this->assertStringStartsWith( 'Closed · opens today at', $status['label'] );
			$this->assertArrayNotHasKey( 'locked', $status );
		}

		public function test_a_given_moment_uses_the_schedule_only(): void {
			$this->hours( '11:00-23:00' );
			\Lafka_Order_Hours::$shop_open = true;

			$this->assertFalse( \lafka_open_status( 60 )['is_open'], 'The gate only knows "now".' );
		}

		public function test_the_announce_bar_tells_its_client_refresh_to_keep_a_locked_label(): void {
			$this->hours( '11:00-23:00' );
			\Lafka_Order_Hours::$shop_open = true;

			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/announce-bar.php';
			$html = (string) ob_get_clean();

			$this->assertStringContainsString( 'data-lafka-status-locked', $html );
			$this->assertStringContainsString( '>Open now</span>', $html );
		}
	}
}
