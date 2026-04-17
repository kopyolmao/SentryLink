<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'director'){
    header("Location: ../loginadmin.php");
    exit;
}

// Handle filters
$status_filter = $_GET['status'] ?? '';
$where = "WHERE deleted_at IS NULL";
if($status_filter) $where .= " AND status = '$status_filter'";

$events = $conn->query("SELECT * FROM events $where ORDER BY event_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events - Director View</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #121212; min-height: 100vh; padding: 1.5rem; border-right: 1px solid #262626; position: fixed; width: 250px; }
    .content { margin-left: 250px; padding: 2rem; }
    .sidebar a { color: #9585c8; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
    .sidebar a:hover, .sidebar a.active { background: rgba(124,92,252,0.1); color: #fff; }
    .logo { font-size: 20px; font-weight: bold; color: #7c5cfc; margin-bottom: 2rem; }
    .stat-card { background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
    .table-dark { background: #121212; border-color: #262626; }
    .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    .badge-open { background: rgba(61,224,160,0.2); color: #3de0a0; }
    .badge-ongoing { background: rgba(124,92,252,0.2); color: #7c5cfc; }
    .badge-closed { background: rgba(252,185,92,0.2); color: #fcb95c; }
    .badge-draft { background: rgba(92,80,128,0.2); color: #9585c8; }
    .filters { margin-bottom: 1.5rem; }
    .filters a { margin-right: 10px; color: #7c5cfc; text-decoration: none; }
    .filters a.active { color: #fff; font-weight: bold; }
    .view-badge { background: rgba(92,180,255,0.2); color: #5cb4ff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar">
        <div class="logo">SyntreLink</div>
        <a href="dashboard.php">Dashboard</a>
        <a href="events.php" class="active">Events</a>
        <a href="admissions.php">Admission Logs</a>
        <a href="reports.php">Reports</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="../logout.php" style="margin-top: 2rem;">Logout</a>
    </div>
    
    <div class="content">
        <h2 style="margin-bottom: 1.5rem;">Events Overview</h2>
        
        <div class="filters">
            <span>Filter: </span>
            <a href="events.php" class="<?php echo !$status_filter ? 'active' : ''; ?>">All</a>
            <a href="?status=draft" class="<?php echo $status_filter == 'draft' ? 'active' : ''; ?>">Draft</a>
            <a href="?status=open" class="<?php echo $status_filter == 'open' ? 'active' : ''; ?>">Open</a>
            <a href="?status=ongoing" class="<?php echo $status_filter == 'ongoing' ? 'active' : ''; ?>">Ongoing</a>
            <a href="?status=closed" class="<?php echo $status_filter == 'closed' ? 'active' : ''; ?>">Closed</a>
        </div>
        
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($events->num_rows > 0): ?>
                    <?php while($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo $event['event_date']; ?></td>
                        <td><?php echo substr($event['start_time'],0,5) . ' - ' . substr($event['end_time'],0,5); ?></td>
                        <td><?php echo htmlspecialchars($event['venue']); ?></td>
                        <td><span class="badge-status badge-<?php echo $event['status']; ?>"><?php echo $event['status']; ?></span></td>
                        <td><?php echo $event['is_free'] ? 'Free' : '$' . $event['ticket_price']; ?></td>
                        <td><span class="view-badge">View Only</span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #5c5080;">No events found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>