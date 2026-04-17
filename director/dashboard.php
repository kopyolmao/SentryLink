<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'director'){
    header("Location: ../loginadmin.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get statistics
$total_events = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE deleted_at IS NULL")->fetch_assoc()['cnt'];
$ongoing_events = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE status = 'ongoing' AND deleted_at IS NULL")->fetch_assoc()['cnt'];
$total_admissions = $conn->query("SELECT COUNT(*) as cnt FROM admissions")->fetch_assoc()['cnt'];
$today_admissions = $conn->query("SELECT COUNT(*) as cnt FROM admissions WHERE DATE(scanned_at) = CURDATE()")->fetch_assoc()['cnt'];

// Get recent events
$events = $conn->query("SELECT * FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 10");

// Get recent admissions
$recent_admissions = $conn->query("
    SELECT a.*, u.first_name, u.last_name, u.student_id, e.title as event_title 
    FROM admissions a 
    JOIN users u ON a.user_id = u.id 
    JOIN events e ON a.event_id = e.id 
    ORDER BY a.scanned_at DESC 
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Director Dashboard - SyntreLink</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #121212; min-height: 100vh; padding: 1.5rem; border-right: 1px solid #262626; }
    .sidebar a { color: #9585c8; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
    .sidebar a:hover, .sidebar a.active { background: rgba(124,92,252,0.1); color: #fff; }
    .content { padding: 2rem; }
    .stat-card { background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
    .stat-val { font-size: 32px; font-weight: 600; color: #fff; }
    .stat-label { font-size: 13px; color: #5c5080; text-transform: uppercase; }
    .table-dark { background: #121212; border-color: #262626; }
    .badge-open { background: rgba(61,224,160,0.2); color: #3de0a0; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    .badge-ongoing { background: rgba(124,92,252,0.2); color: #7c5cfc; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    .badge-closed { background: rgba(252,185,92,0.2); color: #fcb95c; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    .btn-primary { background: #7c5cfc; border: none; padding: 8px 20px; border-radius: 8px; }
    .logo { font-size: 20px; font-weight: bold; color: #7c5cfc; margin-bottom: 2rem; }
    .user-info { background: #1a1525; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; }
</style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" style="width: 250px;">
        <div class="logo">SyntreLink</div>
        <div class="user-info">
            <div style="font-weight: 500;"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></div>
            <div style="font-size: 12px; color: #5c5080;">School Director</div>
        </div>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="events.php">Events (View Only)</a>
        <a href="admissions.php">Admission Logs</a>
        <a href="reports.php">Reports</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="../logout.php" style="margin-top: 2rem;">Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content" style="flex: 1;">
        <h2 style="margin-bottom: 2rem;">Director Overview</h2>
        
        <!-- Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-val"><?php echo $total_events; ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-val"><?php echo $ongoing_events; ?></div>
                    <div class="stat-label">Ongoing Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-val"><?php echo $total_admissions; ?></div>
                    <div class="stat-label">Total Admissions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-val"><?php echo $today_admissions; ?></div>
                    <div class="stat-label">Today's Admissions</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Events -->
        <h4 style="margin: 2rem 0 1rem; color: #7c5cfc;">Recent Events</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Venue</th>
                </tr>
            </thead>
            <tbody>
                <?php while($event = $events->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo $event['event_date']; ?></td>
                    <td>
                        <?php 
                        $status_class = [
                            'open' => 'badge-open', 
                            'ongoing' => 'badge-ongoing', 
                            'closed' => 'badge-closed',
                            'draft' => 'badge-draft',
                            'cancelled' => 'badge-cancelled'
                        ];
                        echo '<span class="'.$status_class[$event['status']].'">'.$event['status'].'</span>';
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($event['venue']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Recent Admissions -->
        <h4 style="margin: 2rem 0 1rem; color: #7c5cfc;">Recent Admissions</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($adm = $recent_admissions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($adm['first_name'] . ' ' . $adm['last_name']); ?></td>
                    <td><?php echo $adm['student_id']; ?></td>
                    <td><?php echo htmlspecialchars($adm['event_title']); ?></td>
                    <td><?php echo $adm['scanned_at']; ?></td>
                    <td><span class="badge-open"><?php echo $adm['status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>