<?php
require_once __DIR__ . '/../db.php';

function add_log($user_id, $role, $action) {
    global $conn;

    $stmt = $conn->prepare(
        "INSERT INTO staff_activity_logs (user_id, role, action, created_at)
         VALUES (?, ?, ?, NOW())"
    );

    $stmt->bind_param("iss", $user_id, $role, $action);
    $stmt->execute();
}
