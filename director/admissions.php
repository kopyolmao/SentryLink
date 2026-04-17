<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'director'){
    header("Location: ../loginadmin.php");
    exit;
}

// Handle filters
$event_filter = $_GET['event_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "1=1";
if($event_filter) $where .= " AND a.event_id = $event_filter";
if($date_from) $where .= " AND DATE(a.scanned_at) >= '$date_from'";
if($date_to) $where .= " AND DATE(a.scanned_at) <= '$date_to'";

$admissions = $conn->query("
    SELECT a.*, u.first_name, u.last_name, u.student_id, u.course, u.year_level,
           e.title as event_title, s.first_name as scanner_name, s.last_name as scanner_last
    FROM admissions a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
    LEFT JOIN users s ON a.scanned_by = s.id
    WHERE $where
    ORDER BY a.scanned_at DESC
    LIMIT 100
");

$events = $conn->query("SELECT id, title, event_date FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admission Logs - Director View</title>
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
    .filters { background: #121212; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #262626; }
    .form-control { background: #121212; border: 1px solid #262626; color: #fff; }
    .form-control:focus { background: #121212; color: #fff; border-color: #7c5cfc; }
    .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    .badge-admitted { background: rgba(61,224,160,0.2); color: #3de0a0; }
    .badge-rejected { background: rgba(255,77,77,0.2); color: #ff4d4d; }
    .badge-duplicate { background: rgba(252,185,92,0.2); color: #fcb95c; }
    .view-badge { background: rgba(92,180,255,0.2); color: #5cb4ff; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar">
        <div class="logo">SyntreLink</div>
        <a href="dashboard.php">Dashboard</a>
        <a href="events.php">Events</a>
        <a href="admissions.php" class="active">Admission Logs</a>
        <a href="reports.php">Reports</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="../logout.php" style="margin-top: 2rem;">Logout</a>
    </div>
    
    <div class="content">
        <h2 style="margin-bottom: 1.5rem;">Admission Logs</h2>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Event</label>
                    <select name="event_id" class="form-select">
                        <option value="">All Events</option>
                        <?php while($e = $events->fetch_assoc()): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $event_filter == $e['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['title']); ?> (<?php echo $e['event_date']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
        
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course/Year</th>
                    <th>Event</th>
                    <th>Scanned By</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Gate</th>
                </tr>
            </thead>
            <tbody>
                <?php if($admissions->num_rows > 0): ?>
                    <?php while($adm = $admissions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($adm['first_name'] . ' ' . $adm['last_name']); ?></td>
                        <td><?php echo $adm['student_id']; ?></td>
                        <td><?php echo htmlspecialchars(($adm['course'] ?? '') . ' / ' . ($adm['year_level'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($adm['event_title']); ?></td>
                        <td><?php echo htmlspecialchars(($adm['scanner_name'] ?? '') . ' ' . ($adm['scanner_last'] ?? 'System')); ?></td>
                        <td><?php echo $adm['scanned_at']; ?></td>
                        <td><span class="badge-status badge-<?php echo $adm['status']; ?>"><?php echo $adm['status']; ?></span></td>
                        <td><?php echo $adm['gate_location'] ?? '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; color: #5c5080;">No admission records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>