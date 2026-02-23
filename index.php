<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>College Announcement System</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="manifest" href="pwa/manifest.json">
<meta name="theme-color" content="#2563eb">

<script>
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("pwa/service-worker.js");
}
</script>
</head>

<body>

<div class="container">
    <div class="card" style="text-align:center;">
        <h1>📢 College Announcement System</h1>
        <p>Centralized Audio Announcement Platform</p>

        <a class="btn" href="auth/login.php">👤 Admin / Staff Login</a>
        <a class="btn btn-secondary" href="locations/auth.php">📍 Location Announcements</a>

        <button id="installApp" class="btn" style="display:none;" onclick="installPWA()">📲 Install App</button>

        <div class="footer">
            Academic Project • Secure • Smart
        </div>
    </div>
</div>

<script src="pwa/install.js"></script>
</body>
</html>
