<?php shell_start('SentryLink | Cashier Dashboard', $user, 'cashier', 'dashboard', 'Cashier Encoding', 'Encode paid event students directly without CSV uploads.'); ?>
<style>
.cashier-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cashier-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    align-items: end;
}

.cashier-field {
    min-width: 0;
}

.cashier-field input,
.cashier-field select {
    width: 100%;
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }

@media (max-width: 900px) {
    .cashier-form {
        grid-template-columns: 1fr;
    }

    .span-12,
    .span-6,
    .span-4 {
        grid-column: auto;
    }
}
</style>
<div class="cashier-layout">
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-2">Encode Paid Ticket</h3>
    <p class="text-secondary mb-3">Select a paid event, enter student number and receipt number, then submit.</p>
    <form method="POST" class="cashier-form">
        <div class="cashier-field span-4">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id" required>
                <option value="">Select paid event</option>
                <?php foreach ($events as $event): ?>
                    <?php $eventId = (int) ($event['id'] ?? 0); ?>
                    <option value="<?= h((string) $eventId) ?>" <?= $selectedEventId === $eventId ? 'selected' : '' ?>>
                        <?= h((string) ($event['title'] ?? 'Untitled')) ?> (<?= h((string) ($event['event_date'] ?? '-')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cashier-field span-4">
            <label class="form-label">Student Number</label>
            <input class="form-control" name="student_no" maxlength="20" value="<?= h($studentNo) ?>" placeholder="e.g. 23-0001" required>
        </div>
        <div class="cashier-field span-4">
            <label class="form-label">Receipt Number</label>
            <input class="form-control" name="receipt_no" maxlength="120" value="<?= h($receiptNo) ?>" placeholder="e.g. OR-2026-0001" required>
        </div>
        <div class="cashier-field span-12">
            <button class="btn btn-primary" name="encode_paid_ticket" value="1">Encode Paid Ticket</button>
        </div>
    </form>
</div>
<div class="panel">
    <h3 class="h5 mb-3">Recent Encoded Tickets</h3>
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Event</th><th>Receipt</th><th>Ticket Code</th><th>Encoded At</th></tr></thead>
            <tbody>
            <?php if ($recentEncodedTickets === []): ?>
                <tr><td colspan="5" class="text-secondary">No encoded tickets yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentEncodedTickets as $entry): ?>
                <tr>
                    <td><?= h((string) ($entry['first_name'] ?? '') . ' ' . (string) ($entry['last_name'] ?? '')) ?><br><small class="text-secondary"><?= h((string) ($entry['student_id'] ?? '')) ?></small></td>
                    <td><?= h((string) ($entry['event_title'] ?? '')) ?><br><small class="text-secondary"><?= h((string) ($entry['event_date'] ?? '')) ?></small></td>
                    <td><?= h((string) ($entry['receipt_id'] ?? '-')) ?></td>
                    <td><code><?= h((string) ($entry['ticket_code'] ?? '')) ?></code></td>
                    <td><?= h((string) ($entry['issued_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<?php shell_end(); ?>
