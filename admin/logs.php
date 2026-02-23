<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

$logs = $conn->query("
    SELECT p.*, a.title
    FROM play_logs p
    JOIN announcements a ON p.announcement_id = a.id
    ORDER BY p.played_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logs</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>📊 Play Logs</h2>

<table>
<thead>
<tr>
    <th>Announcement</th>
    <th>Location</th>
    <th>Played At</th>
</tr>
</thead>
<tbody>
<?php while ($l = $logs->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($l['title'])     ?></td>
    <td><?= htmlspecialchars($l['location'])  ?></td>
    <td><?= htmlspecialchars($l['played_at']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<br>
<a href="dashboard.php">⬅ Back</a>

</div>
</div>

</body>
</html>
