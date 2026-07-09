/**
 * NX2-04 — Customizer live preview for the preset switcher (+ accent/brand).
 *
 * Zero client-side style math: the server localizes, per preset, the exact
 * three CSS strings a real render would emit (PTL, font-faces, dynamic-css).
 * Switching preset = swapping the text of the three style blocks WP already
 * printed, plus toggling html[data-theme]. Publish then renders the same
 * thing server-side by construction.
 */
( function ( api ) {
	'use strict';

	var data = window.lafkaPresetPreview || { payloads: {} };

	function styleEl( id ) {
		var el = document.getElementById( id );
		if ( ! el ) {
			// dynamic-css always exists; PTL/fonts are absent when Peppery is
			// active (empty inline CSS renders no tag) — create the shell so
			// a swap TO a non-default preset has somewhere to land.
			el = document.createElement( 'style' );
			el.id = id;
			document.head.appendChild( el );
		}
		return el;
	}

	function applyPreset( slug ) {
		var payload = data.payloads[ slug ];
		if ( ! payload ) {
			return;
		}
		styleEl( 'lafka-preset-inline-css' ).textContent = payload.ptl;
		styleEl( 'lafka-preset-fonts-inline-css' ).textContent = payload.fonts;
		styleEl( 'lafka-style-inline-css' ).textContent = payload.dynamicCss;
		if ( payload.dark ) {
			document.documentElement.setAttribute( 'data-theme', 'dark' );
		} else {
			document.documentElement.removeAttribute( 'data-theme' );
		}
	}

	api( 'lafka_active_preset', function ( setting ) {
		setting.bind( applyPreset );
	} );

	// Accent + brand: dynamic-css emits these as three plain custom
	// properties (no server-side ramp math), so postMessage can set them
	// directly. --lafka-menu-highlight-bg-color falls back to accent when
	// the operator hasn't overridden the menu hover background.
	api( 'lafka_accent_color', function ( setting ) {
		setting.bind( function ( value ) {
			var root = document.documentElement.style;
			root.setProperty( '--lafka-accent-color', value );
			root.setProperty( '--lafka-color-accent-500', value );
		} );
	} );

	api( 'lafka_brand_color', function ( setting ) {
		setting.bind( function ( value ) {
			document.documentElement.style.setProperty( '--lafka-color-brand-500', value );
		} );
	} );
} )( wp.customize );
