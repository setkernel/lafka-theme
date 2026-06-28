/**
 * Cart page controls — pickup/delivery tabs + clear-order button.
 *
 * Pairs with woocommerce/cart/cart.php (v5.68.0 additions). Reuses the
 * lafka.fulfilment localStorage key set by menu-controls so the user's
 * choice persists across pages.
 *
 * @since 5.68.0
 */
( function () {
	'use strict';

	// The fulfilment storage contract is defined once in PHP and handed to the
	// JS via window.lafkaCfg (wp_localize_script), so the menu and cart
	// controllers can never read different keys. The literals below are a
	// brand-neutral fallback only, used if the localized config is absent.
	var LAFKA_CFG = window.lafkaCfg || {};
	var KEY_FULFILMENT = LAFKA_CFG.fulfilmentKey || 'lafka.fulfilment';
	var DEFAULT_FULFILMENT = LAFKA_CFG.fulfilmentDefault || 'pickup';
	var LEGACY_KEY_FULFILMENT = LAFKA_CFG.fulfilmentLegacyKey || '';

	function $$( sel, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( sel ) );
	}

	function getFulfilment() {
		try {
			var value = localStorage.getItem( KEY_FULFILMENT );
			// One-time migration: adopt the value stored under the pre-rename
			// key when the current key has not been written yet, so returning
			// customers keep their previously-chosen fulfilment method.
			if ( null === value && LEGACY_KEY_FULFILMENT ) {
				value = localStorage.getItem( LEGACY_KEY_FULFILMENT );
				if ( null !== value ) {
					try {
						localStorage.setItem( KEY_FULFILMENT, value );
						localStorage.removeItem( LEGACY_KEY_FULFILMENT );
					} catch {
						/* ignore */
					}
				}
			}
			return value || DEFAULT_FULFILMENT;
		} catch {
			return DEFAULT_FULFILMENT;
		}
	}

	function setFulfilment( mode ) {
		try {
			localStorage.setItem( KEY_FULFILMENT, mode );
		} catch {
			/* ignore */
		}
		document.dispatchEvent( new CustomEvent( 'lafka:fulfilment-change', { detail: { mode: mode }, bubbles: true } ) );
	}

	function initTabs() {
		var tabs = $$( '[data-lafka-cart-tabs] [data-lafka-fulfilment]' );
		if ( ! tabs.length ) {
			return;
		}
		var current = getFulfilment();
		tabs.forEach( function ( tab ) {
			var mode = tab.getAttribute( 'data-lafka-fulfilment' );
			var on = mode === current;
			tab.classList.toggle( 'is-active', on );
			tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			tab.addEventListener( 'click', function () {
				tabs.forEach( function ( t ) {
					var m = t.getAttribute( 'data-lafka-fulfilment' );
					t.classList.toggle( 'is-active', m === mode );
					t.setAttribute( 'aria-selected', m === mode ? 'true' : 'false' );
				} );
				setFulfilment( mode );
			} );
		} );
	}

	function initClearOrder() {
		var clearBtn = document.querySelector( '[data-lafka-cart-clear]' );
		if ( ! clearBtn ) {
			return;
		}
		clearBtn.addEventListener( 'click', function () {
			var ok = window.confirm( 'Clear all items from your order?' );
			if ( ! ok ) {
				return;
			}
			// Set every cart quantity input to 0, then submit the WC update form.
			var qtyInputs = $$( '.woocommerce-cart-form input.qty' );
			qtyInputs.forEach( function ( q ) {
				q.value = '0';
			} );
			var updateBtn = document.querySelector( 'button[name="update_cart"]' );
			if ( updateBtn ) {
				updateBtn.removeAttribute( 'disabled' );
				updateBtn.click();
			}
		} );
	}

	function init() {
		initTabs();
		initClearOrder();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
