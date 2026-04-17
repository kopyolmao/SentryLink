<?php shell_start('SentryLink | Audit Logs', $user, 'admin', 'audit', 'Audit Logs', 'Read-only system actions for accountability.'); ?>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Action</th><th>User</th><th>Target</th><th>IP</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h((string) $log['action']) ?></td>
                    <td><?= h(trim(((string) ($log['first_name'] ?? '')) . ' ' . ((string) ($log['last_name'] ?? 'System')))) ?></td>
                    <td><?= h(((string) ($log['target_type'] ?: '-')) . (!empty($log['target_id']) ? ' #' . (string) $log['target_id'] : '')) ?></td>
                    <td><?= h((string) ($log['ip_address'] ?? '-')) ?></td>
                    <td><?= h((string) $log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
