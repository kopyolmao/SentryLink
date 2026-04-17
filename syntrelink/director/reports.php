<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['director']);
$reports = db_fetch_all(
    $conn,
    "SELECT e.title, e.event_date, e.status,
            SUM(CASE WHEN t.payment_status IN ('paid','free') THEN 1 ELSE 0 END) AS valid_tickets,
            (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id AND a.status = 'admitted') AS admitted_count
     FROM events e
     LEFT JOIN tickets t ON t.event_id = e.id AND t.deleted_at IS NULL
     WHERE e.deleted_at IS NULL
     GROUP BY e.id
     ORDER BY e.event_date DESC"
);

shell_start('SentryLink | Director Reports', $user, 'director', 'reports', 'System Reports', 'Attendance and ticket summaries for leadership review.');
?>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Valid Tickets</th><th>Admitted</th></tr></thead>
            <tbody>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= h($report['title']) ?></td>
                    <td><?= h($report['event_date']) ?></td>
                    <td><span class="badge text-bg-<?= h(event_status_badge($report['status'])) ?>"><?= h(ucfirst($report['status'])) ?></span></td>
                    <td><?= h((string) $report['valid_tickets']) ?></td>
                    <td><?= h((string) $report['admitted_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
