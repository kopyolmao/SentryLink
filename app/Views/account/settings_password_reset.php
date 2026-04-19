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

.password-reset-modal[hidden] {
    display: none !important;
}

.password-reset-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.password-reset-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(5, 8, 24, 0.76);
    backdrop-filter: blur(6px);
}

.password-reset-modal-dialog {
    position: relative;
    width: min(100%, 500px);
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: 28px;
    padding: 1.35rem;
    box-shadow: var(--shadow);
}

.password-reset-modal-copy {
    color: var(--muted);
    margin-bottom: 1rem;
}

.password-reset-captcha-wrap {
    border-radius: 20px;
    border: 1px solid var(--border);
    background: rgba(255, 255, 255, 0.04);
    padding: 0.8rem;
    margin-bottom: 0.9rem;
}

.password-reset-captcha-image {
    display: block;
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
    border-radius: 14px;
    object-fit: contain;
}

.password-reset-captcha-tools {
    display: grid;
    gap: 0.45rem;
    margin-top: 0.75rem;
}

.password-reset-captcha-refresh {
    width: fit-content;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--accent);
    font-weight: 600;
    font-size: 0.82rem;
}

.password-reset-captcha-refresh-status {
    color: var(--muted);
    font-size: 0.78rem;
}

.password-reset-modal-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
}

@media (max-width: 640px) {
    .password-reset-form {
        justify-items: stretch;
    }

    .password-reset-submit {
        width: 100%;
        min-width: 0;
    }

    .password-reset-modal-actions {
        flex-direction: column;
    }

    .password-reset-modal-actions .btn {
        width: 100%;
    }
}
</style>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php
$hasVerifiedEmail = (int) ($user['email_verified'] ?? 0) === 1 && trim((string) ($user['email'] ?? '')) !== '';
?>
<div class="panel password-reset-panel">
    <div class="password-reset-copy">
        <h3 class="h5">Password Reset via Verified Email</h3>
        <p class="text-secondary">Request a generated password and SentryLink will send it to the verified email currently bound to this account.</p>
    </div>

    <div class="password-reset-status">
        <span class="badge text-bg-<?= (int) ($user['email_verified'] ?? 0) === 1 ? 'success' : 'warning' ?>">
            <?= (int) ($user['email_verified'] ?? 0) === 1 ? 'Email Verified' : 'Email Not Verified' ?>
        </span>
        <div class="text-secondary"><strong>Bound Email:</strong> <?= h((string) ($user['email'] ?? 'Not set')) ?></div>
    </div>

    <form method="POST" class="password-reset-form">
        <button type="button" class="btn btn-primary password-reset-submit" id="openPasswordResetModal" <?= $hasVerifiedEmail ? '' : 'disabled' ?>>Change Password</button>
        <?php if (! $hasVerifiedEmail): ?>
            <small class="text-secondary">Verify and bind your account email first before requesting a password reset.</small>
        <?php endif; ?>
    </form>
</div>
<div class="password-reset-modal" id="passwordResetModal" hidden>
    <div class="password-reset-modal-backdrop" data-close-password-reset-modal></div>
    <div class="password-reset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="passwordResetModalTitle" aria-describedby="passwordResetModalDescription">
        <h3 class="h5 mb-2" id="passwordResetModalTitle">Confirm Password Reset</h3>
        <p class="password-reset-modal-copy" id="passwordResetModalDescription">Complete the captcha to continue. A new password will be generated and sent to your verified email.</p>
        <form method="POST">
            <input type="hidden" name="request_password_reset" value="1">
            <input type="hidden" name="reset_captcha_token" id="resetCaptchaToken" value="<?= h((string) ($passwordResetCaptchaToken ?? '')) ?>">
            <div class="password-reset-captcha-wrap">
                <img src="<?= h((string) ($passwordResetCaptchaImage ?? '')) ?>" alt="Password reset captcha challenge" class="password-reset-captcha-image" id="resetCaptchaImage">
                <div class="password-reset-captcha-tools">
                    <input type="text" name="reset_captcha_answer" id="resetCaptchaAnswer" maxlength="10" autocomplete="off" class="form-control text-uppercase" placeholder="Enter captcha text" required>
                    <button type="button" class="password-reset-captcha-refresh" id="refreshResetCaptchaButton">Refresh captcha</button>
                    <div class="password-reset-captcha-refresh-status" id="resetCaptchaRefreshStatus" hidden></div>
                </div>
            </div>
            <div class="password-reset-modal-actions">
                <button type="button" class="btn btn-outline-light" id="cancelPasswordResetModal" data-close-password-reset-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm & Send Password</button>
            </div>
        </form>
    </div>
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
<script>
const openPasswordResetModalButton = document.getElementById("openPasswordResetModal");
const passwordResetModal = document.getElementById("passwordResetModal");
const passwordResetModalCloseButtons = document.querySelectorAll("[data-close-password-reset-modal]");
const cancelPasswordResetModalButton = document.getElementById("cancelPasswordResetModal");
const refreshResetCaptchaButton = document.getElementById("refreshResetCaptchaButton");
const resetCaptchaImage = document.getElementById("resetCaptchaImage");
const resetCaptchaToken = document.getElementById("resetCaptchaToken");
const resetCaptchaAnswer = document.getElementById("resetCaptchaAnswer");
const resetCaptchaRefreshStatus = document.getElementById("resetCaptchaRefreshStatus");
const resetCaptchaRefreshUrl = <?= json_encode(app_url('auth/password-reset-captcha-refresh')) ?>;

function openPasswordResetModal() {
    if (!passwordResetModal) {
        return;
    }

    passwordResetModal.hidden = false;
    document.body.style.overflow = "hidden";
    if (resetCaptchaAnswer) {
        resetCaptchaAnswer.focus();
    }
}

function closePasswordResetModal() {
    if (!passwordResetModal || passwordResetModal.hidden) {
        return;
    }

    passwordResetModal.hidden = true;
    document.body.style.overflow = "";
}

if (openPasswordResetModalButton) {
    openPasswordResetModalButton.addEventListener("click", openPasswordResetModal);
}

passwordResetModalCloseButtons.forEach((button) => {
    button.addEventListener("click", closePasswordResetModal);
});

if (cancelPasswordResetModalButton) {
    cancelPasswordResetModalButton.addEventListener("click", closePasswordResetModal);
}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closePasswordResetModal();
    }
});

if (refreshResetCaptchaButton && resetCaptchaRefreshUrl !== "") {
    refreshResetCaptchaButton.addEventListener("click", async () => {
        refreshResetCaptchaButton.disabled = true;
        if (resetCaptchaRefreshStatus) {
            resetCaptchaRefreshStatus.hidden = true;
            resetCaptchaRefreshStatus.textContent = "";
        }

        try {
            const response = await fetch(resetCaptchaRefreshUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: { Accept: "application/json" },
            });

            const data = await response.json();
            if (!response.ok || !data.ok || data.mode !== "local_image_text") {
                throw new Error(data.message || "Failed to refresh captcha.");
            }

            if (resetCaptchaImage && typeof data.image === "string" && data.image !== "") {
                resetCaptchaImage.src = data.image;
            }

            if (resetCaptchaToken && typeof data.token === "string" && data.token !== "") {
                resetCaptchaToken.value = data.token;
            }

            if (resetCaptchaAnswer) {
                resetCaptchaAnswer.value = "";
                resetCaptchaAnswer.focus();
            }
        } catch (error) {
            if (resetCaptchaRefreshStatus) {
                resetCaptchaRefreshStatus.hidden = false;
                resetCaptchaRefreshStatus.textContent = (error && error.message) ? error.message : "Captcha refresh failed.";
            }
        } finally {
            refreshResetCaptchaButton.disabled = false;
        }
    });
}
</script>
<?php shell_end(); ?>
