<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'ssg'){
    header("Location: ../officers/loginofficers.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get ongoing events for the scanner to select
$events = $conn->query("SELECT id, title, event_date FROM events WHERE status = 'ongoing' AND deleted_at IS NULL ORDER BY event_date DESC");

$selected_event = $_GET['event_id'] ?? '';

// Get scan statistics
$stats = [
    'today' => 0,
    'total' => 0
];
if($selected_event){
    $stats['today'] = $conn->query("SELECT COUNT(*) as cnt FROM admissions WHERE event_id = $selected_event AND DATE(scanned_at) = CURDATE()")->fetch_assoc()['cnt'];
    $stats['total'] = $conn->query("SELECT COUNT(*) as cnt FROM admissions WHERE event_id = $selected_event")->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Scanner - SyntreLink</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .topbar { background: #121212; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #262626; }
    .brand { font-size: 20px; font-weight: bold; color: #7c5cfc; }
    .scanner-container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
    .event-select { background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .form-select { background: #121212; border: 1px solid #262626; color: #fff; }
    .form-select:focus { background: #121212; color: #fff; border-color: #7c5cfc; }
    .camera-view { background: #000; border-radius: 16px; overflow: hidden; position: relative; aspect-ratio: 1; }
    #video { width: 100%; height: 100%; object-fit: cover; }
    .scan-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 250px; height: 250px; border: 3px solid #7c5cfc; border-radius: 12px; }
    .scan-overlay::before, .scan-overlay::after { content: ''; position: absolute; background: #7c5cfc; }
    .scan-overlay::before { top: 50%; left: 0; right: 0; height: 2px; animation: scanLine 2s infinite; }
    @keyframes scanLine { 0%, 100% { top: 10%; } 50% { top: 90%; } }
    .result-card { background: #121212; border: 1px solid #262626; border-radius: 16px; padding: 2rem; margin-top: 1.5rem; display: none; }
    .result-card.show { display: block; }
    .result-card.admitted { border-color: #3de0a0; }
    .result-card.rejected, .result-card.error { border-color: #ff4d4d; }
    .result-card.duplicate { border-color: #fcb95c; }
    .result-icon { font-size: 48px; text-align: center; margin-bottom: 1rem; }
    .result-title { font-size: 24px; font-weight: 600; text-align: center; margin-bottom: 1rem; }
    .result-card.admitted .result-title { color: #3de0a0; }
    .result-card.rejected .result-title, .result-card.error .result-title { color: #ff4d4d; }
    .result-card.duplicate .result-title { color: #fcb95c; }
    .student-info { background: #1a1525; border-radius: 12px; padding: 1.5rem; margin-top: 1rem; }
    .student-info h4 { color: #fff; margin-bottom: 0.5rem; }
    .student-info p { color: #9585c8; margin: 0; font-size: 14px; }
    .manual-lookup { text-align: center; margin-top: 1.5rem; }
    .manual-lookup a { color: #7c5cfc; }
    .stats-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .stat-box { flex: 1; background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 1rem; text-align: center; }
    .stat-val { font-size: 28px; font-weight: 600; color: #7c5cfc; }
    .stat-label { font-size: 12px; color: #5c5080; }
    .btn-logout { background: transparent; border: 1px solid #262626; color: #9585c8; padding: 8px 16px; border-radius: 8px; text-decoration: none; }
    .offline-banner { background: #2a1a0d; color: #fcb95c; padding: 15px; text-align: center; display: none; }
    .offline-banner.show { display: block; }
</style>
</head>
<body>

<div class="offline-banner" id="offlineBanner">
    ⚠️ Offline Mode - Scanner disabled. Use manual lookup.
</div>

<div class="topbar">
    <div class="brand">SyntreLink - Scanner</div>
    <a href="../logout.php" class="btn-logout">Logout</a>
</div>

<div class="scanner-container">
    <div class="event-select">
        <label class="form-label" style="color: #9585c8;">Select Event Gate</label>
        <form method="GET">
            <select name="event_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select Active Event --</option>
                <?php while($event = $events->fetch_assoc()): ?>
                <option value="<?php echo $event['id']; ?>" <?php echo $selected_event == $event['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($event['title']); ?> (<?php echo $event['event_date']; ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </form>
        
        <?php if($selected_event): ?>
        <div class="stats-bar mt-3">
            <div class="stat-box">
                <div class="stat-val"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Scanned Today</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Admissions</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if($selected_event): ?>
    <div class="camera-view">
        <video id="video" playsinline></video>
        <div class="scan-overlay"></div>
        <canvas id="canvas" style="display: none;"></canvas>
    </div>
    
    <div class="manual-lookup">
        <a href="gate_log.php?event_id=<?php echo $selected_event; ?>">View Gate Log</a> | 
        <a href="#" onclick="startScanner()">Enable Camera</a>
    </div>
    
    <div id="resultCard" class="result-card">
        <div class="result-icon" id="resultIcon">✓</div>
        <div class="result-title" id="resultTitle">Admitted</div>
        <div class="student-info" id="studentInfo">
            <h4 id="studentName">Juan Dela Cruz</h4>
            <p>ID: <span id="studentId">STU001</span></p>
            <p>Course: <span id="studentCourse">BSBA</span> | Year: <span id="studentYear">1st</span></p>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align: center; color: #5c5080; padding: 3rem;">
        Please select an event to start scanning
    </div>
    <?php endif; ?>
</div>

<script>
let videoStream = null;
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

function startScanner() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            videoStream = stream;
            video.srcObject = stream;
            video.play();
            requestAnimationFrame(tick);
        })
        .catch(err => {
            alert('Camera access denied: ' + err.message);
        });
}

function tick() {
    if(video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert'
        });
        
        if(code) {
            validateQR(code.data);
            stopScanner();
        }
    }
    
    if(videoStream) {
        requestAnimationFrame(tick);
    }
}

function stopScanner() {
    if(videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
}

function validateQR(token) {
    fetch('../api/qr_validate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            token: token, 
            event_id: <?php echo $selected_event ?: 0; ?>
        })
    })
    .then(res => res.json())
    .then(data => {
        showResult(data);
    })
    .catch(err => {
        showResult({ status: 'error', message: 'Network error' });
    });
}

function showResult(data) {
    const card = document.getElementById('resultCard');
    const icon = document.getElementById('resultIcon');
    const title = document.getElementById('resultTitle');
    const info = document.getElementById('studentInfo');
    
    card.className = 'result-card show';
    info.style.display = 'none';
    
    if(data.status === 'admitted') {
        card.classList.add('admitted');
        icon.textContent = '✓';
        title.textContent = 'Admitted';
        if(data.student) {
            info.style.display = 'block';
            document.getElementById('studentName').textContent = data.student.name;
            document.getElementById('studentId').textContent = data.student.student_id;
            document.getElementById('studentCourse').textContent = data.student.course || '-';
            document.getElementById('studentYear').textContent = data.student.year || '-';
        }
    } else if(data.status === 'duplicate') {
        card.classList.add('duplicate');
        icon.textContent = '⚠';
        title.textContent = 'Already Admitted';
    } else {
        card.classList.add('rejected');
        icon.textContent = '✕';
        title.textContent = data.message || 'Rejected';
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        card.classList.remove('show');
    }, 5000);
}

// Offline detection
window.addEventListener('offline', () => {
    document.getElementById('offlineBanner').classList.add('show');
    stopScanner();
});
</script>

</body>
</html>