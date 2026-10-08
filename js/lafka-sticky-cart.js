/* lafka-theme/js/lafka-sticky-cart.js
 * Sticky cart bar — listens to WooCommerce's `added_to_cart` jQuery event
 * (broadcast by the AJAX add-to-cart endpoint) and updates the bar's
 * count + subtotal in place.
 *
 * Public surface: none. Self-initialises on DOMContentLoaded.
 *
 * @since 5.26.0
 */
(function () {
	'use strict';

	function $bar() {
		return document.querySelector('[data-lafka-sticky-cart]');
	}

	function applyBodyPadding(active) {
		document.body.classList.toggle('lafka-has-sticky-cart', !!active);
	}

	function show(bar) {
		if (!bar) { return; }
		bar.hidden = false;
		applyBodyPadding(true);
	}

	function hide(bar) {
		if (!bar) { return; }
		bar.hidden = true;
		applyBodyPadding(false);
	}

	function setText(selector, value) {
		const bar = $bar();
		if (!bar) { return; }
		const el = bar.querySelector(selector);
		if (el) { el.textContent = value; }
	}

	function update(count, subtotalText) {
		const bar = $bar();
		if (!bar) { return; }
		setText('[data-lafka-cart-count]', String(count));
		if (subtotalText) {
			setText('[data-lafka-cart-subtotal]', subtotalText);
		}
		if (count > 0) { show(bar); } else { hide(bar); }
	}

	function refreshFromFragments(fragments) {
		// WC's added_to_cart event passes a fragments object — typically
		// containing the rendered mini-cart HTML. Parse it via DOMParser
		// (inert document, no script execution) and pull just the subtotal
		// text + item count.
		const miniCartHtml = fragments && fragments['div.widget_shopping_cart_content'];
		if (!miniCartHtml) { return; }
		const doc = new DOMParser().parseFromString('<div>' + miniCartHtml + '</div>', 'text/html');
		const subtotalEl = doc.querySelector(
			'.woocommerce-mini-cart__total .woocommerce-Price-amount, .total .woocommerce-Price-amount'
		);
		const countItems = doc.querySelectorAll('.woocommerce-mini-cart .mini_cart_item, .mini_cart_item');
		const count = countItems.length;
		const subtotalText = subtotalEl ? subtotalEl.textContent.trim() : '';
		update(count, subtotalText);
	}

	function init() {
		const bar = $bar();
		if (!bar) { return; }
		if (!bar.hidden) { applyBodyPadding(true); }

		// WC ships its AJAX events through jQuery on the body element.
		// We piggyback on those — no need for our own endpoint.
		if (window.jQuery) {
			window.jQuery(document.body).on('added_to_cart', function (event, fragments) {
				refreshFromFragments(fragments);
			});
			window.jQuery(document.body).on('removed_from_cart', function (event, fragments) {
				refreshFromFragments(fragments);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
