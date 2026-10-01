/*
 * Maternal Health Hub - Service Worker
 * Provides offline caching and background sync support.
 *
 * Privacy: patient pages are never cached. Only the registration form (needed offline) is kept,
 * in its own cache that is deleted at logout and whenever the login page opens. Visit logs, growth
 * metrics and vaccine doses entered on a patient page that was open when the connection dropped
 * are queued by offline.js instead.
 *
 * Any change to this file reinstalls the worker, which re-fetches PRECACHE_URLS (e.g. offline.html).
 */

// Bump when cached pages or assets change shape.
// v3: stop caching patient pages and attachments; activating v3 deletes the older caches that held them.
const CACHE_NAME = 'maternal-health-v3';
// Must match the name cleared in layouts/app.blade.php (logout) and layouts/guest.blade.php (login page).
const PAGES_CACHE = 'maternal-health-pages';
const OFFLINE_URL = '/offline.html';

// Pages that work offline. Everything else shows the offline page when there is no connection.
const OFFLINE_PAGES = ['/register'];

const STATIC_PREFIXES = ['/build/', '/icons/', '/images/'];
const STATIC_FILES = ['/favicon.ico', '/manifest.json', OFFLINE_URL];

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.json',
    '/favicon.ico',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME && key !== PAGES_CACHE).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

const isStaticAsset = (url) =>
    STATIC_FILES.includes(url.pathname) || STATIC_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only same-origin requests; the browser handles everything else.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Navigation: network first. Only offline-capable pages are kept, and only real (non-redirected) pages.
    if (request.mode === 'navigate') {
        const cacheable = OFFLINE_PAGES.includes(url.pathname);

        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (cacheable && response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches.open(PAGES_CACHE).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(async () => {
                    const cached = cacheable ? await caches.match(request, { cacheName: PAGES_CACHE }) : null;
                    return cached || caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    // Built assets, icons and images: stale-while-revalidate. Anything else (chat attachments,
    // JSON endpoints, sync) goes straight to the network and is never stored.
    if (request.method === 'GET' && isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const networkFetch = fetch(request)
                    .then((response) => {
                        if (response && response.status === 200) {
                            const copy = response.clone();
                            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || networkFetch;
            })
        );
    }
});

/*
 * Background Sync: when the browser reconnects, wake up open pages so they
 * can push the queued offline data to the server.
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-outbox') {
        event.waitUntil(notifyClientsToSync());
    }
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

async function notifyClientsToSync() {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of clients) {
        client.postMessage({ type: 'SYNC_NOW' });
    }
}
