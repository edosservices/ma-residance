const CACHE = 'ma-residence-static-v1';
const PRECACHE = [
    '/favicon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const accept = request.headers.get('accept') || '';

    if (request.mode === 'navigate' || accept.includes('text/html')) {
        return;
    }

    const cacheable = url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/favicon.svg'
        || url.pathname === '/manifest.webmanifest';

    if (!cacheable) {
        return;
    }

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            try {
                const response = await fetch(request);

                if (response.ok) {
                    cache.put(request, response.clone());
                }

                return response;
            } catch (error) {
                const cached = await cache.match(request);

                if (cached) {
                    return cached;
                }

                throw error;
            }
        }),
    );
});
