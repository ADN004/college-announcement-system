<?php
// ─────────────────────────────────────────────────────────────────────────────
//  db.example.php — Database configuration template
//
//  1. Copy this file to db.php
//  2. Fill in your local MySQL credentials
//  3. Never commit db.php to git (it is gitignored)
// ─────────────────────────────────────────────────────────────────────────────

$host = "localhost";
$user = "root";          // Change for production (avoid root)
$pass = "";              // Set a strong password for production
$db   = "college_announcement";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
