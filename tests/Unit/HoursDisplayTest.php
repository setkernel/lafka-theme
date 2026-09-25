<?php
declare(strict_types=1);

/**
 * GX4 A7: human hours for the find-us block and the hero meta line.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/hours-display.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class HoursDisplayTest extends TestCase {

		/** Sun–Thu 11–23, Fri–Sat 11–midnight (neutral example schedule). */
		private const LATE_WEEKEND = array(
			'Sunday'    => '11:00-23:00',
			'Monday'    => '11:00-23:00',
			'Tuesday'   => '11:00-23:00',
			'Wednesday' => '11:00-23:00',
			'Thursday'  => '11:00-23:00',
			'Friday'    => '11:00-00:00',
			'Saturday'  => '11:00-00:00',
		);

		public function test_time_plain(): void {
			$this->assertSame( '11 pm', \lafka_time_plain( '23:00' ) );
			$this->assertSame( 'midnight', \lafka_time_plain( '00:00' ) );
			$this->assertSame( 'midnight', \lafka_time_plain( '24:00' ) );
			$this->assertSame( 'noon', \lafka_time_plain( '12:00' ) );
			$this->assertSame( '11:30 am', \lafka_time_plain( '11:30' ) );
			$this->assertSame( '11 am', \lafka_time_plain( '11:00' ) );
			$this->assertSame( '12:30 pm', \lafka_time_plain( '12:30' ) );
		}

		public function test_sunday_start_groups_into_two_rows(): void {
			$this->assertSame(
				array(
					array(
						'days'  => 'Sun–Thu',
						'hours' => '11 am–11 pm',
					),
					array(
						'days'  => 'Fri–Sat',
						'hours' => '11 am–midnight',
					),
				),
				\lafka_hours_grouped( self::LATE_WEEKEND, 0 )
			);
		}

		public function test_monday_start_does_not_merge_across_the_week(): void {
			$this->assertSame( array( 'Mon–Thu', 'Fri–Sat', 'Sun' ), array_column( \lafka_hours_grouped( self::LATE_WEEKEND, 1 ), 'days' ) );
		}

		public function test_start_defaults_to_the_wp_option(): void {
			$GLOBALS['lafka_test_options']['start_of_week'] = 1;
			$this->assertSame( 'Mon–Thu', \lafka_hours_grouped( self::LATE_WEEKEND )[0]['days'] );
		}

		public function test_closed_and_missing_days(): void {
			$hours = array(
				'Monday'  => 'Closed',
				'Tuesday' => '11:00-22:00',
			);
			$rows  = \lafka_hours_grouped( $hours, 1 );
			$this->assertSame(
				array(
					'days'  => 'Mon',
					'hours' => 'Closed',
				),
				$rows[0]
			);
			$this->assertSame(
				array(
					'days'  => 'Tue',
					'hours' => '11 am–10 pm',
				),
				$rows[1],
				'a single day stays unranged'
			);
			$this->assertSame(
				array(
					'days'  => 'Wed–Sun',
					'hours' => 'Closed',
				),
				$rows[2]
			);
			$this->assertFalse( \lafka_hours_open_every_day( $hours ) );
			$this->assertTrue( \lafka_hours_open_every_day( self::LATE_WEEKEND ) );
			$this->assertSame( array(), \lafka_hours_grouped( array() ) );
		}

		public function test_late_note(): void {
			$this->assertSame( 'Open till midnight Fri & Sat', \lafka_hours_late_note( self::LATE_WEEKEND ) );

			$same = array_fill_keys( array_keys( self::LATE_WEEKEND ), '11:00-23:00' );
			$this->assertSame( '', \lafka_hours_late_note( $same ), 'no later group -> no note' );

			$split             = $same;
			$split['Monday']   = '11:00-01:00';
			$split['Thursday'] = '11:00-01:00';
			$this->assertSame( '', \lafka_hours_late_note( $split ), 'two separate late runs -> no note' );

			$sat_sun             = $same;
			$sat_sun['Saturday'] = '11:00-02:00';
			$sat_sun['Sunday']   = '11:00-02:00';
			$this->assertSame( 'Open till 2 am Sat & Sun', \lafka_hours_late_note( $sat_sun ), 'a run wrapping the week boundary' );
		}

		public function test_grouping_is_filterable(): void {
			\add_filter(
				'lafka_hours_grouped',
				static function () {
					return array();
				}
			);
			$this->assertSame( array(), \lafka_hours_grouped( self::LATE_WEEKEND, 0 ) );
		}
	}
}
