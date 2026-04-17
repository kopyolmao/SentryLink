<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['director']);
$logs = db_fetch_all(
    $conn,
    "SELECT a.action, a.target_type, a.target_id, a.created_at, u.first_name, u.last_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 200"
);

shell_start('SentryLink | Director Audit Logs', $user, 'director', 'audit', 'Audit Log Viewer', 'Read-only system action history.');
?>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Action</th><th>User</th><th>Target</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? 'System'))) ?></td>
                    <td><?= h(($log['target_type'] ?: '-') . ($log['target_id'] ? ' #' . $log['target_id'] : '')) ?></td>
                    <td><?= h($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
