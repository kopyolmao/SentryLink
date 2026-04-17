<?php shell_start('SentryLink | Reset Password', $user, 'student', 'settings', 'Reset Password', 'Use a strong password that meets the current policy.'); ?>
<div class="panel">
    <?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="POST" class="row g-3">
        <div class="col-12"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
        <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
        <div class="col-md-6"><label class="form-label">Confirm New Password</label><input type="password" class="form-control" name="confirm_password" required></div>
        <div class="col-12"><button class="btn btn-primary">Update Password</button></div>
    </form>
</div>
<?php shell_end(); ?>
