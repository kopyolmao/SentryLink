<?php shell_start('SentryLink | Notifications', $user, 'student', 'notifications', 'Notifications', 'System messages tied to your account.'); ?>
<div class="panel">
    <form method="POST" class="mb-3">
        <button class="btn btn-outline-light btn-sm" name="mark_all_read" value="1">Mark All Read</button>
    </form>

    <?php if ($notifications !== []): ?>
        <ul class="list-soft">
            <?php foreach ($notifications as $note): ?>
                <?php $isRead = (int) ($note['is_read'] ?? 0) === 1; ?>
                <li>
                    <form method="POST" class="mb-0">
                        <input type="hidden" name="notification_id" value="<?= (int) ($note['id'] ?? 0) ?>">
                        <button type="submit" class="w-100 border-0 bg-transparent p-0 text-start text-reset">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div>
                                    <strong><?= h((string) $note['title']) ?></strong>
                                    <div class="text-secondary"><?= h((string) $note['message']) ?></div>
                                    <?php if (! $isRead): ?>
                                        <div class="small text-info mt-2">Click this notification to mark as read.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge text-bg-<?= $isRead ? 'secondary' : 'primary' ?>">
                                        <?= $isRead ? 'Read' : 'Unread' ?>
                                    </span>
                                    <div class="text-secondary small mt-2"><?= h((string) $note['created_at']) ?></div>
                                </div>
                            </div>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-secondary mb-0">No notifications yet.</p>
    <?php endif; ?>
</div>
<?php shell_end(); ?>
