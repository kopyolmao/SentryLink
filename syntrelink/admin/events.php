<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';
$error = '';
$action = $_GET['action'] ?? '';
$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_event'])) {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $venue = trim($_POST['venue'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '08:00';
            $endTime = $_POST['end_time'] ?? '17:00';
            $status = $_POST['status'] ?? 'draft';
            $isFree = isset($_POST['is_free']) ? 1 : 0;
            $ticketPrice = $isFree ? null : ($_POST['ticket_price'] !== '' ? (float) $_POST['ticket_price'] : null);
            $capacity = $_POST['max_capacity'] !== '' ? (int) $_POST['max_capacity'] : null;

            if ((int) ($_POST['event_id'] ?? 0) > 0) {
                $eventId = (int) $_POST['event_id'];
                db_execute(
                    $conn,
                    "UPDATE events
                     SET title = ?, description = ?, venue = ?, event_date = ?, start_time = ?, end_time = ?,
                         is_free = ?, ticket_price = ?, max_capacity = ?, status = ?, updated_at = NOW()
                     WHERE id = ?",
                    'ssssssidisi',
                    [$title, $description, $venue, $eventDate, $startTime, $endTime, $isFree, (float) ($ticketPrice ?? 0), $capacity, $status, $eventId]
                );
                audit_log($conn, (int) $user['id'], 'EVENT_UPDATED', 'event', $eventId);
                $message = 'Event updated.';
            } else {
                db_execute(
                    $conn,
                    "INSERT INTO events
                        (title, description, venue, event_date, start_time, end_time, is_free, ticket_price, max_capacity, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    'ssssssidisi',
                    [$title, $description, $venue, $eventDate, $startTime, $endTime, $isFree, (float) ($ticketPrice ?? 0), $capacity, $status, (int) $user['id']]
                );
                $newId = (int) $conn->insert_id;
                audit_log($conn, (int) $user['id'], 'EVENT_CREATED', 'event', $newId);
                $message = 'Event created.';
            }
        }

        if (isset($_POST['prepare_gate'])) {
            $eventId = (int) $_POST['event_id'];
            $count = prepare_event_gate($conn, $eventId);
            audit_log($conn, (int) $user['id'], 'EVENT_GATE_PREPARED', 'event', $eventId);
            $message = 'Gate prepared and attendee cache rebuilt for ' . $count . ' students.';
        }

        if (isset($_POST['soft_delete_event'])) {
            $eventId = (int) $_POST['event_id'];
            db_execute($conn, 'UPDATE events SET deleted_at = NOW(), status = ? WHERE id = ?', 'si', ['cancelled', $eventId]);
            audit_log($conn, (int) $user['id'], 'EVENT_CANCELLED', 'event', $eventId);
            $message = 'Event cancelled.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$editEvent = $editId > 0 ? db_fetch_one($conn, 'SELECT * FROM events WHERE id = ?', 'i', [$editId]) : null;
$events = db_fetch_all(
    $conn,
    "SELECT e.*,
            (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.deleted_at IS NULL) AS ticket_count
     FROM events e
     WHERE e.deleted_at IS NULL
     ORDER BY e.event_date DESC, e.start_time DESC"
);

shell_start('SentryLink | Events', $user, 'admin', 'events', 'Event Management', 'Create, update, and prepare event gates.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="panel">
    <h3 class="h5 mb-3"><?= $editEvent ? 'Edit Event' : 'Create Event' ?></h3>
    <form method="POST" class="row g-3">
        <input type="hidden" name="event_id" value="<?= h($editEvent['id'] ?? '') ?>">
        <div class="col-md-6">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="<?= h($editEvent['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Venue</label>
            <input class="form-control" name="venue" value="<?= h($editEvent['venue'] ?? '') ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"><?= h($editEvent['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Event Date</label>
            <input type="date" class="form-control" name="event_date" value="<?= h($editEvent['event_date'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" value="<?= h($editEvent['start_time'] ?? '08:00') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" value="<?= h($editEvent['end_time'] ?? '17:00') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <?php foreach (['draft', 'open', 'ongoing', 'closed', 'cancelled'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($editEvent['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ticket Price</label>
            <input type="number" step="0.01" class="form-control" name="ticket_price" value="<?= h((string) ($editEvent['ticket_price'] ?? '')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Max Capacity</label>
            <input type="number" class="form-control" name="max_capacity" value="<?= h((string) ($editEvent['max_capacity'] ?? '')) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_free" id="is_free" <?= (int) ($editEvent['is_free'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_free">Free Event</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" name="save_event" value="1"><?= $editEvent ? 'Save Event' : 'Create Event' ?></button>
            <?php if ($editEvent): ?><a class="btn btn-outline-light" href="<?= h(app_url('admin/events')) ?>">Cancel Edit</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Tickets</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td>
                        <strong><?= h($event['title']) ?></strong>
                        <div class="text-secondary"><?= h($event['venue']) ?></div>
                    </td>
                    <td><?= h($event['event_date']) ?><br><small class="text-secondary"><?= h(substr($event['start_time'], 0, 5) . ' - ' . substr($event['end_time'], 0, 5)) ?></small></td>
                    <td><span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span></td>
                    <td><?= h((string) $event['ticket_count']) ?></td>
                    <td>
                        <a class="btn btn-outline-light btn-sm mb-1" href="<?= h(app_url('admin/events') . '?action=edit&id=' . $event['id']) ?>">Edit</a>
                        <a class="btn btn-outline-light btn-sm mb-1" href="<?= h(app_url('admin/events/' . $event['id'] . '/activities')) ?>">Activities</a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button class="btn btn-primary btn-sm mb-1" name="prepare_gate" value="1">Lock & Prepare Gate</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button class="btn btn-danger btn-sm mb-1" name="soft_delete_event" value="1" onclick="return confirm('Cancel this event?')">Cancel</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
