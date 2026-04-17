<?php shell_start('SentryLink | Settings', $user, 'student', 'settings', 'Settings', 'Security and account controls.'); ?>
<div class="panel">
    <h3 class="h5">Security</h3>
    <p class="text-secondary">Password changes are available here. Email verification status is also shown for support checks.</p>
    <div class="mb-3">
        <span class="badge text-bg-<?= (int) $user['email_verified'] === 1 ? 'success' : 'warning' ?>">
            <?= (int) $user['email_verified'] === 1 ? 'Email Verified' : 'Email Not Verified' ?>
        </span>
    </div>
    <a class="btn btn-primary" href="<?= h(app_url('s/settings/reset-password')) ?>">Change Password</a>
</div>
<?php shell_end(); ?>
