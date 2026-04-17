<?php
require_once __DIR__ . '/includes/app.php';

$role = $_GET['role'] ?? 'student';
$roleConfig = [
    'student' => ['label' => 'Student', 'accent' => '#1f66d1'],
    'ssg' => ['label' => 'Officer', 'accent' => '#0f8b8d'],
    'admin' => ['label' => 'Admin', 'accent' => '#9d4edd'],
    'director' => ['label' => 'Director', 'accent' => '#0f8b8d'],
];

if (!isset($roleConfig[$role])) {
    $role = 'student';
}

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $user = db_fetch_one(
        $conn,
        'SELECT * FROM users WHERE email = ? AND role = ? AND deleted_at IS NULL LIMIT 1',
        'ss',
        [$email, $role]
    );

    if (!$user) {
        $message = 'No matching account was found for that email and role.';
        $status = 'danger';
    } else {
        $temporaryPassword = 'Tmp!' . random_int(100000, 999999);
        $passwordHash = password_hash($temporaryPassword, PASSWORD_BCRYPT);

        db_execute(
            $conn,
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            'si',
            [$passwordHash, (int) $user['id']]
        );

        audit_log($conn, (int) $user['id'], 'PASSWORD_RESET_ISSUED', 'user', (int) $user['id']);

        $message = 'Temporary password generated: <strong>' . h($temporaryPassword) . '</strong>. Use it to sign in, then change it immediately in settings.';
        $status = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { min-height: 100vh; display: grid; place-items: center; background: #0b1020; font-family: Arial, Helvetica, sans-serif; }
.card-shell { width: min(480px, calc(100% - 24px)); border-radius: 24px; background: #fff; padding: 34px; box-shadow: 0 28px 80px rgba(0,0,0,0.2); }
.chip { display: inline-block; border-radius: 999px; background: #eef3ff; color: <?= $roleConfig[$role]['accent'] ?>; padding: 6px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px; }
.btn-primary { background: <?= $roleConfig[$role]['accent'] ?>; border: none; border-radius: 12px; padding: 12px 14px; font-weight: 700; }
.btn-primary:hover { filter: brightness(0.95); }
.form-control { border-radius: 12px; padding: 12px 14px; }
</style>
</head>
<body>
<div class="card-shell">
    <div class="chip"><?= h($roleConfig[$role]['label']) ?> Reset</div>
    <h2 class="mb-3">Forgot password</h2>
    <p class="text-muted mb-4">Enter the email registered to your <?= h(strtolower($roleConfig[$role]['label'])) ?> account.</p>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= h($status) ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Generate Temporary Password</button>
    </form>

    <div class="mt-3">
        <a href="<?= h(app_url(role_login($role))) ?>" style="text-decoration: none;">Back to login</a>
    </div>
</div>
</body>
</html>
