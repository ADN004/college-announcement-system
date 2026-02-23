<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$result = $conn->query(
    "SELECT l.*, u.fullname
     FROM staff_activity_logs l
     JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
<title>Activity Logs</title>
<style>
body{font-family:Arial;background:#0f172a;color:white}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #334155}
th{background:#1e293b}
</style>
</head>
<body>

<h2>📊 System Activity Logs</h2>

<table>
<tr>
    <th>User</th>
    <th>Role</th>
    <th>Action</th>
    <th>Date</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['fullname']) ?></td>
    <td><?= $row['role'] ?></td>
    <td><?= htmlspecialchars($row['action']) ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
