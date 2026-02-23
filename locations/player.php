<?php
session_start();
require '../db.php';

// ── Security headers ──────────────────────────────────────────────────────────
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['location'])) {
    header("Location: auth.php");
    exit;
}

$loc_slug = $_SESSION['location'];

// ── Look up location from DB ──────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT slug, label FROM locations WHERE slug=? AND is_active=1 LIMIT 1"
);
$stmt->bind_param("s", $loc_slug);
$stmt->execute();
$loc_row = $stmt->get_result()->fetch_assoc();

if (!$loc_row) {
    // Location was deleted or deactivated
    session_destroy();
    header("Location: auth.php");
    exit;
}

$label  = $loc_row['label'];   // display name, e.g. "Block A"
$now    = date('Y-m-d H:i:s');

// ── Fetch approved announcements for this location ────────────────────────────
$sql = "
    SELECT id, title, file_path
    FROM announcements
    WHERE status = 'approved'
      AND location = ?
      AND (scheduled_at IS NULL OR scheduled_at <= ?)
      AND play_count < play_limit
    ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $label, $now);
$stmt->execute();
$res = $stmt->get_result();

$announcements = [];
while ($row = $res->fetch_assoc()) {
    $announcements[] = $row;
}

// ── Extra counts for helpful empty-state messages ─────────────────────────────
$pending_count   = 0;
$exhausted_count = 0;
if (empty($announcements)) {
    $stmt2 = $conn->prepare("SELECT COUNT(*) c FROM announcements WHERE location = ? AND status = 'pending'");
    $stmt2->bind_param("s", $label);
    $stmt2->execute();
    $pending_count = $stmt2->get_result()->fetch_assoc()['c'];

    $stmt3 = $conn->prepare("SELECT COUNT(*) c FROM announcements WHERE location = ? AND status = 'approved' AND play_count >= play_limit");
    $stmt3->bind_param("s", $label);
    $stmt3->execute();
    $exhausted_count = $stmt3->get_result()->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($label) ?> Announcements</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="player-wrapper">
    <div class="player-card">
        <div class="location-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= htmlspecialchars($label) ?>
        </div>

        <h2>Announcements</h2>

        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                </div>
                <p>No announcements available</p>
                <?php if ($pending_count > 0): ?>
                    <p style="color:var(--warning); font-size:14px; margin-top:8px;">
                        <?= (int)$pending_count ?> announcement<?= $pending_count > 1 ? 's' : '' ?> pending admin approval
                    </p>
                <?php endif; ?>
                <?php if ($exhausted_count > 0): ?>
                    <p style="color:var(--text-muted); font-size:14px; margin-top:8px;">
                        <?= (int)$exhausted_count ?> announcement<?= $exhausted_count > 1 ? 's' : '' ?> already played to limit
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="visualizer" id="visualizer">
                <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
            </div>
            <p class="now-playing">Now Playing</p>
            <h3 id="title"></h3>
            <audio id="player" controls autoplay></audio>
        <?php endif; ?>

        <a href="auth.php" class="back-link" style="margin-top:28px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Switch Location
        </a>
    </div>
</div>

<script>
const list     = <?= json_encode($announcements) ?>;
const locSlug  = <?= json_encode($loc_slug) ?>;
let index      = 0;
const player   = document.getElementById('player');
const title    = document.getElementById('title');
const visualizer = document.getElementById('visualizer');

function playNext() {
    if (index >= list.length) {
        if (title) title.innerText = "All announcements completed";
        if (visualizer) visualizer.style.display = "none";
        const nowPlaying = document.querySelector('.now-playing');
        if (nowPlaying) nowPlaying.textContent = "Finished";
        return;
    }

    const a = list[index];
    if (title) title.innerText = a.title;
    player.src = "../" + a.file_path;
    player.play();

    player.onended = () => {
        fetch("played.php?id=" + a.id + "&location=" + encodeURIComponent(locSlug));
        index++;
        playNext();
    };
}

if (list.length > 0) {
    playNext();
}
</script>

</body>
</html>
