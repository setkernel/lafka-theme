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

	/**
	 * Add one product (or variation id) through WooCommerce's wc-ajax
	 * `add_to_cart`, firing `adding_to_cart` / `added_to_cart` exactly like
	 * WC's own buttons (so the drawer + fragments refresh). Shared with the
	 * counter rows and size chooser via window.lafkaQuickAdd.add (GX4).
	 *
	 * @param {string|number} productId  Product or variation id.
	 * @param {Element}       trigger    Element that gets the loading/added state.
	 * @param {Object}        [opts]     { fallbackUrl, beforeAdded(response), onSuccess(response), onError(response) }
	 */
	function add(productId, trigger, opts) {
		opts = opts || {};
		var fallbackUrl = opts.fallbackUrl || '';
		var ajaxUrl = getWcAjaxUrl('add_to_cart');

		if (!ajaxUrl || !window.jQuery) {
			// No WC AJAX available — degrade to plain link.
			if (fallbackUrl) { window.location.href = fallbackUrl; }
			return;
		}

		setPillState(trigger, 'loading');
		var $body = window.jQuery(document.body);
		$body.trigger('adding_to_cart', [window.jQuery(trigger), { product_id: productId }]);

		window.jQuery.post(ajaxUrl, {
			product_id: productId,
			quantity: 1
		}).done(function (response) {
			if (!response) { setPillState(trigger, 'idle'); return; }
			if (response.error) {
				setPillState(trigger, 'idle');
				if (opts.onError) { opts.onError(response); }
				if (response.product_url) {
					window.location.href = response.product_url;
				}
				return;
			}
			// e.g. the size chooser closes its dialog BEFORE the drawer opens,
			// so the drawer remembers the row's Add as the focus to return to.
			if (opts.beforeAdded) { opts.beforeAdded(response); }
			$body.trigger('added_to_cart', [response.fragments, response.cart_hash, window.jQuery(trigger)]);
			setPillState(trigger, 'added');
			window.setTimeout(function () { setPillState(trigger, 'idle'); }, 1500);
			if (opts.onSuccess) { opts.onSuccess(response); }
		}).fail(function () {
			setPillState(trigger, 'idle');
			if (fallbackUrl) { window.location.href = fallbackUrl; }
		});
	}

	window.lafkaQuickAdd = { add: add };

	function ajaxAddToCart(pill) {
		add(pill.dataset.lafkaQuickaddProductId, pill, { fallbackUrl: pill.dataset.lafkaQuickaddUrl });
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
