/* lafka-theme/js/lafka-pdp-cta.js
 * Sticky PDP CTA — three coordinated jobs:
 *
 *   1. AUTO-SELECT the default variation on page load (default strategy:
 *      median-priced in-stock variation) so the customer doesn't hit the
 *      "Pick a size to continue" wall. Strategy is operator-configurable
 *      via Customizer (lafka_pdp_default_variation_strategy).
 *
 *   2. LIVE TOTAL — recompute the price as the customer changes
 *      variation radios, toppings, or quantity. Renders into the sticky
 *      CTA as "Add — $XX.XX". Reads:
 *        - variation display_price from data-product_variations JSON
 *        - topping prices from input[type=checkbox][data-price]
 *        - quantity from input[name=quantity]
 *
 *   3. CLICK-TO-SUBMIT — forwards taps on the sticky CTA to WC's standard
 *      in-form Add-to-Cart button, so the AJAX add path (and the cart
 *      drawer / sticky cart bar) all work unchanged.
 *
 * Falls back gracefully:
 *   - No form on page → no-op
 *   - data-product_variations missing → no-op for default-select but live
 *     total still tracks topping changes
 *   - No JS → server still renders the original variation form; the
 *     sticky CTA is purely additive
 *
 * @since 5.27.0
 */
(function () {
	'use strict';

	var CONFIG = window.lafkaPdpCtaConfig || {};
	var DEFAULT_STRATEGY = CONFIG.defaultStrategy || 'median';
	var CURRENCY_SYMBOL = CONFIG.currencySymbol || '$';
	var ADD_LABEL = CONFIG.addLabel || 'Add';
	var PICK_LABEL = CONFIG.pickLabel || 'Select options';
	var OUT_OF_STOCK_LABEL = CONFIG.outOfStockLabel || 'Out of stock';

	function $form() { return document.querySelector('form.variations_form'); }
	function $cta() { return document.querySelector('[data-lafka-pdp-cta]'); }

	function readVariations(form) {
		try {
			return JSON.parse(form.dataset.product_variations || '[]');
		} catch (_e) {
			return [];
		}
	}

	function pickDefault(variations, strategy) {
		var inStock = variations.filter(function (v) {
			return v.is_in_stock !== false && v.is_purchasable !== false;
		});
		if (!inStock.length) { return null; }
		var sorted = inStock.slice().sort(function (a, b) {
			return parseFloat(a.display_price) - parseFloat(b.display_price);
		});
		if (strategy === 'lowest') { return sorted[0]; }
		if (strategy === 'highest') { return sorted[sorted.length - 1]; }
		if (strategy === 'none') { return null; }
		// median (default)
		return sorted[Math.floor(sorted.length / 2)];
	}

	function applyDefaultVariation(form, variations) {
		var strategy = DEFAULT_STRATEGY;
		var override = CONFIG.defaultVariationId ? parseInt(CONFIG.defaultVariationId, 10) : 0;
		var wcDefaults = CONFIG.wcDefaultAttrs || {};
		var chosen = null;

		// Priority 1: explicit filter override (lafka_pdp_default_variation).
		if (override) {
			chosen = variations.find(function (v) { return v.variation_id === override; });
		}

		// Priority 2: operator-set WC default form values. Match a variation
		// where every supplied default attribute matches.
		if (!chosen && Object.keys(wcDefaults).length) {
			chosen = variations.find(function (v) {
				return Object.keys(wcDefaults).every(function (k) {
					return v.attributes[k] === wcDefaults[k] || !v.attributes[k];
				});
			});
		}

		// Priority 3: algorithmic strategy (median / lowest / highest).
		if (!chosen) {
			chosen = pickDefault(variations, strategy);
		}

		if (!chosen) { return; }
		Object.keys(chosen.attributes).forEach(function (attrName) {
			var value = chosen.attributes[attrName];
			if (!value) { return; }
			var radio = form.querySelector('input[type="radio"][name="' + attrName + '"][value="' + cssEscape(value) + '"]');
			if (radio && !radio.checked) {
				if (window.jQuery) {
					window.jQuery(radio).prop('checked', true).trigger('change');
				} else {
					radio.checked = true;
					radio.dispatchEvent(new Event('change', { bubbles: true }));
				}
			}
		});
	}

	function cssEscape(value) {
		if (window.CSS && window.CSS.escape) { return window.CSS.escape(value); }
		return String(value).replace(/[^a-zA-Z0-9_-]/g, function (c) { return '\\' + c; });
	}

	function getSelectedVariation(form, variations) {
		var selected = {};
		form.querySelectorAll('input[type="radio"]:checked, select').forEach(function (el) {
			if (el.type === 'radio') {
				selected[el.name] = el.value;
			} else if (el.tagName === 'SELECT' && el.name && el.name.indexOf('attribute_') === 0) {
				selected[el.name] = el.value;
			}
		});
		return variations.find(function (v) {
			return Object.keys(v.attributes).every(function (key) {
				var want = v.attributes[key];
				if (!want) { return true; } // wildcard
				return selected[key] === want;
			});
		});
	}

	function getToppingPrice(checkbox) {
		// Prefer the price text rendered in the topping's label — the
		// Lafka/Pizza-Addons plugin re-renders these on variation change
		// (e.g. premium toppings cost more on gluten-free crust). The
		// data-price attribute is stale for unchecked items in that
		// scenario, which would cause the live total to under-count and
		// produce sticker shock at the cart.
		var label = checkbox.closest('label');
		if (label) {
			var match = label.textContent.match(/\$(\d+(?:\.\d+)?)/);
			if (match) { return parseFloat(match[1]); }
		}
		var fallback = parseFloat(checkbox.dataset.price);
		return isNaN(fallback) ? 0 : fallback;
	}

	function getToppingTotal() {
		var total = 0;
		document.querySelectorAll('input[type="checkbox"][data-price]:checked').forEach(function (c) {
			total += getToppingPrice(c);
		});
		return total;
	}

	function getQuantity(form) {
		var input = form.querySelector('input[name="quantity"]');
		var qty = input ? parseInt(input.value, 10) : 1;
		return isNaN(qty) || qty < 1 ? 1 : qty;
	}

	function formatPrice(amount) {
		return CURRENCY_SYMBOL + amount.toFixed(2);
	}

	function setCtaState(state, total) {
		var cta = $cta();
		if (!cta) { return; }
		var btn = cta.querySelector('[data-lafka-pdp-cta-btn]');
		var priceEl = cta.querySelector('[data-lafka-pdp-cta-price]');
		var labelEl = cta.querySelector('[data-lafka-pdp-cta-label]');
		if (state === 'ready') {
			cta.dataset.state = 'ready';
			btn.disabled = false;
			labelEl.textContent = ADD_LABEL;
			priceEl.textContent = formatPrice(total);
			priceEl.hidden = false;
		} else if (state === 'out-of-stock') {
			cta.dataset.state = 'out-of-stock';
			btn.disabled = true;
			labelEl.textContent = OUT_OF_STOCK_LABEL;
			priceEl.hidden = true;
		} else {
			cta.dataset.state = 'pick';
			btn.disabled = true;
			labelEl.textContent = PICK_LABEL;
			priceEl.hidden = true;
		}
	}

	function recompute() {
		var form = $form();
		if (!form) { return; }
		var variations = readVariations(form);
		var selected = getSelectedVariation(form, variations);
		if (!selected) {
			setCtaState('pick', 0);
			return;
		}
		if (selected.is_in_stock === false) {
			setCtaState('out-of-stock', 0);
			return;
		}
		var base = parseFloat(selected.display_price) || 0;
		var toppings = getToppingTotal();
		var qty = getQuantity(form);
		setCtaState('ready', (base + toppings) * qty);
	}

	function bindEvents(form) {
		// Variation/topping/quantity changes — recompute on any input.
		form.addEventListener('change', recompute);
		form.addEventListener('input', recompute);
		document.addEventListener('change', function (e) {
			if (e.target && e.target.matches('input[type="checkbox"][data-price]')) {
				recompute();
			}
		});

		// WC variation-form events (jQuery): also trigger recompute on
		// `found_variation` / `reset_data` which WC fires after matching.
		if (window.jQuery) {
			window.jQuery(form).on('found_variation reset_data show_variation', recompute);
		}

		// Sticky CTA click → forward to WC's in-form Add-to-Cart button.
		var cta = $cta();
		if (cta) {
			cta.addEventListener('click', function (e) {
				var btn = e.target.closest('[data-lafka-pdp-cta-btn]');
				if (!btn || btn.disabled) { return; }
				e.preventDefault();
				var addBtn = form.querySelector('.single_add_to_cart_button');
				if (addBtn && !addBtn.disabled) {
					addBtn.click();
				} else {
					// Fallback: native form submit so the request still goes through.
					form.submit();
				}
			});
		}
	}

	function init() {
		var form = $form();
		if (!form) { return; }
		document.body.classList.add('lafka-has-pdp-cta');
		var variations = readVariations(form);
		bindEvents(form);
		if (variations.length && DEFAULT_STRATEGY !== 'none') {
			applyDefaultVariation(form, variations);
		}
		recompute();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
