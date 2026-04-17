<?php shell_start($title, $user, $role, 'settings', 'Settings', $subtitle); ?>
<style>
.password-reset-panel {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.password-reset-copy {
    display: grid;
    gap: 0.6rem;
}

.password-reset-copy h3,
.password-reset-copy p {
    margin: 0;
}

.password-reset-status {
    display: grid;
    gap: 0.7rem;
    padding: 1rem 1.1rem;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.035);
}

.password-reset-status .badge {
    width: fit-content;
}

.password-reset-status strong {
    color: var(--text);
}

.password-reset-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
    justify-items: start;
}

.password-reset-field {
    display: grid;
    gap: 0.55rem;
    max-width: 640px;
}

.password-reset-field .form-control,
.password-reset-submit {
    min-height: 54px;
}

.password-reset-submit {
    width: auto;
    min-width: 260px;
    max-width: 100%;
    justify-content: center;
}

@media (max-width: 640px) {
    .password-reset-form {
        justify-items: stretch;
    }

    .password-reset-submit {
        width: 100%;
        min-width: 0;
    }
}
</style>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel password-reset-panel">
    <div class="password-reset-copy">
        <h3 class="h5">Password Reset via Verified Email</h3>
        <p class="text-secondary">Enter the verified email currently bound to this account. SentryLink will generate a new password and send it to that email.</p>
    </div>

    <div class="password-reset-status">
        <span class="badge text-bg-<?= (int) ($user['email_verified'] ?? 0) === 1 ? 'success' : 'warning' ?>">
            <?= (int) ($user['email_verified'] ?? 0) === 1 ? 'Email Verified' : 'Email Not Verified' ?>
        </span>
        <div class="text-secondary"><strong>Bound Email:</strong> <?= h((string) ($user['email'] ?? 'Not set')) ?></div>
    </div>

    <form method="POST" class="password-reset-form">
        <div class="password-reset-field">
            <label class="form-label" for="verified-email">Verified Email</label>
            <input id="verified-email" type="email" class="form-control" name="email" placeholder="Enter your bound email address" required>
        </div>
        <button class="btn btn-primary password-reset-submit" name="request_password_reset" value="1">Email My New Password</button>
    </form>
</div>
<?php if (! empty($showRuntimeSettings)): ?>
<div class="panel">
    <h3 class="h5 mb-3">Runtime Settings</h3>
    <?php if (! empty($runtimeMessage)): ?><div class="alert alert-success"><?= h((string) $runtimeMessage) ?></div><?php endif; ?>
    <?php if (! empty($runtimeError)): ?><div class="alert alert-danger"><?= h((string) $runtimeError) ?></div><?php endif; ?>
    <form method="POST" class="row g-3">
        <div class="col-12">
            <label class="form-label">QR Secret</label>
            <input class="form-control" name="qr_secret" value="<?= h((string) ($runtime['qr_secret'] ?? '')) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">QR Hold Seconds (5-8)</label>
            <input type="number" min="5" max="8" class="form-control" name="qr_hold_seconds" value="<?= h((string) ($runtime['qr_hold_seconds'] ?? 6)) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Offline Grace Seconds (15-20)</label>
            <input type="number" min="15" max="20" class="form-control" name="qr_offline_grace_seconds" value="<?= h((string) ($runtime['qr_offline_grace_seconds'] ?? 18)) ?>" required>
        </div>
        <div class="col-12">
            <button class="btn btn-outline-light" name="save_runtime_settings" value="1">Save Runtime Settings</button>
        </div>
    </form>
</div>
<?php endif; ?>
<?php shell_end(); ?>
