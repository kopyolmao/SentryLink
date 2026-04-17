<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);

if (!isset($_SESSION['import_success'])) {
    redirect_to('admin/tickets/import-receipts');
}

$success = $_SESSION['import_success'];
unset($_SESSION['import_success']);

shell_start('SentryLink | Import Success', $user, 'admin', 'import', 'Import Complete', 'Receipt rows were converted into event tickets.');
?>
<div class="panel text-center">
    <h3 class="h4 mb-3">Import completed successfully</h3>
    <p class="mb-1">Created <strong><?= h((string) $success['inserted']) ?></strong> ticket(s).</p>
    <p class="text-secondary mb-4"><?= h($success['event_title']) ?></p>
    <a class="btn btn-primary" href="<?= h(app_url('admin/tickets/import-receipts')) ?>">Import Another CSV</a>
    <a class="btn btn-outline-light" href="<?= h(app_url('admin/tickets')) ?>">View Tickets</a>
</div>
<?php shell_end(); ?>
