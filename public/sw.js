const CACHE_NAME = "pos-faro-static-v3";
const STATIC_ASSETS = ["/favicon.ico", "/manifest.webmanifest"];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)),
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                ),
            ),
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    if (event.request.method !== "GET") {
        return;
    }

    const url = new URL(event.request.url);

    if (url.pathname.startsWith("/api/")) {
        event.respondWith(fetch(event.request));
        return;
    }

    if (event.request.mode === "navigate") {
        event.respondWith(fetch(event.request).catch(() => new Response(
            "<!doctype html><html lang='es'><meta name='viewport' content='width=device-width'><title>POS Faro sin conexión</title><style>body{font:16px system-ui;margin:0;display:grid;place-items:center;min-height:100vh;background:#f4f7f6;color:#17332f}main{padding:2rem;text-align:center}h1{font-size:1.5rem}</style><main><h1>Estás sin conexión</h1><p>Vuelve a la aplicación abierta para conservar tu carrito. El cobro estará disponible al recuperar la conexión.</p></main>",
            { headers: { "Content-Type": "text/html; charset=utf-8" } },
        )));
        return;
    }

    if (!url.pathname.startsWith("/build/") && !STATIC_ASSETS.includes(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));

                return response;
            });
        }),
    );
});
