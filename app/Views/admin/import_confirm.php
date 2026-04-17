<?php shell_start('SentryLink | Confirm Import', $user, 'admin', 'import', 'Confirm Import', 'Review the validated rows before creating tickets.'); ?>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="metric"><div class="text-secondary">Valid Rows</div><div class="value"><?= count($validRows) ?></div></div></div>
    <div class="col-md-6"><div class="metric"><div class="text-secondary">Invalid Rows</div><div class="value"><?= count($invalidRows) ?></div></div></div>
</div>
<div class="panel">
    <h3 class="h5 mb-3">Event: <?= h($event['title']) ?></h3>
    <?php if ($validRows): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead><tr><th>Receipt ID</th><th>Student No</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($validRows as $row): ?><tr><td><?= h($row['receipt_id']) ?></td><td><?= h($row['student_no']) ?></td><td><span class="badge text-bg-success">Valid</span></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php if ($invalidRows): ?>
        <h4 class="h6 mt-4">Skipped Rows</h4>
        <ul class="list-soft"><?php foreach ($invalidRows as $row): ?><li><?= h($row) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="POST" class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" name="confirm" value="1">Create <?= count($validRows) ?> Ticket(s)</button>
        <button class="btn btn-outline-light" name="cancel" value="1">Cancel</button>
    </form>
</div>
<?php shell_end(); ?>
