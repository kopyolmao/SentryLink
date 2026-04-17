<?php shell_start('SentryLink | Director Admissions', $user, 'director', 'admissions', 'Gate Logs', 'Read-only view of student gate entries and exits across events.'); ?>
<div class="panel">
    <form method="GET" class="filter-form">
        <div class="filter-field filter-span-5"><label class="form-label">Event</label><select class="form-select" name="event_id"><option value="">All events</option><?php foreach ($events as $event): ?><option value="<?= $event['id'] ?>" <?= $eventId === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-field filter-span-3 filter-actions"><button class="btn btn-primary">Filter</button></div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Course / Year</th><th>Event</th><th>Officer</th><th>Gate</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr><td><?= h($log['first_name'] . ' ' . $log['last_name']) ?><br><small class="text-secondary"><?= h($log['student_id']) ?></small></td><td><?= h(($log['course'] ?: '-') . ' / ' . ($log['year_level'] ?: '-')) ?></td><td><?= h($log['title']) ?></td><td><?= h(trim(($log['officer_first'] ?? '') . ' ' . ($log['officer_last'] ?? 'System'))) ?></td><td><?= h($log['gate_location'] ?: 'Main Gate') ?></td><td><?= h($log['scanned_at']) ?></td><td><span class="badge text-bg-<?= h(admission_status_badge($log['status'])) ?>"><?= h(admission_status_label($log['status'])) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
