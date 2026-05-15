/**
 * Menu page controls — fulfilment toggle + search + dietary filter chips.
 *
 * Filters .lafka-favs__item cards based on:
 *   - data-lafka-product-name (text-match against search input)
 *   - data-lafka-product-tags (CSV of WC product tag slugs, matched
 *     against active dietary chips)
 *
 * Fulfilment toggle is persisted to localStorage.peppery.fulfilment for
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

	var KEY_FULFILMENT = 'peppery.fulfilment';
	var DEFAULT_FULFILMENT = 'pickup';

	function $$( sel, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( sel ) );
	}

	function getFulfilment() {
		try {
			return localStorage.getItem( KEY_FULFILMENT ) || DEFAULT_FULFILMENT;
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

	// -------- Fulfilment tabs ---------------------------------------------
	function initTabs() {
		var tabs = $$( '[data-lafka-fulfilment]', root );
		if ( ! tabs.length ) { return; }
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

	// -------- Search ------------------------------------------------------
	function initSearch() {
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		var clear = root.querySelector( '[data-lafka-menu-search-clear]' );
		if ( ! input ) { return; }

		input.addEventListener( 'input', function () {
			clear.hidden = ! input.value;
			applyFilter();
		} );

		clear.addEventListener( 'click', function () {
			input.value = '';
			clear.hidden = true;
			input.focus();
			applyFilter();
		} );
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
