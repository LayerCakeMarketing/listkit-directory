const CACHE_NAME = 'listkit-v1';
const API_CACHE_NAME = 'listkit-api-v1';
const IMAGE_CACHE_NAME = 'listkit-images-v1';

// URLs to cache on install
const urlsToCache = [
  '/',
  '/assets/css/app.css',
  '/assets/js/app.js',
];

// Install event - cache essential files
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (![CACHE_NAME, API_CACHE_NAME, IMAGE_CACHE_NAME].includes(cacheName)) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache when possible
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // Handle API requests
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(handleApiRequest(request));
    return;
  }

  // Handle image requests
  if (request.destination === 'image' || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url.pathname)) {
    event.respondWith(handleImageRequest(request));
    return;
  }

  // Handle other requests with network-first strategy
  event.respondWith(
    fetch(request)
      .then(response => {
        // Don't cache non-successful responses
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        const responseToCache = response.clone();
        caches.open(CACHE_NAME)
          .then(cache => cache.put(request, responseToCache));

        return response;
      })
      .catch(() => {
        // If network fails, try cache
        return caches.match(request);
      })
  );
});

// API request handler - network first with cache fallback
async function handleApiRequest(request) {
  const cache = await caches.open(API_CACHE_NAME);
  
  try {
    const response = await fetch(request);
    
    // Cache successful GET responses
    if (response.ok && request.method === 'GET') {
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    // If network fails, try cache
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return error response
    return new Response(JSON.stringify({ error: 'Network error' }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Image request handler - cache first with network fallback
async function handleImageRequest(request) {
  const cache = await caches.open(IMAGE_CACHE_NAME);
  
  // Try cache first
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  // If not in cache, fetch from network
  try {
    const response = await fetch(request);
    
    // Cache successful responses
    if (response.ok) {
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    // Return placeholder image if available
    return new Response('', {
      status: 404,
      statusText: 'Not Found'
    });
  }
}