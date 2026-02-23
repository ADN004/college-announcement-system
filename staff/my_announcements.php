<?php
require '../session.php';
require '../db.php';

if ($_SESSION['role'] !== 'staff') {
    exit("Access denied");
}

$staff_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT * FROM announcements
    WHERE staff_id = ?
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
<title>My Announcements</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>📢 My Announcements</h2>

<table>
<thead>
<tr>
    <th>Title</th>
    <th>Location</th>
    <th>Type</th>
    <th>Status</th>
    <th>Schedule</th>
    <th>Plays</th>
    <th>Details</th>
</tr>
</thead>
<tbody>

<?php while ($a = $list->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($a['title'])    ?></td>
    <td><?= htmlspecialchars($a['location']) ?></td>
    <td><?= htmlspecialchars(strtoupper($a['type'])) ?></td>

    <td>
        <?php if ($a['status'] === 'pending'): ?>
            <span class="badge pending">Pending</span>
        <?php elseif ($a['status'] === 'approved'): ?>
            <span class="badge approved">Approved</span>
        <?php else: ?>
            <span class="badge rejected">Rejected</span>
        <?php endif; ?>
    </td>

    <td><?= $a['scheduled_at'] ? htmlspecialchars($a['scheduled_at']) : 'Immediate' ?></td>
    <td><?= (int)$a['play_count'] ?> / <?= (int)$a['play_limit'] ?></td>

    <td>
        <?php if ($a['status'] === 'rejected'): ?>
            ❌ <?= htmlspecialchars($a['reject_reason'] ?? '') ?>
        <?php elseif ($a['status'] === 'approved'): ?>
            ✅ Approved at <?= htmlspecialchars($a['approved_at'] ?? '') ?>
        <?php else: ?>
            ⏳ Waiting for admin
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<br>
<a href="dashboard.php">⬅ Back to Dashboard</a>

</div>
</div>

</body>
</html>
