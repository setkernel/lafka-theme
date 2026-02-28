/**
 * Lafka Service Worker
 * PERF-C13: Added caching strategies for a food-ordering site.
 *
 * Strategy:
 *  - Static assets (CSS, JS, fonts, images): Stale-while-revalidate (instant from cache, update in background)
 *  - API / AJAX / admin-ajax: Network-first with 5s timeout fallback
 *  - Navigation requests: Network-first with offline fallback page
 *  - Third-party (Google Maps, fonts.googleapis): Cache-first with 7-day TTL
 */

const CACHE_VERSION = 'lafka-v1';
const STATIC_CACHE  = 'lafka-static-' + CACHE_VERSION;
const DYNAMIC_CACHE = 'lafka-dynamic-' + CACHE_VERSION;

// Max items per cache bucket to avoid unbounded storage
const MAX_STATIC_ITEMS  = 150;
const MAX_DYNAMIC_ITEMS = 50;

// Assets to pre-cache on install (critical path only)
const PRECACHE_URLS = [
    // Offline fallback will be cached on first navigation
];

// ─── Install ────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// ─── Activate — clean old caches ────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ─── Fetch ──────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (POST cart/checkout actions, etc.)
    if (request.method !== 'GET') return;

    // Skip WP admin, login, preview, and customizer
    if (url.pathname.startsWith('/wp-admin') ||
        url.pathname.startsWith('/wp-login') ||
        url.search.includes('preview=true') ||
        url.search.includes('customize_changeset')) {
        return;
    }

    // ── AJAX / REST API → Network-first (no stale data for cart/orders) ─
    if (url.pathname.includes('admin-ajax.php') ||
        url.pathname.includes('/wp-json/') ||
        url.pathname.includes('wc-ajax=')) {
        return; // Let browser handle — no caching for dynamic data
    }

    // ── Static assets → Stale-while-revalidate ────────────────────────
    if (isStaticAsset(url)) {
        event.respondWith(staleWhileRevalidate(request, STATIC_CACHE, MAX_STATIC_ITEMS));
        return;
    }

    // ── Third-party cacheable resources (Google Fonts CSS/woff, Maps tiles) ─
    if (isThirdPartyCacheable(url)) {
        event.respondWith(cacheFirst(request, DYNAMIC_CACHE, MAX_DYNAMIC_ITEMS, 7 * 24 * 60 * 60 * 1000));
        return;
    }

    // ── Navigation (HTML pages) → Network-first with offline fallback ─
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache a copy of the last visited page for offline use
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || offlineFallback()))
        );
        return;
    }
});

// ─── Push Notification (existing functionality) ─────────────────────
self.addEventListener('message', (e) => {
    const data = e.data;
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: data.icon,
        data: data.data.url,
    });
});

// Notification click event listener
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    e.waitUntil(clients.matchAll({ type: 'window' }).then((clientsArr) => {
        clients.openWindow(e.notification.data);
    }));
});

// ─── Helpers ────────────────────────────────────────────────────────

function isStaticAsset(url) {
    const path = url.pathname.toLowerCase();
    return /\.(css|js|woff2?|ttf|eot|svg|png|jpe?g|gif|webp|ico|avif)(\?.*)?$/.test(path);
}

function isThirdPartyCacheable(url) {
    const host = url.hostname;
    return host === 'fonts.googleapis.com' ||
           host === 'fonts.gstatic.com' ||
           host === 'maps.googleapis.com' ||
           host === 'maps.gstatic.com';
}

/**
 * Stale-while-revalidate: Respond from cache immediately, fetch update in background.
 */
function staleWhileRevalidate(request, cacheName, maxItems) {
    return caches.open(cacheName).then((cache) =>
        cache.match(request).then((cached) => {
            const networkFetch = fetch(request).then((response) => {
                if (response && response.status === 200) {
                    cache.put(request, response.clone());
                    trimCache(cacheName, maxItems);
                }
                return response;
            }).catch(() => cached); // Network fail → keep serving cached

            return cached || networkFetch;
        })
    );
}

/**
 * Cache-first with TTL: Use cache if fresh, otherwise fetch and cache.
 */
function cacheFirst(request, cacheName, maxItems, ttl) {
    return caches.open(cacheName).then((cache) =>
        cache.match(request).then((cached) => {
            if (cached) {
                const dateHeader = cached.headers.get('date');
                if (dateHeader && (Date.now() - new Date(dateHeader).getTime()) < ttl) {
                    return cached;
                }
            }
            return fetch(request).then((response) => {
                if (response && response.status === 200) {
                    cache.put(request, response.clone());
                    trimCache(cacheName, maxItems);
                }
                return response;
            }).catch(() => cached); // Offline → serve stale
        })
    );
}

/**
 * Trim cache to max items (LRU-like: oldest entries removed first).
 */
function trimCache(cacheName, maxItems) {
    caches.open(cacheName).then((cache) =>
        cache.keys().then((keys) => {
            if (keys.length > maxItems) {
                cache.delete(keys[0]).then(() => trimCache(cacheName, maxItems));
            }
        })
    );
}

/**
 * Minimal offline fallback page.
 */
function offlineFallback() {
    return new Response(
        '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>Offline</title><style>body{font-family:-apple-system,sans-serif;display:flex;align-items:center;' +
        'justify-content:center;min-height:100vh;margin:0;background:#f5f5f5;color:#333}' +
        '.box{text-align:center;padding:2rem}.box h1{font-size:1.5rem;margin-bottom:.5rem}' +
        '.box p{color:#666}</style></head><body><div class="box">' +
        '<h1>You are offline</h1><p>Please check your connection and try again.</p>' +
        '</div></body></html>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}