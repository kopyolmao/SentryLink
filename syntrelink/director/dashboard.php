<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['director']);

$metrics = [
    'events' => (int) db_scalar($conn, "SELECT COUNT(*) FROM events WHERE deleted_at IS NULL"),
    'ongoing' => (int) db_scalar($conn, "SELECT COUNT(*) FROM events WHERE status = 'ongoing' AND deleted_at IS NULL"),
    'tickets' => (int) db_scalar($conn, "SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL"),
    'admissions' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM admissions'),
];

$events = db_fetch_all($conn, "SELECT title, event_date, status, venue FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 8");
$recentAdmissions = db_fetch_all(
    $conn,
    "SELECT a.scanned_at, a.status, u.student_id, u.first_name, u.last_name, e.title
     FROM admissions a
     INNER JOIN users u ON u.id = a.user_id
     INNER JOIN events e ON e.id = a.event_id
     ORDER BY a.scanned_at DESC
     LIMIT 10"
);

shell_start('SentryLink | Director Dashboard', $user, 'director', 'dashboard', 'Director Overview', 'Read-only visibility into event operations and attendance.');
?>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Events</div><div class="value"><?= $metrics['events'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Ongoing</div><div class="value"><?= $metrics['ongoing'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Tickets</div><div class="value"><?= $metrics['tickets'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Admissions</div><div class="value"><?= $metrics['admissions'] ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="h5 mb-3">Recent Events</h3>
            <ul class="list-soft">
                <?php foreach ($events as $event): ?>
                    <li>
                        <strong><?= h($event['title']) ?></strong>
                        <div class="text-secondary"><?= h($event['event_date']) ?> | <?= h($event['venue']) ?> | <?= h(ucfirst($event['status'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="h5 mb-3">Latest Admissions</h3>
            <ul class="list-soft">
                <?php foreach ($recentAdmissions as $row): ?>
                    <li>
                        <strong><?= h($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                        <div class="text-secondary"><?= h($row['student_id']) ?> | <?= h($row['title']) ?> | <?= h($row['scanned_at']) ?> | <?= h($row['status']) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php shell_end(); ?>
