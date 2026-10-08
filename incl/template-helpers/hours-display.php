<?php
/**
 * GX4: human hours and the counter header's status wording.
 *
 *   lafka_time_plain( '23:00' )        -> "11 pm" ("midnight", "noon", "11:30 am")
 *   lafka_hours_grouped( $hours )      -> [ { days: "Sun–Thu", hours: "11 am–11 pm" }, … ]
 *   lafka_hours_late_note( $hours )    -> "Open till midnight Fri & Sat" | ''
 *   lafka_counter_open_status()        -> lafka_open_status() with the counter header's parts
 *
 * The hours map is lafka_get_restaurant_info()['hours'] (the plugin publishes
 * the order-hours schedule there): [ 'Monday' => '11:00-23:00' | 'Closed', … ].
 * Whether the store is open NOW is not decided here: lafka_open_status() reads
 * the plugin's Lafka_Order_Hours::status().
 *
 * Filters: lafka_hours_grouped, lafka_hours_late_note, lafka_counter_open_status.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_time_plain' ) ) {
	/**
	 * "HH:MM" (24h) -> a short spoken time: "11 pm", "11:30 am", "noon",
	 * "midnight" (00:00 and 24:00).
	 *
	 * @param string $hhmm 24h time.
	 */
	function lafka_time_plain( string $hhmm ): string {
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $hhmm ), $m ) ) {
			return $hhmm;
		}
		$h   = (int) $m[1] % 24;
		$min = (int) $m[2];
		if ( 0 === $min && 0 === $h ) {
			return __( 'midnight', 'lafka' );
		}
		if ( 0 === $min && 12 === $h ) {
			return __( 'noon', 'lafka' );
		}
		$h12  = 0 === $h % 12 ? 12 : $h % 12;
		$time = 0 === $min ? (string) $h12 : $h12 . ':' . sprintf( '%02d', $min );
		/* translators: %s: hour (and minutes), e.g. "11" or "11:30" */
		$spoken = $h < 12 ? sprintf( __( '%s am', 'lafka' ), $time ) : sprintf( __( '%s pm', 'lafka' ), $time );
		// A no-break space keeps "11 am" on one line when the label wraps.
		return str_replace( ' ', "\u{00A0}", $spoken );
	}
}

if ( ! function_exists( 'lafka_hours_day_names' ) ) {
	/**
	 * Day keys (as stored in the hours map) => short labels, Sunday first
	 * (index = PHP 'w' / WP start_of_week).
	 *
	 * @return array<int,array{0:string,1:string}>
	 */
	function lafka_hours_day_names(): array {
		return array(
			array( 'Sunday', __( 'Sun', 'lafka' ) ),
			array( 'Monday', __( 'Mon', 'lafka' ) ),
			array( 'Tuesday', __( 'Tue', 'lafka' ) ),
			array( 'Wednesday', __( 'Wed', 'lafka' ) ),
			array( 'Thursday', __( 'Thu', 'lafka' ) ),
			array( 'Friday', __( 'Fri', 'lafka' ) ),
			array( 'Saturday', __( 'Sat', 'lafka' ) ),
		);
	}
}

if ( ! function_exists( 'lafka_hours_range_plain' ) ) {
	/**
	 * One stored range ("11:00-23:00", comma-separated for split shifts, or
	 * "Closed") as plain text: "11 am–11 pm".
	 *
	 * @param string $range Stored range.
	 */
	function lafka_hours_range_plain( string $range ): string {
		$range = trim( $range );
		if ( '' === $range || 'closed' === strtolower( $range ) ) {
			return __( 'Closed', 'lafka' );
		}
		$parts = array();
		foreach ( array_map( 'trim', explode( ',', $range ) ) as $span ) {
			if ( preg_match( '/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $span, $m ) ) {
				$parts[] = lafka_time_plain( $m[1] ) . '–' . lafka_time_plain( $m[2] );
			} elseif ( '' !== $span ) {
				$parts[] = $span;
			}
		}
		return implode( ', ', $parts );
	}
}

if ( ! function_exists( 'lafka_hours_grouped' ) ) {
	/**
	 * Consecutive days with identical hours, in week order from $start_of_week
	 * (default: the WP `start_of_week` option). A single day stays unranged;
	 * missing / "Closed" days read "Closed". The run does not merge across the
	 * week boundary (Monday start: Mon–Thu, Fri–Sat, Sun).
	 *
	 * @param array<string,string> $hours         Day name => range.
	 * @param int|null             $start_of_week 0 = Sunday.
	 * @return list<array{days:string,hours:string}>
	 */
	function lafka_hours_grouped( array $hours, ?int $start_of_week = null ): array {
		if ( empty( $hours ) ) {
			return array();
		}
		if ( null === $start_of_week ) {
			$start_of_week = (int) get_option( 'start_of_week', 0 );
		}
		$names  = lafka_hours_day_names();
		$groups = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$day   = $names[ ( $start_of_week + $i ) % 7 ];
			$range = isset( $hours[ $day[0] ] ) ? (string) $hours[ $day[0] ] : '';
			$plain = lafka_hours_range_plain( $range );
			$last  = count( $groups ) - 1;
			if ( $last >= 0 && $groups[ $last ]['hours'] === $plain ) {
				$groups[ $last ]['to'] = $day[1];
				++$groups[ $last ]['n'];
			} else {
				$groups[] = array(
					'from'  => $day[1],
					'to'    => $day[1],
					'n'     => 1,
					'hours' => $plain,
				);
			}
		}
		$out = array();
		foreach ( $groups as $group ) {
			$days  = 1 === $group['n'] ? $group['from'] : $group['from'] . '–' . $group['to'];
			$out[] = array(
				'days'  => $days,
				'hours' => $group['hours'],
			);
		}
		/**
		 * Filter the grouped hours rows.
		 *
		 * @param list<array{days:string,hours:string}> $out   Rows.
		 * @param array<string,string>                  $hours Raw map.
		 */
		return (array) apply_filters( 'lafka_hours_grouped', $out, $hours );
	}
}

if ( ! function_exists( 'lafka_hours_open_every_day' ) ) {
	/**
	 * True when all seven days have hours (drives "Hours, every day").
	 *
	 * @param array<string,string> $hours Day name => range.
	 */
	function lafka_hours_open_every_day( array $hours ): bool {
		foreach ( lafka_hours_day_names() as $day ) {
			$range = isset( $hours[ $day[0] ] ) ? trim( (string) $hours[ $day[0] ] ) : '';
			if ( '' === $range || 'closed' === strtolower( $range ) ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'lafka_hours_late_note' ) ) {
	/**
	 * "Open till midnight Fri & Sat" when exactly one run of days closes later
	 * than every other open day; else ''.
	 *
	 * @param array<string,string> $hours Day name => range.
	 */
	function lafka_hours_late_note( array $hours ): string {
		$closes = array(); // day index (Sunday first) => [ minutes, 'HH:MM' ].
		foreach ( lafka_hours_day_names() as $i => $day ) {
			$range = isset( $hours[ $day[0] ] ) ? (string) $hours[ $day[0] ] : '';
			$spans = array_map( 'trim', explode( ',', $range ) );
			$span  = (string) end( $spans );
			if ( ! preg_match( '/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $span, $m ) ) {
				continue;
			}
			$open  = (int) $m[1] * 60 + (int) $m[2];
			$close = (int) $m[3] * 60 + (int) $m[4];
			if ( $close <= $open ) {
				$close += 1440; // past midnight.
			}
			$closes[ $i ] = array( $close, sprintf( '%02d:%02d', (int) $m[3] % 24, (int) $m[4] ) );
		}
		if ( count( $closes ) < 2 ) {
			return '';
		}
		$values = array_unique( array_column( $closes, 0 ) );
		if ( 2 !== count( $values ) ) {
			return '';
		}
		$late = max( $values );
		$days = array_keys( array_filter( $closes, static fn( $c ) => $c[0] === $late ) );
		// The late days must be one consecutive run (wrapping Sat -> Sun).
		$runs = 0;
		foreach ( $days as $d ) {
			if ( ! in_array( ( $d + 6 ) % 7, $days, true ) ) {
				++$runs;
			}
		}
		if ( 1 !== $runs || count( $days ) === count( $closes ) ) {
			return '';
		}
		// Walk the run from its first day (the one whose previous day is not late).
		$names = lafka_hours_day_names();
		$first = $days[0];
		while ( in_array( ( $first + 6 ) % 7, $days, true ) ) {
			$first = ( $first + 6 ) % 7;
		}
		$last = ( $first + count( $days ) - 1 ) % 7;
		if ( 1 === count( $days ) ) {
			$label = $names[ $first ][1];
		} elseif ( 2 === count( $days ) ) {
			/* translators: 1: first day, 2: second day (e.g. "Fri & Sat") */
			$label = sprintf( __( '%1$s & %2$s', 'lafka' ), $names[ $first ][1], $names[ $last ][1] );
		} else {
			$label = $names[ $first ][1] . '–' . $names[ $last ][1];
		}
		$time = lafka_time_plain( $closes[ $first ][1] );
		/* translators: 1: closing time such as midnight; 2: the days it applies to, such as Fri & Sat. */
		$note = sprintf( __( 'Open till %1$s %2$s', 'lafka' ), $time, $label );
		return (string) apply_filters( 'lafka_hours_late_note', $note, $hours );
	}
}

if ( ! function_exists( 'lafka_counter_open_status' ) ) {
	/**
	 * The counter header's status: `strong` ("Open now" / "Closed") + `rest`
	 * ("until 11 pm" / "opens tomorrow at 11 am") + `label` (both joined) and
	 * `is_open`. A reader of lafka_open_status(), which reads the plugin.
	 *
	 * @param int|null $now Unix timestamp (tests); default now.
	 * @return array{is_open:bool,strong:string,rest:string,label:string}|null
	 */
	function lafka_counter_open_status( ?int $now = null ): ?array {
		$status = lafka_open_status( $now );
		if ( ! is_array( $status ) ) {
			return null;
		}
		$out = array(
			'is_open' => ! empty( $status['is_open'] ),
			'strong'  => (string) $status['strong'],
			'rest'    => (string) $status['rest'],
			'label'   => (string) $status['label'],
		);
		return (array) apply_filters( 'lafka_counter_open_status', $out, $status );
	}
}
