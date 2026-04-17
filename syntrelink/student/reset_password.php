<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'New password confirmation does not match.';
    } else {
        $policyErrors = password_policy_errors($new);
        if ($policyErrors) {
            $error = implode(' ', $policyErrors);
        } else {
            db_execute(
                $conn,
                'UPDATE users SET password_hash = ?, session_token = NULL, updated_at = NOW() WHERE id = ?',
                'si',
                [password_hash($new, PASSWORD_BCRYPT), (int) $user['id']]
            );
            audit_log($conn, (int) $user['id'], 'PASSWORD_CHANGED', 'user', (int) $user['id']);
            $message = 'Password updated successfully.';
            $user = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [(int) $user['id']]);
        }
    }
}

shell_start('SentryLink | Reset Password', $user, 'student', 'settings', 'Reset Password', 'Use a strong password that meets the current policy.');
?>
<div class="panel">
    <?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="POST" class="row g-3">
        <div class="col-12">
            <label class="form-label">Current Password</label>
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="current_password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">New Password</label>
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="new_password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm New Password</label>
            <div class="password-field">
                <input type="password" class="form-control js-password-input" name="confirm_password" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span aria-hidden="true">&#128065;</span>
                </button>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Update Password</button>
        </div>
    </form>
</div>
<?php shell_end(); ?>
