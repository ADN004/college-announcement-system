<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

$users = $conn->query("SELECT COUNT(*) c FROM users WHERE status != 'deleted'")->fetch_assoc()['c'];
$ann   = $conn->query("SELECT COUNT(*) c FROM announcements")->fetch_assoc()['c'];
$pend  = $conn->query("SELECT COUNT(*) c FROM announcements WHERE status='pending'")->fetch_assoc()['c'];
$appr  = $conn->query("SELECT COUNT(*) c FROM announcements WHERE status='approved'")->fetch_assoc()['c'];
$locs  = $conn->query("SELECT COUNT(*) c FROM locations WHERE is_active=1")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>🛂 Admin Dashboard</h2>
<p style="color:#9ca3af; margin-bottom:18px">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></p>

<p style="margin-bottom:20px">
    👥 Users: <b><?= (int)$users ?></b> &nbsp;|&nbsp;
    📢 Announcements: <b><?= (int)$ann ?></b> &nbsp;|&nbsp;
    ⏳ Pending: <b><?= (int)$pend ?></b> &nbsp;|&nbsp;
    ✅ Approved: <b><?= (int)$appr ?></b> &nbsp;|&nbsp;
    📍 Active Locations: <b><?= (int)$locs ?></b>
</p>

<div class="nav">
    <a href="approve.php">✅ Approve Announcements</a>
    <a href="users.php">👤 Manage Users</a>
    <a href="locations.php">📍 Manage Locations</a>
    <a href="logs.php">📊 Logs</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

</div>
</div>

</body>
</html>
