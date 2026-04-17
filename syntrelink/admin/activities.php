<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$event = $eventId > 0 ? db_fetch_one($conn, 'SELECT * FROM events WHERE id = ?', 'i', [$eventId]) : null;

if (!$event) {
    redirect_to('admin/events');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db_execute(
        $conn,
        "INSERT INTO activities (event_id, title, type, house_name, start_time, end_time, venue_area, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        'isssssss',
        [
            $eventId,
            trim($_POST['title'] ?? ''),
            $_POST['type'] ?? 'other',
            trim($_POST['house_name'] ?? ''),
            $_POST['start_time'] ?? '08:00',
            $_POST['end_time'] ?? '09:00',
            trim($_POST['venue_area'] ?? ''),
            trim($_POST['description'] ?? ''),
        ]
    );
    audit_log($conn, (int) $user['id'], 'ACTIVITY_CREATED', 'event', $eventId);
    $message = 'Activity added.';
}

$activities = db_fetch_all($conn, 'SELECT * FROM activities WHERE event_id = ? AND deleted_at IS NULL ORDER BY start_time ASC', 'i', [$eventId]);

shell_start('SentryLink | Activities', $user, 'admin', 'events', 'Activity Management', 'Manage sub-activities for ' . $event['title']);
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<div class="panel">
    <form method="POST" class="row g-3">
        <div class="col-md-4"><input class="form-control" name="title" placeholder="Activity title" required></div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="school_prepared">School Prepared</option>
                <option value="house_booth">House Booth</option>
                <option value="competition">Competition</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" name="house_name" placeholder="House"></div>
        <div class="col-md-2"><input type="time" class="form-control" name="start_time" required></div>
        <div class="col-md-2"><input type="time" class="form-control" name="end_time" required></div>
        <div class="col-md-6"><input class="form-control" name="venue_area" placeholder="Venue area"></div>
        <div class="col-md-6"><input class="form-control" name="description" placeholder="Description"></div>
        <div class="col-12"><button class="btn btn-primary">Add Activity</button></div>
    </form>
</div>
<div class="panel">
    <?php if ($activities): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead><tr><th>Title</th><th>Type</th><th>Time</th><th>Venue</th><th>House</th></tr></thead>
                <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= h($activity['title']) ?></td>
                        <td><?= h($activity['type']) ?></td>
                        <td><?= h(substr($activity['start_time'], 0, 5) . ' - ' . substr($activity['end_time'], 0, 5)) ?></td>
                        <td><?= h($activity['venue_area']) ?></td>
                        <td><?= h($activity['house_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No activities yet for this event.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
