<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'staff') {
    exit("Access denied");
}

$staff_id = (int)$_SESSION['user_id'];

// Mark all notifications as read (prepared statement)
$upd = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
$upd->bind_param("i", $staff_id);
$upd->execute();

// Fetch notifications (prepared statement)
$stmt = $conn->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>🔔 Notifications</h2>

<?php if ($list->num_rows === 0): ?>
    <p style="color:#9ca3af">No notifications yet.</p>
<?php endif; ?>

<?php while ($n = $list->fetch_assoc()): ?>
<div class="notification">
    <?= htmlspecialchars($n['message'])    ?><br>
    <small><?= htmlspecialchars($n['created_at']) ?></small>
</div>
<?php endwhile; ?>

<br>
<a href="dashboard.php">⬅ Back</a>

</div>
</div>

</body>
</html>
