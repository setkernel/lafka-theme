/**
 * Menu page controls — fulfilment toggle + search + dietary filter chips.
 *
 * Filters .lafka-favs__item cards based on:
 *   - data-lafka-product-name (text-match against search input)
 *   - data-lafka-product-tags (CSV of WC product tag slugs, matched
 *     against active dietary chips)
 *
 * Fulfilment toggle is persisted to localStorage.lafka.fulfilment for
 * use by other surfaces (cart, checkout). It does NOT hide products —
 * the operator's catalogue is the same for pickup and delivery.
 *
 * @since 5.68.0
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-lafka-menu-controls]' );
	if ( ! root ) {
		return;
	}

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
		var ev = new CustomEvent( 'lafka:fulfilment-change', { detail: { mode: mode }, bubbles: true } );
		document.dispatchEvent( ev );
	}

	// -------- Fulfilment radiogroup ---------------------------------------
	// These are mutually-exclusive mode switches that reveal no panels, so the
	// container is a role="radiogroup" with role="radio" buttons (aria-checked).
	// We manage a roving tabindex (checked = 0, others = -1) and ArrowLeft/Right/
	// Up/Down to move + check selection, per the ARIA radiogroup pattern.
	function initTabs() {
		var tabs = $$( '[data-lafka-fulfilment]', root );
		if ( ! tabs.length ) { return; }

		// Reflect a mode in the DOM (class + ARIA + roving tabindex) without
		// persisting it — used to mirror the stored state on load.
		function reflect( mode ) {
			tabs.forEach( function ( tab ) {
				var on = tab.getAttribute( 'data-lafka-fulfilment' ) === mode;
				tab.classList.toggle( 'is-active', on );
				tab.setAttribute( 'aria-checked', on ? 'true' : 'false' );
				tab.setAttribute( 'tabindex', on ? '0' : '-1' );
			} );
		}

		// User-driven selection: reflect, persist (fires change event), and
		// optionally move focus to the newly-checked radio (arrow-key nav).
		function select( mode, moveFocus ) {
			reflect( mode );
			if ( moveFocus ) {
				tabs.forEach( function ( tab ) {
					if ( tab.getAttribute( 'data-lafka-fulfilment' ) === mode ) {
						tab.focus();
					}
				} );
			}
			setFulfilment( mode );
		}

		// Mirror persisted state on load (no change event on first paint).
		reflect( getFulfilment() );

		tabs.forEach( function ( tab, idx ) {
			var mode = tab.getAttribute( 'data-lafka-fulfilment' );

			tab.addEventListener( 'click', function () {
				select( mode );
			} );

			tab.addEventListener( 'keydown', function ( e ) {
				var next;
				if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
					next = ( idx + 1 ) % tabs.length;
				} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
					next = ( idx - 1 + tabs.length ) % tabs.length;
				} else {
					return;
				}
				e.preventDefault();
				select( tabs[ next ].getAttribute( 'data-lafka-fulfilment' ), true );
			} );
		} );
	}

	// -------- Search ------------------------------------------------------
	function initSearch() {
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		var clear = root.querySelector( '[data-lafka-menu-search-clear]' );
		if ( ! input ) { return; }

		input.addEventListener( 'input', function () {
			if ( clear ) { clear.hidden = ! input.value; }
			applyFilter();
		} );

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				input.value = '';
				clear.hidden = true;
				input.focus();
				applyFilter();
			} );
		}
	}

	// -------- Dietary filter chips ----------------------------------------
	function initFilters() {
		var chips = $$( '[data-lafka-filter]', root );
		var clearAll = root.querySelector( '[data-lafka-clear-filters]' );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var pressed = chip.getAttribute( 'aria-pressed' ) === 'true';
				chip.setAttribute( 'aria-pressed', pressed ? 'false' : 'true' );
				chip.classList.toggle( 'is-on', ! pressed );
				updateClearAllVisibility();
				applyFilter();
			} );
		} );

		if ( clearAll ) {
			clearAll.addEventListener( 'click', function () {
				chips.forEach( function ( chip ) {
					chip.setAttribute( 'aria-pressed', 'false' );
					chip.classList.remove( 'is-on' );
				} );
				updateClearAllVisibility();
				applyFilter();
			} );
		}

		function updateClearAllVisibility() {
			if ( ! clearAll ) { return; }
			var any = chips.some( function ( c ) { return c.getAttribute( 'aria-pressed' ) === 'true'; } );
			clearAll.hidden = ! any;
		}
	}

	// -------- Apply combined filter to product cards ----------------------
	function applyFilter() {
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		var query = input ? input.value.trim().toLowerCase() : '';
		var activeChips = $$( '[data-lafka-filter][aria-pressed="true"]', root ).map( function ( c ) {
			return c.getAttribute( 'data-lafka-filter' );
		} );

		var cards = $$( '.lafka-favs__item, .lafka-menu__grid > li' );
		var emptyTargets = $$( '.lafka-menu__group' );
		var totalVisible = 0;
		var visiblePerGroup = new Map();

		cards.forEach( function ( card ) {
			var name = ( card.getAttribute( 'data-lafka-product-name' ) || card.textContent || '' ).toLowerCase();
			var tags = ( card.getAttribute( 'data-lafka-product-tags' ) || '' ).toLowerCase().split( ',' ).map( function ( t ) { return t.trim(); } );

			var matchSearch = ! query || name.indexOf( query ) !== -1;
			var matchChips = activeChips.length === 0 || activeChips.every( function ( chip ) {
				return tags.indexOf( chip ) !== -1;
			} );

			var visible = matchSearch && matchChips;
			card.hidden = ! visible;
			card.classList.toggle( 'is-hidden-by-filter', ! visible );
			if ( visible ) {
				totalVisible++;
				var group = card.closest( '.lafka-menu__group' );
				if ( group ) {
					visiblePerGroup.set( group, ( visiblePerGroup.get( group ) || 0 ) + 1 );
				}
			}
		} );

		// Hide entire group sections when they have no visible cards.
		emptyTargets.forEach( function ( g ) {
			g.hidden = ! ( visiblePerGroup.get( g ) > 0 );
		} );

		// Show / hide global empty state.
		var emptyEl = document.querySelector( '[data-lafka-menu-empty]' );
		if ( emptyEl ) {
			emptyEl.hidden = totalVisible > 0;
		}
	}

	function init() {
		initTabs();
		initSearch();
		initFilters();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
