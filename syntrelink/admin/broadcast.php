<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleFilter = $_POST['role'] ?? '';
    $courseFilter = trim($_POST['course'] ?? '');
    $yearFilter = trim($_POST['year_level'] ?? '');
    $houseFilter = trim($_POST['house'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['message'] ?? '');

    $sql = 'SELECT id FROM users WHERE is_active = 1 AND deleted_at IS NULL';
    $types = '';
    $params = [];

    if ($roleFilter !== '') {
        $sql .= ' AND role = ?';
        $types .= 's';
        $params[] = $roleFilter;
    }
    if ($courseFilter !== '') {
        $sql .= ' AND course = ?';
        $types .= 's';
        $params[] = $courseFilter;
    }
    if ($yearFilter !== '') {
        $sql .= ' AND year_level = ?';
        $types .= 's';
        $params[] = $yearFilter;
    }
    if ($houseFilter !== '') {
        $sql .= ' AND house = ?';
        $types .= 's';
        $params[] = $houseFilter;
    }

    $recipients = db_fetch_all($conn, $sql, $types, $params);
    foreach ($recipients as $recipient) {
        notify_user($conn, (int) $recipient['id'], $title, $body, 'info');
    }
    audit_log($conn, (int) $user['id'], 'BROADCAST_SENT', 'notification', null);
    $message = 'Broadcast sent to ' . count($recipients) . ' user(s).';
}

shell_start('SentryLink | Broadcast', $user, 'admin', 'broadcast', 'Notification Broadcast', 'Send notices to all users or filtered groups.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<div class="panel">
    <form method="POST" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
                <option value="">All roles</option>
                <?php foreach (['student', 'ssg', 'admin', 'director'] as $role): ?>
                    <option value="<?= h($role) ?>"><?= h(ucfirst($role)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Course</label><input class="form-control" name="course"></div>
        <div class="col-md-3"><label class="form-label">Year Level</label><input class="form-control" name="year_level"></div>
        <div class="col-md-3"><label class="form-label">House</label><input class="form-control" name="house"></div>
        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
        <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
        <div class="col-12"><button class="btn btn-primary">Send Broadcast</button></div>
    </form>
</div>
<?php shell_end(); ?>
