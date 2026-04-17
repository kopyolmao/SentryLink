<?php
require_once __DIR__ . '/includes/app.php';

redirect_if_authenticated();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $result = send_role_login($email, $password, ['student']);

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
<title>SentryLink | Student Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #07111f; color: #eaf2ff; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; }
.wrap { width: min(920px, calc(100% - 24px)); display: grid; grid-template-columns: 1.1fr 0.9fr; background: #0d1a2b; border: 1px solid #1f3555; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,0.35); }
.hero { padding: 48px; background: linear-gradient(160deg, #0a1630 0%, #123360 100%); }
.hero h1 { font-size: 44px; margin-bottom: 16px; }
.hero p { color: #b6cae8; max-width: 420px; line-height: 1.6; }
.panel { padding: 42px; background: #f5f8fc; color: #10213a; }
.badge-role { display: inline-block; background: #dce9ff; color: #1e4e8c; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 18px; }
.form-control { padding: 12px 14px; border-radius: 12px; border: 1px solid #cbd8ea; }
.form-control:focus { border-color: #1f66d1; box-shadow: 0 0 0 0.2rem rgba(31,102,209,0.15); }
.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 54px; }
.password-toggle { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); border: 0; background: transparent; color: #5c6f8d; font-size: 18px; line-height: 1; padding: 0; }
.password-toggle:hover { color: #1f66d1; }
.btn-primary { background: #1f66d1; border: none; border-radius: 12px; padding: 12px 14px; font-weight: 700; }
.btn-primary:hover { background: #1856af; }
.small-link { color: #1f66d1; text-decoration: none; }
.small-link:hover { text-decoration: underline; }
.other-links a { color: #5c6f8d; text-decoration: none; display: inline-block; margin-right: 12px; }
.other-links a:hover { color: #1f66d1; }
@media (max-width: 768px) {
    .wrap { grid-template-columns: 1fr; }
    .hero { display: none; }
}
</style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <div class="badge-role">ACLC Mandaue</div>
        <h1>SentryLink</h1>
        <p>Student access for event tickets, live QR refresh, and attendance history. This preserves the current project flow while aligning the routing with the new plan.</p>
    </section>
    <section class="panel">
        <div class="badge-role">Student Login</div>
        <h2 class="mb-3">Sign in</h2>
        <p class="text-muted mb-4">Use your registered student email and password.</p>

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
            <button type="submit" class="btn btn-primary w-100">Open Student Portal</button>
        </form>

        <div class="mt-3">
            <a class="small-link" href="<?= h(app_url('s/auth/forgot-password')) ?>">Forgot password?</a>
        </div>

        <div class="other-links mt-4">
            <a href="<?= h(app_url('o/auth/login')) ?>">Officer Login</a>
            <a href="<?= h(app_url('admin/auth/login')) ?>">Admin Login</a>
            <a href="<?= h(app_url('director/auth/login')) ?>">Director Login</a>
        </div>
    </section>
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
