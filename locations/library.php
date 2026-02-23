<?php
session_start();
require '../db.php';

/* Allow only library location */
if (!isset($_SESSION['location']) || $_SESSION['location'] !== 'library') {
    header("Location: auth.php");
    exit;
}

$now = date('Y-m-d H:i:s');

/* Fetch approved & playable Library announcements */
$sql = "
SELECT id, title, file_path
FROM announcements
WHERE status = 'approved'
  AND location = 'Library'
  AND (scheduled_at IS NULL OR scheduled_at <= ?)
  AND play_count < play_limit
ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$res = $stmt->get_result();

$announcements = [];
while ($row = $res->fetch_assoc()) {
    $announcements[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Library Announcements</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>🔊 Library Announcements</h2>

<?php if (empty($announcements)): ?>
    <p>No announcements available</p>
<?php else: ?>
    <h3 id="title"></h3>
    <audio id="player" controls autoplay></audio>
<?php endif; ?>

</div>
</div>

<script>
const list = <?= json_encode($announcements) ?>;
let index = 0;
const player = document.getElementById('player');
const title  = document.getElementById('title');

function playNext() {
    if (index >= list.length) {
        title.innerText = "✅ All announcements completed";
        return;
    }

    const a = list[index];
    title.innerText = a.title;
    player.src = "../" + a.file_path;
    player.play();

    player.onended = () => {
        fetch("played.php?id=" + a.id + "&location=Library");
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
