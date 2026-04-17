<?php shell_start('SentryLink | Director Reports', $user, 'director', 'reports', 'System Reports', 'Attendance and ticket summaries for leadership review.'); ?>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Valid Tickets</th><th>Admitted</th></tr></thead>
            <tbody><?php foreach ($reports as $report): ?><tr><td><?= h($report['title']) ?></td><td><?= h($report['event_date']) ?></td><td><span class="badge text-bg-<?= h(event_status_badge($report['status'])) ?>"><?= h(ucfirst($report['status'])) ?></span></td><td><?= h((string) $report['valid_tickets']) ?></td><td><?= h((string) $report['admitted_count']) ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
