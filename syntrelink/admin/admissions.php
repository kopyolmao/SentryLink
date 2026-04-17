<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$eventFilter = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$statusFilter = $_GET['status'] ?? '';

$where = 'WHERE 1=1';
$types = '';
$params = [];

if ($eventFilter > 0) {
    $where .= ' AND a.event_id = ?';
    $types .= 'i';
    $params[] = $eventFilter;
}
if ($statusFilter !== '') {
    $where .= ' AND a.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

$logs = db_fetch_all(
    $conn,
    "SELECT a.scanned_at, a.status, a.gate_location,
            u.student_id, u.first_name, u.last_name,
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

shell_start('SentryLink | Admissions', $user, 'admin', 'admissions', 'Admission Logs', 'Event gate entries across all officers.');
?>
<div class="panel">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id">
                <option value="">All events</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventFilter === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['admitted', 'duplicate', 'rejected'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary">Apply Filters</button></div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Event</th><th>Officer</th><th>Gate</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['first_name'] . ' ' . $log['last_name']) ?><br><small class="text-secondary"><?= h($log['student_id']) ?></small></td>
                    <td><?= h($log['title']) ?></td>
                    <td><?= h(trim(($log['officer_first'] ?? '') . ' ' . ($log['officer_last'] ?? ''))) ?></td>
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
