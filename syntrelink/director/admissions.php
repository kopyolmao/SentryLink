<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['director']);
$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

$where = 'WHERE 1=1';
$types = '';
$params = [];
if ($eventId > 0) {
    $where .= ' AND a.event_id = ?';
    $types = 'i';
    $params[] = $eventId;
}

$logs = db_fetch_all(
    $conn,
    "SELECT a.scanned_at, a.status, a.gate_location,
            u.student_id, u.first_name, u.last_name, u.course, u.year_level,
            e.title,
            s.first_name AS officer_first, s.last_name AS officer_last
     FROM admissions a
     INNER JOIN users u ON u.id = a.user_id
     INNER JOIN events e ON e.id = a.event_id
     LEFT JOIN users s ON s.id = a.scanned_by
     $where
     ORDER BY a.scanned_at DESC
     LIMIT 200",
    $types,
    $params
);
$events = db_fetch_all($conn, 'SELECT id, title FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC');

shell_start('SentryLink | Director Admissions', $user, 'director', 'admissions', 'Admission Logs', 'Read-only view of student admissions across events.');
?>
<div class="panel">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id">
                <option value="">All events</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventId === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary">Filter</button></div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Course / Year</th><th>Event</th><th>Officer</th><th>Gate</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['first_name'] . ' ' . $log['last_name']) ?><br><small class="text-secondary"><?= h($log['student_id']) ?></small></td>
                    <td><?= h(($log['course'] ?: '-') . ' / ' . ($log['year_level'] ?: '-')) ?></td>
                    <td><?= h($log['title']) ?></td>
                    <td><?= h(trim(($log['officer_first'] ?? '') . ' ' . ($log['officer_last'] ?? 'System'))) ?></td>
                    <td><?= h($log['gate_location'] ?: 'Main Gate') ?></td>
                    <td><?= h($log['scanned_at']) ?></td>
                    <td><span class="badge text-bg-<?= h(admission_status_badge($log['status'])) ?>"><?= h(ucfirst($log['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
