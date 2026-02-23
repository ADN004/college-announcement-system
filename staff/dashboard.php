<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'staff') {
    exit("Access denied");
}

$staff_id = $_SESSION['user_id'];

$n = $conn->query("
    SELECT COUNT(*) AS c 
    FROM notifications 
    WHERE user_id=$staff_id AND is_read=0
")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Staff Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>👨‍🏫 Welcome, <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Staff') ?></h2>

<div class="nav">
    <a href="create.php">➕ Create Announcement</a>
    <a href="my_announcements.php">📢 My Announcements</a>
    <a href="notifications.php">🔔 Notifications (<?= $n ?>)</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<p>Use the menu above to manage your announcements.</p>

</div>
</div>

</body>
</html>
