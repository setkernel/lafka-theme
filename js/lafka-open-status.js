/**
 * GX4: shared open/closed compute + the counter header's 60 s refresh.
 *
 * window.lafkaOpenStatus.compute( hours, now ) mirrors the PHP
 * lafka_open_status() schedule logic (hours = { monday: "11:00-23:00" | "Closed" })
 * and returns { open, strong, rest }. The refresh only touches
 * [data-lafka-open-status][data-lafka-gate="schedule"]: when the ORDER GATE
 * overrides the schedule (force open/close, holiday) the server's verdict
 * stands and is never "refreshed" back to the schedule.
 *
 * Strings come from window.lafkaOpenStatusL10n (wp_localize_script), with
 * English fallbacks.
 *
 * @since 7.2.0 (GX4)
 */
( function () {
	'use strict';

	var L = window.lafkaOpenStatusL10n || {};
	var DAYS = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];
	var DAY_LABELS = L.days || [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];

	function t( key, fallback ) {
		return L[ key ] || fallback;
	}

	function fill( template, a, b ) {
		return template.replace( '%1$s', a ).replace( '%2$s', b ).replace( '%s', a );
	}

	function toMinutes( hhmm ) {
		var m = /^(\d{1,2}):(\d{2})$/.exec( hhmm || '' );
		return m ? parseInt( m[ 1 ], 10 ) * 60 + parseInt( m[ 2 ], 10 ) : -1;
	}

	function plain( hhmm ) {
		var m = /^(\d{1,2}):(\d{2})$/.exec( hhmm || '' );
		if ( ! m ) {
			return hhmm;
		}
		var h = parseInt( m[ 1 ], 10 ) % 24;
		var i = parseInt( m[ 2 ], 10 );
		if ( i === 0 && h === 0 ) {
			return t( 'midnight', 'midnight' );
		}
		if ( i === 0 && h === 12 ) {
			return t( 'noon', 'noon' );
		}
		var h12 = h % 12 === 0 ? 12 : h % 12;
		var time = i === 0 ? String( h12 ) : h12 + ':' + ( i < 10 ? '0' + i : i );
		return fill( h < 12 ? t( 'am', '%s am' ) : t( 'pm', '%s pm' ), time );
	}

	function rangeOf( hours, day ) {
		var v = hours[ day ];
		var m = v && ! /^closed$/i.test( v ) ? /^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/.exec( v ) : null;
		return m ? { open: m[ 1 ], close: m[ 2 ] } : null;
	}

	function compute( hours, now ) {
		var today;
		var nowMin;
		if ( now ) {
			today = now.getDay();
			nowMin = now.getHours() * 60 + now.getMinutes();
		} else if ( typeof L.offset === 'number' ) {
			// The STORE's wall clock (WP timezone offset), never the visitor's.
			var store = new Date( Date.now() + L.offset * 60000 );
			today = store.getUTCDay();
			nowMin = store.getUTCHours() * 60 + store.getUTCMinutes();
		} else {
			var local = new Date();
			today = local.getDay();
			nowMin = local.getHours() * 60 + local.getMinutes();
		}
		var openNow = t( 'openNow', 'Open now' );
		var closed = t( 'closed', 'Closed' );

		var y = rangeOf( hours, DAYS[ ( today + 6 ) % 7 ] );
		if ( y && toMinutes( y.close ) < toMinutes( y.open ) && nowMin < toMinutes( y.close ) ) {
			return { open: true, strong: openNow, rest: fill( t( 'until', 'until %s' ), plain( y.close ) ) };
		}
		var r = rangeOf( hours, DAYS[ today ] );
		if ( r ) {
			var o = toMinutes( r.open );
			var c = toMinutes( r.close );
			if ( nowMin >= o && ( c < o || nowMin < c ) ) {
				return { open: true, strong: openNow, rest: fill( t( 'until', 'until %s' ), plain( r.close ) ) };
			}
			if ( nowMin < o ) {
				return { open: false, strong: closed, rest: fill( t( 'opensToday', 'opens today at %s' ), plain( r.open ) ) };
			}
		}
		for ( var offset = 1; offset <= 7; offset++ ) {
			var idx = ( today + offset ) % 7;
			var n = rangeOf( hours, DAYS[ idx ] );
			if ( n ) {
				var rest = offset === 1
					? fill( t( 'opensTomorrow', 'opens tomorrow at %s' ), plain( n.open ) )
					: fill( t( 'opensOn', 'opens %1$s at %2$s' ), DAY_LABELS[ idx ], plain( n.open ) );
				return { open: false, strong: closed, rest: rest };
			}
		}
		return { open: false, strong: closed, rest: '' };
	}

	window.lafkaOpenStatus = { compute: compute, plain: plain };

	function render( node ) {
		var hours;
		try {
			hours = JSON.parse( node.getAttribute( 'data-lafka-hours' ) || '{}' ) || {};
		} catch {
			return;
		}
		if ( ! Object.keys( hours ).length ) {
			return;
		}
		var s = compute( hours );
		var text = node.querySelector( '[data-lafka-open-status-text]' );
		if ( ! text ) {
			return;
		}
		var strong = document.createElement( 'strong' );
		strong.textContent = s.strong;
		text.textContent = '';
		text.appendChild( strong );
		if ( s.rest ) {
			text.appendChild( document.createTextNode( ' · ' + s.rest ) );
		}
		node.classList.toggle( 'is-open', s.open );
		node.classList.toggle( 'is-closed', ! s.open );
	}

	function refresh() {
		var nodes = document.querySelectorAll( '[data-lafka-open-status][data-lafka-gate="schedule"]' );
		Array.prototype.forEach.call( nodes, render );
	}

	// A cached page can be hours old: re-check once on load, then every minute.
	refresh();
	setInterval( refresh, 60000 );
	document.addEventListener( 'visibilitychange', function () {
		if ( document.visibilityState === 'visible' ) {
			refresh();
		}
	} );
}() );
