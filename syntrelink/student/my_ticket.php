<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$userId = (int) $user['id'];

$tickets = db_fetch_all(
    $conn,
    "SELECT t.ticket_code, t.receipt_id, t.payment_status, t.issued_at,
            e.title, e.event_date, e.start_time, e.end_time, e.venue, e.status
     FROM tickets t
     INNER JOIN events e ON e.id = t.event_id
     WHERE t.user_id = ? AND t.deleted_at IS NULL
     ORDER BY e.event_date DESC, e.start_time DESC",
    'i',
    [$userId]
);

shell_start('SentryLink | My Tickets', $user, 'student', 'tickets', 'My Tickets', 'All ticket records tied to your account.');
?>
<div class="panel">
    <?php if ($tickets): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Ticket Code</th>
                        <th>Receipt ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <?php [$label, $badge] = ticket_status_badge($ticket['payment_status']); ?>
                    <tr>
                        <td><?= h($ticket['title']) ?></td>
                        <td><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?><br><small class="text-secondary"><?= h(substr($ticket['start_time'], 0, 5) . ' - ' . substr($ticket['end_time'], 0, 5)) ?></small></td>
                        <td><?= h($ticket['venue']) ?></td>
                        <td><code><?= h($ticket['ticket_code']) ?></code></td>
                        <td><?= h($ticket['receipt_id'] ?: 'Free Event') ?></td>
                        <td><span class="badge text-bg-<?= h($badge) ?>"><?= h($label) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No tickets found yet.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
