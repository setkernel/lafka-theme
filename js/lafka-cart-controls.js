/**
 * Cart page controls — pickup/delivery tabs + clear-order button + quantity
 * auto-update.
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
		// GX4: the counter header / drawer radios (js/lafka-fulfilment.js)
		// announce their choice — mirror it so the page never shows two answers.
		document.addEventListener( 'lafka:fulfilment-change', function ( e ) {
			var detail = e.detail || {};
			if ( detail.source !== 'lafka-fulfilment' || ! detail.mode ) {
				return;
			}
			tabs.forEach( function ( t ) {
				var on = t.getAttribute( 'data-lafka-fulfilment' ) === detail.mode;
				t.classList.toggle( 'is-active', on );
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );
		} );
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

	/*
	 * O-16: a quantity change updates the cart by itself ~600 ms after the
	 * last tap/keystroke, through WooCommerce's own "Update cart" button (its
	 * cart.js turns that click into the AJAX update), so the disabled
	 * full-width button can be hidden. Without JavaScript the class is never
	 * added and the button stays. The theme's −/+ buttons change the value
	 * through jQuery (.trigger('change')), which native listeners never see —
	 * so listen through jQuery when it is present.
	 */
	var AUTOUPDATE_DELAY = 600;
	var autoUpdateTimer = null;

	function submitQuantityUpdate() {
		autoUpdateTimer = null;
		var updateBtn = document.querySelector( '.woocommerce-cart-form button[name="update_cart"]' );
		if ( ! updateBtn ) {
			return;
		}
		updateBtn.removeAttribute( 'disabled' );
		updateBtn.removeAttribute( 'aria-disabled' );
		updateBtn.click();
	}

	function scheduleQuantityUpdate( event ) {
		var target = event && event.target;
		if ( ! target || ! target.matches || ! target.matches( '.woocommerce-cart-form input.qty' ) ) {
			return;
		}
		// Mid-typing an empty box is not a quantity yet.
		if ( '' === String( target.value ).trim() ) {
			return;
		}
		if ( autoUpdateTimer ) {
			window.clearTimeout( autoUpdateTimer );
		}
		autoUpdateTimer = window.setTimeout( submitQuantityUpdate, AUTOUPDATE_DELAY );
	}

	function markAutoUpdate() {
		// WooCommerce replaces the form after each update: re-mark every time.
		$$( '.woocommerce-cart-form' ).forEach( function ( form ) {
			form.classList.add( 'lafka-cart-autoupdate' );
		} );
	}

	function initAutoUpdate() {
		if ( ! document.querySelector( '.woocommerce-cart-form' ) ) {
			return;
		}
		markAutoUpdate();
		if ( window.jQuery ) {
			window.jQuery( document.body )
				.on( 'change input', '.woocommerce-cart-form input.qty', scheduleQuantityUpdate )
				.on( 'updated_wc_div updated_cart_totals', markAutoUpdate );
		} else {
			document.addEventListener( 'change', scheduleQuantityUpdate );
			document.addEventListener( 'input', scheduleQuantityUpdate );
		}
	}

	function init() {
		initTabs();
		initClearOrder();
		initAutoUpdate();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
