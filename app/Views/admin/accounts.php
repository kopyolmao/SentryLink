<?php shell_start('SentryLink | Accounts', $user, 'admin', 'admins', 'Admin and Officer Accounts', 'Create or activate staff accounts.'); ?>
<style>
.staff-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.staff-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}

.staff-field {
    min-width: 0;
}

.staff-field input,
.staff-field select {
    width: 100%;
}

.staff-field label {
    display: block;
    margin-bottom: 0.45rem;
}

.staff-copy {
    color: var(--muted);
    margin-top: -0.25rem;
    margin-bottom: 0.2rem;
}

.staff-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.staff-actions .btn {
    min-width: 220px;
    justify-content: center;
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }
.span-3 { grid-column: span 3; }
.span-2 { grid-column: span 2; }

@media (max-width: 1000px) {
    .span-6,
    .span-4,
    .span-3,
    .span-2 {
        grid-column: span 6;
    }
}

@media (max-width: 640px) {
    .staff-form {
        grid-template-columns: 1fr;
    }

    .span-12,
    .span-6,
    .span-4,
    .span-3,
    .span-2 {
        grid-column: auto;
    }

    .staff-actions .btn {
        width: 100%;
    }
}
</style>
<div class="staff-layout">
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="panel">
    <h3 class="h5 mb-2">Create Staff Account</h3>
    <div class="staff-copy">Create an officer or admin account with the assigned ID, email, and a temporary password.</div>
    <form method="POST" class="staff-form">
        <div class="staff-field span-3">
            <label class="form-label">Account ID</label>
            <input class="form-control" name="account_id" placeholder="ID" value="<?= h(old('account_id')) ?>" required>
        </div>
        <div class="staff-field span-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
                <option value="ssg" <?= old('role') === 'ssg' || old('role') === '' ? 'selected' : '' ?>>Officer</option>
                <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="staff-field span-6">
            <label class="form-label">Temporary Password</label>
            <div class="password-field">
                <input class="form-control js-password-input" type="password" name="password" value="<?= h(old('password') !== '' ? old('password') : 'Password123!') ?>" required>
                <button type="button" class="password-toggle js-password-toggle" aria-label="Show password" aria-pressed="false">
                    <span class="material-symbols-outlined js-password-icon" aria-hidden="true">visibility</span>
                </button>
            </div>
        </div>
        <div class="staff-field span-3">
            <label class="form-label">First Name</label>
            <input class="form-control" name="first_name" placeholder="First Name" value="<?= h(old('first_name')) ?>" required>
        </div>
        <div class="staff-field span-3">
            <label class="form-label">Last Name</label>
            <input class="form-control" name="last_name" placeholder="Last Name" value="<?= h(old('last_name')) ?>" required>
        </div>
        <div class="staff-field span-6">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" placeholder="Email" value="<?= h(old('email')) ?>" required>
        </div>
        <div class="staff-field span-12">
            <div class="staff-actions">
                <button class="btn btn-primary" name="create_account" value="1">Create Account</button>
            </div>
        </div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?= h($account['first_name'] . ' ' . $account['last_name']) ?><br><small class="text-secondary"><?= h($account['student_id']) ?></small></td>
                    <td><?= h($account['email']) ?></td>
                    <td><?= h(ucfirst($account['role'])) ?></td>
                    <td><span class="badge text-bg-<?= (int) $account['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $account['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                    <td><form method="POST"><input type="hidden" name="target_id" value="<?= $account['id'] ?>"><button class="btn btn-outline-light btn-sm" name="toggle_active" value="1"><?= (int) $account['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<?php
$script = '<script>
document.querySelectorAll(".js-password-toggle").forEach((button) => {
    const input = button.closest(".password-field")?.querySelector(".js-password-input");
    if (!input) {
        return;
    }

    button.addEventListener("click", () => {
        const isVisible = input.type === "text";
        input.type = isVisible ? "password" : "text";
        button.setAttribute("aria-pressed", isVisible ? "false" : "true");
        button.setAttribute("aria-label", isVisible ? "Show password" : "Hide password");
        const icon = button.querySelector(".js-password-icon");
        if (icon) {
            icon.textContent = isVisible ? "visibility" : "visibility_off";
        }
    });
});
</script>';

shell_end($script);
?>
