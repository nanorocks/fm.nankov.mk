"use strict";

const CACHE_NAME = "fm-macedonia-v1";
const OFFLINE_URL = '/offline.html';

// App-shell assets to pre-cache
const PRECACHE = [OFFLINE_URL, '/manifest.json'];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.map((k) => k !== CACHE_NAME && caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    const url = new URL(event.request.url);

    // Never intercept: cross-origin requests, audio streams, admin panel, or non-GET
    if (
        event.request.method !== 'GET' ||
        url.origin !== self.location.origin ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/api') ||
        // Exclude live audio streams (they can't and shouldn't be cached)
        event.request.destination === 'audio' ||
        event.request.headers.get('Range')
    ) return;

    // Navigation requests: network-first, fall back to offline page
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Static assets (build output, images, fonts): cache-first
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname.startsWith('/fonts/')
    ) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response.ok) {
                        caches.open(CACHE_NAME).then((c) => c.put(event.request, response.clone()));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Everything else: network-first
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
