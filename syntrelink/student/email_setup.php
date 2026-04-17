<?php
require_once __DIR__ . '/../includes/app.php';

if (!isset($_SESSION['pending_user_id'])) {
    redirect_to('s/auth/login');
}
send_no_cache_headers();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_code'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $existing = db_fetch_one(
            $conn,
            'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1',
            'si',
            [$email, (int) $_SESSION['pending_user_id']]
        );

        if ($existing) {
            $error = 'That email is already assigned to another account.';
        } else {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['verification_email'] = $email;
            $_SESSION['verification_code'] = $code;
            $_SESSION['code_expiry'] = time() + 600;
            $success = 'Verification code generated: <strong>' . h($code) . '</strong>. This local build shows the code directly for testing.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $enteredCode = trim($_POST['code'] ?? '');

    if (!isset($_SESSION['verification_code'], $_SESSION['verification_email'], $_SESSION['code_expiry'])) {
        $error = 'Request a new verification code first.';
    } elseif (time() > (int) $_SESSION['code_expiry']) {
        $error = 'Verification code expired. Request a new one.';
        unset($_SESSION['verification_code'], $_SESSION['verification_email'], $_SESSION['code_expiry']);
    } elseif ($enteredCode !== $_SESSION['verification_code']) {
        $error = 'Invalid verification code.';
    } else {
        $userId = (int) $_SESSION['pending_user_id'];

        db_execute(
            $conn,
            'UPDATE users SET email = ?, email_verified = 1, updated_at = NOW() WHERE id = ?',
            'si',
            [$_SESSION['verification_email'], $userId]
        );

        $user = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$userId]);
        establish_user_session($user);

        unset(
            $_SESSION['pending_user_id'],
            $_SESSION['email_setup_required'],
            $_SESSION['verification_email'],
            $_SESSION['verification_code'],
            $_SESSION['code_expiry']
        );

        audit_log($conn, $userId, 'EMAIL_VERIFIED', 'user', $userId);
        redirect_to('s/dashboard');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Email Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { min-height: 100vh; display: grid; place-items: center; background: #07111f; font-family: Arial, Helvetica, sans-serif; }
.card-shell { width: min(480px, calc(100% - 24px)); border-radius: 24px; background: #ffffff; padding: 34px; box-shadow: 0 28px 80px rgba(0,0,0,0.22); }
.chip { display: inline-block; border-radius: 999px; background: #dce9ff; color: #1f66d1; padding: 6px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px; }
.form-control { border-radius: 12px; padding: 12px 14px; }
.btn-primary { background: #1f66d1; border: none; border-radius: 12px; padding: 12px 14px; font-weight: 700; }
</style>
</head>
<body>
<div class="card-shell">
    <div class="chip">First Login Setup</div>
    <h2 class="mb-3">Verify your email</h2>
    <p class="text-muted mb-4">Students must confirm an email address before entering the dashboard.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['verification_code'])): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" name="send_code" class="btn btn-primary w-100">Generate Verification Code</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" maxlength="6" class="form-control" required>
            </div>
            <button type="submit" name="verify_code" class="btn btn-primary w-100">Verify and Continue</button>
        </form>
    <?php endif; ?>
</div>
<script>
window.addEventListener("pageshow", (event) => {
    const navigationEntries = typeof performance.getEntriesByType === "function"
        ? performance.getEntriesByType("navigation")
        : [];
    const isBackForward = navigationEntries.length > 0 && navigationEntries[0].type === "back_forward";

    if (event.persisted || isBackForward) {
        window.location.reload();
    }
});
</script>
</body>
</html>
