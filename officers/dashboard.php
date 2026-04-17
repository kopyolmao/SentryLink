<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'ssg'){
    header("Location: ../loginofficers.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Statistics
$today = date('Y-m-d');
$today_scans = $conn->query("SELECT COUNT(*) as cnt FROM admissions WHERE scanned_by = $user_id AND DATE(scanned_at) = '$today'")->fetch_assoc()['cnt'];
$total_admissions = $conn->query("SELECT COUNT(*) as cnt FROM admissions WHERE scanned_by = $user_id")->fetch_assoc()['cnt'];

// Get recent admissions
$recent_logs = $conn->query("
    SELECT a.*, u.first_name, u.last_name, u.student_id, e.title as event_title
    FROM admissions a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
    ORDER BY a.scanned_at DESC
    LIMIT 10
");

// Get active events
$events = $conn->query("SELECT id, title, event_date FROM events WHERE status = 'ongoing' AND deleted_at IS NULL");

$name = $user['first_name'] . ' ' . $user['last_name'];
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SyntreLink — Officer Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root[data-theme="dark"] {
    --bg: #07060f; --bg2: #0d0b1a; --bg3: #110f21;
    --border: rgba(124, 92, 252, 0.14); --purple: #7c5cfc; --purple-light: #a48bff;
    --text: #e8e6f0; --text2: #9585c8; --text3: #5c5080;
    --card-shadow: 0 4px 20px rgba(0,0,0,0.4); --radius: 12px; --green: #3de0a0;
}
body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
.topbar { background: var(--bg2); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
.brand { font-size: 20px; font-weight: bold; color: var(--purple); }
.btn-logout { background: transparent; border: 1px solid var(--border); color: var(--text2); padding: 8px 16px; border-radius: 8px; text-decoration: none; }
.container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
.page-title { font-size: 24px; margin-bottom: 2rem; }
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; text-align: center; }
.stat-val { font-size: 32px; font-weight: 600; color: var(--purple); }
.stat-label { font-size: 12px; color: var(--text3); text-transform: uppercase; }
.section-title { font-size: 16px; font-weight: 500; color: var(--purple-light); margin-bottom: 1rem; }
.log-card { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
.log-info { font-size: 14px; }
.log-info strong { color: var(--text); }
.log-time { font-size: 12px; color: var(--text3); }
.badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; }
.badge-admitted { background: rgba(61,224,160,0.2); color: var(--green); }
.btn-primary { background: var(--purple); border: none; padding: 10px 20px; border-radius: 8px; color: #fff; text-decoration: none; display: inline-block; }
.btn-primary:hover { background: #6a4de8; }
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">SyntreLink — Officer Terminal</div>
    <a href="../logout.php" class="btn-logout">Logout</a>
</div>

<div class="container">
    <h1 class="page-title">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?php echo $today_scans; ?></div>
            <div class="stat-label">Scans Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $total_admissions; ?></div>
            <div class="stat-label">Total Scans</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?php echo $events->num_rows; ?></div>
            <div class="stat-label">Active Events</div>
        </div>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <a href="scanner.php" class="btn-primary">Open QR Scanner</a>
    </div>
    
    <div class="section-title">Recent Gate Activity</div>
    <?php if($recent_logs->num_rows > 0): ?>
        <?php while($log = $recent_logs->fetch_assoc()): ?>
        <div class="log-card">
            <div class="log-info">
                <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                (<?php echo $log['student_id']; ?>) - <?php echo htmlspecialchars($log['event_title']); ?>
            </div>
            <div>
                <span class="badge badge-<?php echo $log['status']; ?>"><?php echo $log['status']; ?></span>
                <span class="log-time"><?php echo $log['scanned_at']; ?></span>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div style="color: var(--text3); text-align: center; padding: 2rem;">No recent activity</div>
    <?php endif; ?>
</div>

</body>
</html>