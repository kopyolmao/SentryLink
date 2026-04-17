<?php shell_start('SentryLink | Account', $user, 'student', 'account', 'Account', 'Keep your student details current for gate validation and reports.'); ?>
<style>
.profile-panel {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.profile-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}

.profile-field {
    min-width: 0;
}

.profile-field input {
    width: 100%;
}

.profile-copy {
    color: var(--muted);
    margin-top: -0.25rem;
    margin-bottom: 0.25rem;
}

.profile-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.profile-actions .btn {
    min-width: 220px;
    justify-content: center;
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }

@media (max-width: 900px) {
    .span-6,
    .span-4 {
        grid-column: span 6;
    }
}

@media (max-width: 640px) {
    .profile-form {
        grid-template-columns: 1fr;
    }

    .span-12,
    .span-6,
    .span-4 {
        grid-column: auto;
    }

    .profile-actions .btn {
        width: 100%;
    }
}
</style>
<div class="panel profile-panel">
    <?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <div class="profile-copy">Student ID and bound email are controlled by the system and cannot be edited here.</div>
    <form method="POST" class="profile-form">
        <div class="profile-field span-6">
            <label class="form-label">First Name</label>
            <input class="form-control" name="first_name" value="<?= h($user['first_name']) ?>" required>
        </div>

        <div class="profile-field span-6">
            <label class="form-label">Last Name</label>
            <input class="form-control" name="last_name" value="<?= h($user['last_name']) ?>" required>
        </div>

        <div class="profile-field span-6">
            <label class="form-label">Student ID</label>
            <input class="form-control" value="<?= h($user['student_id']) ?>" disabled>
        </div>

        <div class="profile-field span-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?= h($user['email']) ?>" disabled>
        </div>

        <div class="profile-field span-4">
            <label class="form-label">Course</label>
            <input class="form-control" name="course" value="<?= h($user['course']) ?>">
        </div>

        <div class="profile-field span-4">
            <label class="form-label">Year Level</label>
            <input class="form-control" name="year_level" value="<?= h($user['year_level']) ?>">
        </div>

        <div class="profile-field span-4">
            <label class="form-label">House</label>
            <input class="form-control" name="house" value="<?= h($user['house']) ?>">
        </div>

        <div class="profile-field span-12">
            <div class="profile-actions">
                <button class="btn btn-primary">Save Profile</button>
                <a class="btn btn-outline-light" href="<?= h(app_url('s/settings/reset-password')) ?>">Change Password</a>
            </div>
        </div>
    </form>
</div>
<?php shell_end(); ?>
