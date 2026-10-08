/**
 * Live open/closed status: keeps the announce bar and the counter header's
 * wording true on a page that was cached or left open.
 *
 * The wording is never computed here. The server prints the status once as
 * window.lafkaOpenStatus ({ is_open, strong, rest, label, until }); `until` is
 * the Unix time the wording next changes. When that time passes the script
 * asks the plugin's /lafka/v1/open-status route for the new wording.
 *
 * Depends on lafka-core (window.lafka.api).
 *
 * @since 7.2.0 (GX4)
 */
( function ( w, d ) {
	'use strict';

	const lafka = w.lafka;
	let state = w.lafkaOpenStatus;
	if ( ! lafka || ! lafka.api || ! state ) {
		return;
	}

	function render() {
		const open = !! state.is_open;
		const rest = state.rest ? ' · ' + state.rest : '';

		d.querySelectorAll( '[data-lafka-open-status]' ).forEach( function ( node ) {
			const text = node.querySelector( '[data-lafka-open-status-text]' );
			if ( text ) {
				const strong = d.createElement( 'strong' );
				strong.textContent = state.strong;
				text.textContent = '';
				text.appendChild( strong );
				text.appendChild( d.createTextNode( rest ) );
			}
			node.classList.toggle( 'is-open', open );
			node.classList.toggle( 'is-closed', ! open );
		} );

		const bar = d.querySelector( '[data-lafka-announce-bar]' );
		const label = bar && bar.querySelector( '[data-lafka-status-label]' );
		if ( label ) {
			label.textContent = state.label;
			bar.classList.toggle( 'lafka-announce-bar--open', open );
			bar.classList.toggle( 'lafka-announce-bar--closed', ! open );
		}
	}

	let retryAt = 0;

	function check() {
		const now = Date.now() / 1000;
		if ( ! state.until || now < state.until || now < retryAt ) {
			return;
		}
		retryAt = now + 60;
		lafka.api.get( 'lafka/v1/open-status' ).then( function ( next ) {
			if ( next && next.label ) {
				state = next;
				render();
			}
		} ).catch( function () {
			// Keep the printed wording; the next tick tries again.
		} );
	}

	check();
	w.setInterval( check, 30000 );
	d.addEventListener( 'visibilitychange', function () {
		if ( d.visibilityState === 'visible' ) {
			check();
		}
	} );
}( window, document ) );
