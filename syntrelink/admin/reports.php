<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$reports = db_fetch_all(
    $conn,
    "SELECT e.id, e.title, e.event_date, e.status,
            SUM(CASE WHEN t.payment_status IN ('paid','free') THEN 1 ELSE 0 END) AS valid_tickets,
            (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id AND a.status = 'admitted') AS admitted_count
     FROM events e
     LEFT JOIN tickets t ON t.event_id = e.id AND t.deleted_at IS NULL
     WHERE e.deleted_at IS NULL
     GROUP BY e.id
     ORDER BY e.event_date DESC"
);

shell_start('SentryLink | Reports', $user, 'admin', 'reports', 'Reports', 'Attendance and ticket summary per event.');
?>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Valid Tickets</th><th>Admitted</th><th>Attendance Rate</th></tr></thead>
            <tbody>
            <?php foreach ($reports as $report): ?>
                <?php $rate = (int) $report['valid_tickets'] > 0 ? round(((int) $report['admitted_count'] / (int) $report['valid_tickets']) * 100, 1) : 0; ?>
                <tr>
                    <td><?= h($report['title']) ?></td>
                    <td><?= h($report['event_date']) ?></td>
                    <td><span class="badge text-bg-<?= h(event_status_badge($report['status'])) ?>"><?= h(ucfirst($report['status'])) ?></span></td>
                    <td><?= h((string) $report['valid_tickets']) ?></td>
                    <td><?= h((string) $report['admitted_count']) ?></td>
                    <td><?= h((string) $rate) ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
