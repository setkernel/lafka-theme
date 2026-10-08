/**
 * Lafka countdown — a small vanilla timer (replaces jQuery Countdown).
 *
 * Markup it starts:
 *  - `[data-lafka-countdown-until]`: Unix time (seconds) the countdown ends at;
 *    optional `data-lafka-countdown-format` (default "{dn} {dl} {hn}:{mnn}:{snn}").
 *  - `.lafka_order_hours_countdown` (plugin "store closed" card / branch modal):
 *    `data-diff-days|hours|minutes|seconds` from now, `data-output-format`.
 * Tokens: {dn} {dl} days (Intl unit format, e.g. "2 days"), {hn} hours,
 * {mnn} / {snn} two-digit minutes / seconds. Output goes in a
 * `span.countdown_time_small` (or `.countdown_time_tiny` for sale timers).
 * `window.lafkaCountdown( root )` (re)starts every timer inside `root`.
 */
( function () {
	'use strict';

	const lang = document.documentElement.lang || undefined;
	const pad = ( n ) => String( n ).padStart( 2, '0' );

	function render( el, end, format, cls ) {
		const left = Math.max( 0, Math.round( ( end - Date.now() ) / 1000 ) );
		const d = Math.floor( left / 86400 );
		const dayText = new Intl.NumberFormat( lang, { style: 'unit', unit: 'day', unitDisplay: 'long' } ).format( d );
		const text = format
			.replace( '{dn} {dl}', dayText ).replace( '{dn}', String( d ) ).replace( '{dl}', '' )
			.replace( '{hn}', String( Math.floor( ( left % 86400 ) / 3600 ) ) )
			.replace( '{mnn}', pad( Math.floor( ( left % 3600 ) / 60 ) ) )
			.replace( '{snn}', pad( left % 60 ) );
		const span = document.createElement( 'span' );
		span.className = cls;
		span.textContent = text.trim();
		el.replaceChildren( span );
		return left > 0;
	}

	function start( el, end, format, cls ) {
		window.clearInterval( el.lafkaCountdownTimer );
		if ( render( el, end, format, cls ) ) {
			el.lafkaCountdownTimer = window.setInterval( () => {
				if ( ! render( el, end, format, cls ) ) {
					window.clearInterval( el.lafkaCountdownTimer );
				}
			}, 1000 );
		}
	}

	window.lafkaCountdown = function ( root ) {
		const scope = root && root.querySelectorAll ? root : document;
		scope.querySelectorAll( '[data-lafka-countdown-until]' ).forEach( ( el ) => {
			const format = el.getAttribute( 'data-lafka-countdown-format' ) || '{dn} {dl} {hn}:{mnn}:{snn}';
			start( el, Number( el.getAttribute( 'data-lafka-countdown-until' ) ) * 1000, format, 'countdown_time_tiny' );
		} );
		scope.querySelectorAll( '.lafka_order_hours_countdown' ).forEach( ( el ) => {
			const secs = ( Number( el.dataset.diffDays ) || 0 ) * 86400 + ( Number( el.dataset.diffHours ) || 0 ) * 3600
				+ ( Number( el.dataset.diffMinutes ) || 0 ) * 60 + ( Number( el.dataset.diffSeconds ) || 0 );
			start( el, Date.now() + secs * 1000, el.dataset.outputFormat || '{hn}:{mnn}:{snn}', 'countdown_time_small' );
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => window.lafkaCountdown( document ) );
	} else {
		window.lafkaCountdown( document );
	}
}() );
