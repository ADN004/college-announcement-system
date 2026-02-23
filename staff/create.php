<?php
require '../session.php';
require '../db.php';
require '../logs/log_helper.php';

if ($_SESSION['role'] !== 'staff') {
    exit("Access denied");
}

$msg      = "";
$msg_ok   = false;

// ── Fetch active locations for dropdown ───────────────────────────────────────
$loc_res = $conn->query("SELECT label FROM locations WHERE is_active=1 ORDER BY label ASC");
$locations = [];
while ($l = $loc_res->fetch_assoc()) {
    $locations[] = $l['label'];
}

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $staff_id     = $_SESSION['user_id'];
    $title        = trim($_POST['title']     ?? '');
    $location     = trim($_POST['location']  ?? '');
    $type         = $_POST['type']            ?? '';
    $play_limit   = max(1, (int)($_POST['play_limit'] ?? 1));
    $schedule     = !empty($_POST['schedule']) ? $_POST['schedule'] : null;
    $file_path    = "";
    $text_content = null;

    // Validate location against active locations in DB
    if (!in_array($location, $locations)) {
        $msg = "Invalid location selected.";
    } elseif (!in_array($type, ['record', 'audio', 'tts'])) {
        $msg = "Invalid announcement type.";
    } else {

        /* ── LIVE RECORD ──────────────────────────────────────────────────── */
        if ($type === 'record' && isset($_FILES['recorded_audio'])) {
            $new = time() . "_" . rand(1000, 9999) . ".webm";
            if (move_uploaded_file($_FILES['recorded_audio']['tmp_name'], "../uploads/pending/$new")) {
                $file_path = "uploads/pending/$new";
            } else {
                $msg = "Recording upload failed.";
            }
        }

        /* ── AUDIO UPLOAD ─────────────────────────────────────────────────── */
        if ($type === 'audio' && isset($_FILES['audio'])) {
            $allowed_ext  = ['mp3', 'wav', 'ogg', 'webm', 'aac', 'm4a'];
            $allowed_mime = [
                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
                'audio/ogg',  'audio/webm', 'audio/aac', 'audio/mp4',
                'audio/x-m4a', 'video/webm', // browsers report webm as video/webm
            ];

            $ext  = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['audio']['tmp_name']);

            if (!in_array($ext, $allowed_ext)) {
                $msg = "Invalid file extension. Allowed: " . implode(', ', $allowed_ext);
            } elseif (!in_array($mime, $allowed_mime)) {
                $msg = "Invalid file type detected (MIME: $mime). Upload a valid audio file.";
            } else {
                $new = time() . "_" . rand(1000, 9999) . "." . $ext;
                if (move_uploaded_file($_FILES['audio']['tmp_name'], "../uploads/pending/$new")) {
                    $file_path = "uploads/pending/$new";
                } else {
                    $msg = "Audio upload failed.";
                }
            }
        }

        /* ── TEXT TO SPEECH ───────────────────────────────────────────────── */
        if ($type === 'tts') {
            $text_content = trim($_POST['tts_text'] ?? '');
            $new          = time() . "_" . rand(1000, 9999) . ".wav";
            shell_exec("espeak " . escapeshellarg($text_content) . " -w ../uploads/pending/$new");

            if (file_exists("../uploads/pending/$new")) {
                $file_path = "uploads/pending/$new";
            } else {
                $msg = "TTS generation failed. Is espeak installed?";
            }
        }

        /* ── INSERT DB ────────────────────────────────────────────────────── */
        if ($file_path !== "") {
            $stmt = $conn->prepare(
                "INSERT INTO announcements
                 (staff_id, title, location, type, text_content, file_path, scheduled_at, play_limit)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "issssssi",
                $staff_id, $title, $location, $type,
                $text_content, $file_path, $schedule, $play_limit
            );

            if ($stmt->execute()) {
                add_log($staff_id, 'staff', "Submitted announcement '$title' for $location");
                $msg    = "Announcement submitted for admin approval.";
                $msg_ok = true;
            } else {
                $msg = "Database error — please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Announcement</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
<div class="card">

<h2>📢 Create Announcement</h2>

<form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <input type="text" name="title" placeholder="Announcement Title" required maxlength="255">

    <select name="location" required>
        <option value="">Select Location</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="type" id="type" onchange="toggleType()" required>
        <option value="">Announcement Method</option>
        <option value="record">🎙 Live Record</option>
        <option value="audio">📁 Upload Audio</option>
        <option value="tts">🗣 Text to Speech</option>
    </select>

    <!-- LIVE RECORD -->
    <div id="record_box" style="display:none">
        <button type="button" onclick="startRec()">▶ Start Recording</button>
        <button type="button" onclick="stopRec()">⏹ Stop</button>
        <audio id="preview" controls></audio>
        <input type="file" name="recorded_audio" id="recorded_audio" hidden>
    </div>

    <!-- AUDIO UPLOAD -->
    <div id="audio_box" style="display:none">
        <input type="file" name="audio" accept="audio/*">
        <small style="display:block;margin-top:-8px;color:#9ca3af">
            Allowed: mp3, wav, ogg, webm, aac, m4a
        </small>
    </div>

    <!-- TTS -->
    <div id="tts_box" style="display:none">
        <textarea name="tts_text" placeholder="Enter text to convert to speech" maxlength="2000"></textarea>
    </div>

    <label style="color:#9ca3af; font-size:13px">Schedule (optional)</label>
    <input type="datetime-local" name="schedule">

    <label style="color:#9ca3af; font-size:13px">Play Limit</label>
    <input type="number" name="play_limit" min="1" max="100" value="1">

    <button type="submit">Submit for Approval</button>
</form>

<?php if ($msg): ?>
    <p style="margin-top:14px; color:<?= $msg_ok ? '#22c55e' : '#ef4444' ?>">
        <?= htmlspecialchars($msg) ?>
    </p>
<?php endif; ?>

<a href="dashboard.php">⬅ Back</a>

</div>
</div>

<script>
let recorder, audioChunks = [];

function toggleType() {
    const t = document.getElementById("type").value;
    document.getElementById("record_box").style.display = (t === "record") ? "block" : "none";
    document.getElementById("audio_box").style.display  = (t === "audio")  ? "block" : "none";
    document.getElementById("tts_box").style.display    = (t === "tts")    ? "block" : "none";
}

function startRec() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        recorder = new MediaRecorder(stream);
        recorder.start();
        audioChunks = [];
        recorder.ondataavailable = e => audioChunks.push(e.data);
    });
}

function stopRec() {
    recorder.stop();
    recorder.onstop = () => {
        const blob     = new Blob(audioChunks, { type: 'audio/webm' });
        const audioURL = URL.createObjectURL(blob);
        document.getElementById("preview").src = audioURL;

        const file = new File([blob], "record.webm");
        const dt   = new DataTransfer();
        dt.items.add(file);
        document.getElementById("recorded_audio").files = dt.files;
    };
}
</script>

</body>
</html>
