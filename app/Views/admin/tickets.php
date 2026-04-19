<?php shell_start('SentryLink | Tickets', $user, 'admin', 'tickets', 'Ticket Management', 'Review ticket inventory by event or payment status.'); ?>
<?php
$ticketExportParams = [];
if ($eventFilter > 0) {
    $ticketExportParams['event_id'] = (string) $eventFilter;
}
if ($statusFilter !== '') {
    $ticketExportParams['status'] = $statusFilter;
}
$ticketExportParams['export'] = 'csv';
$exportError = session()->getFlashdata('export_error');
$hasTicketRows = $tickets !== [];
?>
<?php if ($exportError !== null && $exportError !== ''): ?><div class="alert alert-danger"><?= h((string) $exportError) ?></div><?php endif; ?>
<div class="panel">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">Event</label><select class="form-select" name="event_id"><option value="">All events</option><?php foreach ($events as $event): ?><option value="<?= $event['id'] ?>" <?= $eventFilter === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Payment Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['pending', 'paid', 'free', 'cancelled'] as $status): ?><option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 d-flex gap-2 filter-actions">
            <button class="btn btn-primary">Apply Filters</button>
            <?php if ($hasTicketRows): ?>
                <a class="btn btn-outline-light" href="<?= h(app_url('admin/tickets?' . http_build_query($ticketExportParams))) ?>">Export CSV</a>
            <?php else: ?>
                <button type="button" class="btn btn-outline-light" disabled title="No data to export">Export CSV</button>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Event</th><th>Ticket Code</th><th>Receipt</th><th>Status</th><th>Issued</th></tr></thead>
            <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <?php [$label, $badge] = ticket_status_badge($ticket['payment_status']); ?>
                <tr>
                    <td><?= h($ticket['first_name'] . ' ' . $ticket['last_name']) ?><br><small class="text-secondary"><?= h($ticket['student_id']) ?></small></td>
                    <td><?= h($ticket['title']) ?></td>
                    <td><code><?= h($ticket['ticket_code']) ?></code></td>
                    <td><?= h($ticket['receipt_id'] ?: 'Free Event') ?></td>
                    <td><span class="badge text-bg-<?= h($badge) ?>"><?= h($label) ?></span></td>
                    <td><?= h($ticket['issued_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
