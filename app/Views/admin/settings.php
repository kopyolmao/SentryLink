<?php shell_start('SentryLink | Settings', $user, 'admin', 'settings', 'Settings', 'Security settings available in the current build.'); ?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-3">Change Admin Password</h3>
    <form method="POST" class="row g-3">
        <div class="col-12"><input type="password" class="form-control" name="current_password" placeholder="Current password" required></div>
        <div class="col-md-6"><input type="password" class="form-control" name="new_password" placeholder="New password" required></div>
        <div class="col-md-6"><input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required></div>
        <div class="col-12"><button class="btn btn-primary">Update Password</button></div>
    </form>
</div>
<div class="panel">
    <h3 class="h5 mb-3">Current Runtime Settings</h3>
    <ul class="list-soft">
        <li>QR grace window: 5 seconds</li>
        <li>Student QR refresh interval: 10 seconds</li>
        <li>Authentication roles enabled: student, officer, admin, director, cashier</li>
        <li>Base URL is auto-detected so the app works on the local network.</li>
    </ul>
</div>
<?php shell_end(); ?>
