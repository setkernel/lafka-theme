/* lafka-theme/js/lafka-push-subscribe.js
 * Web Push subscribe flow (Pillar 3E, v6.12.0).
 *
 * Responsibilities:
 *
 *   1. Count page views in this session. After `threshold` views, if the
 *      browser supports notifications AND the user has never been prompted
 *      AND Notification.permission === 'default', surface a custom in-page
 *      prompt (NOT the browser-native dialog yet — browsers permanently
 *      block the site if the customer rejects the native dialog).
 *
 *   2. On Accept click → call Notification.requestPermission() → on grant,
 *      registration.pushManager.subscribe({ userVisibleOnly: true,
 *      applicationServerKey }) → POST the resulting subscription JSON to
 *      /wp-json/lafka/v1/push/subscribe with the wp_rest nonce.
 *
 *   3. On Deny / Dismiss → write a localStorage record so the prompt does
 *      not reappear for 30 days.
 *
 *   4. dataLayer events:
 *        - push_prompt_shown   { threshold, pageviews }
 *        - push_prompt_accept  { granted: bool }
 *        - push_prompt_deny    { reason: 'dismiss' | 'deny' | 'browser_deny' }
 *
 * Inputs (read-only):
 *   - window.lafkaPushSettings (wp_localize_script payload):
 *       {
 *         enabled: boolean,
 *         applicationServerKey: string,   // base64url VAPID public key
 *         restRoot: string,
 *         restNonce: string,
 *         threshold: number,              // page views before prompt
 *         swUrl: string,                  // service worker URL
 *       }
 *
 * Outputs:
 *   - DOM: flips data-visible="true" / "false" on .lafka-push-prompt.
 *   - sessionStorage.lafka_push_pageviews — running counter for this session.
 *   - localStorage.lafka_push_dismissed_at — unix ts; suppresses prompt
 *     for 30 days when set.
 *   - Network: 1 POST to /wp-json/lafka/v1/push/subscribe on accept.
 *
 * @since 6.12.0
 */
(function () {
    'use strict';

    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    const settings = window.lafkaPushSettings || {};
    if (!settings.enabled || !settings.applicationServerKey) {
        return;
    }

    const threshold = parseInt(settings.threshold || '2', 10) || 2;
    const restRoot = (settings.restRoot || '/wp-json/').replace(/\/$/, '');
    const restNonce = settings.restNonce || '';
    const swUrl = settings.swUrl || '/sw.js';
    const SUPPRESS_DAYS = 30;
    const STORAGE_KEY = 'lafka_push_dismissed_at';
    const SESSION_KEY = 'lafka_push_pageviews';

    // ─────────────────────────────────────────────────────────────────────
    // dataLayer wrapper.
    // ─────────────────────────────────────────────────────────────────────
    function pushEvent(eventName, params) {
        if (!eventName || !window.dataLayer || typeof window.dataLayer.push !== 'function') {
            return;
        }
        const payload = { event: eventName };
        if (params && typeof params === 'object') {
            Object.keys(params).forEach((k) => {
                payload[k] = params[k];
            });
        }
        window.dataLayer.push(payload);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Suppression window.
    // ─────────────────────────────────────────────────────────────────────
    function isSuppressed() {
        try {
            const stamp = parseInt(window.localStorage.getItem(STORAGE_KEY) || '0', 10);
            if (!stamp) {
                return false;
            }
            const ageMs = Date.now() - stamp;
            return ageMs < SUPPRESS_DAYS * 86400 * 1000;
        } catch (_err) {
            return false;
        }
    }

    function suppress() {
        try {
            window.localStorage.setItem(STORAGE_KEY, String(Date.now()));
        } catch (_err) {
            // Private-browsing — silently degrade.
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Page-view counter (per session).
    // ─────────────────────────────────────────────────────────────────────
    function bumpPageView() {
        try {
            const current = parseInt(window.sessionStorage.getItem(SESSION_KEY) || '0', 10);
            const next = current + 1;
            window.sessionStorage.setItem(SESSION_KEY, String(next));
            return next;
        } catch (_err) {
            return threshold; // Treat as immediately past threshold so the prompt still works.
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // base64url decoder for the VAPID applicationServerKey.
    // ─────────────────────────────────────────────────────────────────────
    function urlBase64ToUint8Array(b64) {
        const padding = '='.repeat((4 - (b64.length % 4)) % 4);
        const padded = (b64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(padded);
        const out = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i += 1) {
            out[i] = raw.charCodeAt(i);
        }
        return out;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Register the SW and resolve the registration. Cached so subscribe
    // can re-use the same registration.
    // ─────────────────────────────────────────────────────────────────────
    let _swReady = null;
    function getSwRegistration() {
        if (_swReady) {
            return _swReady;
        }
        _swReady = navigator.serviceWorker.getRegistration(swUrl).then((reg) => {
            if (reg) {
                return reg;
            }
            return navigator.serviceWorker.register(swUrl, { scope: '/' });
        });
        return _swReady;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Subscribe flow — invoked when the user clicks Accept.
    // ─────────────────────────────────────────────────────────────────────
    function subscribe() {
        return getSwRegistration().then((reg) => {
            return Notification.requestPermission().then((perm) => {
                if (perm !== 'granted') {
                    pushEvent('push_prompt_deny', { reason: perm === 'denied' ? 'browser_deny' : 'dismiss' });
                    suppress();
                    return null;
                }
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(settings.applicationServerKey)
                });
            });
        }).then((subscription) => {
            if (!subscription) {
                return null;
            }
            const body = subscription.toJSON();
            return window.fetch(restRoot + '/lafka/v1/push/subscribe', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': restNonce
                },
                body: JSON.stringify({
                    endpoint: body.endpoint,
                    keys: body.keys || {}
                }),
                keepalive: true
            }).then((res) => {
                if (res && res.ok) {
                    pushEvent('push_prompt_accept', { granted: true });
                } else {
                    pushEvent('push_prompt_accept', { granted: false });
                }
                return subscription;
            }).catch(() => {
                pushEvent('push_prompt_accept', { granted: false });
                return subscription;
            });
        }).catch(() => {
            // Any error path — treat as a deny so we don't pester.
            pushEvent('push_prompt_deny', { reason: 'browser_deny' });
            suppress();
            return null;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Prompt DOM wiring.
    // ─────────────────────────────────────────────────────────────────────
    function bindPrompt(promptEl) {
        const acceptBtn = promptEl.querySelector('.lafka-push-prompt__accept');
        const denyBtn = promptEl.querySelector('.lafka-push-prompt__deny');
        const closeBtn = promptEl.querySelector('.lafka-push-prompt__close');

        function dismiss(reason) {
            promptEl.setAttribute('data-dismissing', 'true');
            pushEvent('push_prompt_deny', { reason: reason || 'dismiss' });
            suppress();
            window.setTimeout(() => {
                if (promptEl.parentNode) {
                    promptEl.parentNode.removeChild(promptEl);
                }
            }, 220);
        }

        if (acceptBtn) {
            acceptBtn.addEventListener('click', () => {
                promptEl.setAttribute('data-dismissing', 'true');
                subscribe().then(() => {
                    window.setTimeout(() => {
                        if (promptEl.parentNode) {
                            promptEl.parentNode.removeChild(promptEl);
                        }
                    }, 220);
                });
            });
        }
        if (denyBtn) {
            denyBtn.addEventListener('click', () => dismiss('dismiss'));
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', () => dismiss('dismiss'));
        }

        // Escape closes.
        function onKeydown(evt) {
            if (evt.key === 'Escape' || evt.keyCode === 27) {
                dismiss('dismiss');
                document.removeEventListener('keydown', onKeydown);
            }
        }
        document.addEventListener('keydown', onKeydown);

        // Animate in — force a layout flush so the CSS transition runs.
        void promptEl.offsetWidth;
        promptEl.setAttribute('data-visible', 'true');
        pushEvent('push_prompt_shown', { threshold: threshold });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Boot.
    // ─────────────────────────────────────────────────────────────────────
    function init() {
        if (Notification.permission !== 'default') {
            // Already granted or denied — nothing to ask. We still register
            // the SW so already-subscribed users keep receiving pushes.
            getSwRegistration();
            return;
        }
        if (isSuppressed()) {
            return;
        }
        const views = bumpPageView();
        if (views < threshold) {
            return;
        }
        const promptEl = document.querySelector('.lafka-push-prompt');
        if (!promptEl) {
            return;
        }
        bindPrompt(promptEl);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
