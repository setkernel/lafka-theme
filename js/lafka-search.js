/**
 * Header search overlay (audit 2026-06-27 #3).
 *
 * Wires the header search icon ([data-lafka-search-toggle]) to a native
 * <dialog id="lafka-search-dialog">. showModal() provides Escape-to-close and
 * focus trapping for free; the close button uses a <form method="dialog">.
 * This adds the open trigger and backdrop-click-to-close. No jQuery.
 */
(function () {
	'use strict';

	var dialog = document.getElementById( 'lafka-search-dialog' );
	if ( ! dialog || typeof dialog.showModal !== 'function' ) {
		return;
	}

	var toggles = document.querySelectorAll( '[data-lafka-search-toggle]' );
	Array.prototype.forEach.call( toggles, function ( toggle ) {
		toggle.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			dialog.showModal();
			var input = dialog.querySelector( 'input[type="search"]' );
			if ( input ) {
				input.focus();
			}
		} );
	} );

	// Close when the backdrop (the dialog element itself, outside the panel)
	// is clicked.
	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );
})();
