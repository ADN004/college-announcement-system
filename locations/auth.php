<?php
session_start();
require '../db.php';
require '../csrf.php';

// ── Security headers ──────────────────────────────────────────────────────────
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ── Rate limiting ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['loc_attempts']))      $_SESSION['loc_attempts']      = 0;
if (!isset($_SESSION['loc_lockout_until'])) $_SESSION['loc_lockout_until'] = 0;

$locked           = $_SESSION['loc_lockout_until'] > time();
$lockout_remaining = max(0, (int)$_SESSION['loc_lockout_until'] - time());

$error = "";

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($locked) {
        $error = "Too many failed attempts. Try again in " . ceil($lockout_remaining / 60) . " minute(s).";
    } else {
        csrf_verify();

        $slug     = trim($_POST['location'] ?? '');
        $password = $_POST['password']       ?? '';

        // Look up location in DB
        $stmt = $conn->prepare(
            "SELECT * FROM locations WHERE slug=? AND is_active=1 LIMIT 1"
        );
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $loc = $stmt->get_result()->fetch_assoc();

        if ($loc && password_verify($password, $loc['password'])) {
            // Successful auth
            $_SESSION['location']          = $loc['slug'];
            $_SESSION['location_label']    = $loc['label'];
            $_SESSION['loc_attempts']      = 0;
            $_SESSION['loc_lockout_until'] = 0;
            header("Location: player.php");
            exit;
        } else {
            $_SESSION['loc_attempts']++;
            if ($_SESSION['loc_attempts'] >= 5) {
                $_SESSION['loc_lockout_until'] = time() + 900; // 15 min
                $_SESSION['loc_attempts']      = 0;
                $error = "Too many failed attempts. Locked for 15 minutes.";
            } else {
                $remaining = 5 - $_SESSION['loc_attempts'];
                $error     = "Wrong password. $remaining attempt(s) remaining.";
            }
        }
    }
}

if ($locked && !$error) {
    $error = "Too many failed attempts. Try again in " . ceil($lockout_remaining / 60) . " minute(s).";
}

// ── Fetch active locations for dropdown ───────────────────────────────────────
$loc_list = $conn->query("SELECT slug, label FROM locations WHERE is_active=1 ORDER BY label ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Location Access</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="login-box">

    <div style="font-size:48px; margin-bottom:10px;">📍</div>
    <h2>Location Access</h2>
    <small>Enter credentials to access announcements</small>

    <?php if ($error): ?>
        <p style="color:#ef4444; margin:12px 0;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" style="margin-top:20px; max-width:100%;">
        <?= csrf_field() ?>

        <select name="location" required <?= $locked ? 'disabled' : '' ?>>
            <option value="" disabled selected>Select Location</option>
            <?php while ($l = $loc_list->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($l['slug']) ?>">
                    <?= htmlspecialchars($l['label']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="password" name="password" placeholder="Location Password" required
               <?= $locked ? 'disabled' : '' ?>>

        <button type="submit" style="width:100%; margin-top:6px;"
                <?= $locked ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
            ➜ Enter Location
        </button>
    </form>

    <br>
    <small>Location-based announcement playback</small>

</div>

</body>
</html>
