<?php
require '../session.php';
require '../db.php';
require '../logs/log_helper.php';

if ($_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

$error   = "";
$success = "";

/* ── ADD USER ───────────────────────────────────────────────────────────────── */
if (isset($_POST['add'])) {
    csrf_verify();

    $name = trim($_POST['name']     ?? '');
    $user = trim($_POST['username'] ?? '');
    $pwd  = $_POST['password']      ?? '';
    $role = $_POST['role']          ?? 'staff';

    if (strlen($pwd) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!in_array($role, ['admin', 'staff'])) {
        $error = "Invalid role.";
    } else {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, 'active')"
        );
        $stmt->bind_param("ssss", $name, $user, $hash, $role);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            add_log($_SESSION['user_id'], 'admin', "Added user '$user' with role '$role'");
            $success = "User '$user' created successfully.";
        } else {
            $error = "Username already exists or database error.";
        }
    }
}

/* ── BLOCK ──────────────────────────────────────────────────────────────────── */
if (isset($_POST['block_id'])) {
    csrf_verify();
    $bid  = (int)$_POST['block_id'];
    $stmt = $conn->prepare("UPDATE users SET status='blocked' WHERE id=?");
    $stmt->bind_param("i", $bid);
    $stmt->execute();
    add_log($_SESSION['user_id'], 'admin', "Blocked user ID $bid");
    header("Location: users.php");
    exit;
}

/* ── DELETE ─────────────────────────────────────────────────────────────────── */
if (isset($_POST['delete_id'])) {
    csrf_verify();
    $did  = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("UPDATE users SET status='deleted' WHERE id=?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    add_log($_SESSION['user_id'], 'admin', "Deleted user ID $did");
    header("Location: users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>👤 Manage Users</h2>

<?php if ($error):   ?><p style="color:#ef4444; margin-bottom:12px"><?= htmlspecialchars($error)   ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:#22c55e; margin-bottom:12px"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="POST">
    <?= csrf_field() ?>
    <input  name="name"     placeholder="Full Name"         required>
    <input  name="username" placeholder="Username"          required>
    <input  name="password" type="password" placeholder="Password (min 8 chars)" required minlength="8">
    <select name="role">
        <option value="staff">Staff</option>
        <option value="admin">Admin</option>
    </select>
    <button name="add">Add User</button>
</form>

<hr style="border-color:#263067; margin:20px 0">

<table>
<thead>
<tr>
    <th>Name</th>
    <th>Username</th>
    <th>Role</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
while ($u = $users->fetch_assoc()):
?>
<tr>
    <td><?= htmlspecialchars($u['name'])     ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['role'])     ?></td>
    <td><?= htmlspecialchars($u['status'])   ?></td>
    <td>
        <?php if ($u['status'] === 'active'): ?>
        <form method="POST" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="block_id" value="<?= (int)$u['id'] ?>">
            <button type="submit" style="background:linear-gradient(135deg,#facc15,#eab308);color:#000;padding:6px 12px;font-size:13px">Block</button>
        </form>
        <?php endif; ?>
        <?php if ($u['status'] !== 'deleted'): ?>
        <form method="POST" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_id" value="<?= (int)$u['id'] ?>">
            <button type="submit" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:6px 12px;font-size:13px"
                    onclick="return confirm('Delete this user?')">Delete</button>
        </form>
        <?php endif; ?>
    </td>
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
