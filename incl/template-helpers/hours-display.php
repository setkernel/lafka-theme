<?php
/**
 * GX4: human hours + the gate-aware open/closed status.
 *
 *   lafka_time_plain( '23:00' )        -> "11 pm" ("midnight", "noon", "11:30 am")
 *   lafka_hours_grouped( $hours )      -> [ { days: "Sun–Thu", hours: "11 am–11 pm" }, … ]
 *   lafka_hours_late_note( $hours )    -> "Open till midnight Fri & Sat" | ''
 *   lafka_gated_open_status()          -> lafka_open_status() reconciled with the ORDER GATE
 *   lafka_counter_open_status()        -> the same, with the counter header's wording
 *
 * The hours map is lafka_get_restaurant_info()['hours'] (the single NAP/hours
 * store): [ 'Monday' => '11:00-23:00' | 'Closed', … ].
 *
 * Gate: the storefront badge used to read the SCHEDULE only, so a store the
 * operator force-opened (or a holiday closure) showed the wrong state. When
 * lafka-plugin's order-hours module is on, Lafka_Order_Hours::is_shop_open()
 * — which honours force override + holidays — decides open/closed, and the
 * status is marked `gate = override` whenever it disagrees with the schedule
 * (or a force override is on), so the client never "refreshes" it back to
 * the schedule.
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
		return $h < 12 ? sprintf( __( '%s am', 'lafka' ), $time ) : sprintf( __( '%s pm', 'lafka' ), $time );
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
			$days = 1 === $group['n'] ? $group['from'] : $group['from'] . '–' . $group['to'];
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
		/* translators: 1: closing time ("midnight"), 2: days ("Fri & Sat") */
		$note = sprintf( __( 'Open till %1$s %2$s', 'lafka' ), $time, $label );
		return (string) apply_filters( 'lafka_hours_late_note', $note, $hours );
	}
}

if ( ! function_exists( 'lafka_open_status_gate' ) ) {
	/**
	 * The ORDER GATE's verdict, or null when lafka-plugin's order-hours module
	 * is not running. Lafka_Order_Hours::is_shop_open() honours the force
	 * override and holiday closures that the display schedule cannot express.
	 */
	function lafka_open_status_gate(): ?bool {
		if ( ! class_exists( 'Lafka_Order_Hours' ) || ! method_exists( 'Lafka_Order_Hours', 'is_shop_open' ) ) {
			return null;
		}
		if ( function_exists( 'is_lafka_order_hours' ) && ! is_lafka_order_hours() ) {
			return null;
		}
		return (bool) Lafka_Order_Hours::is_shop_open();
	}
}

if ( ! function_exists( 'lafka_open_status_forced' ) ) {
	/** Whether the plugin's force override (open or closed) is on. */
	function lafka_open_status_forced(): bool {
		return class_exists( 'Lafka_Order_Hours' )
			&& property_exists( 'Lafka_Order_Hours', 'lafka_order_hours_force_override_check' )
			&& ! empty( Lafka_Order_Hours::$lafka_order_hours_force_override_check );
	}
}

if ( ! function_exists( 'lafka_open_status_next_open_human' ) ) {
	/** The plugin's "Saturday at 11:00 AM", or '' (no gate / no next opening). */
	function lafka_open_status_next_open_human(): string {
		if ( ! class_exists( 'Lafka_Order_Hours' ) || ! method_exists( 'Lafka_Order_Hours', 'format_next_open_time_human' ) ) {
			return '';
		}
		$next = method_exists( 'Lafka_Order_Hours', 'get_next_opening_time' ) ? Lafka_Order_Hours::get_next_opening_time() : null;
		return (string) Lafka_Order_Hours::format_next_open_time_human( $next );
	}
}

if ( ! function_exists( 'lafka_gated_open_status' ) ) {
	/**
	 * The schedule status (lafka_open_status_schedule()) reconciled with the
	 * order gate — the same rule lafka_open_status() applies for "now" (GX0),
	 * plus: only while the order-hours MODULE is on, and a force override
	 * always counts as an override. Adds `gate`: `schedule` (the client may
	 * refresh it) or `override` (never refresh). Passes the public
	 * `lafka_open_status` filter. Null when there are no hours and nothing
	 * overrides them.
	 *
	 * @param int|null $now Unix timestamp (tests); default now.
	 * @return array<string,mixed>|null
	 */
	function lafka_gated_open_status( ?int $now = null ): ?array {
		if ( function_exists( 'lafka_open_status_schedule' ) ) {
			$status = lafka_open_status_schedule( $now );
		} else {
			$status = function_exists( 'lafka_open_status' ) ? lafka_open_status( $now ) : null;
		}
		$gate   = lafka_open_status_gate();
		$forced = null !== $gate && lafka_open_status_forced();

		if ( null === $gate || ( is_array( $status ) && ! $forced && (bool) $status['is_open'] === $gate ) ) {
			$result = is_array( $status ) ? $status + array( 'gate' => 'schedule' ) : null;
		} elseif ( ! is_array( $status ) && ! $forced ) {
			$result = null; // No hours configured and nothing overriding them: say nothing.
		} elseif ( $gate ) {
			$schedule_open = is_array( $status ) && ! empty( $status['is_open'] );
			$result        = array(
				'is_open'   => true,
				'short'     => __( 'Open now', 'lafka' ),
				// A force-open during scheduled hours still has a real closing time.
				'label'     => $schedule_open ? $status['label'] : __( 'Open now', 'lafka' ),
				'dot_color' => 'var(--lafka-color-success-500)',
				'close'     => $schedule_open && isset( $status['close'] ) ? $status['close'] : '',
				'locked'    => true,
				'gate'      => 'override',
			);
		} else {
			$next   = lafka_open_status_next_open_human();
			$result = array(
				'is_open'   => false,
				'short'     => __( 'Closed', 'lafka' ),
				/* translators: %s: next opening, e.g. "Saturday at 11:00 AM" */
				'label'     => '' !== $next ? sprintf( __( 'Closed · opens %s', 'lafka' ), $next ) : __( 'Closed', 'lafka' ),
				'dot_color' => 'var(--lafka-color-text-secondary)',
				'next'      => $next,
				'locked'    => true,
				'gate'      => 'override',
			);
		}

		/** This filter is documented in incl/template-helpers/open-status.php */
		$result = apply_filters( 'lafka_open_status', $result, $now );
		return is_array( $result ) ? $result + array( 'gate' => 'schedule' ) : null;
	}
}

if ( ! function_exists( 'lafka_counter_open_status' ) ) {
	/**
	 * The counter header's status: `strong` ("Open now" / "Closed") + `rest`
	 * ("until 11 pm" / "opens tomorrow at 11 am") + `label` (both joined),
	 * `is_open` and `gate` (schedule | override).
	 *
	 * @param int|null $now Unix timestamp (tests); default now.
	 * @return array{is_open:bool,strong:string,rest:string,label:string,gate:string}|null
	 */
	function lafka_counter_open_status( ?int $now = null ): ?array {
		$status = lafka_gated_open_status( $now );
		if ( null === $status ) {
			return null;
		}
		$open = ! empty( $status['is_open'] );
		$rest = '';
		if ( $open && ! empty( $status['close'] ) ) {
			/* translators: %s: closing time, e.g. "11 pm" */
			$rest = sprintf( __( 'until %s', 'lafka' ), lafka_time_plain( (string) $status['close'] ) );
		} elseif ( ! $open && 'override' === $status['gate'] && ! empty( $status['next'] ) ) {
			/* translators: %s: next opening, e.g. "Saturday at 11:00 AM" */
			$rest = sprintf( __( 'opens %s', 'lafka' ), $status['next'] );
		} elseif ( ! $open && ! empty( $status['opens'] ) ) {
			$time = lafka_time_plain( (string) $status['opens'] );
			$day  = (int) ( $status['opens_day'] ?? 0 );
			if ( 0 === $day ) {
				/* translators: %s: opening time today */
				$rest = sprintf( __( 'opens today at %s', 'lafka' ), $time );
			} elseif ( 1 === $day ) {
				/* translators: %s: opening time tomorrow */
				$rest = sprintf( __( 'opens tomorrow at %s', 'lafka' ), $time );
			} else {
				/* translators: 1: weekday, 2: opening time */
				$rest = sprintf( __( 'opens %1$s at %2$s', 'lafka' ), (string) ( $status['opens_on'] ?? '' ), $time );
			}
		}
		$strong = $open ? __( 'Open now', 'lafka' ) : __( 'Closed', 'lafka' );
		$out    = array(
			'is_open' => $open,
			'strong'  => $strong,
			'rest'    => $rest,
			'label'   => '' !== $rest ? $strong . ' · ' . $rest : $strong,
			'gate'    => (string) $status['gate'],
		);
		return (array) apply_filters( 'lafka_counter_open_status', $out, $status );
	}
}
