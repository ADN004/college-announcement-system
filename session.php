<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Security headers ──────────────────────────────────────────────────────────
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ── CSRF helpers (available to all admin/staff pages) ─────────────────────────
require_once __DIR__ . '/csrf.php';

// ── Session timeout: 60 minutes of inactivity ─────────────────────────────────
define('SESSION_TIMEOUT', 3600);

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $sessionDir  = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
    $projectBase = str_replace($docRoot, '', $sessionDir);
    header("Location: " . $projectBase . "/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $sessionDir  = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
    $projectBase = str_replace($docRoot, '', $sessionDir);
    header("Location: " . $projectBase . "/login.php");
    exit;
}
