/**
 * Header chrome — inject an "Order now" CTA next to the cart icon.
 *
 * Pure DOM injection — no PHP changes needed. Targets the existing
 * .lafka-search-cart-holder which sits at the right end of the header
 * row. The link points to the shop page (filterable via the
 * data-lafka-shop-url body attribute, set in core-functions.php).
 *
 * @since 5.55.0
 */
( function () {
	'use strict';

	function relocateLogo() {
		// Legacy theme renders #logo inside #header_top .inner when the
		// operator has "show top header" enabled. We hide #header_top so
		// the logo would disappear with it. Move it into the main row.
		var logo = document.getElementById( 'logo' );
		var mmh = document.querySelector( '#header .main_menu_holder.inner' );
		if ( logo && mmh && logo.parentElement !== mmh ) {
			mmh.insertBefore( logo, mmh.firstChild );
		}
	}

	function injectCta() {
		var holder = document.querySelector( '#header .lafka-search-cart-holder' );
		if ( ! holder ) {
			return;
		}
		if ( holder.querySelector( '.lafka-header-cta' ) ) {
			return;
		}

		var url = document.body.getAttribute( 'data-lafka-shop-url' ) || '/menu/';
		var label = document.body.getAttribute( 'data-lafka-cta-label' ) || 'Order now';

		var cta = document.createElement( 'a' );
		cta.className = 'lafka-header-cta';
		cta.href = url;

		var labelSpan = document.createElement( 'span' );
		labelSpan.className = 'lafka-header-cta__label';
		labelSpan.textContent = label;
		cta.appendChild( labelSpan );

		var arrowSpan = document.createElement( 'span' );
		arrowSpan.className = 'lafka-header-cta__arrow';
		arrowSpan.setAttribute( 'aria-hidden', 'true' );
		arrowSpan.textContent = '→';
		cta.appendChild( arrowSpan );

		holder.appendChild( cta );
	}

	function init() {
		relocateLogo();
		injectCta();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
