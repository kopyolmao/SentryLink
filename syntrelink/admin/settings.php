<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        $policyErrors = password_policy_errors($new);
        if ($policyErrors) {
            $error = implode(' ', $policyErrors);
        } else {
            db_execute($conn, 'UPDATE users SET password_hash = ?, session_token = NULL, updated_at = NOW() WHERE id = ?', 'si', [password_hash($new, PASSWORD_BCRYPT), (int) $user['id']]);
            audit_log($conn, (int) $user['id'], 'ADMIN_PASSWORD_CHANGED', 'user', (int) $user['id']);
            $message = 'Admin password updated.';
            $user = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [(int) $user['id']]);
        }
    }
}

shell_start('SentryLink | Settings', $user, 'admin', 'settings', 'Settings', 'Security settings available in the current build.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-3">Change Admin Password</h3>
    <form method="POST" class="row g-3">
        <div class="col-12">
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="current_password" placeholder="Current password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="new_password" placeholder="New password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="confirm_password" placeholder="Confirm new password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-12"><button class="btn btn-primary">Update Password</button></div>
    </form>
</div>
<div class="panel">
    <h3 class="h5 mb-3">Current Runtime Settings</h3>
    <ul class="list-soft">
        <li>QR grace window: 5 seconds</li>
        <li>Student QR refresh interval: 10 seconds</li>
        <li>Authentication roles enabled: student, officer, admin, director</li>
        <li>SMTP and infrastructure-level settings remain developer configured.</li>
    </ul>
</div>
<?php shell_end(); ?>
