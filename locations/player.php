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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($label) ?> Announcements</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .player-wrap {
            max-width: 600px;
            margin: 60px auto;
            padding: 25px;
        }

        .location-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(79,140,255,0.15);
            border: 1px solid rgba(79,140,255,0.35);
            color: #93c5fd;
            font-size: 13px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 999px;
            margin-bottom: 18px;
        }

        .player-card {
            background: linear-gradient(180deg, #121a33, #0c1330);
            border: 1px solid #263067;
            border-radius: 20px;
            padding: 36px 30px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            text-align: center;
        }

        .player-card h1 { font-size: 28px; margin-bottom: 6px; }

        .ann-title {
            color: #93c5fd;
            font-size: 16px;
            margin-bottom: 20px;
            min-height: 24px;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 30px 0;
            color: #9ca3af;
        }

        .empty-state svg { opacity: 0.35; }

        .switch-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #9ca3af;
            font-size: 14px;
            margin-top: 28px;
            transition: color 0.2s;
        }

        .switch-link:hover { color: #93c5fd; }

        audio { width: 100%; margin-top: 10px; border-radius: 12px; }
    </style>
</head>
<body>

<div class="player-wrap">

    <div style="text-align:center; margin-bottom:20px;">
        <span class="location-badge">📍 <?= htmlspecialchars($label) ?></span>
    </div>

    <div class="player-card">
        <h1>Announcements</h1>
        <p class="ann-title" id="ann-title"></p>

        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M11 5L6 9H2v6h4l5 4V5z"/>
                    <line x1="23" y1="9" x2="17" y2="15"/>
                    <line x1="17" y1="9" x2="23" y2="15"/>
                </svg>
                <span>No announcements available</span>
            </div>
        <?php else: ?>
            <audio id="player" controls autoplay></audio>
        <?php endif; ?>

        <div>
            <a class="switch-link" href="auth.php">← Switch Location</a>
        </div>
    </div>

</div>

<script>
const list     = <?= json_encode($announcements) ?>;
const locSlug  = <?= json_encode($loc_slug) ?>;
let index      = 0;
const player   = document.getElementById('player');
const annTitle = document.getElementById('ann-title');

function playNext() {
    if (index >= list.length) {
        if (annTitle) annTitle.innerText = "✅ All announcements completed";
        return;
    }
    const a = list[index];
    if (annTitle) annTitle.innerText = a.title;
    player.src = "../" + a.file_path;
    player.play();

    player.onended = () => {
        fetch("played.php?id=" + a.id + "&location=" + encodeURIComponent(locSlug));
        index++;
        playNext();
    };
}

if (list.length > 0) playNext();
</script>

</body>
</html>
