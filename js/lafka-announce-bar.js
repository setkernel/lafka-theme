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

	const bar = document.querySelector( '[data-lafka-announce-bar]' );
	// Locked: the server label comes from the order gate (force open/closed,
	// holiday), which the weekly hours map below cannot reproduce.
	if ( ! bar || bar.hasAttribute( 'data-lafka-status-locked' ) ) {
		return;
	}

	const dot = bar.querySelector( '[data-lafka-status-dot]' );
	const label = bar.querySelector( '[data-lafka-status-label]' );
	if ( ! dot || ! label ) {
		return;
	}

	let hoursMap = {};
	try {
		hoursMap = JSON.parse( bar.dataset.lafkaHours || '{}' ) || {};
	} catch {
		hoursMap = {};
	}
	if ( ! hoursMap || ! Object.keys( hoursMap ).length ) {
		return;
	}

	const DAYS = [
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday'
	];

	function toMinutes( hhmm ) {
		const m = /^(\d{1,2}):(\d{2})$/.exec( hhmm );
		if ( ! m ) {
			return -1;
		}
		const h = parseInt( m[ 1 ], 10 );
		const i = parseInt( m[ 2 ], 10 );
		return h * 60 + i;
	}

	function format12h( hhmm ) {
		const m = /^(\d{1,2}):(\d{2})$/.exec( hhmm );
		if ( ! m ) {
			return hhmm;
		}
		let h = parseInt( m[ 1 ], 10 );
		const i = parseInt( m[ 2 ], 10 );
		const ampm = h >= 12 ? 'pm' : 'am';
		h = h % 12;
		if ( h === 0 ) {
			h = 12;
		}
		const ipad = i < 10 ? '0' + i : '' + i;
		return h + ':' + ipad + ' ' + ampm;
	}

	function rangeOf( dayName ) {
		const v = hoursMap[ dayName ];
		if ( ! v || /^closed$/i.test( v ) ) {
			return null;
		}
		const m = /^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/.exec( v );
		if ( ! m ) {
			return null;
		}
		return { open: m[ 1 ], close: m[ 2 ] };
	}

	function compute() {
		const now = new Date();
		const todayIdx = now.getDay();
		const nowMin = now.getHours() * 60 + now.getMinutes();
		const todayName = DAYS[ todayIdx ];
		const yesterdayName = DAYS[ ( todayIdx + 6 ) % 7 ];

		const yRange = rangeOf( yesterdayName );
		if ( yRange ) {
			const yOpenMin = toMinutes( yRange.open );
			const yCloseMin = toMinutes( yRange.close );
			if ( yCloseMin >= 0 && yCloseMin < yOpenMin && nowMin < yCloseMin ) {
				return {
					open: true,
					label: 'Open now · until ' + format12h( yRange.close ),
					dot: 'var(--lafka-color-success-500)'
				};
			}
		}

		const tRange = rangeOf( todayName );
		if ( tRange ) {
			const tOpen = toMinutes( tRange.open );
			const tClose = toMinutes( tRange.close );
			const rolls = tClose < tOpen;
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

		for ( let offset = 1; offset <= 7; offset++ ) {
			const nextName = DAYS[ ( todayIdx + offset ) % 7 ];
			const r = rangeOf( nextName );
			if ( r ) {
				const when = offset === 1 ? 'tomorrow' : nextName.charAt( 0 ).toUpperCase() + nextName.slice( 1 );
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
		const s = compute();
		label.textContent = s.label;
		dot.style.setProperty( '--lafka-dot', s.dot );
		bar.classList.toggle( 'lafka-announce-bar--open', s.open );
		bar.classList.toggle( 'lafka-announce-bar--closed', ! s.open );
	}

	apply();
	setInterval( apply, 60000 );
}() );
