<?php
require_once __DIR__ . '/../includes/app.php';

redirect_if_authenticated();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $result = send_role_login($email, $password, ['ssg']);

    if ($result['ok']) {
        header('Location: ' . $result['redirect']);
        exit;
    }

    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SentryLink | Officer Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #05161b; color: #e8ffff; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; }
.wrap { width: min(900px, calc(100% - 24px)); display: grid; grid-template-columns: 1fr 1fr; border-radius: 24px; overflow: hidden; box-shadow: 0 28px 84px rgba(0,0,0,0.4); }
.left { padding: 48px; background: linear-gradient(150deg, #063d46 0%, #0e7774 100%); }
.left h1 { font-size: 42px; margin-bottom: 16px; }
.left p { color: rgba(255,255,255,0.85); line-height: 1.7; max-width: 380px; }
.right { padding: 40px; background: #f7fffe; color: #103539; }
.chip { display: inline-block; background: #d9fffb; color: #0f8b8d; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 16px; }
.form-control { border-radius: 12px; padding: 12px 14px; border: 1px solid #c4dddd; }
.form-control:focus { border-color: #0f8b8d; box-shadow: 0 0 0 0.2rem rgba(15,139,141,0.14); }
.btn-primary { background: #0f8b8d; border: none; border-radius: 12px; padding: 12px 14px; font-weight: 700; }
.btn-primary:hover { background: #0c7678; }
a { color: #0f8b8d; text-decoration: none; }
a:hover { text-decoration: underline; }
@media (max-width: 768px) {
    .wrap { grid-template-columns: 1fr; }
    .left { display: none; }
}
</style>
</head>
<body>
<div class="wrap">
    <section class="left">
        <div class="chip">Officer Terminal</div>
        <h1>Gate scanning and live admission control</h1>
        <p>Use this portal for QR validation, gate logs, and fallback attendee lookup during event operations.</p>
    </section>
    <section class="right">
        <div class="chip">SSG / SSC Login</div>
        <h2 class="mb-3">Officer access</h2>
        <p class="text-muted mb-4">Only officer accounts can enter this portal.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Officer Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Open Officer Portal</button>
        </form>

        <div class="mt-3">
            <a href="<?= h(app_url('o/auth/forgot-password')) ?>">Forgot password?</a>
        </div>
    </section>
</div>
