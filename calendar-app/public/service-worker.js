const CACHE_NAME = 'pwa-calendar-cache-v1';
const urlsToCache = [
    '/',
    '/manifest.json',
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png'
];

// インストール時に静的リソースをキャッシュする
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// fetchイベントでキャッシュ戦略を適用
self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);

    // APIリクエストは常にネットワークから取得 (Network First)
    if (requestUrl.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                // オフライン時は何も返さないか、特定のJSONを返す
                return new Response(JSON.stringify({ error: 'offline' }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // それ以外のリクエストはキャッシュを優先 (Cache First)
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request);
            })
    );
});