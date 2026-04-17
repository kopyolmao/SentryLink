<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$statusFilter = $_GET['status'] ?? '';
$eventFilter = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

$where = 'WHERE t.deleted_at IS NULL';
$types = '';
$params = [];

if ($statusFilter !== '') {
    $where .= ' AND t.payment_status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($eventFilter > 0) {
    $where .= ' AND t.event_id = ?';
    $types .= 'i';
    $params[] = $eventFilter;
}

$tickets = db_fetch_all(
    $conn,
    "SELECT t.ticket_code, t.receipt_id, t.payment_status, t.issued_at,
            u.student_id, u.first_name, u.last_name,
            e.title
     FROM tickets t
     INNER JOIN users u ON u.id = t.user_id
     INNER JOIN events e ON e.id = t.event_id
     $where
     ORDER BY t.issued_at DESC",
    $types,
    $params
);

$events = db_fetch_all($conn, 'SELECT id, title FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC');

shell_start('SentryLink | Tickets', $user, 'admin', 'tickets', 'Ticket Management', 'Review ticket inventory by event or payment status.');
?>
<div class="panel">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id">
                <option value="">All events</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventFilter === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payment Status</label>
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'paid', 'free', 'cancelled'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary">Apply Filters</button></div>
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
