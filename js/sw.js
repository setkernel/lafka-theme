/**
 * Lafka Service Worker
 *
 * One worker for the whole site: Web Push, static-asset caching and the
 * installable app's offline support. The Lafka plugin passes its settings in
 * `self.LAFKA_SW` (prepended by incl/system/lafka-service-worker.php):
 *   { version, pwa, menuPath, offlineUrl, never[] }
 *
 * Strategy:
 *  - Static assets (CSS, JS, fonts, images): Stale-while-revalidate.
 *  - Third-party (Google Fonts): Cache-first with 7-day TTL.
 *  - The menu page (installable app on): network first; every good copy is
 *    kept as a snapshot and shown only when the network fails or is slow.
 *  - Any other page (installable app on): network only, and when the network
 *    fails the precached offline page (name, phone, hours).
 *  - NEVER cached or answered from a cache: the cart, checkout, My Account,
 *    order pages, wp-admin and login, REST, Store API, admin-ajax, wc-ajax,
 *    add-to-cart or any lafka_* request, nothing that is not a GET, and no
 *    page served to a visitor with a session (X-Lafka-Session) or marked
 *    private/no-store.
 *  - Map tiles and map APIs are never cached here (the tile servers' usage
 *    policies); only same-origin static assets and Google Fonts are.
 *
 * Cache names carry the plugin version; older ones are deleted on activate.
 */

const CONFIG        = self.LAFKA_SW || {};
const CACHE_VERSION = String( CONFIG.version || 'lafka-v1' );
const STATIC_CACHE  = 'lafka-static-' + CACHE_VERSION;
const DYNAMIC_CACHE = 'lafka-dynamic-' + CACHE_VERSION;
const PAGES_CACHE   = 'lafka-pages-' + CACHE_VERSION;
const CURRENT_CACHES = [ STATIC_CACHE, DYNAMIC_CACHE, PAGES_CACHE ];

// Max items per cache bucket to avoid unbounded storage
const MAX_STATIC_ITEMS  = 300;
const MAX_DYNAMIC_ITEMS = 50;
const MAX_SHARED_FILES  = 150;

// A slow network on the menu page falls back to the snapshot after this long.
const MENU_TIMEOUT_MS = 5000;

const APP_ON      = CONFIG.pwa === true;
const OFFLINE_URL = CONFIG.offlineUrl || '';
const MENU_PATH   = CONFIG.menuPath || '';
const NEVER_PATHS = [ '/wp-admin', '/wp-login', '/wp-json/', '/wp-cron', '/xmlrpc', '/cart/', '/checkout/', '/my-account/' ].concat( CONFIG.never || [] );

// ─── Install ────────────────────────────────────────────────────────
self.addEventListener( 'install', ( event ) => {
    event.waitUntil(
        ( APP_ON && OFFLINE_URL
            ? caches.open( PAGES_CACHE ).then( ( cache ) => cache.add( new Request( OFFLINE_URL, { cache: 'reload' } ) ) ).catch( () => {} )
            : Promise.resolve()
        ).then( () => self.skipWaiting() )
    );
} );

// ─── Activate: delete caches of other versions ──────────────────────
self.addEventListener( 'activate', ( event ) => {
    event.waitUntil(
        caches.keys().then( ( keys ) =>
            Promise.all(
                keys
                    .filter( ( key ) => key.indexOf( 'lafka-' ) === 0 && ( CURRENT_CACHES.indexOf( key ) === -1 || ( ! APP_ON && key === PAGES_CACHE ) ) )
                    .map( ( key ) => caches.delete( key ) )
            )
        ).then( () => self.clients.claim() )
    );
} );

// ─── Fetch ──────────────────────────────────────────────────────────
self.addEventListener( 'fetch', ( event ) => {
    const { request } = event;
    const url = new URL( request.url );

    // Only GET: cart and checkout actions are POSTs.
    if ( request.method !== 'GET' ) {
        return;
    }

    // Same-origin requests that must always reach the server.
    if ( url.origin === self.location.origin && isNeverCached( url ) ) {
        if ( request.mode === 'navigate' && APP_ON ) {
            event.respondWith( fetch( request ).catch( offlinePage ) );
        }
        return;
    }

    // Same-origin static assets: stale-while-revalidate.
    if ( url.origin === self.location.origin && isStaticAsset( url ) ) {
        event.respondWith( staleWhileRevalidate( request, STATIC_CACHE, MAX_STATIC_ITEMS ) );
        return;
    }

    // Third-party cacheable resources (Google Fonts CSS/woff).
    if ( isThirdPartyCacheable( url ) ) {
        event.respondWith( cacheFirst( request, DYNAMIC_CACHE, MAX_DYNAMIC_ITEMS, 7 * 24 * 60 * 60 * 1000 ) );
        return;
    }

    // Pages: only with the installable app on.
    if ( request.mode === 'navigate' && APP_ON && url.origin === self.location.origin ) {
        event.respondWith( isMenuPage( url ) ? menuPage( request ) : fetch( request ).catch( offlinePage ) );
    }
} );

// ─── The page tells the worker which files the menu uses ────────────
self.addEventListener( 'message', ( event ) => {
    const data = event.data;
    if ( ! APP_ON || ! data || data.type !== 'lafka-cache-files' || ! Array.isArray( data.urls ) ) {
        return;
    }
    event.waitUntil(
        caches.open( STATIC_CACHE ).then( ( cache ) =>
            Promise.all(
                data.urls.slice( 0, MAX_SHARED_FILES ).map( ( href ) => {
                    let url;
                    try {
                        url = new URL( href );
                    } catch {
                        return null;
                    }
                    if ( url.origin !== self.location.origin || isNeverCached( url ) || ! isStaticAsset( url ) ) {
                        return null;
                    }
                    return fetch( url.href ).then( ( response ) => {
                        if ( response && response.status === 200 ) {
                            return cache.put( url.href, response );
                        }
                        return null;
                    } ).catch( () => null );
                } )
            )
        ).then( () => trimCache( STATIC_CACHE, MAX_STATIC_ITEMS ) )
    );
} );

// ─── Push Notifications (Pillar 3E, v6.12.0) ────────────────────────
// Wired against the lafka-plugin Web Push protocol sender. The push event
// receives an encrypted payload (already decrypted by the browser by the
// time it hits this handler) shaped like:
//   { title: string, body: string, icon?: string, badge?: string, url?: string, tag?: string }
self.addEventListener('push', (event) => {
    if (!event || !event.data) {
        return;
    }
    let payload;
    try {
        payload = event.data.json();
    } catch {
        // Older sender or malformed payload — fall back to text.
        try {
            payload = { title: 'Update', body: event.data.text() };
        } catch {
            payload = { title: 'Update', body: '' };
        }
    }

    const title = (payload && payload.title) ? String(payload.title) : 'Update';
    const options = {
        body: (payload && payload.body) ? String(payload.body) : '',
        icon: (payload && payload.icon) ? String(payload.icon) : undefined,
        badge: (payload && payload.badge) ? String(payload.badge) : undefined,
        tag: (payload && payload.tag) ? String(payload.tag) : undefined,
        data: {
            url: (payload && payload.url) ? String(payload.url) : '/'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification && event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // If a window with the target URL is already open, focus it.
            for (let i = 0; i < clientList.length; i += 1) {
                const client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise open a new window.
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
            return null;
        })
    );
});

// ─── Helpers ────────────────────────────────────────────────────────

/**
 * Requests that are never cached and never answered from a cache.
 */
function isNeverCached( url ) {
    const path = url.pathname.toLowerCase();
    const search = url.search.toLowerCase();
    if ( path.indexOf( 'admin-ajax.php' ) !== -1 || path.indexOf( 'wp-admin' ) !== -1 ) {
        return true;
    }
    for ( let i = 0; i < NEVER_PATHS.length; i += 1 ) {
        const never = NEVER_PATHS[ i ].toLowerCase();
        if ( path.indexOf( never ) !== -1 || path + '/' === never ) {
            return true;
        }
    }
    // Cart actions, AJAX, previews, nonces and every plugin-internal endpoint.
    return /(^|[?&])(wc-ajax|add-to-cart|remove_item|removed_item|undo_item|order_again|wc-api|preview|customize_changeset_uuid|_wpnonce|key|rest_route|lafka_[a-z_]*|lafka-[a-z-]*)(=|&|$)/.test( search );
}

function isMenuPage( url ) {
    if ( ! MENU_PATH ) {
        return false;
    }
    const path = url.pathname.replace( /\/?$/, '/' );
    if ( path !== MENU_PATH ) {
        return false;
    }
    // The app's start URL carries ?source=pwa; any other query is a different page.
    const params = Array.from( url.searchParams.keys() );
    return params.every( ( key ) => key === 'source' );
}

/**
 * The menu page: the network first, so prices and the cart count are never
 * stale; a copy is kept for when the network fails or is slow.
 */
function menuPage( request ) {
    const key = new URL( MENU_PATH, self.location.origin ).href;
    const network = fetch( request ).then( ( response ) => {
        const type = response.headers.get( 'content-type' ) || '';
        const control = ( response.headers.get( 'cache-control' ) || '' ).toLowerCase();
        const session = response.headers.get( 'x-lafka-session' ) === '1' || control.indexOf( 'no-store' ) !== -1 || control.indexOf( 'private' ) !== -1;
        const keepable = response.ok && ! response.redirected && type.indexOf( 'text/html' ) !== -1;
        if ( session ) {
            // A visitor with a cart or an account: no copy of the page stays on the device.
            caches.open( PAGES_CACHE ).then( ( cache ) => cache.delete( key ) );
        } else if ( keepable ) {
            const copy = response.clone();
            caches.open( PAGES_CACHE ).then( ( cache ) => cache.put( key, copy ) );
            refreshOfflinePage();
        }
        return response;
    } );
    return caches.open( PAGES_CACHE ).then( ( cache ) => cache.match( key ).then( ( snapshot ) => {
        if ( ! snapshot ) {
            return network.catch( offlinePage );
        }
        const slow = new Promise( ( resolve ) => setTimeout( () => resolve( snapshot ), MENU_TIMEOUT_MS ) );
        return Promise.race( [ network.catch( () => snapshot ), slow ] );
    } ) );
}

/**
 * Keep the offline page's name, phone and hours current (at most hourly).
 */
function refreshOfflinePage() {
    if ( ! OFFLINE_URL ) {
        return;
    }
    caches.open( PAGES_CACHE ).then( ( cache ) => cache.match( OFFLINE_URL ).then( ( cached ) => {
        const saved = cached && cached.headers.get( 'date' ) ? new Date( cached.headers.get( 'date' ) ).getTime() : 0;
        if ( saved && Date.now() - saved < 3600000 ) {
            return null;
        }
        return fetch( new Request( OFFLINE_URL, { cache: 'reload' } ) ).then( ( response ) => ( response.ok ? cache.put( OFFLINE_URL, response ) : null ) );
    } ) ).catch( () => {} );
}

function isStaticAsset( url ) {
    const path = url.pathname.toLowerCase();
    return /\.(css|js|woff2?|ttf|eot|svg|png|jpe?g|gif|webp|ico|avif)$/.test( path );
}

function isThirdPartyCacheable( url ) {
    const host = url.hostname;
    return host === 'fonts.googleapis.com' ||
           host === 'fonts.gstatic.com';
}

/**
 * Stale-while-revalidate: Respond from cache immediately, fetch update in background.
 */
function staleWhileRevalidate( request, cacheName, maxItems ) {
    return caches.open( cacheName ).then( ( cache ) =>
        cache.match( request ).then( ( cached ) => {
            const networkFetch = fetch( request ).then( ( response ) => {
                if ( response && response.status === 200 ) {
                    cache.put( request, response.clone() );
                    trimCache( cacheName, maxItems );
                }
                return response;
            } ).catch( () => cached ); // Network fail → keep serving cached

            return cached || networkFetch;
        } )
    );
}

/**
 * Cache-first with TTL: Use cache if fresh, otherwise fetch and cache.
 */
function cacheFirst( request, cacheName, maxItems, ttl ) {
    return caches.open( cacheName ).then( ( cache ) =>
        cache.match( request ).then( ( cached ) => {
            if ( cached ) {
                const dateHeader = cached.headers.get( 'date' );
                if ( dateHeader && ( Date.now() - new Date( dateHeader ).getTime() ) < ttl ) {
                    return cached;
                }
            }
            return fetch( request ).then( ( response ) => {
                if ( response && response.status === 200 ) {
                    cache.put( request, response.clone() );
                    trimCache( cacheName, maxItems );
                }
                return response;
            } ).catch( () => cached ); // Offline → serve stale
        } )
    );
}

/**
 * Trim cache to max items (LRU-like: oldest entries removed first).
 */
function trimCache( cacheName, maxItems ) {
    return caches.open( cacheName ).then( ( cache ) =>
        cache.keys().then( ( keys ) => {
            if ( keys.length > maxItems ) {
                return cache.delete( keys[ 0 ] ).then( () => trimCache( cacheName, maxItems ) );
            }
            return null;
        } )
    );
}

/**
 * The offline page: the precached one (name, phone, hours), else a minimal one.
 */
function offlinePage() {
    return ( OFFLINE_URL ? caches.open( PAGES_CACHE ).then( ( cache ) => cache.match( OFFLINE_URL ) ) : Promise.resolve( null ) )
        .then( ( cached ) => cached || new Response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<title>Offline</title><style>body{font-family:-apple-system,sans-serif;display:flex;align-items:center;' +
            'justify-content:center;min-height:100vh;margin:0;background:#f5f5f5;color:#333}' +
            '.box{text-align:center;padding:2rem}.box h1{font-size:1.5rem;margin-bottom:.5rem}' +
            '.box p{color:#666}</style></head><body><div class="box">' +
            '<h1>You are offline</h1><p>Please check your connection and try again.</p>' +
            '</div></body></html>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        ) );
}
