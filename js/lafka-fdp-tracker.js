/* lafka-theme/js/lafka-fdp-tracker.js
 * Free-delivery progress tracker (Pillar 3A, v6.9.0).
 *
 * Responsibilities:
 *
 *   1. Post-process the plain-text .lafka-cart-drawer__threshold markup
 *      emitted by the plugin's woocommerce_add_to_cart_fragments filter
 *      (lafka-plugin/incl/woocommerce/lafka-cart-drawer-fragments.php).
 *      The plugin still owns the AJAX fragment payload — we never modify
 *      it server-side; instead, on every cart-update event we reach into
 *      the drawer footer, find the plain threshold notice, and rebuild it
 *      as the rich .lafka-fdp component so initial-render and AJAX-
 *      refresh stay visually consistent.
 *
 *   2. Wire window.lafkaDataLayer.cartSnapshot on every cart-state change
 *      so the plugin's Phase 1C sticky_cart_open event reads non-zero
 *      values (closes the v9.25.0 P1 risk where the snapshot lookup
 *      returned 0/0 because nothing populated it).
 *
 *   3. Fire 'free_delivery_unlocked' dataLayer event exactly once when
 *      the customer crosses the threshold — not repeatedly on each
 *      fragment refresh thereafter.
 *
 * Inputs (read-only):
 *   - .lafka-fdp[data-threshold][data-value][data-state][data-pct] —
 *     server-rendered, refreshed in place on each fragment update.
 *   - .lafka-cart-drawer__threshold — plugin-owned, refreshed via WC
 *     fragments. We transform this into the rich component.
 *
 * Outputs (writes):
 *   - window.lafkaDataLayer.cartSnapshot = { items_count, value }
 *   - window.dataLayer.push({ event: 'free_delivery_unlocked', threshold, value })
 *
 * Security: all DOM construction uses document.createElement +
 * textContent — never innerHTML — so a hostile Customizer or filter
 * override can't slip a tag through.
 *
 * Defensive:
 *   - No throw on missing .lafka-fdp (operator may have threshold = 0).
 *   - No throw on missing WC params (drawer might not exist on the page).
 *   - The 'unlocked' gate sticks across fragment refreshes — only resets
 *     when the cart drops back below the threshold, so re-crossing fires
 *     again (legitimate second conversion signal).
 *
 * @since 6.9.0
 */
(function () {
	'use strict';

	// Skip everything in environments without document (SSR / test runners).
	if (typeof document === 'undefined') {
		return;
	}

	/**
	 * Read all data-* attrs off a .lafka-fdp element into a plain object.
	 * Returns null when the element is missing or malformed.
	 */
	function readFdpState(fdpEl) {
		if (!fdpEl || !fdpEl.dataset) {
			return null;
		}
		var threshold = parseFloat(fdpEl.dataset.threshold);
		var value = parseFloat(fdpEl.dataset.value);
		var remaining = parseFloat(fdpEl.dataset.remaining);
		var pct = parseInt(fdpEl.dataset.pct, 10);
		var state = fdpEl.dataset.state || 'below';
		if (isNaN(threshold) || threshold <= 0) {
			return null;
		}
		var safeValue = isNaN(value) ? 0 : value;
		return {
			threshold: threshold,
			value: safeValue,
			remaining: isNaN(remaining) ? Math.max(0, threshold - safeValue) : remaining,
			pct: isNaN(pct) ? 0 : Math.max(0, Math.min(100, pct)),
			state: state === 'reached' ? 'reached' : 'below'
		};
	}

	/**
	 * Push to the GTM dataLayer if it exists. No-op otherwise.
	 * Mirrors the defensive wrapper pattern in lafka-custom-events.js.
	 */
	function pushEvent(eventName, params) {
		if (!eventName || typeof window === 'undefined') {
			return;
		}
		if (!window.dataLayer || typeof window.dataLayer.push !== 'function') {
			return;
		}
		var payload = { event: eventName };
		if (params && typeof params === 'object') {
			for (var key in params) {
				if (Object.prototype.hasOwnProperty.call(params, key)) {
					payload[key] = params[key];
				}
			}
		}
		window.dataLayer.push(payload);
	}

	/**
	 * Update the cart-snapshot global so Phase 1C events (sticky_cart_open)
	 * can read non-zero values. Reads items count from the drawer count
	 * pill (.lafka-cart-drawer__count-badge) and value from the latest
	 * .lafka-fdp[data-value]. Falls back to .lafka-cart-drawer__subtotal
	 * strong when no .lafka-fdp is mounted (operator disabled threshold).
	 */
	function updateCartSnapshot() {
		var itemsCount = 0;
		var pill = document.querySelector('[data-lafka-cart-count-pill]');
		if (pill) {
			var parsed = parseInt(pill.textContent || '0', 10);
			if (!isNaN(parsed)) {
				itemsCount = parsed;
			}
		}
		var value = 0;
		var fdpEl = document.querySelector('.lafka-fdp');
		if (fdpEl) {
			var fdpState = readFdpState(fdpEl);
			if (fdpState) {
				value = fdpState.value;
			}
		}
		if (0 === value) {
			// Fallback when threshold is disabled and no .lafka-fdp exists.
			var sub = document.querySelector('.lafka-cart-drawer__subtotal strong');
			if (sub) {
				var raw = (sub.textContent || '').replace(/[^\d.\-]/g, '');
				var parsedVal = parseFloat(raw);
				if (!isNaN(parsedVal)) {
					value = parsedVal;
				}
			}
		}
		if (typeof window === 'undefined') {
			return;
		}
		window.lafkaDataLayer = window.lafkaDataLayer || {};
		window.lafkaDataLayer.cartSnapshot = {
			items_count: itemsCount,
			value: value
		};
	}

	// Sticky "have we fired free_delivery_unlocked yet for this cart state?"
	// gate. Lives at module scope so it survives fragment refreshes within
	// the same page view. Reset on every cart change that drops back below
	// the threshold — legitimate re-crossing should fire again.
	var unlockedFired = false;

	/**
	 * Fire free_delivery_unlocked exactly once per crossing.
	 */
	function maybeFireUnlocked(fdpState) {
		if (!fdpState) {
			return;
		}
		if (fdpState.state === 'reached') {
			if (!unlockedFired) {
				unlockedFired = true;
				pushEvent('free_delivery_unlocked', {
					threshold: fdpState.threshold,
					value: fdpState.value
				});
			}
		} else {
			// Customer dropped back below — re-arm the gate so the next
			// crossing fires again.
			unlockedFired = false;
		}
	}

	/**
	 * Format a numeric value as currency for the client-rebuilt label.
	 * Server-render uses wc_price() (currency-correct). Client refresh
	 * falls back to a USD-style prefix '$' — good enough for the OSS
	 * default. Sites with a non-USD currency can override
	 * window.lafkaFdpFormatCurrency.
	 */
	function formatCurrency(value) {
		if (typeof window !== 'undefined' && typeof window.lafkaFdpFormatCurrency === 'function') {
			return window.lafkaFdpFormatCurrency(value);
		}
		var num = parseFloat(value);
		if (isNaN(num)) {
			num = 0;
		}
		return '$' + num.toFixed(2);
	}

	/**
	 * Build the .lafka-fdp element via createElement + textContent only.
	 * No innerHTML anywhere — protects against a hostile Customizer or
	 * filter override slipping a tag through wc_price().
	 *
	 * Mirrors partials/free-delivery-progress.php — keep in sync if
	 * attributes change.
	 */
	function buildFdpElement(state) {
		var root = document.createElement('div');
		var contextClass = state.context === 'cart' ? 'lafka-fdp--cart-page' : 'lafka-fdp--drawer';
		root.className = 'lafka-fdp ' + contextClass;
		root.setAttribute('data-lafka-fdp', '');
		root.dataset.state = state.state;
		root.dataset.threshold = String(state.threshold);
		root.dataset.value = String(state.value);
		root.dataset.remaining = String(state.remaining);
		root.dataset.pct = String(state.pct);
		root.setAttribute('role', 'status');
		root.setAttribute('aria-live', 'polite');

		var label = document.createElement('div');
		label.className = 'lafka-fdp__label';
		var title = document.createElement('span');
		title.className = 'lafka-fdp__title';
		title.textContent = state.reached
			? '✓ Free delivery unlocked'
			: 'Add ' + formatCurrency(state.remaining) + ' more for free delivery!';
		label.appendChild(title);

		var bar = document.createElement('div');
		bar.className = 'lafka-fdp__bar';
		bar.setAttribute('aria-hidden', 'true');
		var fill = document.createElement('div');
		fill.className = 'lafka-fdp__fill';
		fill.style.width = state.pct + '%';
		bar.appendChild(fill);

		var sub = document.createElement('div');
		sub.className = 'lafka-fdp__sub';
		sub.textContent =
			formatCurrency(state.value) +
			' / ' +
			formatCurrency(state.threshold) +
			' · ' +
			state.pct +
			'%';

		root.appendChild(label);
		root.appendChild(bar);
		root.appendChild(sub);
		return root;
	}

	/**
	 * Update an existing .lafka-fdp element in place — preserves the
	 * DOM node so the CSS width transition animates the fill change.
	 */
	function updateFdpElement(fdpEl, state) {
		if (!fdpEl) {
			return;
		}
		fdpEl.dataset.state = state.state;
		fdpEl.dataset.value = String(state.value);
		fdpEl.dataset.remaining = String(state.remaining);
		fdpEl.dataset.pct = String(state.pct);

		var fill = fdpEl.querySelector('.lafka-fdp__fill');
		if (fill) {
			fill.style.width = state.pct + '%';
		}
		var titleEl = fdpEl.querySelector('.lafka-fdp__title');
		if (titleEl) {
			titleEl.textContent = state.reached
				? '✓ Free delivery unlocked'
				: 'Add ' + formatCurrency(state.remaining) + ' more for free delivery!';
		}
		var subEl = fdpEl.querySelector('.lafka-fdp__sub');
		if (subEl) {
			subEl.textContent =
				formatCurrency(state.value) +
				' / ' +
				formatCurrency(state.threshold) +
				' · ' +
				state.pct +
				'%';
		}
	}

	/**
	 * After a fragment refresh, the plugin re-emits the plain-text
	 * .lafka-cart-drawer__threshold notice inside .lafka-cart-drawer__total.
	 * Replace it with the rich .lafka-fdp component (built from the same
	 * cart-state values the plugin used, read indirectly via the new
	 * subtotal markup + threshold cached on the initial-render .lafka-fdp).
	 *
	 * If no prior .lafka-fdp exists (operator disabled, no Customizer
	 * threshold), leave the plain text alone — gracefully degrades.
	 */
	function syncDrawerFdpFromFragment() {
		var drawerTotal = document.querySelector('.lafka-cart-drawer .lafka-cart-drawer__total');
		if (!drawerTotal) {
			return;
		}

		// Existing .lafka-fdp inside the drawer footer — created on initial
		// server render. We use its data-threshold as our source of truth
		// for the threshold value (Customizer-driven, doesn't change
		// per-AJAX).
		var existingFdp = drawerTotal.querySelector('.lafka-fdp');
		var thresholdRef = existingFdp ? readFdpState(existingFdp) : null;

		// Fragment re-render may have replaced our .lafka-fdp with the
		// plain .lafka-cart-drawer__threshold notice. Look for both.
		var plainThreshold = drawerTotal.querySelector('.lafka-cart-drawer__threshold');

		// Read the current subtotal off the fragment-emitted strong tag.
		var subtotalStrong = drawerTotal.querySelector('.lafka-cart-drawer__subtotal strong');
		var currentValue = 0;
		if (subtotalStrong) {
			// Strip currency symbols + commas, parse the float.
			var subtotalText = (subtotalStrong.textContent || '').replace(/[^\d.\-]/g, '');
			var parsedVal = parseFloat(subtotalText);
			if (!isNaN(parsedVal)) {
				currentValue = parsedVal;
			}
		}

		// If we don't have a threshold reference, we can't rebuild the
		// component — fragment plain text stays. Common case: operator
		// disabled the threshold, so nothing to render anyway. Still
		// update the snapshot for sticky_cart_open consumers.
		if (!thresholdRef) {
			updateCartSnapshot();
			return;
		}

		var threshold = thresholdRef.threshold;
		var remaining = Math.max(0, threshold - currentValue);
		var reached = remaining <= 0;
		var pct = Math.max(0, Math.min(100, Math.round((currentValue / threshold) * 100)));
		var nextState = {
			context: 'drawer',
			threshold: threshold,
			value: currentValue,
			remaining: remaining,
			pct: pct,
			state: reached ? 'reached' : 'below',
			reached: reached
		};

		if (existingFdp) {
			// Same element — animates via the CSS width transition.
			updateFdpElement(existingFdp, nextState);
			// If the plugin also re-emitted a plain notice, remove it.
			if (plainThreshold && plainThreshold !== existingFdp) {
				plainThreshold.parentNode.removeChild(plainThreshold);
			}
		} else if (plainThreshold) {
			// No prior .lafka-fdp survived; build one from scratch and
			// swap in for the plugin's plain notice.
			var newFdp = buildFdpElement(nextState);
			plainThreshold.parentNode.replaceChild(newFdp, plainThreshold);
		}

		updateCartSnapshot();
		maybeFireUnlocked({
			threshold: threshold,
			value: currentValue,
			remaining: remaining,
			pct: pct,
			state: reached ? 'reached' : 'below'
		});
	}

	/**
	 * Bind cart-update listeners. WC fires several jQuery events in
	 * sequence on AJAX add/remove:
	 *
	 *   - 'added_to_cart' — after AJAX add succeeds (drawer auto-opens).
	 *   - 'removed_from_cart' — after a line-item remove.
	 *   - 'wc_fragments_refreshed' — after the WC fragments cookie hits
	 *     server and the response repopulates fragments from cookie cache.
	 *   - 'wc_fragments_loaded' — after initial fragments load on page-
	 *     load with a non-empty cart cookie.
	 *   - 'updated_wc_div' — after WC re-renders the cart page (e.g. after
	 *     "Update cart" submit).
	 *
	 * We listen to all of them so the snapshot + FDP stay current
	 * regardless of which path triggered the update.
	 */
	function bindCartListeners() {
		var $body = window.jQuery ? window.jQuery(document.body) : null;
		if (!$body) {
			// No jQuery — initial render still wired the snapshot below
			// but we can't subscribe to WC AJAX events without it. WC
			// ships jQuery on every front-end page that includes WC
			// assets, so this branch should be rare.
			return;
		}

		var events = [
			'added_to_cart',
			'removed_from_cart',
			'wc_fragments_refreshed',
			'wc_fragments_loaded',
			'updated_wc_div'
		];

		events.forEach(function (eventName) {
			$body.on(eventName, function () {
				syncDrawerFdpFromFragment();
			});
		});
	}

	// ─────────────────────────────────────────────────────────────────────
	// Boot
	// ─────────────────────────────────────────────────────────────────────
	function boot() {
		// Initial snapshot off the server-rendered .lafka-fdp (if any) +
		// initial unlocked-gate check so the event fires for customers
		// who land on /cart/ already past the threshold.
		updateCartSnapshot();
		var initialFdp = document.querySelector('.lafka-fdp');
		if (initialFdp) {
			maybeFireUnlocked(readFdpState(initialFdp));
		}
		bindCartListeners();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
