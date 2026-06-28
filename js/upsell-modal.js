/* lafka-theme/js/upsell-modal.js
 * +Add buttons in upsell row: AJAX-add for simple products, navigate for variable.
 *
 * Uses textContent for all dynamic updates; never innerHTML.
 *
 * @since 5.16.0
 */
(function ($) {
	'use strict';
	if (!$) return;

	document.addEventListener('click', function (e) {
		var btn = e.target.closest && e.target.closest('.lafka-pdp-upsell__add');
		if (!btn) return;
		e.preventDefault();
		var productId   = btn.dataset.productId;
		var productType = btn.dataset.productType;
		var permalink   = btn.dataset.permalink;

		if (productType === 'simple') {
			addSimple(btn, productId);
		} else {
			window.location.href = permalink;
		}
	});

	function addSimple(btn, productId) {
		btn.disabled = true;
		var originalText = btn.textContent;
		btn.textContent = '...';
		var endpoint = (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url
			? window.wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart')
			: '/?wc-ajax=add_to_cart');
		$.post(endpoint, { product_id: productId, quantity: 1 })
			.done(function (response) {
				// WC returns { error: true, product_url } for products it
				// can't AJAX-add (out of stock, qty limit, options required).
				// Don't claim success or fire added_to_cart with undefined
				// fragments — bail (and follow product_url if provided).
				if (!response) {
					btn.disabled = false;
					btn.textContent = originalText;
					return;
				}
				if (response.error) {
					if (response.product_url) {
						window.location.href = response.product_url;
					}
					btn.disabled = false;
					btn.textContent = originalText;
					return;
				}
				$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $(btn)]);
				btn.disabled = false;
				btn.textContent = '✓ Added';
				setTimeout(function () { btn.textContent = originalText; }, 2000);
			})
			.fail(function () {
				btn.disabled = false;
				btn.textContent = originalText;
				console.error('lafka-pdp: failed to add to cart');
			});
	}
})(window.jQuery);
