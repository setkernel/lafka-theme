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

	const root = document.querySelector( '[data-lafka-menu-controls]' ) || document.createElement( 'div' );

	// The fulfilment storage contract is defined once in PHP and handed to the
	// JS via window.lafkaCfg (wp_localize_script), so the menu and cart
	// controllers can never read different keys. The literals below are a
	// brand-neutral fallback only, used if the localized config is absent.
	const LAFKA_CFG = window.lafkaCfg || {};
	const KEY_FULFILMENT = LAFKA_CFG.fulfilmentKey || 'lafka.fulfilment';
	const DEFAULT_FULFILMENT = LAFKA_CFG.fulfilmentDefault || 'pickup';
	const LEGACY_KEY_FULFILMENT = LAFKA_CFG.fulfilmentLegacyKey || '';

	function $$( sel, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( sel ) );
	}

	function getFulfilment() {
		try {
			let value = localStorage.getItem( KEY_FULFILMENT );
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
		const ev = new CustomEvent( 'lafka:fulfilment-change', { detail: { mode: mode }, bubbles: true } );
		document.dispatchEvent( ev );
	}

	// -------- Fulfilment radiogroup ---------------------------------------
	// These are mutually-exclusive mode switches that reveal no panels, so the
	// container is a role="radiogroup" with role="radio" buttons (aria-checked).
	// We manage a roving tabindex (checked = 0, others = -1) and ArrowLeft/Right/
	// Up/Down to move + check selection, per the ARIA radiogroup pattern.
	function initTabs() {
		const tabs = $$( '[data-lafka-fulfilment]', root );
		if ( ! tabs.length ) { return; }

		// Reflect a mode in the DOM (class + ARIA + roving tabindex) without
		// persisting it — used to mirror the stored state on load.
		function reflect( mode ) {
			tabs.forEach( function ( tab ) {
				const on = tab.getAttribute( 'data-lafka-fulfilment' ) === mode;
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
			const detail = e.detail || {};
			if ( detail.source === 'lafka-fulfilment' && detail.mode ) {
				reflect( detail.mode );
			}
		} );

		tabs.forEach( function ( tab, idx ) {
			const mode = tab.getAttribute( 'data-lafka-fulfilment' );

			tab.addEventListener( 'click', function () {
				select( mode );
			} );

			tab.addEventListener( 'keydown', function ( e ) {
				let next;
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
		let out = String( text || '' ).toLowerCase();
		if ( out.normalize ) {
			out = out.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
		}
		return out.replace( /\s+/g, ' ' ).trim();
	}

	function searchForm() {
		return root.querySelector( '[data-lafka-menu-search]' );
	}

	function isServerSearch() {
		const form = searchForm();
		return !! form && form.getAttribute( 'data-lafka-menu-search-mode' ) === 'server';
	}

	function initSearch() {
		const form = searchForm();
		const input = root.querySelector( '[data-lafka-menu-search-input]' );
		const clear = root.querySelector( '[data-lafka-menu-search-clear]' );
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
				const visible = applyFilter();
				if ( ! input.value.trim() || visible > 0 ) {
					e.preventDefault();
				}
			} );
		}
	}

	// -------- Dietary filter chips ----------------------------------------
	function initFilters() {
		const chips = $$( '[data-lafka-filter]', root );
		const clearAll = root.querySelector( '[data-lafka-clear-filters]' );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				const pressed = chip.getAttribute( 'aria-pressed' ) === 'true';
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
		const clearAll = root.querySelector( '[data-lafka-clear-filters]' );
		if ( ! clearAll ) { return; }
		clearAll.hidden = ! $$( '[data-lafka-filter]', root ).some( function ( c ) {
			return c.getAttribute( 'aria-pressed' ) === 'true';
		} );
	}

	// "Clear search and filters" in the empty state.
	function initReset() {
		$$( '[data-lafka-menu-reset]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const input = root.querySelector( '[data-lafka-menu-search-input]' );
				const clear = root.querySelector( '[data-lafka-menu-search-clear]' );
				if ( input ) { input.value = ''; }
				if ( clear ) { clear.hidden = true; }
				resetChips();
				applyFilter();
				if ( input ) { input.focus(); }
			} );
		} );
	}

	// -------- Apply combined filter to product cards ----------------------
	let announceTimer = null;

	function announce( text ) {
		const status = document.querySelector( '[data-lafka-menu-status]' );
		if ( ! status ) { return; }
		window.clearTimeout( announceTimer );
		announceTimer = window.setTimeout( function () {
			status.textContent = text;
		}, 400 );
	}

	function countLabel( n ) {
		const i18n = window.lafkaMenuI18n || {};
		if ( 0 === n ) {
			return i18n.none || 'No menu items match.';
		}
		if ( 1 === n ) {
			return i18n.one || '1 item matches.';
		}
		return ( i18n.many || '%d items match.' ).replace( '%d', String( n ) );
	}

	function applyFilter() {
		const input = root.querySelector( '[data-lafka-menu-search-input]' );
		const query = input && ! isServerSearch() ? fold( input.value ) : '';
		const activeChips = $$( '[data-lafka-filter][aria-pressed="true"]', root ).map( function ( c ) {
			return c.getAttribute( 'data-lafka-filter' );
		} );
		const filtering = '' !== query || activeChips.length > 0;

		const cards = $$( '.lafka-favs__item, .lafka-menu__grid > li' );
		let totalVisible = 0;

		cards.forEach( function ( card ) {
			const haystack = fold( card.getAttribute( 'data-lafka-product-search' ) || card.getAttribute( 'data-lafka-product-name' ) || card.textContent );
			const tags = ( card.getAttribute( 'data-lafka-product-tags' ) || '' ).toLowerCase().split( ',' ).map( function ( t ) { return t.trim(); } );

			const matchSearch = ! query || query.split( ' ' ).every( function ( word ) {
				return haystack.indexOf( word ) !== -1;
			} );
			const matchChips = activeChips.length === 0 || activeChips.every( function ( chip ) {
				return tags.indexOf( chip ) !== -1;
			} );

			const visible = matchSearch && matchChips;
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
		const emptyEl = document.querySelector( '[data-lafka-menu-empty]' );
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
		const nav = document.querySelector( '.lafka-menu__cats' );
		if ( ! nav ) { return; }
		const list = nav.querySelector( '.lafka-menu__cats-list' );
		const chips = $$( '.lafka-menu__cat-chip', nav );

		// Keep a chip visible inside the horizontally-scrolling strip without
		// scrolling the page vertically (scrollIntoView would).
		function reveal( chip ) {
			if ( ! list || ! chip || list.scrollWidth <= list.clientWidth ) { return; }
			const left = chip.offsetLeft - ( ( list.clientWidth - chip.offsetWidth ) / 2 );
			list.scrollLeft = Math.max( 0, left );
		}

		// Section anchors land below the sticky strip.
		function measure() {
			const top = parseFloat( window.getComputedStyle( nav ).top ) || 0;
			document.documentElement.style.setProperty( '--lafka-menu-cats-offset', Math.round( top + nav.offsetHeight + 8 ) + 'px' );
		}
		measure();
		window.addEventListener( 'resize', measure, { passive: true } );

		reveal( nav.querySelector( '.lafka-menu__cat-chip.is-active' ) );

		// In-page chips only (/menu/): archives link to other pages.
		const targets = [];
		chips.forEach( function ( chip ) {
			const href = chip.getAttribute( 'href' ) || '';
			if ( href.charAt( 0 ) !== '#' ) { return; }
			const el = document.getElementById( href.slice( 1 ) );
			if ( el ) {
				targets.push( { chip: chip, el: el } );
			}
		} );
		if ( targets.length < 2 ) { return; }

		let current = null;
		function setActive( chip ) {
			if ( chip === current ) { return; }
			current = chip;
			chips.forEach( function ( c ) {
				const on = c === chip;
				c.classList.toggle( 'is-active', on );
				if ( on ) {
					c.setAttribute( 'aria-current', 'true' );
				} else {
					c.removeAttribute( 'aria-current' );
				}
			} );
			reveal( chip );
		}

		let ticking = false;
		function spy() {
			ticking = false;
			// A section is "in view" once its top passes a quarter of the way
			// down the visible area below the strip.
			const navBottom = nav.getBoundingClientRect().bottom;
			const line = navBottom + Math.max( 16, ( window.innerHeight - navBottom ) * 0.25 );
			// The first target is "All" (the whole body); a section wins once
			// its top has scrolled up to just below the strip.
			let active = targets[ 0 ].chip;
			for ( let i = 1; i < targets.length; i++ ) {
				const t = targets[ i ];
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

	// M-34: on the menu page the sticky bar's "Order online" (empty cart)
	// points at this very page — take the customer to the menu instead of
	// reloading it. Delegated: the bar is a refreshed cart fragment.
	function initBarShortcut() {
		const body = document.getElementById( 'lafka-menu-all' );
		if ( ! body ) { return; }
		document.addEventListener( 'click', function ( e ) {
			const link = e.target && e.target.closest ? e.target.closest( 'a.lafka-counter-bar__order' ) : null;
			if ( ! link || link.hash || link.pathname !== window.location.pathname || link.host !== window.location.host ) {
				return;
			}
			e.preventDefault();
			body.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			const input = root.querySelector( '[data-lafka-menu-search-input]' );
			if ( input ) {
				input.focus( { preventScroll: true } );
			}
		} );
	}

	function init() {
		initBarShortcut();
		initTabs();
		initSearch();
		initFilters();
		initReset();
		initCategoryStrip();
		// A prefilled live search (browser back / autofill) applies at once.
		const input = root.querySelector( '[data-lafka-menu-search-input]' );
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
