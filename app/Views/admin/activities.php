<?php shell_start('SentryLink | Activities', $user, 'admin', 'events', 'Activity Management', 'Manage event sub-activities.'); ?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

<div class="panel">
    <h3 class="h5 mb-3">Add Activity for <?= h((string) ($event['title'] ?? 'Event')) ?></h3>
    <form method="POST" class="row g-3">
        <div class="col-md-4"><input class="form-control" name="title" placeholder="Activity title" required></div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="school_prepared">School Prepared</option>
                <option value="house_booth">House Booth</option>
                <option value="competition">Competition</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" name="house_name" placeholder="House"></div>
        <div class="col-md-2"><input type="time" class="form-control" name="start_time" required></div>
        <div class="col-md-2"><input type="time" class="form-control" name="end_time" required></div>
        <div class="col-md-6"><input class="form-control" name="venue_area" placeholder="Venue area"></div>
        <div class="col-md-6"><input class="form-control" name="description" placeholder="Description"></div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Add Activity</button>
            <a class="btn btn-outline-light" href="<?= h(app_url('admin/events')) ?>">Back to Events</a>
        </div>
    </form>
</div>

<div class="panel">
    <?php if ($activities !== []): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead><tr><th>Title</th><th>Type</th><th>Time</th><th>Venue</th><th>House</th></tr></thead>
                <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= h((string) $activity['title']) ?></td>
                        <td><?= h((string) $activity['type']) ?></td>
                        <td><?= h(substr((string) $activity['start_time'], 0, 5) . ' - ' . substr((string) $activity['end_time'], 0, 5)) ?></td>
                        <td><?= h((string) ($activity['venue_area'] ?? '-')) ?></td>
                        <td><?= h((string) ($activity['house_name'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No activities yet for this event.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
