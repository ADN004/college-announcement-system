<?php
require '../session.php';
require '../db.php';
require '../logs/log_helper.php';

if ($_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

/* ===== APPROVE (POST) ===== */
if (isset($_POST['approve_id'])) {
    csrf_verify();

    $id   = (int)$_POST['approve_id'];
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $a = $stmt->get_result()->fetch_assoc();

    if ($a) {
        $old = "../" . $a['file_path'];
        $new = "../uploads/approved/" . basename($a['file_path']);

        if (rename($old, $new)) {
            $new_path = "uploads/approved/" . basename($a['file_path']);

            $upd = $conn->prepare(
                "UPDATE announcements SET status='approved', file_path=?, approved_at=NOW() WHERE id=?"
            );
            $upd->bind_param("si", $new_path, $id);
            $upd->execute();

            $ins = $conn->prepare(
                "INSERT INTO notifications (user_id, announcement_id, message)
                 VALUES (?, ?, 'Your announcement has been approved')"
            );
            $ins->bind_param("ii", $a['staff_id'], $id);
            $ins->execute();

            add_log($_SESSION['user_id'], 'admin', "Approved announcement ID $id: {$a['title']}");
        } else {
            $_SESSION['approve_error'] = "Failed to move audio file. Check that uploads/approved/ exists and is writable.";
        }
    } else {
        $_SESSION['approve_error'] = "Announcement not found or already processed.";
    }

    header("Location: approve.php");
    exit;
}

/* ===== REJECT (POST) ===== */
if (isset($_POST['reject_id'])) {
    csrf_verify();

    $id     = (int)$_POST['reject_id'];
    $reason = trim($_POST['reason'] ?? '');

    $stmt = $conn->prepare(
        "SELECT staff_id, file_path, title FROM announcements WHERE id=? AND status='pending'"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $a = $stmt->get_result()->fetch_assoc();

    if ($a) {
        $upd = $conn->prepare(
            "UPDATE announcements SET status='rejected', reject_reason=? WHERE id=?"
        );
        $upd->bind_param("si", $reason, $id);
        $upd->execute();

        $msg = "Announcement rejected: " . $reason;
        $ins = $conn->prepare(
            "INSERT INTO notifications (user_id, announcement_id, message) VALUES (?, ?, ?)"
        );
        $ins->bind_param("iis", $a['staff_id'], $id, $msg);
        $ins->execute();

        $old = "../" . $a['file_path'];
        $new = "../uploads/rejected/" . basename($a['file_path']);
        if (file_exists($old)) {
            rename($old, $new);
        }

        add_log($_SESSION['user_id'], 'admin', "Rejected announcement ID $id: {$a['title']}");
    }

    header("Location: approve.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Approve Announcements</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>✅ Pending Announcements</h2>

<?php if (!empty($_SESSION['approve_error'])): ?>
    <p style="color:#ef4444"><?= htmlspecialchars($_SESSION['approve_error']) ?></p>
    <?php unset($_SESSION['approve_error']); ?>
<?php endif; ?>

<?php
$list = $conn->query("
    SELECT a.*, u.name AS staff_name
    FROM announcements a
    JOIN users u ON a.staff_id = u.id
    WHERE a.status='pending'
    ORDER BY a.created_at DESC
");

if ($list->num_rows === 0) {
    echo "<p>No pending announcements</p>";
}

while ($a = $list->fetch_assoc()):
?>

<div class="card">

<b><?= htmlspecialchars($a['title']) ?></b><br>
Staff: <?= htmlspecialchars($a['staff_name']) ?><br>
Location: <?= htmlspecialchars($a['location']) ?><br>
Type: <?= htmlspecialchars(strtoupper($a['type'])) ?><br><br>

<audio controls>
    <source src="../<?= htmlspecialchars($a['file_path']) ?>">
</audio>

<br><br>

<!-- Approve (POST + CSRF) -->
<form method="POST" style="display:inline">
    <?= csrf_field() ?>
    <input type="hidden" name="approve_id" value="<?= (int)$a['id'] ?>">
    <button type="submit" class="btn-success">✅ Approve</button>
</form>

<!-- Reject (POST + CSRF) -->
<form method="POST" style="margin-top:10px">
    <?= csrf_field() ?>
    <input type="hidden" name="reject_id" value="<?= (int)$a['id'] ?>">
    <input type="text"   name="reason"    placeholder="Reject reason" required>
    <button type="submit" style="background:linear-gradient(135deg,#ef4444,#b91c1c)">❌ Reject</button>
</form>

</div>

<?php endwhile; ?>

<a href="dashboard.php">⬅ Back</a>

</div>
</div>

</body>
</html>
