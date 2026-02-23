<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'staff') {
    exit("Access denied");
}

$staff_id = (int)$_SESSION['user_id'];

$n = $conn->query("
    SELECT COUNT(*) AS c
    FROM notifications
    WHERE user_id=$staff_id AND is_read=0
")->fetch_assoc()['c'];

$my_total    = $conn->query("SELECT COUNT(*) c FROM announcements WHERE staff_id=$staff_id")->fetch_assoc()['c'];
$my_approved = $conn->query("SELECT COUNT(*) c FROM announcements WHERE staff_id=$staff_id AND status='approved'")->fetch_assoc()['c'];
$my_pending  = $conn->query("SELECT COUNT(*) c FROM announcements WHERE staff_id=$staff_id AND status='pending'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Staff') ?></h2>
        <p>Manage your announcements and stay updated</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card accent">
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
            </div>
            <div class="stat-value"><?= (int)$my_total ?></div>
            <div class="stat-label">My Announcements</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-value"><?= (int)$my_approved ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-value"><?= (int)$my_pending ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>

    <div class="card">
        <div class="nav">
            <a href="create.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create Announcement
            </a>
            <a href="my_announcements.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                My Announcements
            </a>
            <a href="notifications.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Notifications <?php if ($n > 0): ?><span class="badge pending" style="margin-left:4px;"><?= (int)$n ?></span><?php endif; ?>
            </a>
            <a href="../logout.php" class="logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
        <p style="color: var(--text-muted);">Use the menu above to manage your announcements.</p>
    </div>

</div>

</body>
</html>
