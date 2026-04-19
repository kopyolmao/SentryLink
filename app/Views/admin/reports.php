<?php shell_start('SentryLink | Reports', $user, 'admin', 'reports', 'Reports', 'Attendance and ticket summary per event.'); ?>
<?php
$exportError = session()->getFlashdata('export_error');
$hasReportRows = $reports !== [];
?>
<?php if ($exportError !== null && $exportError !== ''): ?><div class="alert alert-danger"><?= h((string) $exportError) ?></div><?php endif; ?>
<div class="panel">
    <div class="d-flex justify-content-end mb-3">
        <?php if ($hasReportRows): ?>
            <a class="btn btn-outline-light" href="<?= h(app_url('admin/reports?export=csv')) ?>">Export CSV</a>
        <?php else: ?>
            <button type="button" class="btn btn-outline-light" disabled title="No data to export">Export CSV</button>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Valid Tickets</th><th>Admitted</th><th>Out</th><th>Total Scans</th><th>Attendance Rate</th></tr></thead>
            <tbody>
            <?php foreach ($reports as $report): ?>
                <?php $rate = (int) $report['valid_tickets'] > 0 ? round(((int) $report['admitted_count'] / (int) $report['valid_tickets']) * 100, 1) : 0; ?>
                <tr><td><?= h($report['title']) ?></td><td><?= h($report['event_date']) ?></td><td><span class="badge text-bg-<?= h(event_status_badge($report['status'])) ?>"><?= h(ucfirst($report['status'])) ?></span></td><td><?= h((string) $report['valid_tickets']) ?></td><td><?= h((string) $report['admitted_count']) ?></td><td><?= h((string) ($report['out_count'] ?? 0)) ?></td><td><?= h((string) ($report['scan_count'] ?? 0)) ?></td><td><?= h((string) $rate) ?>%</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
