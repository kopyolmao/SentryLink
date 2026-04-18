<?php shell_start('SentryLink | Account', $user, 'student', 'account', 'Account', 'Keep your student details current for gate validation and reports.'); ?>
<?php
$profilePhotoPath = trim((string) ($user['profile_photo'] ?? ''));
$profilePhotoUrl = '';
if ($profilePhotoPath !== '') {
    $profilePhotoUrl = preg_match('/^https?:\/\//i', $profilePhotoPath) === 1
        ? $profilePhotoPath
        : app_url(ltrim($profilePhotoPath, '/'));
}
$profileInitial = strtoupper(substr(trim((string) ($user['first_name'] ?? 'S')), 0, 1));
?>
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

.profile-media {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.profile-avatar,
.profile-avatar-fallback {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.12);
    flex-shrink: 0;
}

.profile-avatar {
    object-fit: cover;
}

.profile-avatar-fallback {
    display: grid;
    place-items: center;
    font-size: 1.8rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.05);
    color: var(--muted);
}

.profile-media-copy {
    flex: 1 1 260px;
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
    <?php if (($error ?? '') !== ''): ?><div class="alert alert-danger"><?= h((string) $error) ?></div><?php endif; ?>
    <div class="profile-copy">Student ID and bound email are controlled by the system and cannot be edited here.</div>
    <form method="POST" enctype="multipart/form-data" class="profile-form" id="studentProfileForm">
        <div class="profile-field span-12">
            <div class="profile-media">
                <?php if ($profilePhotoUrl !== ''): ?>
                    <img class="profile-avatar" src="<?= h($profilePhotoUrl) ?>" alt="Profile photo">
                <?php else: ?>
                    <div class="profile-avatar-fallback" aria-hidden="true"><?= h($profileInitial !== '' ? $profileInitial : 'S') ?></div>
                <?php endif; ?>
                <div class="profile-media-copy">
                    <label class="form-label" for="profile_photo">Profile Photo</label>
                    <input class="form-control" id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                    <small class="text-secondary">JPG, PNG, or WEBP. Max file size: 2MB.</small>
                </div>
            </div>
        </div>

        <div class="profile-field span-6">
            <label class="form-label">First Name</label>
            <input class="form-control js-profile-track" name="first_name" value="<?= h($user['first_name']) ?>" maxlength="50" pattern="[A-Za-z][A-Za-z .'-]{0,49}" title="Use 1-50 letters with spaces, apostrophes, dots, or hyphens." required>
        </div>

        <div class="profile-field span-6">
            <label class="form-label">Last Name</label>
            <input class="form-control js-profile-track" name="last_name" value="<?= h($user['last_name']) ?>" maxlength="50" pattern="[A-Za-z][A-Za-z .'-]{0,49}" title="Use 1-50 letters with spaces, apostrophes, dots, or hyphens." required>
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
            <input class="form-control js-profile-track" name="course" value="<?= h($user['course']) ?>" maxlength="100">
        </div>

        <div class="profile-field span-4">
            <label class="form-label">Year Level</label>
            <input class="form-control js-profile-track" name="year_level" value="<?= h($user['year_level']) ?>" maxlength="50">
        </div>

        <div class="profile-field span-4">
            <label class="form-label">House</label>
            <input class="form-control js-profile-track" name="house" value="<?= h($user['house']) ?>" maxlength="100">
        </div>

        <div class="profile-field span-12">
            <div class="profile-actions">
                <button class="btn btn-primary" id="saveProfileBtn" disabled>Save Profile</button>
                <a class="btn btn-outline-light" href="<?= h(app_url('s/settings/reset-password')) ?>">Change Password</a>
            </div>
        </div>
    </form>
</div>
<?php
$script = '<script>
const studentProfileForm = document.getElementById("studentProfileForm");
const saveProfileBtn = document.getElementById("saveProfileBtn");
const profileFields = studentProfileForm ? studentProfileForm.querySelectorAll(".js-profile-track") : [];
const profilePhotoInput = document.getElementById("profile_photo");
const initialProfileValues = {};

if (studentProfileForm && saveProfileBtn) {
    profileFields.forEach((field) => {
        initialProfileValues[field.name] = field.value;
    });

    const updateSaveState = () => {
        const textChanged = Array.from(profileFields).some((field) => field.value !== (initialProfileValues[field.name] || ""));
        const photoChanged = !!(profilePhotoInput && profilePhotoInput.files && profilePhotoInput.files.length > 0);
        saveProfileBtn.disabled = !(textChanged || photoChanged);
    };

    profileFields.forEach((field) => {
        field.addEventListener("input", updateSaveState);
        field.addEventListener("change", updateSaveState);
    });

    if (profilePhotoInput) {
        profilePhotoInput.addEventListener("change", updateSaveState);
    }

    updateSaveState();
}
</script>';

shell_end($script);
?>
