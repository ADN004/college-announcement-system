<?php
require '../session.php';
require '../db.php';
require '../logs/log_helper.php';

if ($_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

$error   = "";
$success = "";

/* ── Helper: label → slug ───────────────────────────────────────────────────── */
function make_slug(string $label): string {
    $slug = strtolower(trim($label));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    return trim($slug, '_');
}

/* ── ADD LOCATION ───────────────────────────────────────────────────────────── */
if (isset($_POST['add_location'])) {
    csrf_verify();

    $label = trim($_POST['label'] ?? '');
    $pwd   = $_POST['password']   ?? '';

    if ($label === '') {
        $error = "Location name is required.";
    } elseif (strlen($pwd) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $slug = make_slug($label);
        $hash = password_hash($pwd, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO locations (slug, label, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $slug, $label, $hash);

        if ($stmt->execute()) {
            add_log($_SESSION['user_id'], 'admin', "Added location '$label' (slug: $slug)");
            $success = "Location '$label' added.";
        } else {
            $error = "A location with a similar name already exists.";
        }
    }
}

/* ── CHANGE PASSWORD ────────────────────────────────────────────────────────── */
if (isset($_POST['change_password'])) {
    csrf_verify();

    $loc_id = (int)($_POST['loc_id']      ?? 0);
    $pwd    = $_POST['new_password']       ?? '';

    if (strlen($pwd) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE locations SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $loc_id);
        $stmt->execute();
        add_log($_SESSION['user_id'], 'admin', "Changed password for location ID $loc_id");
        $success = "Password updated.";
    }
}

/* ── TOGGLE ACTIVE ──────────────────────────────────────────────────────────── */
if (isset($_POST['toggle_id'])) {
    csrf_verify();
    $tid  = (int)$_POST['toggle_id'];
    $stmt = $conn->prepare("UPDATE locations SET is_active = NOT is_active WHERE id=?");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    add_log($_SESSION['user_id'], 'admin', "Toggled active state for location ID $tid");
    header("Location: locations.php");
    exit;
}

/* ── DELETE ─────────────────────────────────────────────────────────────────── */
if (isset($_POST['delete_loc_id'])) {
    csrf_verify();
    $did  = (int)$_POST['delete_loc_id'];
    $stmt = $conn->prepare("DELETE FROM locations WHERE id=?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    add_log($_SESSION['user_id'], 'admin', "Deleted location ID $did");
    header("Location: locations.php");
    exit;
}

$locations = $conn->query("SELECT * FROM locations ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Locations</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>📍 Manage Locations</h2>
<p style="color:#9ca3af; margin-bottom:20px">
    Locations added here appear automatically in the Location Login screen and staff announcement form.
</p>

<?php if ($error):   ?><p style="color:#ef4444; margin-bottom:12px"><?= htmlspecialchars($error)   ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:#22c55e; margin-bottom:12px"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<!-- ── Add Location ── -->
<h3>Add New Location</h3>
<form method="POST">
    <?= csrf_field() ?>
    <input name="label"    placeholder="Location Name (e.g. Block B)" required maxlength="100">
    <input name="password" type="password" placeholder="Access Password (min 6 chars)" required minlength="6">
    <button name="add_location">➕ Add Location</button>
</form>

<hr style="border-color:#263067; margin:24px 0">

<!-- ── Location List ── -->
<h3>Existing Locations</h3>

<?php if ($locations->num_rows === 0): ?>
    <p style="color:#9ca3af">No locations defined yet.</p>
<?php endif; ?>

<?php while ($loc = $locations->fetch_assoc()): ?>
<div class="card" style="margin-bottom:14px">

    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:12px">
        <b style="font-size:17px"><?= htmlspecialchars($loc['label']) ?></b>
        <code style="color:#9ca3af; font-size:12px">slug: <?= htmlspecialchars($loc['slug']) ?></code>
        <?php if ($loc['is_active']): ?>
            <span class="badge approved">Active</span>
        <?php else: ?>
            <span class="badge rejected">Inactive</span>
        <?php endif; ?>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end">

        <!-- Change Password -->
        <form method="POST" style="display:flex; gap:8px; align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="loc_id" value="<?= (int)$loc['id'] ?>">
            <input type="password" name="new_password" placeholder="New password" minlength="6" style="width:200px; margin:0">
            <button name="change_password" style="white-space:nowrap">🔑 Change Password</button>
        </form>

        <!-- Toggle Active -->
        <form method="POST" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="toggle_id" value="<?= (int)$loc['id'] ?>">
            <button type="submit" style="background:linear-gradient(135deg,#334155,#1e293b)">
                <?= $loc['is_active'] ? '⏸ Deactivate' : '▶ Activate' ?>
            </button>
        </form>

        <!-- Delete -->
        <form method="POST" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_loc_id" value="<?= (int)$loc['id'] ?>">
            <button type="submit" style="background:linear-gradient(135deg,#ef4444,#b91c1c)"
                    onclick="return confirm('Delete location \'<?= htmlspecialchars(addslashes($loc['label'])) ?>\'? This cannot be undone.')">
                🗑 Delete
            </button>
        </form>

    </div>
</div>
<?php endwhile; ?>

<br>
<a href="dashboard.php">⬅ Back</a>

</div>
</div>

</body>
</html>
