/* lafka-theme/js/lafka-exit-intent.js
 * Exit-intent reminder toast (Pillar 3C, v6.10.0).
 *
 * Responsibilities:
 *
 *   1. Detect exit-intent and surface a polite, dismissible toast that
 *      nudges the customer back to /cart/ if they have items but haven't
 *      checked out yet.
 *
 *   2. Desktop trigger: mouseleave from the TOP edge of the viewport —
 *      classic "heading toward browser tab / close button" signal.
 *
 *   3. Mobile trigger: rapid scroll-back-to-top exceeding a velocity
 *      threshold (≥800px upward in <500ms while currently within the top
 *      200px of the page). Normal slow scrolling back to read content
 *      does NOT fire — only fast, intent-like flicks toward the address
 *      bar / browser back gesture.
 *
 *   4. Gating (ALL must be true):
 *        - Customizer toggle `enabled` is true.
 *        - Cart has items (window.lafkaDataLayer.cartSnapshot.items_count > 0).
 *        - Not on a conversion page (/cart/, /checkout/, /order-received/,
 *          /my-account/) — passed in via wp_localize_script blocklist.
 *        - Has been at least `gracePeriodSeconds` (default 30) since page
 *          load — never fires immediately on landing.
 *        - User hasn't already seen the toast this session
 *          (sessionStorage.lafka_exit_intent_shown !== 'true').
 *
 *   5. Toast UI:
 *        - Bottom-right desktop, bottom-center / full-width minus gutters
 *          on mobile (CSS-driven).
 *        - Built entirely via document.createElement + textContent — never
 *          innerHTML — so a hostile Customizer override can't slip a tag
 *          through.
 *        - Headline picks dynamically:
 *            * "below" threshold (cart value < threshold OR no threshold
 *              configured) → headlineBelow template with {amount} token
 *              replaced by formatted remaining-to-free-delivery currency.
 *            * "reached" threshold → headlineReached template (no token).
 *        - Two CTAs: primary red pill "Resume checkout" → navigate to
 *          /cart/, ghost "Maybe later" → dismiss.
 *        - Close × in top-right (32×32 tap target).
 *
 *   6. Analytics:
 *        - Fires `exit_intent_shown` when toast appears.
 *        - Fires `exit_intent_resume_click` when primary CTA clicked.
 *        - Fires `exit_intent_dismiss` when × or "Maybe later" clicked
 *          OR when toast is closed by pressing Escape.
 *
 * Inputs (read-only):
 *   - window.lafkaExitIntentSettings (wp_localize_script payload):
 *       {
 *         enabled: boolean,
 *         gracePeriodSeconds: number,
 *         pageBlocklist: string[],   // path substrings — '/cart/' etc.
 *         cartUrl: string,            // wc_get_cart_url() resolved server-side
 *         headlineBelow: string,      // "Add {amount} more for free delivery"
 *         headlineReached: string,    // "Your cart is ready..."
 *         bodyText: string,
 *         ctaLabel: string,
 *         dismissLabel: string,
 *         closeAriaLabel: string,
 *       }
 *   - window.lafkaDataLayer.cartSnapshot (populated by lafka-fdp-tracker
 *     on v6.9.0+) — { items_count, value }.
 *   - .lafka-fdp[data-threshold] — read once for free-delivery threshold;
 *     falls back to null if not on page (operator may have disabled it).
 *
 * Outputs (writes):
 *   - DOM: appends a .lafka-exit-toast element to <body> on trigger.
 *   - sessionStorage.lafka_exit_intent_shown = 'true' on dismiss.
 *   - window.dataLayer.push({ event: 'exit_intent_*' }).
 *
 * Defensive:
 *   - Operator-disabled (enabled === false) is a no-op — script still
 *     loads to keep enqueue logic simple, but exits immediately.
 *   - Missing wp_localize_script payload = no-op (script alone is inert).
 *   - sessionStorage access can throw in private-browsing modes — wrapped
 *     in try/catch so detection still works even when the dismiss-gate
 *     can't persist.
 *   - Path blocklist match uses indexOf to handle WP subdir installs
 *     ('/wp-shop/cart/' still matches '/cart/').
 *
 * @since 6.10.0
 */
(function () {
	'use strict';

	if (typeof document === 'undefined' || typeof window === 'undefined') {
		return;
	}

	var settings = window.lafkaExitIntentSettings;
	if (!settings) {
		return;
	}
	// wp_localize_script stringifies booleans + ints, so accept both '1' / 1
	// and true / 'true' as the enabled signal. Anything else = no-op.
	var enabledRaw = settings.enabled;
	var isEnabled = (
		true === enabledRaw ||
		'1' === enabledRaw ||
		1 === enabledRaw ||
		'true' === enabledRaw
	);
	if (!isEnabled) {
		return;
	}

	// Defensive defaults — payload SHOULD ship these from PHP, but if a
	// future filter strips a field we don't want to crash.
	var graceMs = (parseInt(settings.gracePeriodSeconds, 10) || 30) * 1000;
	var blocklist = Array.isArray(settings.pageBlocklist) ? settings.pageBlocklist : [];
	var cartUrl = settings.cartUrl || '/cart/';
	var headlineBelow = settings.headlineBelow || 'Add {amount} more for free delivery';
	var headlineReached = settings.headlineReached || 'Your cart is ready — checkout in 30 seconds';
	var bodyText = settings.bodyText || 'Tap below to pick up where you left off.';
	var ctaLabel = settings.ctaLabel || 'Resume checkout';
	var dismissLabel = settings.dismissLabel || 'Maybe later';
	var closeAriaLabel = settings.closeAriaLabel || 'Close reminder';

	// ─────────────────────────────────────────────────────────────────────
	// Page blocklist check — never fire on conversion pages.
	// ─────────────────────────────────────────────────────────────────────
	var currentPath = window.location && window.location.pathname ? window.location.pathname : '';
	for (var i = 0; i < blocklist.length; i++) {
		var token = blocklist[i];
		if (token && currentPath.indexOf(token) !== -1) {
			return;
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// Session gate — once per browser session.
	// ─────────────────────────────────────────────────────────────────────
	function alreadyShownThisSession() {
		try {
			return window.sessionStorage && 'true' === window.sessionStorage.getItem('lafka_exit_intent_shown');
		} catch (_err) {
			// Private mode / disabled storage — treat as not shown so the
			// toast can still fire (best-effort UX).
			return false;
		}
	}

	function markShownThisSession() {
		try {
			if (window.sessionStorage) {
				window.sessionStorage.setItem('lafka_exit_intent_shown', 'true');
			}
		} catch (_err) {
			// no-op
		}
	}

	if (alreadyShownThisSession()) {
		return;
	}

	// ─────────────────────────────────────────────────────────────────────
	// dataLayer push wrapper — silently no-op when GTM isn't on the page.
	// ─────────────────────────────────────────────────────────────────────
	function pushEvent(eventName, params) {
		if (!eventName) {
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

	// ─────────────────────────────────────────────────────────────────────
	// Cart snapshot reader — lazy so we pick up FDP tracker's late writes.
	// ─────────────────────────────────────────────────────────────────────
	function readCartSnapshot() {
		if (!window.lafkaDataLayer || !window.lafkaDataLayer.cartSnapshot) {
			return { items_count: 0, value: 0 };
		}
		var snap = window.lafkaDataLayer.cartSnapshot;
		return {
			items_count: parseInt(snap.items_count, 10) || 0,
			value: parseFloat(snap.value) || 0
		};
	}

	/**
	 * Read the operator-configured free-delivery threshold from any
	 * .lafka-fdp[data-threshold] on the page (sticky cart drawer, cart
	 * page partial). Returns null when there's no FDP component mounted —
	 * the operator probably has the threshold disabled, in which case the
	 * toast falls back to the "reached"-style headline.
	 */
	function readThreshold() {
		var fdp = document.querySelector('.lafka-fdp[data-threshold]');
		if (!fdp) {
			return null;
		}
		var t = parseFloat(fdp.dataset.threshold);
		if (isNaN(t) || t <= 0) {
			return null;
		}
		return t;
	}

	function formatCurrency(value) {
		// Mirror lafka-fdp-tracker.js — use override hook when site overrides
		// the format for non-USD currencies.
		if (typeof window.lafkaFdpFormatCurrency === 'function') {
			return window.lafkaFdpFormatCurrency(value);
		}
		var num = parseFloat(value);
		if (isNaN(num)) {
			num = 0;
		}
		return '$' + num.toFixed(2);
	}

	// ─────────────────────────────────────────────────────────────────────
	// Toast builder — createElement + textContent only.
	// ─────────────────────────────────────────────────────────────────────
	function buildHeadlineText(snapshot, threshold) {
		if (null === threshold) {
			// Operator hasn't configured a delivery threshold — show the
			// "ready" headline (cart-page-equivalent state).
			return { text: headlineReached, state: 'reached' };
		}
		var remaining = threshold - snapshot.value;
		if (remaining <= 0) {
			return { text: headlineReached, state: 'reached' };
		}
		var formatted = formatCurrency(remaining);
		// Token substitution: replace {amount} with formatted remaining.
		// Use a function so we don't risk regex special chars in the
		// formatted output.
		var text = headlineBelow.split('{amount}').join(formatted);
		return { text: text, state: 'below' };
	}

	function buildToast(snapshot, threshold) {
		var headline = buildHeadlineText(snapshot, threshold);

		var root = document.createElement('div');
		root.className = 'lafka-exit-toast';
		root.dataset.state = headline.state;
		root.setAttribute('role', 'dialog');
		root.setAttribute('aria-live', 'polite');
		root.setAttribute('aria-labelledby', 'lafka-exit-toast-headline');

		var inner = document.createElement('div');
		inner.className = 'lafka-exit-toast__inner';

		// Close × in top-right corner.
		var closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'lafka-exit-toast__close';
		closeBtn.setAttribute('aria-label', closeAriaLabel);
		closeBtn.textContent = '×';
		inner.appendChild(closeBtn);

		// Headline.
		var headlineEl = document.createElement('p');
		headlineEl.className = 'lafka-exit-toast__headline';
		headlineEl.id = 'lafka-exit-toast-headline';
		headlineEl.textContent = headline.text;
		inner.appendChild(headlineEl);

		// Body copy.
		var bodyEl = document.createElement('p');
		bodyEl.className = 'lafka-exit-toast__body';
		bodyEl.textContent = bodyText;
		inner.appendChild(bodyEl);

		// Actions row.
		var actions = document.createElement('div');
		actions.className = 'lafka-exit-toast__actions';

		var primary = document.createElement('a');
		primary.className = 'lafka-exit-toast__primary';
		primary.href = cartUrl;
		primary.textContent = ctaLabel;
		actions.appendChild(primary);

		var dismiss = document.createElement('button');
		dismiss.type = 'button';
		dismiss.className = 'lafka-exit-toast__dismiss';
		dismiss.textContent = dismissLabel;
		actions.appendChild(dismiss);

		inner.appendChild(actions);
		root.appendChild(inner);

		return {
			root: root,
			closeBtn: closeBtn,
			primary: primary,
			dismiss: dismiss
		};
	}

	// ─────────────────────────────────────────────────────────────────────
	// Show / dismiss lifecycle.
	// ─────────────────────────────────────────────────────────────────────
	var hasShown = false;
	var armed = false;

	function dismiss(reason) {
		var toast = document.querySelector('.lafka-exit-toast');
		if (!toast) {
			return;
		}
		// Trigger CSS exit animation.
		toast.dataset.dismissing = 'true';
		pushEvent('exit_intent_dismiss', { reason: reason || 'manual' });
		markShownThisSession();
		// Remove after CSS transition completes. 200ms enter, 150ms exit
		// per spec — add a 50ms safety pad for prefers-reduced-motion =
		// no-transition users (the timeout still fires).
		window.setTimeout(function () {
			if (toast.parentNode) {
				toast.parentNode.removeChild(toast);
			}
		}, 220);
		// Detach listeners — we're done for this session.
		detachTriggers();
	}

	function showToast() {
		if (hasShown) {
			return;
		}
		var snapshot = readCartSnapshot();
		if (snapshot.items_count <= 0) {
			// Cart emptied between arming and trigger — bail silently.
			return;
		}
		hasShown = true;

		var threshold = readThreshold();
		var parts = buildToast(snapshot, threshold);
		document.body.appendChild(parts.root);

		// Force a layout flush before flipping data-visible so the CSS
		// transition runs from the initial off-screen state.
		// eslint-disable-next-line no-unused-expressions
		parts.root.offsetWidth;
		parts.root.dataset.visible = 'true';

		pushEvent('exit_intent_shown', {
			items_count: snapshot.items_count,
			value: snapshot.value,
			state: parts.root.dataset.state
		});
		markShownThisSession();

		// Wire up close + dismiss + primary handlers.
		parts.closeBtn.addEventListener('click', function () {
			dismiss('close_button');
		});
		parts.dismiss.addEventListener('click', function () {
			dismiss('maybe_later');
		});
		parts.primary.addEventListener('click', function () {
			pushEvent('exit_intent_resume_click', {
				items_count: snapshot.items_count,
				value: snapshot.value
			});
			markShownThisSession();
			// Don't preventDefault — let the browser navigate to /cart/.
		});

		// Escape key closes the toast.
		document.addEventListener('keydown', onKeydown);

		// Once shown, detach the trigger listeners — only one toast per
		// session, and we don't want repeated mouseleave or scroll
		// callbacks firing.
		detachTriggers();
	}

	function onKeydown(evt) {
		if (evt.key === 'Escape' || evt.keyCode === 27) {
			dismiss('escape');
			document.removeEventListener('keydown', onKeydown);
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// Desktop trigger — mouseleave above the top edge of the viewport.
	// ─────────────────────────────────────────────────────────────────────
	function onMouseLeave(evt) {
		if (!armed || hasShown) {
			return;
		}
		// Bail unless the mouse is exiting upward (heading for tab bar).
		// evt.clientY can be 0 or negative when leaving the top edge.
		if (typeof evt.clientY === 'number' && evt.clientY > 5) {
			return;
		}
		// Skip if the related target is still inside the page (relatedTarget
		// is set when moving between elements within the document — only
		// becomes null/HTML when actually leaving the viewport).
		if (evt.relatedTarget || evt.toElement) {
			return;
		}
		var snapshot = readCartSnapshot();
		if (snapshot.items_count <= 0) {
			return;
		}
		showToast();
	}

	// ─────────────────────────────────────────────────────────────────────
	// Mobile trigger — rapid upward scroll velocity near top of page.
	//
	// Why this shape: mouseleave isn't fired on touch devices in any
	// reliable cross-browser way, and even when it is the trajectory
	// signal is meaningless ("up" on touch ≠ exit intent). Instead we
	// watch for a fast scroll-back-to-top that lands the user near the
	// top of the page — the user-intent equivalent of "I'm reaching for
	// the address bar or the back gesture".
	//
	// Thresholds (per spec):
	//   - ≥800px upward distance
	//   - in <500ms
	//   - landing within the top 200px of the page
	// ─────────────────────────────────────────────────────────────────────
	var scrollSamples = [];

	function onScroll() {
		if (!armed || hasShown) {
			return;
		}
		var now = Date.now();
		var y = window.pageYOffset || document.documentElement.scrollTop || 0;
		scrollSamples.push({ t: now, y: y });
		// Trim samples older than 500ms — the velocity window.
		while (scrollSamples.length > 0 && now - scrollSamples[0].t > 500) {
			scrollSamples.shift();
		}
		if (scrollSamples.length < 2) {
			return;
		}
		var oldest = scrollSamples[0];
		var deltaY = oldest.y - y; // positive when scrolling UP (y decreasing)
		var deltaT = now - oldest.t;
		// Must be: scrolling up ≥800px in <500ms AND currently in top 200px.
		if (deltaY < 800 || deltaT > 500 || y > 200) {
			return;
		}
		var snapshot = readCartSnapshot();
		if (snapshot.items_count <= 0) {
			return;
		}
		showToast();
	}

	// ─────────────────────────────────────────────────────────────────────
	// Lifecycle: arm after grace period, then attach triggers.
	// ─────────────────────────────────────────────────────────────────────
	function attachTriggers() {
		// Desktop: mouseleave on document (most reliable cross-browser
		// signal for "cursor left viewport"). Listener bound to document
		// so we still catch the event even if an overlay is mounted.
		document.addEventListener('mouseleave', onMouseLeave);
		// Mobile: passive scroll listener so we don't block scrolling.
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	function detachTriggers() {
		document.removeEventListener('mouseleave', onMouseLeave);
		window.removeEventListener('scroll', onScroll);
	}

	function arm() {
		armed = true;
		attachTriggers();
	}

	// Boot — wait for grace period to expire, then arm.
	window.setTimeout(arm, graceMs);

	// Expose a tiny test hook so QA / browser DevTools can trigger the
	// toast manually without simulating a mouseleave. Calling
	// `window.lafkaExitIntent.show()` from console fires showToast()
	// regardless of arm state (still respects has-shown + cart-empty
	// guards). Used in the v6.10.0 acceptance screenshots.
	window.lafkaExitIntent = {
		show: function () {
			showToast();
		},
		dismiss: function () {
			dismiss('manual');
		},
		armed: function () {
			return armed;
		}
	};
})();
