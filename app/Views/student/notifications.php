<?php shell_start('SentryLink | Notifications', $user, 'student', 'notifications', 'Notifications', 'System messages tied to your account.'); ?>
<div class="panel">
    <form method="POST" class="mb-3">
        <button class="btn btn-outline-light btn-sm" name="mark_all_read" value="1">Mark All Read</button>
    </form>

    <?php if ($notifications !== []): ?>
        <ul class="list-soft">
            <?php foreach ($notifications as $note): ?>
                <li>
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <strong><?= h((string) $note['title']) ?></strong>
                            <div class="text-secondary"><?= h((string) $note['message']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-<?= (int) ($note['is_read'] ?? 0) === 1 ? 'secondary' : 'primary' ?>">
                                <?= (int) ($note['is_read'] ?? 0) === 1 ? 'Read' : 'Unread' ?>
                            </span>
                            <div class="text-secondary small mt-2"><?= h((string) $note['created_at']) ?></div>
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
