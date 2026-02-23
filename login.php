<?php
session_start();
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
    <title>Login | Announcement System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-box">
    <h2>Announcement System</h2>

    <?php if (isset($_GET['timeout'])): ?>
        <p style="color:#facc15; margin-bottom:12px;">Session expired. Please log in again.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:#ef4444; margin-bottom:12px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="text"     name="username" placeholder="Username" required autocomplete="username">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit" <?= $locked ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
            Login
        </button>
    </form>

    <small>Admin / Staff Login</small>
</div>

</body>
</html>
