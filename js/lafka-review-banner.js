/* lafka-theme/js/lafka-review-banner.js
 * Post-purchase review banner (Pillar 3D, v6.11.0).
 *
 * Responsibilities:
 *
 *   1. Read the `lafka_review_prompt_show` cookie set by the plugin's
 *      `lafka_review_banner_set_cookie` hook. When set ('1'), find the
 *      partial-rendered banner element in the DOM and animate it in.
 *
 *   2. Bind dismiss button → POST /wp-json/lafka/v1/review-banner-dismiss
 *      then animate the banner out. The REST endpoint flips the user-meta
 *      `_lafka_review_banner_dismissed` flag + clears the cookie so the
 *      banner never reappears for that user.
 *
 *   3. Bind CTA click → fire `review_banner_click` dataLayer event then let
 *      the browser follow the link (target="_blank" — opens the operator's
 *      Google review URL in a new tab).
 *
 *   4. Fire `review_banner_shown` dataLayer event on render + send
 *      fire-and-forget POST to /wp-json/lafka/v1/review-banner-shown for
 *      server-side analytics.
 *
 *   5. Suppress on conversion pages (/cart/, /checkout/, /order-received/,
 *      /my-account/) — the server-side partial also blocks these, but the
 *      JS double-checks because subdir installs may not match the partial's
 *      `is_cart()` etc. detection cleanly.
 *
 *   6. Once per page visit — re-renders only on full page navigation, never
 *      on AJAX state changes. Implementation: a script-scope `hasRun` flag
 *      gates the animate-in path.
 *
 * Inputs (read-only):
 *   - document.cookie — looks for `lafka_review_prompt_show=1`.
 *   - .lafka-review-banner element pre-rendered by the partial.
 *   - window.lafkaReviewBannerSettings (wp_localize_script payload):
 *       {
 *         restRoot: string,          // wp-json URL base
 *         restNonce: string,          // X-WP-Nonce header value
 *         pageBlocklist: string[],   // path substrings — '/cart/' etc.
 *       }
 *
 * Outputs (writes):
 *   - DOM: flips data-visible="true" / data-dismissing="true" on the banner.
 *   - window.dataLayer.push({ event: 'review_banner_*' }).
 *   - Network: 2 fire-and-forget POSTs (shown + dismiss) — rate-limited
 *     server-side.
 *
 * @since 6.11.0
 */
(function () {
	'use strict';

	if (typeof document === 'undefined' || typeof window === 'undefined') {
		return;
	}

	var settings = window.lafkaReviewBannerSettings || {};
	var restRoot = settings.restRoot || '/wp-json/';
	var restNonce = settings.restNonce || '';
	var blocklist = Array.isArray(settings.pageBlocklist) ? settings.pageBlocklist : [
		'/cart/', '/checkout/', '/order-received/', '/my-account/'
	];

	// ─────────────────────────────────────────────────────────────────────
	// Conversion-page guard — JS-side double-check.
	// ─────────────────────────────────────────────────────────────────────
	var currentPath = window.location && window.location.pathname ? window.location.pathname : '';
	for (var i = 0; i < blocklist.length; i++) {
		var token = blocklist[i];
		if (token && currentPath.indexOf(token) !== -1) {
			return;
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// Cookie reader — bail when the plugin hasn't set the show flag.
	// ─────────────────────────────────────────────────────────────────────
	function getCookieValue(name) {
		var raw = document.cookie || '';
		var pairs = raw.split(';');
		for (var p = 0; p < pairs.length; p++) {
			var parts = pairs[p].split('=');
			var key = (parts[0] || '').trim();
			if (key === name) {
				return decodeURIComponent((parts[1] || '').trim());
			}
		}
		return '';
	}

	if (getCookieValue('lafka_review_prompt_show') !== '1') {
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
	// Fire-and-forget POST — used for the shown + dismiss endpoints.
	// We never block on the response; the server is the source of truth and
	// the next page-load will re-evaluate the cookie regardless.
	// ─────────────────────────────────────────────────────────────────────
	function postBeacon(path) {
		try {
			var url = restRoot.replace(/\/$/, '') + path;
			if (typeof window.fetch !== 'function') {
				return;
			}
			window.fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: restNonce ? { 'X-WP-Nonce': restNonce } : {},
				keepalive: true
			}).catch(function () {
				// swallow — the dismiss meta on the next request still gates
				// re-render, and the shown beacon is best-effort.
			});
		} catch (_err) {
			// no-op
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// Boot — once per page.
	// ─────────────────────────────────────────────────────────────────────
	var hasRun = false;
	function init() {
		if (hasRun) {
			return;
		}
		hasRun = true;

		var banner = document.querySelector('.lafka-review-banner');
		if (!banner) {
			// Partial didn't render (e.g. SSR raced cookie set; banner will
			// render on the next page load).
			return;
		}

		// Animate in — force a layout flush before flipping the attribute so
		// the CSS transition runs from the initial off-screen state.
		void banner.offsetWidth;
		banner.setAttribute('data-visible', 'true');

		pushEvent('review_banner_shown', {});
		postBeacon('/lafka/v1/review-banner-shown');

		// Bind close button.
		var closeBtn = banner.querySelector('.lafka-review-banner__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function (evt) {
				if (evt && typeof evt.preventDefault === 'function') {
					evt.preventDefault();
				}
				dismiss();
			});
		}

		// Bind CTA click — fire event then let browser navigate.
		var cta = banner.querySelector('.lafka-review-banner__cta');
		if (cta) {
			cta.addEventListener('click', function () {
				pushEvent('review_banner_click', {
					url: cta.getAttribute('href') || ''
				});
				// Mark as engaged-with — also flip the dismiss meta so the
				// banner doesn't keep showing after the user has acted. This
				// is the "they clicked → don't nag them again" path.
				postBeacon('/lafka/v1/review-banner-dismiss');
			});
		}

		// Escape key closes the banner.
		document.addEventListener('keydown', onKeydown);
	}

	function onKeydown(evt) {
		if (evt.key === 'Escape' || evt.keyCode === 27) {
			dismiss();
		}
	}

	function dismiss() {
		var banner = document.querySelector('.lafka-review-banner');
		if (!banner) {
			return;
		}
		banner.setAttribute('data-dismissing', 'true');
		pushEvent('review_banner_dismiss', {});
		postBeacon('/lafka/v1/review-banner-dismiss');

		// Remove after CSS transition completes. 200ms enter, 150ms exit per
		// spec — add a 50ms safety pad for prefers-reduced-motion users (the
		// timeout still fires).
		window.setTimeout(function () {
			if (banner.parentNode) {
				banner.parentNode.removeChild(banner);
			}
		}, 220);

		document.removeEventListener('keydown', onKeydown);
	}

	// Defer to DOM ready — partial is rendered via wp_footer, so by
	// DOMContentLoaded it's guaranteed in the DOM.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// Expose a tiny test hook so QA / browser DevTools can trigger / dismiss
	// the banner manually without faking a cookie. Calling
	// `window.lafkaReviewBanner.show()` re-runs init even after first run.
	window.lafkaReviewBanner = {
		show: function () {
			hasRun = false;
			init();
		},
		dismiss: function () {
			dismiss();
		}
	};
})();
