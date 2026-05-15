/* lafka-theme/js/lafka-archive-quickadd.js
 * Archive-card quick-add pill — click handler.
 *
 * The pill is a <span role="button"> rendered inside the card's outer
 * <a> link wrapper (the card itself is one big link to PDP). This file's
 * job is to:
 *
 *   1. Intercept pill clicks in the CAPTURE phase, stopPropagation so
 *      the parent link doesn't fire (would navigate the user to the
 *      PDP, defeating the purpose of one-tap add).
 *   2. For "add" actions: hit WooCommerce's wc-ajax `add_to_cart`
 *      endpoint directly — the same endpoint WC's own add-to-cart
 *      button uses, so the `added_to_cart` jQuery event still fires
 *      and the cart drawer + sticky cart bar refresh automatically.
 *   3. For "choose" actions: navigate to the product's PDP URL where
 *      the v5.27 sticky CTA + auto-default variation take over.
 *   4. Handle keyboard activation (Enter / Space) so the pill is
 *      properly accessible.
 *
 * Falls back to plain navigation if WC's localised params are absent.
 *
 * @since 5.28.0
 */
(function () {
	'use strict';

	function isPill(el) {
		return el && el.classList && el.classList.contains('lafka-archive-quickadd');
	}

	function findPill(target) {
		return target && target.closest ? target.closest('.lafka-archive-quickadd') : null;
	}

	function getWcAjaxUrl(endpoint) {
		var params = window.wc_add_to_cart_params || {};
		if (params.wc_ajax_url) {
			return params.wc_ajax_url.replace('%%endpoint%%', endpoint);
		}
		return null;
	}

	function setPillState(pill, state) {
		pill.classList.toggle('loading', state === 'loading');
		pill.classList.toggle('added', state === 'added');
	}

	function ajaxAddToCart(pill) {
		var productId = pill.dataset.lafkaQuickaddProductId;
		var fallbackUrl = pill.dataset.lafkaQuickaddUrl;
		var ajaxUrl = getWcAjaxUrl('add_to_cart');

		if (!ajaxUrl || !window.jQuery) {
			// No WC AJAX available — degrade to plain link.
			if (fallbackUrl) { window.location.href = fallbackUrl; }
			return;
		}

		setPillState(pill, 'loading');
		var $body = window.jQuery(document.body);
		$body.trigger('adding_to_cart', [window.jQuery(pill), { product_id: productId }]);

		window.jQuery.post(ajaxUrl, {
			product_id: productId,
			quantity: 1
		}).done(function (response) {
			if (!response) { return; }
			if (response.error) {
				if (response.product_url) {
					window.location.href = response.product_url;
				}
				return;
			}
			$body.trigger('added_to_cart', [response.fragments, response.cart_hash, window.jQuery(pill)]);
			setPillState(pill, 'added');
			window.setTimeout(function () { setPillState(pill, 'idle'); }, 1500);
		}).fail(function () {
			setPillState(pill, 'idle');
			if (fallbackUrl) { window.location.href = fallbackUrl; }
		});
	}

	function handleActivation(pill) {
		var action = pill.dataset.lafkaQuickaddAction;
		if (action === 'add') {
			ajaxAddToCart(pill);
		} else {
			var url = pill.dataset.lafkaQuickaddUrl;
			if (url) { window.location.href = url; }
		}
	}

	// Capture-phase click handler — intercepts BEFORE the parent <a>
	// would bubble its own navigation.
	document.addEventListener('click', function (e) {
		var pill = findPill(e.target);
		if (!pill) { return; }
		e.preventDefault();
		e.stopPropagation();
		handleActivation(pill);
	}, true);

	// Keyboard activation (Enter / Space) on the span role=button.
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter' && e.key !== ' ') { return; }
		if (!isPill(e.target)) { return; }
		e.preventDefault();
		e.stopPropagation();
		handleActivation(e.target);
	}, true);
})();
