<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$userId = (int) $user['id'];

$stats = [
    'tickets' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM tickets WHERE user_id = ? AND deleted_at IS NULL', 'i', [$userId]),
    'active' => (int) db_scalar($conn, "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND payment_status IN ('paid','free') AND deleted_at IS NULL", 'i', [$userId]),
    'notifications' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', 'i', [$userId]),
];

$upcoming = db_fetch_all(
    $conn,
    "SELECT t.id, t.payment_status, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.status
     FROM tickets t
     INNER JOIN events e ON e.id = t.event_id
     WHERE t.user_id = ? AND t.deleted_at IS NULL
     ORDER BY e.event_date ASC, e.start_time ASC
     LIMIT 6",
    'i',
    [$userId]
);

shell_start(
    'SentryLink | Student Dashboard',
    $user,
    'student',
    'dashboard',
    'Student Dashboard',
    'Ticket status, live QR access, and recent updates.'
);
?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="metric"><div class="text-secondary">Total Tickets</div><div class="value"><?= $stats['tickets'] ?></div></div></div>
    <div class="col-md-4"><div class="metric"><div class="text-secondary">Active Tickets</div><div class="value"><?= $stats['active'] ?></div></div></div>
    <div class="col-md-4"><div class="metric"><div class="text-secondary">Unread Notifications</div><div class="value"><?= $stats['notifications'] ?></div></div></div>
</div>

<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0">My Tickets</h3>
        <div>
            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('s/my-tickets')) ?>">View All</a>
            <a class="btn btn-primary btn-sm" href="<?= h(app_url('s/my-qr')) ?>">Open My QR</a>
        </div>
    </div>

    <?php if ($upcoming): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Schedule</th>
                        <th>Venue</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($upcoming as $ticket): ?>
                    <?php [$label, $badge] = ticket_status_badge($ticket['payment_status']); ?>
                    <tr>
                        <td><?= h($ticket['title']) ?></td>
                        <td><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?></td>
                        <td><?= h(substr($ticket['start_time'], 0, 5) . ' - ' . substr($ticket['end_time'], 0, 5)) ?></td>
                        <td><?= h($ticket['venue']) ?></td>
                        <td><span class="badge text-bg-<?= h($badge) ?>"><?= h($label) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No tickets yet. Admin receipt imports will make your event tickets appear here.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
