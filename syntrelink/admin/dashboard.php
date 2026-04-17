<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);

$metrics = [
    'students' => (int) db_scalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'student' AND deleted_at IS NULL"),
    'events' => (int) db_scalar($conn, "SELECT COUNT(*) FROM events WHERE deleted_at IS NULL"),
    'tickets' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL'),
    'admissions' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM admissions WHERE DATE(scanned_at) = CURDATE()'),
];

$recentEvents = db_fetch_all($conn, "SELECT id, title, event_date, status FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 6");
$recentAudit = db_fetch_all(
    $conn,
    "SELECT a.action, a.created_at, u.first_name, u.last_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 8"
);

shell_start('SentryLink | Admin Dashboard', $user, 'admin', 'dashboard', 'Admin Dashboard', 'Operational overview for events, tickets, and gate activity.');
?>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Students</div><div class="value"><?= $metrics['students'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Events</div><div class="value"><?= $metrics['events'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Tickets</div><div class="value"><?= $metrics['tickets'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Admissions Today</div><div class="value"><?= $metrics['admissions'] ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 mb-0">Recent Events</h3>
                <a class="btn btn-primary btn-sm" href="<?= h(app_url('admin/events')) ?>">Manage Events</a>
            </div>
            <?php if ($recentEvents): ?>
                <ul class="list-soft">
                    <?php foreach ($recentEvents as $event): ?>
                        <li class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= h($event['title']) ?></strong>
                                <div class="text-secondary"><?= h($event['event_date']) ?></div>
                            </div>
                            <span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-secondary mb-0">No events found.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h3 class="h5 mb-3">Quick Actions</h3>
            <div class="d-grid gap-2">
                <a class="btn btn-primary" href="<?= h(app_url('admin/tickets/import-receipts')) ?>">Import Receipts</a>
                <a class="btn btn-outline-light" href="<?= h(app_url('admin/students')) ?>">Manage Students</a>
                <a class="btn btn-outline-light" href="<?= h(app_url('admin/notifications/broadcast')) ?>">Send Broadcast</a>
            </div>
        </div>
        <div class="panel">
            <h3 class="h5 mb-3">Latest Audit Entries</h3>
            <?php if ($recentAudit): ?>
                <ul class="list-soft">
                    <?php foreach ($recentAudit as $log): ?>
                        <li>
                            <strong><?= h($log['action']) ?></strong>
                            <div class="text-secondary"><?= h(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? 'System'))) ?> | <?= h($log['created_at']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-secondary mb-0">No audit entries yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php shell_end(); ?>
