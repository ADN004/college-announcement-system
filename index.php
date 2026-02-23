<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Announcement System</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="manifest" href="pwa/manifest.json">
<meta name="theme-color" content="#6366f1">

<script>
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("pwa/service-worker.js");
}
</script>
</head>

<body>

<div class="landing-wrapper">
    <div class="landing-card">
        <div class="logo-container">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <h1>College Announcement System</h1>
        <p class="tagline">Centralized Audio Announcement Platform</p>

        <div class="landing-actions">
            <a class="btn" href="auth/login.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Admin / Staff Login
            </a>
            <a class="btn btn-secondary" href="locations/auth.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Location Announcements
            </a>
        </div>

        <button id="installApp" class="btn btn-outline" style="display:none; width:100%;" onclick="installPWA()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Install App
        </button>

        <div class="landing-footer">
            Academic Project &bull; Secure &bull; Smart
        </div>
    </div>
</div>

<script src="pwa/install.js"></script>
</body>
</html>
