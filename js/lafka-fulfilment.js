/**
 * GX4: the Pickup / Delivery preference.
 *
 * One preference, owned by lafka-plugin (cookie `lafka_order_method`, which
 * preselects the matching WooCommerce shipping rate at checkout). This file:
 *  - writes the cookie AND mirrors the menu/cart controllers' localStorage key
 *    (window.lafkaCfg.fulfilmentKey, default `lafka.fulfilment`);
 *  - keeps every `[data-lafka-fulfilment-input]` radio group on the page (header,
 *    drawer) in sync, and shows the matching `[data-lafka-fulfilment-note]`;
 *  - dispatches `lafka:fulfilment` ({ method }) for insights, plus the existing
 *    `lafka:fulfilment-change` ({ mode }) the menu/cart controllers listen to.
 *
 * @since 7.2.0 (GX4)
 */
( function () {
	'use strict';

	var cfg = window.lafkaCfg || {};
	var KEY = cfg.fulfilmentKey || 'lafka.fulfilment';
	var COOKIE = 'lafka_order_method';
	var MODES = [ 'pickup', 'delivery' ];

	function readCookie() {
		var m = new RegExp( '(?:^|;\\s*)' + COOKIE + '=([^;]+)' ).exec( document.cookie );
		return m ? decodeURIComponent( m[ 1 ] ) : '';
	}

	function read() {
		var value = readCookie();
		if ( MODES.indexOf( value ) === -1 ) {
			try {
				value = window.localStorage.getItem( KEY ) || '';
			} catch {
				value = '';
			}
		}
		return MODES.indexOf( value ) === -1 ? '' : value;
	}

	function write( method ) {
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = COOKIE + '=' + method + '; path=/; max-age=31536000; SameSite=Lax' + secure;
		try {
			window.localStorage.setItem( KEY, method );
		} catch {
			// Storage blocked: the cookie still carries the preference.
		}
	}

	function sync( method ) {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lafka-fulfilment-input]' ), function ( input ) {
			input.checked = input.value === method;
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lafka-fulfilment-note]' ), function ( note ) {
			note.hidden = note.getAttribute( 'data-lafka-fulfilment-note' ) !== method;
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lafka-fulfilment-text]' ), function ( node ) {
			var label = node.getAttribute( 'data-lafka-fulfilment-' + method );
			if ( label ) {
				node.textContent = label;
			}
		} );
	}

	function choose( method, source ) {
		if ( MODES.indexOf( method ) === -1 ) {
			return;
		}
		write( method );
		sync( method );
		document.dispatchEvent( new CustomEvent( 'lafka:fulfilment', { detail: { method: method } } ) );
		if ( source !== 'controls' ) {
			document.dispatchEvent( new CustomEvent( 'lafka:fulfilment-change', { detail: { mode: method, source: 'lafka-fulfilment' }, bubbles: true } ) );
		}
	}

	document.addEventListener( 'change', function ( e ) {
		var input = e.target;
		if ( input && input.matches && input.matches( '[data-lafka-fulfilment-input]' ) && input.checked ) {
			choose( input.value, 'radio' );
		}
	} );

	// The /menu/ and cart page controllers announce their own changes.
	document.addEventListener( 'lafka:fulfilment-change', function ( e ) {
		var detail = e.detail || {};
		if ( detail.source !== 'lafka-fulfilment' && detail.mode ) {
			choose( detail.mode, 'controls' );
		}
	} );

	function init() {
		var method = read();
		if ( method ) {
			sync( method );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
