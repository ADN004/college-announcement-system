<?php
require '../db.php';

if (isset($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $location = trim($_GET['location'] ?? 'Unknown');

    // Increment play count (prepared statement)
    $upd = $conn->prepare("UPDATE announcements SET play_count = play_count + 1 WHERE id = ?");
    $upd->bind_param("i", $id);
    $upd->execute();

    // Insert play log
    $ins = $conn->prepare(
        "INSERT INTO play_logs (announcement_id, location, played_at) VALUES (?, ?, NOW())"
    );
    $ins->bind_param("is", $id, $location);
    $ins->execute();
}
