<?php
require_once __DIR__ . '/includes/app.php';

redirect_if_authenticated();

$isDirectorLogin = isset($_GET['director']);
$allowedRoles = $isDirectorLogin ? ['director'] : ['admin'];
$roleLabel = $isDirectorLogin ? 'School Director Login' : 'Admin Login';
$accent = $isDirectorLogin ? '#0f8b8d' : '#9d4edd';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $result = send_role_login($email, $password, $allowedRoles);

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
<title>SentryLink | <?= h($roleLabel) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #090611; color: #f3ecff; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; }
.card-shell { width: min(460px, calc(100% - 24px)); background: #130d21; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 34px; box-shadow: 0 24px 72px rgba(0,0,0,0.45); }
.role-chip { display: inline-block; background: rgba(255,255,255,0.06); color: <?= $accent ?>; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 14px; }
.brand { font-size: 34px; font-weight: 800; margin-bottom: 6px; }
.brand span { color: <?= $accent ?>; }
.form-control { background: #1d1730; color: #fff; border: 1px solid #3a3154; border-radius: 12px; padding: 12px 14px; }
.form-control:focus { background: #1d1730; color: #fff; border-color: <?= $accent ?>; box-shadow: 0 0 0 0.2rem rgba(157,78,221,0.15); }
.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 54px; }
.password-toggle { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); border: 0; background: transparent; color: #baa8df; font-size: 18px; line-height: 1; padding: 0; }
.password-toggle:hover { color: #fff; }
.btn-primary { background: <?= $accent ?>; border: none; border-radius: 12px; padding: 12px 14px; font-weight: 700; }
.btn-primary:hover { filter: brightness(0.95); }
.links a { color: #baa8df; text-decoration: none; margin-right: 12px; }
.links a:hover { color: #fff; }
</style>
</head>
<body>
<div class="card-shell">
    <div class="role-chip"><?= h($roleLabel) ?></div>
    <div class="brand">Sentry<span>Link</span></div>
    <p class="text-secondary mb-4"><?= $isDirectorLogin ? 'View-only leadership access.' : 'Administrative control panel access.' ?></p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="password-wrap">
                <input type="password" name="password" class="form-control js-password-input" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">👁</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100"><?= $isDirectorLogin ? 'Open Director Portal' : 'Open Admin Portal' ?></button>
    </form>

    <div class="mt-3">
        <a href="<?= h(app_url($isDirectorLogin ? 'director/auth/forgot-password' : 'admin/auth/forgot-password')) ?>" style="color: <?= h($accent) ?>; text-decoration: none;">Forgot password?</a>
    </div>

    <div class="links mt-4">
        <a href="<?= h(app_url('s/auth/login')) ?>">Student</a>
        <a href="<?= h(app_url('o/auth/login')) ?>">Officer</a>
        <?php if ($isDirectorLogin): ?>
            <a href="<?= h(app_url('admin/auth/login')) ?>">Admin</a>
        <?php else: ?>
            <a href="<?= h(app_url('director/auth/login')) ?>">Director</a>
        <?php endif; ?>
    </div>
</div>
<script>
document.querySelectorAll(".js-password-toggle").forEach((toggle) => {
    toggle.addEventListener("click", () => {
        const input = toggle.parentElement.querySelector(".js-password-input");
        const isVisible = input.type === "text";
        input.type = isVisible ? "password" : "text";
        toggle.setAttribute("aria-label", isVisible ? "Show password" : "Hide password");
        toggle.setAttribute("aria-pressed", isVisible ? "false" : "true");
        toggle.textContent = isVisible ? "👁" : "🙈";
    });
});
</script>
</body>
</html>
