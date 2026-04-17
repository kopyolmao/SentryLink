<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $yearLevel = trim($_POST['year_level'] ?? '');
    $house = trim($_POST['house'] ?? '');

    db_execute(
        $conn,
        'UPDATE users SET first_name = ?, last_name = ?, course = ?, year_level = ?, house = ?, updated_at = NOW() WHERE id = ?',
        'sssssi',
        [$firstName, $lastName, $course, $yearLevel, $house, (int) $user['id']]
    );

    audit_log($conn, (int) $user['id'], 'PROFILE_UPDATED', 'user', (int) $user['id']);
    $user = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [(int) $user['id']]);
    $message = 'Profile updated.';
}

shell_start('SentryLink | Profile', $user, 'student', 'profile', 'Profile', 'Keep your student details current for gate validation and reports.');
?>
<div class="panel">
    <?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input class="form-control" name="first_name" value="<?= h($user['first_name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Last Name</label>
            <input class="form-control" name="last_name" value="<?= h($user['last_name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Student ID</label>
            <input class="form-control" value="<?= h($user['student_id']) ?>" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?= h($user['email']) ?>" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Course</label>
            <input class="form-control" name="course" value="<?= h($user['course']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Year Level</label>
            <input class="form-control" name="year_level" value="<?= h($user['year_level']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">House</label>
            <input class="form-control" name="house" value="<?= h($user['house']) ?>">
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Save Profile</button>
        </div>
    </form>
</div>
<?php shell_end(); ?>
