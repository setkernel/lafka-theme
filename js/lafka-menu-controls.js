/**
 * Menu page controls — fulfilment toggle + search + dietary filter chips.
 *
 * Filters .lafka-favs__item cards / counter rows based on:
 *   - data-lafka-product-search (name + short description; case- and
 *     accent-insensitive, every word must match), else the product name
 *   - data-lafka-product-tags (CSV of WC product tag slugs, matched
 *     against active dietary chips)
 *
 * Fulfilment toggle is persisted to localStorage.lafka.fulfilment for
 * use by other surfaces (cart, checkout). It does NOT hide products —
 * the operator's catalogue is the same for pickup and delivery.
 *
 * Also drives the category strip: the sticky offset used by section anchors,
 * the scroll-spy that marks the chip of the section in view, and keeping the
 * active chip visible in the horizontally scrolling strip.
 *
 * @since 5.68.0
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-lafka-menu-controls]' ) || document.createElement( 'div' );

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

		// GX4: mirror the counter drawer / header radios (js/lafka-fulfilment.js).
		document.addEventListener( 'lafka:fulfilment-change', function ( e ) {
			var detail = e.detail || {};
			if ( detail.source === 'lafka-fulfilment' && detail.mode ) {
				reflect( detail.mode );
			}
		} );

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
	// Case- and accent-insensitive ("jalapeno" finds "Jalapeño").
	function fold( text ) {
		var out = String( text || '' ).toLowerCase();
		if ( out.normalize ) {
			out = out.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
		}
		return out.replace( /\s+/g, ' ' ).trim();
	}

	function searchForm() {
		return root.querySelector( '[data-lafka-menu-search]' );
	}

	function isServerSearch() {
		var form = searchForm();
		return !! form && form.getAttribute( 'data-lafka-menu-search-mode' ) === 'server';
	}

	function initSearch() {
		var form = searchForm();
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		var clear = root.querySelector( '[data-lafka-menu-search-clear]' );
		if ( ! input ) { return; }

		input.addEventListener( 'input', function () {
			if ( clear ) { clear.hidden = ! input.value; }
			if ( ! isServerSearch() ) {
				applyFilter();
			}
		} );

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				input.value = '';
				clear.hidden = true;
				input.focus();
				if ( ! isServerSearch() ) {
					applyFilter();
				}
			} );
		}

		// Live mode filters in place; Enter only leaves the page (a real
		// product search) when nothing here matches, or the page has no rows.
		if ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				if ( isServerSearch() ) {
					if ( ! input.value.trim() ) { e.preventDefault(); }
					return;
				}
				var visible = applyFilter();
				if ( ! input.value.trim() || visible > 0 ) {
					e.preventDefault();
				}
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
				resetChips();
				applyFilter();
			} );
		}
	}

	function resetChips() {
		$$( '[data-lafka-filter]', root ).forEach( function ( chip ) {
			chip.setAttribute( 'aria-pressed', 'false' );
			chip.classList.remove( 'is-on' );
		} );
		updateClearAllVisibility();
	}

	function updateClearAllVisibility() {
		var clearAll = root.querySelector( '[data-lafka-clear-filters]' );
		if ( ! clearAll ) { return; }
		clearAll.hidden = ! $$( '[data-lafka-filter]', root ).some( function ( c ) {
			return c.getAttribute( 'aria-pressed' ) === 'true';
		} );
	}

	// "Clear search and filters" in the empty state.
	function initReset() {
		$$( '[data-lafka-menu-reset]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var input = root.querySelector( '[data-lafka-menu-search-input]' );
				var clear = root.querySelector( '[data-lafka-menu-search-clear]' );
				if ( input ) { input.value = ''; }
				if ( clear ) { clear.hidden = true; }
				resetChips();
				applyFilter();
				if ( input ) { input.focus(); }
			} );
		} );
	}

	// -------- Apply combined filter to product cards ----------------------
	var announceTimer = null;

	function announce( text ) {
		var status = document.querySelector( '[data-lafka-menu-status]' );
		if ( ! status ) { return; }
		window.clearTimeout( announceTimer );
		announceTimer = window.setTimeout( function () {
			status.textContent = text;
		}, 400 );
	}

	function countLabel( n ) {
		var i18n = window.lafkaMenuI18n || {};
		if ( 0 === n ) {
			return i18n.none || 'No menu items match.';
		}
		if ( 1 === n ) {
			return i18n.one || '1 item matches.';
		}
		return ( i18n.many || '%d items match.' ).replace( '%d', String( n ) );
	}

	function applyFilter() {
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		var query = input && ! isServerSearch() ? fold( input.value ) : '';
		var activeChips = $$( '[data-lafka-filter][aria-pressed="true"]', root ).map( function ( c ) {
			return c.getAttribute( 'data-lafka-filter' );
		} );
		var filtering = '' !== query || activeChips.length > 0;

		var cards = $$( '.lafka-favs__item, .lafka-menu__grid > li' );
		var totalVisible = 0;

		cards.forEach( function ( card ) {
			var haystack = fold( card.getAttribute( 'data-lafka-product-search' ) || card.getAttribute( 'data-lafka-product-name' ) || card.textContent );
			var tags = ( card.getAttribute( 'data-lafka-product-tags' ) || '' ).toLowerCase().split( ',' ).map( function ( t ) { return t.trim(); } );

			var matchSearch = ! query || query.split( ' ' ).every( function ( word ) {
				return haystack.indexOf( word ) !== -1;
			} );
			var matchChips = activeChips.length === 0 || activeChips.every( function ( chip ) {
				return tags.indexOf( chip ) !== -1;
			} );

			var visible = matchSearch && matchChips;
			card.hidden = ! visible;
			card.classList.toggle( 'is-hidden-by-filter', ! visible );
			if ( visible ) {
				totalVisible++;
			}
		} );

		// Hide subsections, then whole sections, left without a visible row.
		$$( '[data-lafka-menu-sub], .lafka-menu__group' ).forEach( function ( g ) {
			g.hidden = ! g.querySelector( '.lafka-favs__item:not([hidden]), .lafka-menu__grid > li:not([hidden])' );
		} );

		// Show / hide the empty state (it carries the reset button).
		var emptyEl = document.querySelector( '[data-lafka-menu-empty]' );
		if ( emptyEl && cards.length ) {
			emptyEl.hidden = totalVisible > 0;
		}

		if ( filtering ) {
			announce( countLabel( totalVisible ) );
		}
		document.body.classList.toggle( 'lafka-menu-is-filtered', filtering );
		return totalVisible;
	}

	// -------- Category strip: sticky offset, scroll-spy, active chip -------
	function initCategoryStrip() {
		var nav = document.querySelector( '.lafka-menu__cats' );
		if ( ! nav ) { return; }
		var list = nav.querySelector( '.lafka-menu__cats-list' );
		var chips = $$( '.lafka-menu__cat-chip', nav );

		// Keep a chip visible inside the horizontally-scrolling strip without
		// scrolling the page vertically (scrollIntoView would).
		function reveal( chip ) {
			if ( ! list || ! chip || list.scrollWidth <= list.clientWidth ) { return; }
			var left = chip.offsetLeft - ( ( list.clientWidth - chip.offsetWidth ) / 2 );
			list.scrollLeft = Math.max( 0, left );
		}

		// Section anchors land below the sticky strip.
		function measure() {
			var top = parseFloat( window.getComputedStyle( nav ).top ) || 0;
			document.documentElement.style.setProperty( '--lafka-menu-cats-offset', Math.round( top + nav.offsetHeight + 8 ) + 'px' );
		}
		measure();
		window.addEventListener( 'resize', measure, { passive: true } );

		reveal( nav.querySelector( '.lafka-menu__cat-chip.is-active' ) );

		// In-page chips only (/menu/): archives link to other pages.
		var targets = [];
		chips.forEach( function ( chip ) {
			var href = chip.getAttribute( 'href' ) || '';
			if ( href.charAt( 0 ) !== '#' ) { return; }
			var el = document.getElementById( href.slice( 1 ) );
			if ( el ) {
				targets.push( { chip: chip, el: el } );
			}
		} );
		if ( targets.length < 2 ) { return; }

		var current = null;
		function setActive( chip ) {
			if ( chip === current ) { return; }
			current = chip;
			chips.forEach( function ( c ) {
				var on = c === chip;
				c.classList.toggle( 'is-active', on );
				if ( on ) {
					c.setAttribute( 'aria-current', 'true' );
				} else {
					c.removeAttribute( 'aria-current' );
				}
			} );
			reveal( chip );
		}

		var ticking = false;
		function spy() {
			ticking = false;
			// A section is "in view" once its top passes a quarter of the way
			// down the visible area below the strip.
			var navBottom = nav.getBoundingClientRect().bottom;
			var line = navBottom + Math.max( 16, ( window.innerHeight - navBottom ) * 0.25 );
			// The first target is "All" (the whole body); a section wins once
			// its top has scrolled up to just below the strip.
			var active = targets[ 0 ].chip;
			for ( var i = 1; i < targets.length; i++ ) {
				var t = targets[ i ];
				if ( t.el.hidden ) { continue; }
				if ( t.el.getBoundingClientRect().top <= line ) {
					active = t.chip;
				} else {
					break;
				}
			}
			setActive( active );
		}

		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( spy );
			}
		}, { passive: true } );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				setActive( chip );
			} );
		} );

		spy();
	}

	function init() {
		initTabs();
		initSearch();
		initFilters();
		initReset();
		initCategoryStrip();
		// A prefilled live search (browser back / autofill) applies at once.
		var input = root.querySelector( '[data-lafka-menu-search-input]' );
		if ( input && input.value && ! isServerSearch() ) {
			applyFilter();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
