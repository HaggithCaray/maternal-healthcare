/*
 * Maternal Health Hub - Service Worker
 * Provides offline caching and background sync support.
 */

// Bump when cached pages or assets change shape (v2: styles, fonts and icons are served from /build).
const CACHE_NAME = 'maternal-health-v2';
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.json',
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
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

const isApiRequest = (url) => {
    const path = new URL(url).pathname;
    return path.startsWith('/api/') || path.startsWith('/sync/') || path.startsWith('/broadcasting/');
};

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle same-origin requests. Skip API/sync/broadcast requests entirely.
    if (url.origin !== self.location.origin || isApiRequest(url)) {
        return;
    }

    // Navigation requests: network first, fall back to cache, then offline page.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) =>
                        cached || caches.match(OFFLINE_URL)
                    )
                )
        );
        return;
    }

    // Static assets (same origin): stale-while-revalidate.
    if (request.method === 'GET') {
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
