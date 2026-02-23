<?php
require 'session.php';
require 'db.php';
require 'csrf.php';
require 'logs/log_helper.php';

// ── Security headers ──────────────────────────────────────────────────────────
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

$error = "";

// ── Rate limiting setup ───────────────────────────────────────────────────────
if (!isset($_SESSION['login_attempts']))       $_SESSION['login_attempts']       = 0;
if (!isset($_SESSION['login_lockout_until'])) $_SESSION['login_lockout_until'] = 0;

$locked           = $_SESSION['login_lockout_until'] > time();
$lockout_remaining = max(0, (int)$_SESSION['login_lockout_until'] - time());

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($locked) {
        $error = "Too many failed attempts. Try again in " . ceil($lockout_remaining / 60) . " minute(s).";
    } else {
        csrf_verify();

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status='active' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Successful login — regenerate session ID to prevent fixation
                session_regenerate_id(true);
                $_SESSION['user_id']             = $user['id'];
                $_SESSION['role']                = $user['role'];
                $_SESSION['name']                = $user['name'];
                $_SESSION['login_attempts']      = 0;
                $_SESSION['login_lockout_until'] = 0;
                $_SESSION['last_activity']       = time();

                add_log($user['id'], $user['role'], "Logged in");

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: staff/dashboard.php");
                }
                exit;
            }
        }

        // Failed login
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_lockout_until'] = time() + 900; // 15 min lockout
            $_SESSION['login_attempts']      = 0;
            $error = "Too many failed attempts. Account locked for 15 minutes.";
        } else {
            $remaining = 5 - $_SESSION['login_attempts'];
            $error     = "Invalid credentials. $remaining attempt(s) remaining before lockout.";
        }
    }
}

if ($locked && !$error) {
    $error = "Too many failed attempts. Try again in " . ceil($lockout_remaining / 60) . " minute(s).";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Announcement System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <div class="logo-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to the Announcement System</p>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning">Session expired. Please log in again.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <input type="text"     name="username" placeholder="Username" required autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit" <?= $locked ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </button>
        </form>

        <p class="footer-text">Admin / Staff Login</p>
    </div>
</div>

</body>
</html>
