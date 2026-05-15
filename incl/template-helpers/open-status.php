<?php
/**
 * Open/closed status helper.
 *
 * Computes whether the restaurant is currently open from the hours map
 * returned by `lafka_get_restaurant_info()` (plugin: lafka-plugin schema
 * helpers). Returns a structured array the announce bar (and any other
 * surface) can render.
 *
 * Filter surface:
 *   lafka_open_status(array $status, int $now) — override the result
 *
 * @package Lafka
 * @since   5.54.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_open_status_get_hours_map' ) ) {
	/**
	 * Resolve the hours map from the plugin's restaurant-info resolver, or
	 * an empty array when no plugin / no hours are configured.
	 *
	 * @return array<string, string> e.g. ['Monday' => '11:00-23:00', ...]
	 */
	function lafka_open_status_get_hours_map() {
		if ( function_exists( 'lafka_get_restaurant_info' ) ) {
			$info = lafka_get_restaurant_info();
			if ( ! empty( $info['hours'] ) && is_array( $info['hours'] ) ) {
				return $info['hours'];
			}
		}
		return array();
	}
}

if ( ! function_exists( 'lafka_open_status_to_minutes' ) ) {
	/**
	 * Convert "HH:MM" to minutes since midnight. Returns -1 on parse fail.
	 *
	 * @param string $hhmm e.g. "11:30"
	 * @return int 0..1439, or -1 if invalid.
	 */
	function lafka_open_status_to_minutes( $hhmm ) {
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $hhmm ), $m ) ) {
			return -1;
		}
		$h = (int) $m[1];
		$i = (int) $m[2];
		if ( $h < 0 || $h > 47 || $i < 0 || $i > 59 ) {
			return -1;
		}
		return $h * 60 + $i;
	}
}

if ( ! function_exists( 'lafka_open_status_format_12h' ) ) {
	/**
	 * Pretty 12h string for an "HH:MM" 24h value. e.g. "23:00" -> "11:00 pm".
	 *
	 * @param string $hhmm
	 * @return string
	 */
	function lafka_open_status_format_12h( $hhmm ) {
		$ts = strtotime( '2026-01-01 ' . $hhmm );
		if ( ! $ts ) {
			return $hhmm;
		}
		return strtolower( date_i18n( 'g:i a', $ts ) );
	}
}

if ( ! function_exists( 'lafka_open_status' ) ) {
	/**
	 * Compute open/closed status at a given timestamp (defaults to WP now).
	 *
	 * Returns:
	 *   [
	 *     'is_open'  => bool,
	 *     'short'    => 'Open now' | 'Closed',
	 *     'label'    => 'Open now · Until 11:00 pm' | 'Closed · Opens today at 11:00 am' | 'Closed · Opens Monday at 11:00 am',
	 *     'dot_color'=> css var name for the status dot,
	 *   ]
	 *
	 * Returns null when no hours are configured (caller hides the strip).
	 * Handles past-midnight closes (e.g. "11:00-02:00") by treating the
	 * close as next-day. Looks up to 7 days forward to find the next open day.
	 *
	 * @param int|null $now Unix ts; default: current_time('timestamp').
	 * @return array|null
	 */
	function lafka_open_status( $now = null ) {
		$hours = lafka_open_status_get_hours_map();
		if ( empty( $hours ) ) {
			return apply_filters( 'lafka_open_status', null, $now );
		}

		if ( null === $now ) {
			$now = function_exists( 'current_time' ) ? (int) current_time( 'timestamp' ) : time();
		}

		$day_names = array(
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
		);

		$today_idx     = (int) date( 'w', $now );
		$now_minutes   = ( (int) date( 'H', $now ) * 60 ) + (int) date( 'i', $now );
		$today_name    = $day_names[ $today_idx ];
		$today_hours   = isset( $hours[ $today_name ] ) ? (string) $hours[ $today_name ] : '';

		// 1. Check if currently open under today's range OR yesterday's range
		// (yesterday's range can extend past midnight into today, e.g. Fri 11:00-02:00).
		$yesterday_idx   = ( $today_idx + 6 ) % 7;
		$yesterday_name  = $day_names[ $yesterday_idx ];
		$yesterday_hours = isset( $hours[ $yesterday_name ] ) ? (string) $hours[ $yesterday_name ] : '';

		if ( $yesterday_hours && 'closed' !== strtolower( $yesterday_hours ) && preg_match( '/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', $yesterday_hours, $m ) ) {
			$y_open  = lafka_open_status_to_minutes( $m[1] );
			$y_close = lafka_open_status_to_minutes( $m[2] );
			if ( $y_close < $y_open ) {
				// Yesterday's close rolls into today: e.g. open 23:00, close 02:00.
				// Today is "still open" if now < close-of-yesterday.
				if ( $now_minutes < $y_close ) {
					return apply_filters(
						'lafka_open_status',
						array(
							'is_open'   => true,
							'short'     => __( 'Open now', 'lafka' ),
							/* translators: %s — closing time (lowercased "h:mm am/pm") */
							'label'     => sprintf( __( 'Open now · until %s', 'lafka' ), lafka_open_status_format_12h( $m[2] ) ),
							'dot_color' => 'var(--lafka-color-success-500)',
						),
						$now
					);
				}
			}
		}

		if ( $today_hours && 'closed' !== strtolower( $today_hours ) && preg_match( '/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', $today_hours, $m ) ) {
			$t_open  = lafka_open_status_to_minutes( $m[1] );
			$t_close = lafka_open_status_to_minutes( $m[2] );
			$rolls   = $t_close < $t_open;

			if ( $now_minutes >= $t_open && ( $rolls || $now_minutes < $t_close ) ) {
				return apply_filters(
					'lafka_open_status',
					array(
						'is_open'   => true,
						'short'     => __( 'Open now', 'lafka' ),
						'label'     => sprintf( __( 'Open now · until %s', 'lafka' ), lafka_open_status_format_12h( $m[2] ) ),
						'dot_color' => 'var(--lafka-color-success-500)',
					),
					$now
				);
			}

			// Not open yet today — opens later today.
			if ( $now_minutes < $t_open ) {
				return apply_filters(
					'lafka_open_status',
					array(
						'is_open'   => false,
						'short'     => __( 'Closed', 'lafka' ),
						/* translators: %s — opening time today */
						'label'     => sprintf( __( 'Closed · opens today at %s', 'lafka' ), lafka_open_status_format_12h( $m[1] ) ),
						'dot_color' => 'var(--lafka-color-brand-500)',
					),
					$now
				);
			}
		}

		// Closed for today — search forward up to 7 days for next open day.
		for ( $offset = 1; $offset <= 7; $offset++ ) {
			$next_idx   = ( $today_idx + $offset ) % 7;
			$next_name  = $day_names[ $next_idx ];
			$next_hours = isset( $hours[ $next_name ] ) ? (string) $hours[ $next_name ] : '';

			if ( $next_hours && 'closed' !== strtolower( $next_hours ) && preg_match( '/^(\d{1,2}:\d{2})-/', $next_hours, $m ) ) {
				$when = ( 1 === $offset ) ? __( 'tomorrow', 'lafka' ) : $next_name;
				return apply_filters(
					'lafka_open_status',
					array(
						'is_open'   => false,
						'short'     => __( 'Closed', 'lafka' ),
						/* translators: 1: day-of-week label ("tomorrow" or "Monday"); 2: opening time */
						'label'     => sprintf( __( 'Closed · opens %1$s at %2$s', 'lafka' ), $when, lafka_open_status_format_12h( $m[1] ) ),
						'dot_color' => 'var(--lafka-color-brand-500)',
					),
					$now
				);
			}
		}

		// All seven days closed — operator data is broken.
		return apply_filters(
			'lafka_open_status',
			array(
				'is_open'   => false,
				'short'     => __( 'Closed', 'lafka' ),
				'label'     => __( 'Closed', 'lafka' ),
				'dot_color' => 'var(--lafka-color-text-muted)',
			),
			$now
		);
	}
}

if ( ! function_exists( 'lafka_open_status_hours_for_client' ) ) {
	/**
	 * Serialize the hours map for client-side JS (announce-bar live refresh).
	 * Day keys lower-cased ("monday") to keep the JS lookup simple.
	 *
	 * @return array<string, string>
	 */
	function lafka_open_status_hours_for_client() {
		$hours  = lafka_open_status_get_hours_map();
		$client = array();
		foreach ( $hours as $day_name => $range ) {
			$client[ strtolower( $day_name ) ] = (string) $range;
		}
		return $client;
	}
}
