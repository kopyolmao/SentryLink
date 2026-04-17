<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';
$error = '';

$events = db_fetch_all($conn, "SELECT id, title, event_date FROM events WHERE status IN ('draft', 'open', 'ongoing') AND deleted_at IS NULL ORDER BY event_date DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import']) && isset($_FILES['csv_file'])) {
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($eventId <= 0) {
        $error = 'Please select an event.';
    } elseif (!is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $error = 'Upload a valid CSV file.';
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $validRows = [];
        $invalidRows = [];
        $rowNumber = 0;

        if ($handle !== false) {
            fgetcsv($handle);

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNumber++;
                $receiptId = trim($row[0] ?? '');
                $studentNo = trim($row[1] ?? '');

                if ($receiptId === '' || $studentNo === '') {
                    $invalidRows[] = "Row {$rowNumber}: Missing receipt_id or student_no.";
                    continue;
                }

                $student = db_fetch_one($conn, "SELECT id FROM users WHERE student_id = ? AND role = 'student' AND deleted_at IS NULL", 's', [$studentNo]);
                if (!$student) {
                    $invalidRows[] = "Row {$rowNumber}: Student {$studentNo} was not found.";
                    continue;
                }

                $existingReceipt = db_fetch_one($conn, 'SELECT id FROM tickets WHERE receipt_id = ?', 's', [$receiptId]);
                if ($existingReceipt) {
                    $invalidRows[] = "Row {$rowNumber}: Receipt {$receiptId} already exists.";
                    continue;
                }

                $existingTicket = db_fetch_one($conn, 'SELECT id FROM tickets WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL', 'ii', [(int) $student['id'], $eventId]);
                if ($existingTicket) {
                    $invalidRows[] = "Row {$rowNumber}: Student {$studentNo} already has a ticket for this event.";
                    continue;
                }

                $validRows[] = [
                    'receipt_id' => $receiptId,
                    'user_id' => (int) $student['id'],
                    'student_no' => $studentNo,
                ];
            }

            fclose($handle);
        }

        $_SESSION['import_data'] = [
            'event_id' => $eventId,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];

        if ($validRows) {
            redirect_to('admin/tickets/import-receipts/confirm');
        }

        $error = 'No valid rows were found in the CSV.';
    }
}

shell_start('SentryLink | Receipt Import', $user, 'admin', 'import', 'CSV Receipt Import', 'Import cashier receipt data and turn it into tickets.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="panel">
    <h3 class="h5 mb-3">Upload CSV</h3>
    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id" required>
                <option value="">Select event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>"><?= h($event['title']) ?> (<?= h($event['event_date']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">CSV File</label>
            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
        </div>
        <div class="col-12"><button class="btn btn-primary" name="import" value="1">Validate Import</button></div>
    </form>
</div>

<div class="panel">
    <h3 class="h5 mb-3">Expected CSV Columns</h3>
    <p class="mb-1"><code>receipt_id,student_no</code></p>
    <p class="text-secondary mb-0">Event selection happens on this screen, so the file only needs receipt ID and student number.</p>
</div>
<?php shell_end(); ?>
