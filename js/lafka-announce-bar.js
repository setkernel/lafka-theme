/**
 * Announce bar — 60s open/closed recompute.
 *
 * Reads the hours map from data-lafka-hours (JSON, day-keyed
 * "HH:MM-HH:MM" or "Closed"), recomputes status against the local
 * clock, and updates the dot color + label. Mirrors the PHP
 * lafka_open_status() logic so server-render + client-refresh agree.
 *
 * Trusts the user's browser local time. For local restaurant
 * pickup/delivery sites visitors are typically in the same TZ as
 * the operator, which is good enough.
 *
 * @since 5.54.0
 */
( function () {
	'use strict';

	var bar = document.querySelector( '[data-lafka-announce-bar]' );
	if ( ! bar ) {
		return;
	}

	var dot = bar.querySelector( '[data-lafka-status-dot]' );
	var label = bar.querySelector( '[data-lafka-status-label]' );
	if ( ! dot || ! label ) {
		return;
	}

	var hoursMap = {};
	try {
		hoursMap = JSON.parse( bar.dataset.lafkaHours || '{}' ) || {};
	} catch {
		hoursMap = {};
	}
	if ( ! hoursMap || ! Object.keys( hoursMap ).length ) {
		return;
	}

	var DAYS = [
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday'
	];

	function toMinutes( hhmm ) {
		var m = /^(\d{1,2}):(\d{2})$/.exec( hhmm );
		if ( ! m ) {
			return -1;
		}
		var h = parseInt( m[ 1 ], 10 );
		var i = parseInt( m[ 2 ], 10 );
		return h * 60 + i;
	}

	function format12h( hhmm ) {
		var m = /^(\d{1,2}):(\d{2})$/.exec( hhmm );
		if ( ! m ) {
			return hhmm;
		}
		var h = parseInt( m[ 1 ], 10 );
		var i = parseInt( m[ 2 ], 10 );
		var ampm = h >= 12 ? 'pm' : 'am';
		h = h % 12;
		if ( h === 0 ) {
			h = 12;
		}
		var ipad = i < 10 ? '0' + i : '' + i;
		return h + ':' + ipad + ' ' + ampm;
	}

	function rangeOf( dayName ) {
		var v = hoursMap[ dayName ];
		if ( ! v || /^closed$/i.test( v ) ) {
			return null;
		}
		var m = /^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/.exec( v );
		if ( ! m ) {
			return null;
		}
		return { open: m[ 1 ], close: m[ 2 ] };
	}

	function compute() {
		var now = new Date();
		var todayIdx = now.getDay();
		var nowMin = now.getHours() * 60 + now.getMinutes();
		var todayName = DAYS[ todayIdx ];
		var yesterdayName = DAYS[ ( todayIdx + 6 ) % 7 ];

		var yRange = rangeOf( yesterdayName );
		if ( yRange ) {
			var yOpenMin = toMinutes( yRange.open );
			var yCloseMin = toMinutes( yRange.close );
			if ( yCloseMin >= 0 && yCloseMin < yOpenMin && nowMin < yCloseMin ) {
				return {
					open: true,
					label: 'Open now · until ' + format12h( yRange.close ),
					dot: 'var(--lafka-color-success-500)'
				};
			}
		}

		var tRange = rangeOf( todayName );
		if ( tRange ) {
			var tOpen = toMinutes( tRange.open );
			var tClose = toMinutes( tRange.close );
			var rolls = tClose < tOpen;
			if ( nowMin >= tOpen && ( rolls || nowMin < tClose ) ) {
				return {
					open: true,
					label: 'Open now · until ' + format12h( tRange.close ),
					dot: 'var(--lafka-color-success-500)'
				};
			}
			if ( nowMin < tOpen ) {
				return {
					open: false,
					label: 'Closed · opens today at ' + format12h( tRange.open ),
					dot: 'var(--lafka-color-brand-500)'
				};
			}
		}

		for ( var offset = 1; offset <= 7; offset++ ) {
			var nextName = DAYS[ ( todayIdx + offset ) % 7 ];
			var r = rangeOf( nextName );
			if ( r ) {
				var when = offset === 1 ? 'tomorrow' : nextName.charAt( 0 ).toUpperCase() + nextName.slice( 1 );
				return {
					open: false,
					label: 'Closed · opens ' + when + ' at ' + format12h( r.open ),
					dot: 'var(--lafka-color-brand-500)'
				};
			}
		}

		return { open: false, label: 'Closed', dot: 'var(--lafka-color-text-muted)' };
	}

	function apply() {
		var s = compute();
		label.textContent = s.label;
		dot.style.setProperty( '--lafka-dot', s.dot );
		bar.classList.toggle( 'lafka-announce-bar--open', s.open );
		bar.classList.toggle( 'lafka-announce-bar--closed', ! s.open );
	}

	apply();
	setInterval( apply, 60000 );
}() );
