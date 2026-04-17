<?php shell_start('SentryLink | Director Events', $user, 'director', 'events', 'Event Status Overview', 'View-only list of all events and their status.'); ?>
<div class="panel">
    <form method="GET" class="filter-form">
        <div class="filter-field filter-span-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['draft', 'open', 'ongoing', 'closed', 'cancelled'] as $item): ?><option value="<?= h($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= h(ucfirst($item)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-field filter-span-4 filter-actions"><button class="btn btn-primary">Filter</button></div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Status</th><th>Price</th></tr></thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr><td><?= h($event['title']) ?></td><td><?= h($event['event_date']) ?><br><small class="text-secondary"><?= h(substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5)) ?></small></td><td><?= h($event['venue']) ?></td><td><span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span></td><td><?= (int) $event['is_free'] === 1 ? 'Free' : h((string) $event['ticket_price']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
