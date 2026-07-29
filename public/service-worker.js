// PWA_VERSION must stay aligned with view asset query strings and pwa-version.json.
const PWA_VERSION = '5';
const CACHE_VERSION = `vascor-ops-v${PWA_VERSION}`;
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const CACHE_PREFIX = 'vascor-ops-';
const OFFLINE_URL = './offline.html';
const PRECACHE_RESOURCES = [
    OFFLINE_URL,
    './manifest.webmanifest?v=5',
    './assets/css/pwa.css?v=5',
    './assets/css/pwa-update.css?v=5',
    './assets/css/pwa-install-footer.css?v=5',
    './assets/js/pwa.js?v=5',
    './assets/js/pwa-install-footer.js?v=5'
];
const ALWAYS_FRESH_PATHS = new Set([
    'manifest.webmanifest',
    'pwa-version.json',
    'assets/js/pwa.js',
    'assets/css/pwa-update.css'
]);

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_RESOURCES)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith(CACHE_PREFIX) && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

const isSensitiveRequest = (url) => (
    url.pathname.endsWith('/control-escaneres-api.php')
    || url.pathname.includes('/uploads/')
    || url.searchParams.get('accion') === 'logout'
    || url.searchParams.get('modulo') === 'auth'
);

const networkFirstStatic = async (request) => {
    const cache = await caches.open(STATIC_CACHE);
    try {
        const response = await fetch(request, { cache: 'no-store' });
        if (response.ok) {
            await cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request, { ignoreSearch: true });
        if (cached) return cached;
        throw error;
    }
};

const staleWhileRevalidate = async (request) => {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    const update = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);
    if (cached) {
        void update;
        return cached;
    }
    const response = await update;
    if (response) return response;
    throw new Error('Static resource unavailable');
};

const networkOnlyNavigationWithOfflineFallback = async (request) => {
    try {
        return await fetch(request, { cache: 'no-store' });
    } catch (error) {
        const cache = await caches.open(STATIC_CACHE);
        return cache.match(OFFLINE_URL);
    }
};

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || isSensitiveRequest(url)) return;

    if (request.mode === 'navigate') {
        event.respondWith(networkOnlyNavigationWithOfflineFallback(request));
        return;
    }

    const scopePath = new URL(self.registration.scope).pathname;
    const relativePath = decodeURIComponent(url.pathname.startsWith(scopePath)
        ? url.pathname.slice(scopePath.length)
        : url.pathname.replace(/^\//, ''));

    if (ALWAYS_FRESH_PATHS.has(relativePath)) {
        event.respondWith(networkFirstStatic(request));
        return;
    }

    if (relativePath.startsWith('assets/')) {
        event.respondWith(staleWhileRevalidate(request));
    }
});
