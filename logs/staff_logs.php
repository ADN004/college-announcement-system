<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit;
}

$uid = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT action, created_at
     FROM staff_activity_logs
     WHERE user_id=?
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Activity</title>
<style>
body{font-family:Arial;background:#020617;color:white}
li{padding:10px;border-bottom:1px solid #1e293b}
</style>
</head>
<body>

<h2>📝 My Activity Logs</h2>

<ul>
<?php while($row = $result->fetch_assoc()): ?>
    <li>
        <?= htmlspecialchars($row['action']) ?><br>
        <small><?= $row['created_at'] ?></small>
    </li>
<?php endwhile; ?>
</ul>

</body>
</html>
