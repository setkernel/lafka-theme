/**
 * Classic checkout: plain-language inline field errors (O-31).
 *
 * WooCommerce's checkout.js validates a field on blur/change (it adds
 * .woocommerce-invalid[-required-field|-email|-phone] to the .form-row and
 * aria-invalid to the input) but only paints it red; the worded message
 * (p.checkout-inline-error-message#{field}_description + aria-describedby)
 * appears only after a failed submit. This prints that SAME element as soon
 * as a field is left invalid — WooCommerce itself removes it again on input
 * and when the field validates — and flags a phone with too few digits
 * (window.lafkaCheckoutFields.phoneMinDigits, default 7). The server-side
 * phone rule lives in lafka-plugin.
 *
 * Runs after WooCommerce's handler (next tick), so it only reads the state
 * WooCommerce decided and never fights it.
 *
 * @since 7.3.0
 */
( function ( $ ) {
	'use strict';

	if ( ! $ ) {
		return;
	}

	const cfg = window.lafkaCheckoutFields || {};
	const i18n = cfg.i18n || {};
	let MIN_DIGITS = parseInt( cfg.phoneMinDigits, 10 );
	if ( isNaN( MIN_DIGITS ) ) {
		MIN_DIGITS = 7;
	}
	const MESSAGE_CLASS = 'checkout-inline-error-message';

	function labelText( row ) {
		const label = row.querySelector( 'label' );
		if ( ! label ) {
			return '';
		}
		const copy = label.cloneNode( true );
		Array.prototype.forEach.call( copy.querySelectorAll( '.required, .optional, abbr, .screen-reader-text' ), function ( node ) {
			node.parentNode.removeChild( node );
		} );
		return ( copy.textContent || '' ).replace( /\s+/g, ' ' ).replace( /[\s:*]+$/, '' ).trim();
	}

	function messageFor( row, field ) {
		const empty = '' === String( field.value || '' ).trim();
		if ( row.classList.contains( 'woocommerce-invalid-required-field' ) && empty ) {
			const label = labelText( row );
			return label ? String( i18n.required || '%s is required.' ).replace( '%s', label ) : ( i18n.invalid || '' );
		}
		if ( row.classList.contains( 'woocommerce-invalid-email' ) ) {
			return i18n.email || '';
		}
		if ( row.classList.contains( 'woocommerce-invalid-phone' ) ) {
			return i18n.phone || '';
		}
		return i18n.invalid || '';
	}

	function digits( value ) {
		return String( value || '' ).replace( /\D+/g, '' ).length;
	}

	function markPhone( row, field ) {
		const value = String( field.value || '' ).trim();
		if ( ! MIN_DIGITS || '' === value || digits( value ) >= MIN_DIGITS ) {
			return;
		}
		row.classList.remove( 'woocommerce-validated' );
		row.classList.add( 'woocommerce-invalid', 'woocommerce-invalid-phone' );
		field.setAttribute( 'aria-invalid', 'true' );
	}

	function describe( field ) {
		const row = field.closest( '.form-row' );
		if ( ! row || ! field.id ) {
			return;
		}
		// Phone rows only (WooCommerce's .validate-phone) — never the card
		// number / expiry inputs, which are type="tel" too.
		if ( row.classList.contains( 'validate-phone' ) ) {
			markPhone( row, field );
		}
		let existing = row.querySelector( '.' + MESSAGE_CLASS );
		if ( ! row.classList.contains( 'woocommerce-invalid' ) ) {
			return; // WooCommerce already removed its message when the field validated.
		}
		const text = messageFor( row, field );
		if ( ! text ) {
			return;
		}
		const id = field.id + '_description';
		if ( ! existing ) {
			existing = document.createElement( 'p' );
			existing.className = MESSAGE_CLASS;
			existing.id = id;
			row.appendChild( existing );
		}
		existing.textContent = text;
		field.setAttribute( 'aria-invalid', 'true' );
		field.setAttribute( 'aria-describedby', existing.id || id );
	}

	$( document.body ).on( 'validate change focusout', 'form.checkout .form-row .input-text, form.checkout .form-row select', function () {
		const field = this;
		window.setTimeout( function () {
			describe( field );
		}, 0 );
	} );

	// A submit re-validates every field; keep a too-short phone flagged there
	// too. No return value: a handler's return decides whether WooCommerce
	// submits, and this one must never overrule another's `false`.
	$( 'form.checkout' ).on( 'checkout_place_order', function () {
		$( this ).find( '.validate-phone .input-text' ).each( function () {
			describe( this );
		} );
	} );
}( window.jQuery ) );
