/**
 * Mobile slide-out nav drawer — toggle + scroll-lock + ESC + scrim close.
 *
 * Pairs with partials/mobile-nav.php and styles/lafka-mobile-nav.css.
 *
 * @since 5.56.0
 */
( function () {
	'use strict';

	var nav = document.getElementById( 'lafka-mobile-nav' );
	if ( ! nav ) {
		return;
	}

	var toggleBtn = document.querySelector( '[data-lafka-menu-toggle]' );
	var closeEls = nav.querySelectorAll( '[data-lafka-mobile-nav-close]' );
	var panel = nav.querySelector( '.lafka-mobile-nav__panel' );

	// Focusable elements inside the drawer panel, used by the Tab focus trap.
	var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

	// Page wrappers that sit *behind* the drawer scrim. The drawer is rendered
	// at the end of <body> via wp_footer, so we must NOT inert <body> — that
	// would also disable the drawer. Instead we inert the real page wrappers
	// (header + main content + footer) so neither keyboard focus nor the
	// screen-reader virtual cursor can reach the obscured page while the
	// drawer is open (WCAG 2.4.3 / 2.4.7).
	var supportsInert = ( 'inert' in HTMLElement.prototype );
	var backgroundEls = null;

	function getBackgroundEls() {
		if ( null === backgroundEls ) {
			backgroundEls = [];
			[ '#header', '#content', '#footer' ].forEach( function ( sel ) {
				var el = document.querySelector( sel );
				if ( el && el !== nav && ! nav.contains( el ) ) {
					backgroundEls.push( el );
				}
			} );
		}
		return backgroundEls;
	}

	function setBackgroundInert( on ) {
		getBackgroundEls().forEach( function ( el ) {
			if ( on ) {
				if ( supportsInert ) {
					el.inert = true;
				} else {
					el.setAttribute( 'aria-hidden', 'true' );
				}
			} else if ( supportsInert ) {
				el.inert = false;
			} else {
				el.removeAttribute( 'aria-hidden' );
			}
		} );
	}

	function open() {
		nav.classList.add( 'is-open' );
		nav.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'lafka-mobile-nav-open' );
		setBackgroundInert( true );
		if ( toggleBtn ) {
			toggleBtn.setAttribute( 'aria-expanded', 'true' );
		}
		// Focus the close button so keyboard users can dismiss easily.
		setTimeout( function () {
			var closeBtn = nav.querySelector( '.lafka-mobile-nav__close' );
			if ( closeBtn ) {
				closeBtn.focus();
			} else if ( panel ) {
				panel.focus();
			}
		}, 240 );
	}

	function close() {
		nav.classList.remove( 'is-open' );
		nav.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'lafka-mobile-nav-open' );
		// Restore the background to the a11y tree BEFORE returning focus to the
		// toggle — focus() on an inert ancestor would otherwise be dropped.
		setBackgroundInert( false );
		if ( toggleBtn ) {
			toggleBtn.setAttribute( 'aria-expanded', 'false' );
			toggleBtn.focus();
		}
	}

	if ( toggleBtn ) {
		toggleBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( nav.classList.contains( 'is-open' ) ) {
				close();
			} else {
				open();
			}
		} );
	}

	closeEls.forEach( function ( el ) {
		el.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			close();
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! nav.classList.contains( 'is-open' ) ) {
			return;
		}

		if ( e.key === 'Escape' ) {
			close();
			return;
		}

		// Tab focus trap — keep focus inside the drawer panel so a keyboard
		// user can never tab onto the page content hidden behind the scrim.
		// Mirrors cart-drawer.js: wrap shift+Tab first->last and Tab last->first.
		if ( e.key === 'Tab' && panel ) {
			var focusables = Array.prototype.slice.call(
				panel.querySelectorAll( FOCUSABLE_SELECTOR )
			);
			if ( ! focusables.length ) {
				return;
			}
			var first = focusables[ 0 ];
			var last  = focusables[ focusables.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	} );

	// Auto-close when an internal link is clicked (route change).
	nav.querySelectorAll( '.lafka-mobile-nav__list a, .lafka-mobile-nav__nav .menu a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function () {
			close();
		} );
	} );
}() );
