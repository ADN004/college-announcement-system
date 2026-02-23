const CACHE_NAME = "college-announcement-v1";

const urlsToCache = [
  "/college_announcement_system/",
  "/college_announcement_system/index.php",
  "/college_announcement_system/auth/login.php"
];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});
