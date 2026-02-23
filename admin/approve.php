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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approve Announcements</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span class="sep">/</span>
            <span>Approve Announcements</span>
        </div>
        <h2>Pending Announcements</h2>
        <p>Review and approve or reject submitted announcements</p>
    </div>

    <?php if (!empty($_SESSION['approve_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['approve_error']) ?></div>
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

    if ($list->num_rows === 0):
    ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <p>No pending announcements</p>
        </div>
    </div>
    <?php endif; ?>

    <?php while ($a = $list->fetch_assoc()): ?>
    <div class="announcement-card">
        <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="ann-meta">
            <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?= htmlspecialchars($a['staff_name']) ?>
            </span>
            <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($a['location']) ?>
            </span>
            <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?= htmlspecialchars(strtoupper($a['type'])) ?>
            </span>
        </div>

        <audio controls>
            <source src="../<?= htmlspecialchars($a['file_path']) ?>">
        </audio>

        <div class="ann-actions">
            <!-- Approve (POST + CSRF) -->
            <form method="POST" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="approve_id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Approve
                </button>
            </form>

            <!-- Reject (POST + CSRF) -->
            <form method="POST" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="reject_id" value="<?= (int)$a['id'] ?>">
                <input type="text" name="reason" placeholder="Rejection reason..." required style="margin-bottom:0;">
                <button type="submit" class="btn btn-danger btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reject
                </button>
            </form>
        </div>
    </div>
    <?php endwhile; ?>

    <a href="dashboard.php" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Dashboard
    </a>

</div>

</body>
</html>
