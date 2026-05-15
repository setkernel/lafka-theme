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

	function open() {
		nav.classList.add( 'is-open' );
		nav.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'lafka-mobile-nav-open' );
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
		if ( e.key === 'Escape' && nav.classList.contains( 'is-open' ) ) {
			close();
		}
	} );

	// Auto-close when an internal link is clicked (route change).
	nav.querySelectorAll( '.lafka-mobile-nav__list a, .lafka-mobile-nav__nav .menu a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function () {
			close();
		} );
	} );
}() );
