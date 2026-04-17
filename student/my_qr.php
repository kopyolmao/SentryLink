<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'student'){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get active event ticket
$ticket = $conn->query("
    SELECT t.*, e.title as event_title, e.id as event_id, e.event_date, e.start_time, e.end_time
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    WHERE t.user_id = $user_id 
    AND t.payment_status IN ('paid', 'free') 
    AND t.deleted_at IS NULL 
    AND e.status = 'ongoing'
    ORDER BY e.event_date DESC 
    LIMIT 1
")->fetch_assoc();

if(!$ticket){
    $no_ticket = true;
} else {
    $no_ticket = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My QR Code - SyntreLink</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
    .topbar { background: #121212; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #262626; }
    .brand { font-size: 20px; font-weight: bold; color: #7c5cfc; }
    .back-btn { color: #9585c8; text-decoration: none; }
    .container { max-width: 500px; margin: 3rem auto; text-align: center; padding: 0 1rem; }
    .qr-container { background: #121212; border: 1px solid #262626; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; }
    .qr-image { width: 280px; height: 280px; margin-bottom: 1.5rem; }
    .countdown-ring { width: 60px; height: 60px; margin: 0 auto 1rem; position: relative; }
    .countdown-ring svg { transform: rotate(-90deg); }
    .countdown-ring circle { fill: none; stroke-width: 4; }
    .countdown-ring .bg { stroke: #262626; }
    .countdown-ring .progress { stroke: #7c5cfc; stroke-linecap: round; transition: stroke-dashoffset 1s linear; }
    .countdown-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 18px; font-weight: 600; color: #fff; }
    .event-info { color: #9585c8; margin-bottom: 1rem; }
    .event-title { font-size: 18px; font-weight: 500; color: #fff; margin-bottom: 0.5rem; }
    .event-time { font-size: 14px; color: #5c5080; }
    .alert-message { background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 2rem; }
    .alert-icon { font-size: 48px; margin-bottom: 1rem; }
    .btn-primary { background: #7c5cfc; border: none; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; }
    .btn-primary:hover { background: #6a4de8; color: #fff; }
    .offline-banner { background: #2a1a0d; color: #fcb95c; padding: 10px; text-align: center; display: none; }
    .offline-banner.show { display: block; }
    .refresh-indicator { font-size: 12px; color: #5c5080; margin-top: 10px; }
</style>
</head>
<body>

<div class="offline-banner" id="offlineBanner">
    No internet - QR cannot be refreshed. Please connect and try again.
</div>

<div class="topbar">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    <div class="brand">SyntreLink</div>
</div>

<div class="container">
    <?php if($no_ticket): ?>
    <div class="alert-message">
        <div class="alert-icon">🎫</div>
        <h3>No Active Ticket</h3>
        <p style="color: #9585c8; margin-bottom: 1rem;">You don't have any active event tickets.</p>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
    <?php else: ?>
    <h2 style="margin-bottom: 2rem; color: #7c5cfc;">My Event QR</h2>
    
    <div class="qr-container">
        <div class="countdown-ring">
            <svg width="60" height="60">
                <circle class="bg" cx="30" cy="30" r="26"></circle>
                <circle class="progress" cx="30" cy="30" r="26" stroke-dasharray="163" stroke-dashoffset="0" id="countdownCircle"></circle>
            </svg>
            <div class="countdown-text" id="countdownText">10</div>
        </div>
        
        <img id="qrImage" class="qr-image" src="" alt="QR Code">
        
        <div class="event-info">
            <div class="event-title"><?php echo htmlspecialchars($ticket['event_title']); ?></div>
            <div class="event-time"><?php echo date('M d, Y', strtotime($ticket['event_date'])); ?> | <?php echo substr($ticket['start_time'],0,5); ?> - <?php echo substr($ticket['end_time'],0,5); ?></div>
        </div>
        
        <div class="refresh-indicator" id="refreshIndicator">Generating QR...</div>
    </div>
    
    <p style="color: #5c5080; font-size: 13px;">
        This QR code refreshes every 10 seconds for security.<br>
        Show this to the officer at the gate.
    </p>
    <?php endif; ?>
</div>

<script>
const REFRESH_INTERVAL = 9000; // 9 seconds
const CIRCUMFERENCE = 2 * Math.PI * 26; // 163

let countdown = 10;
let qrInterval, countdownInterval;
const circle = document.getElementById('countdownCircle');
const countdownText = document.getElementById('countdownText');
const refreshIndicator = document.getElementById('refreshIndicator');

function updateCountdown() {
    countdown--;
    if(countdown <= 0) countdown = 10;
    
    countdownText.textContent = countdown;
    const offset = CIRCUMFERENCE - (countdown / 10) * CIRCUMFERENCE;
    circle.style.strokeDashoffset = offset;
}

async function refreshQR() {
    try {
        refreshIndicator.textContent = 'Refreshing...';
        const res = await fetch('api/qr_generate.php', { credentials: 'same-origin' });
        const data = await res.json();
        
        if(data.error) {
            showOfflineWarning();
            return;
        }
        
        document.getElementById('qrImage').src = data.qr_image;
        countdown = 10;
        refreshIndicator.textContent = 'QR generated at ' + new Date().toLocaleTimeString();
        document.getElementById('offlineBanner').classList.remove('show');
    } catch(e) {
        showOfflineWarning();
    }
}

function showOfflineWarning() {
    document.getElementById('offlineBanner').classList.add('show');
    refreshIndicator.textContent = 'Offline - using cached QR';
}

// Initial load
refreshQR();

// Refresh QR every 9 seconds
qrInterval = setInterval(refreshQR, REFRESH_INTERVAL);

// Countdown every second
countdownInterval = setInterval(updateCountdown, 1000);

// Online/offline detection
window.addEventListener('offline', showOfflineWarning);
window.addEventListener('online', () => {
    document.getElementById('offlineBanner').classList.remove('show');
    refreshQR();
});

// Cleanup on page leave
window.addEventListener('beforeunload', () => {
    clearInterval(qrInterval);
    clearInterval(countdownInterval);
});
</script>

</body>
</html>