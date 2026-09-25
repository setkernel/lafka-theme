/**
 * GX4: counter rows' "Add" + the 2-tap size chooser.
 *
 *  - [data-lafka-add][data-lafka-add-mode="direct"]  -> add the product now.
 *  - [data-lafka-add][data-lafka-add-mode="chooser"] -> open <dialog id="lafka-chooser">
 *    filled from the page's JSON island (#lafka-chooser-data, built by
 *    lafka_chooser_payload()): secondary attributes as preselected radios, the
 *    price-driving attribute as big worded buttons. Tapping one adds THAT
 *    variation id through WooCommerce's wc-ajax add_to_cart (WC resolves the
 *    parent + attributes), firing added_to_cart so the drawer opens as today.
 *
 * Uses window.lafkaQuickAdd.add (js/lafka-archive-quickadd.js) when loaded,
 * else a minimal fetch() fallback. Escape / backdrop / Close dismiss the
 * dialog; focus returns to the invoking Add.
 *
 * @since 7.2.0 (GX4)
 */
( function () {
	'use strict';

	var cfg = window.lafkaCounter || {};
	var i18n = cfg.i18n || {};
	var data = null;
	var current = null;
	var trigger = null;

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	function fill( template, a, b ) {
		return template.replace( '%1$s', a ).replace( '%2$s', b ).replace( '%s', a );
	}

	function payloads() {
		if ( data === null ) {
			var node = document.getElementById( 'lafka-chooser-data' );
			try {
				data = node ? JSON.parse( node.textContent ) : {};
			} catch {
				data = {};
			}
		}
		return data;
	}

	function announce( message ) {
		var live = document.querySelector( '[data-lafka-chooser-live]' );
		if ( live ) {
			live.textContent = '';
			window.setTimeout( function () {
				live.textContent = message;
			}, 50 );
		}
	}

	// One proven add path: js/lafka-archive-quickadd.js (enqueued with this
	// file). Without it (or without WC AJAX) the product page takes over.
	function add( id, el, opts ) {
		if ( window.lafkaQuickAdd && window.lafkaQuickAdd.add ) {
			window.lafkaQuickAdd.add( id, el, opts );
		} else {
			window.location.href = opts.fallbackUrl;
		}
	}

	function selection( dialog ) {
		var sel = {};
		Array.prototype.forEach.call( dialog.querySelectorAll( '[data-lafka-chooser-attr]:checked' ), function ( input ) {
			sel[ input.name ] = input.value;
		} );
		return sel;
	}

	function match( product, sel ) {
		for ( var i = 0; i < product.variations.length; i++ ) {
			var v = product.variations[ i ];
			var ok = true;
			for ( var key in sel ) {
				if ( Object.prototype.hasOwnProperty.call( sel, key ) ) {
					var have = v.attributes[ key ] || '';
					if ( have !== '' && have !== sel[ key ] ) {
						ok = false;
						break;
					}
				}
			}
			if ( ok ) {
				return v;
			}
		}
		return null;
	}

	function primaryAttr( product ) {
		for ( var i = 0; i < product.attributes.length; i++ ) {
			if ( product.attributes[ i ].primary ) {
				return product.attributes[ i ];
			}
		}
		return product.attributes[ product.attributes.length - 1 ];
	}

	function renderOptions( dialog ) {
		var product = current;
		var primary = primaryAttr( product );
		var wrap = dialog.querySelector( '[data-lafka-chooser-options]' );
		var sel = selection( dialog );
		wrap.textContent = '';
		primary.options.forEach( function ( option ) {
			var want = {};
			for ( var k in sel ) {
				if ( Object.prototype.hasOwnProperty.call( sel, k ) ) {
					want[ k ] = sel[ k ];
				}
			}
			want[ primary.key ] = option.value;
			var variation = match( product, want );
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'lafka-chooser__option';
			var name = document.createElement( 'span' );
			name.className = 'lafka-chooser__option-name';
			name.textContent = option.label;
			var price = document.createElement( 'span' );
			price.className = 'lafka-chooser__option-price';
			var cta = document.createElement( 'span' );
			cta.className = 'lafka-chooser__option-cta';
			if ( variation ) {
				price.textContent = variation.price_text;
				cta.textContent = t( 'addToOrder', 'Add to order' );
				button.setAttribute( 'aria-label', fill( t( 'option', '%1$s, %2$s, add to order' ), option.label, variation.price_text ) );
				button.setAttribute( 'data-lafka-chooser-variation', variation.id );
				button.setAttribute( 'data-lafka-chooser-label', option.label );
			} else {
				button.disabled = true;
				cta.textContent = t( 'unavailable', 'Not available' );
			}
			button.appendChild( name );
			button.appendChild( price );
			button.appendChild( cta );
			wrap.appendChild( button );
		} );
	}

	function open( product, el ) {
		var dialog = document.getElementById( 'lafka-chooser' );
		if ( ! dialog || typeof dialog.showModal !== 'function' ) {
			window.location.href = product.url;
			return;
		}
		current = product;
		trigger = el;
		dialog.querySelector( '[data-lafka-chooser-name]' ).textContent = product.name;
		var groups = dialog.querySelector( '[data-lafka-chooser-groups]' );
		groups.textContent = '';
		product.attributes.forEach( function ( attr ) {
			if ( attr.primary ) {
				dialog.querySelector( '[data-lafka-chooser-primary-label]' ).textContent = attr.label;
				return;
			}
			var fieldset = document.createElement( 'fieldset' );
			fieldset.className = 'lafka-chooser__group';
			var legend = document.createElement( 'legend' );
			legend.className = 'lafka-chooser__legend';
			legend.textContent = attr.label;
			fieldset.appendChild( legend );
			var row = document.createElement( 'div' );
			row.className = 'lafka-chooser__radios';
			attr.options.forEach( function ( option ) {
				var label = document.createElement( 'label' );
				label.className = 'lafka-chooser__radio';
				var input = document.createElement( 'input' );
				input.type = 'radio';
				input.name = attr.key;
				input.value = option.value;
				input.checked = option.value === attr.default;
				input.setAttribute( 'data-lafka-chooser-attr', '' );
				var span = document.createElement( 'span' );
				span.textContent = option.label;
				label.appendChild( input );
				label.appendChild( span );
				row.appendChild( label );
			} );
			fieldset.appendChild( row );
			groups.appendChild( fieldset );
		} );
		var more = dialog.querySelector( '[data-lafka-chooser-more]' );
		more.hidden = ! product.has_addons;
		dialog.querySelector( '[data-lafka-chooser-more-link]' ).setAttribute( 'href', product.url );
		renderOptions( dialog );
		dialog.showModal();
		var first = dialog.querySelector( '.lafka-chooser__option:not([disabled])' );
		if ( first ) {
			first.focus();
		}
	}

	function close() {
		var dialog = document.getElementById( 'lafka-chooser' );
		if ( dialog && dialog.open ) {
			dialog.close();
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var target = e.target;
		var addButton = target.closest ? target.closest( '[data-lafka-add]' ) : null;
		if ( addButton ) {
			e.preventDefault();
			var id = addButton.getAttribute( 'data-lafka-add' );
			var url = addButton.getAttribute( 'data-lafka-add-url' );
			if ( addButton.getAttribute( 'data-lafka-add-mode' ) === 'chooser' ) {
				var product = payloads()[ id ];
				if ( ! product ) {
					window.location.href = url;
					return;
				}
				open( product, addButton );
				return;
			}
			add( id, addButton, {
				fallbackUrl: url,
				onSuccess: function () {
					var name = addButton.querySelector( '.screen-reader-text' );
					announce( fill( t( 'addedSimple', 'Added %s' ), name ? name.textContent.trim() : '' ) );
				},
			} );
			return;
		}

		var option = target.closest ? target.closest( '[data-lafka-chooser-variation]' ) : null;
		if ( option && current ) {
			var chosen = current;
			var label = option.getAttribute( 'data-lafka-chooser-label' );
			add( option.getAttribute( 'data-lafka-chooser-variation' ), option, {
				fallbackUrl: chosen.url,
				beforeAdded: function () {
					close();
				},
				onSuccess: function () {
					announce( fill( t( 'added', 'Added %1$s, %2$s' ), chosen.name, label ) );
				},
			} );
			return;
		}

		// Backdrop click (the dialog element itself, outside the panel).
		if ( target.id === 'lafka-chooser' ) {
			close();
		}
	} );

	document.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.hasAttribute && e.target.hasAttribute( 'data-lafka-chooser-attr' ) && current ) {
			renderOptions( document.getElementById( 'lafka-chooser' ) );
		}
	} );

	document.addEventListener( 'close', function ( e ) {
		if ( e.target && e.target.id === 'lafka-chooser' ) {
			current = null;
			if ( trigger && trigger.focus && document.body.contains( trigger ) ) {
				trigger.focus();
			}
		}
	}, true );
}() );

/*
 * GX4 polish: jump links into "More from our menu" land exactly.
 *
 * Those sections use content-visibility: auto, so until they have rendered
 * once their height is only an estimate (contain-intrinsic-size) and a jump
 * into or past them (a menu section, "Find us") would land off by the
 * difference. Before the browser scrolls to such a target (an in-page link
 * click, or a #fragment URL on load) every section is rendered for real
 * (.is-rendered); the `auto` intrinsic size then remembers the true heights.
 */
( function () {
	'use strict';

	var rest = document.querySelector( '.lafka-counter-rest' );
	if ( ! rest ) {
		return;
	}

	/* Render the sections when `id` is inside or after them; return the target. */
	function prepare( id ) {
		var target = null;
		try {
			target = id ? document.getElementById( decodeURIComponent( id ) ) : null;
		} catch ( e ) { // eslint-disable-line no-unused-vars -- a malformed %-escape is just "no target".
			target = null;
		}
		if ( ! target ) {
			return null;
		}
		if ( rest.contains( target ) || ( rest.compareDocumentPosition( target ) & Node.DOCUMENT_POSITION_FOLLOWING ) ) {
			rest.classList.add( 'is-rendered' );
			return target;
		}
		return null;
	}

	// Capture phase: runs before the browser's own fragment scroll.
	document.addEventListener( 'click', function ( e ) {
		var link = e.target && e.target.closest ? e.target.closest( 'a[href*="#"]' ) : null;
		if ( ! link || link.pathname !== window.location.pathname || link.host !== window.location.host ) {
			return;
		}
		prepare( link.hash.slice( 1 ) );
	}, true );

	if ( window.location.hash.length > 1 ) {
		var target = prepare( window.location.hash.slice( 1 ) );
		if ( target ) {
			target.scrollIntoView();
		}
	}
}() );
