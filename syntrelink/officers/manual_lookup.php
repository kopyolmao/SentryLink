<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['ssg']);
$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$studentId = trim($_GET['student_id'] ?? '');

$events = db_fetch_all($conn, "SELECT id, title, event_date FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC");
$result = null;

if ($eventId > 0 && $studentId !== '') {
    $result = db_fetch_one(
        $conn,
        "SELECT u.student_id, u.first_name, u.last_name, u.course, u.year_level,
                t.payment_status, e.title,
                (SELECT COUNT(*) FROM admissions a WHERE a.user_id = u.id AND a.event_id = e.id AND a.status = 'admitted') AS admission_count
         FROM users u
         INNER JOIN tickets t ON t.user_id = u.id
         INNER JOIN events e ON e.id = t.event_id
         WHERE e.id = ? AND u.student_id = ? AND t.deleted_at IS NULL
         LIMIT 1",
        'is',
        [$eventId, $studentId]
    );
}

shell_start('SentryLink | Manual Lookup', $user, 'ssg', 'lookup', 'Manual Lookup', 'Search a student when camera scanning fails.');
?>
<div class="panel">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id">
                <option value="">Select event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventId === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Student ID</label>
            <input class="form-control" name="student_id" value="<?= h($studentId) ?>" placeholder="STU001">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Search</button>
        </div>
    </form>
</div>

<div class="panel">
    <?php if ($result): ?>
        <h3 class="h5 mb-3"><?= h($result['first_name'] . ' ' . $result['last_name']) ?></h3>
        <p class="mb-1"><strong>Student ID:</strong> <?= h($result['student_id']) ?></p>
        <p class="mb-1"><strong>Course / Year:</strong> <?= h(($result['course'] ?: '-') . ' / ' . ($result['year_level'] ?: '-')) ?></p>
        <p class="mb-1"><strong>Ticket Status:</strong> <span class="badge text-bg-<?= h(ticket_status_badge($result['payment_status'])[1]) ?>"><?= h(ticket_status_badge($result['payment_status'])[0]) ?></span></p>
        <p class="mb-0"><strong>Admission State:</strong> <?= (int) $result['admission_count'] > 0 ? 'Already admitted' : 'Not yet admitted' ?></p>
    <?php elseif ($eventId > 0 && $studentId !== ''): ?>
        <p class="text-secondary mb-0">No matching ticket record was found for that student in the selected event.</p>
    <?php else: ?>
        <p class="text-secondary mb-0">Choose an event and enter a student ID to start a manual lookup.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
