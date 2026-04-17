<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$userId = (int) $user['id'];

if (isset($_POST['mark_all_read'])) {
    db_execute($conn, 'UPDATE notifications SET is_read = 1 WHERE user_id = ?', 'i', [$userId]);
}

$notifications = db_fetch_all(
    $conn,
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
    'i',
    [$userId]
);

shell_start('SentryLink | Notifications', $user, 'student', 'notifications', 'Notifications', 'System messages tied to your account.');
?>
<div class="panel">
    <form method="POST" class="mb-3">
        <button class="btn btn-outline-light btn-sm" name="mark_all_read" value="1">Mark All Read</button>
    </form>

    <?php if ($notifications): ?>
        <ul class="list-soft">
            <?php foreach ($notifications as $note): ?>
                <li>
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <strong><?= h($note['title']) ?></strong>
                            <div class="text-secondary"><?= h($note['message']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-<?= (int) $note['is_read'] === 1 ? 'secondary' : 'primary' ?>">
                                <?= (int) $note['is_read'] === 1 ? 'Read' : 'Unread' ?>
                            </span>
                            <div class="text-secondary small mt-2"><?= h($note['created_at']) ?></div>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-secondary mb-0">No notifications yet.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
