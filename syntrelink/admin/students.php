<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {
    $password = $_POST['password'] ?? 'Password123!';
    $policyErrors = password_policy_errors($password);

    if ($policyErrors) {
        $error = implode(' ', $policyErrors);
    } else {
        try {
            db_execute(
                $conn,
                "INSERT INTO users (student_id, first_name, last_name, email, password_hash, role, house, year_level, course, email_verified, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, 0, NOW(), NOW())",
                'ssssssss',
                [
                    trim($_POST['student_id'] ?? ''),
                    trim($_POST['first_name'] ?? ''),
                    trim($_POST['last_name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    password_hash($password, PASSWORD_BCRYPT),
                    trim($_POST['house'] ?? ''),
                    trim($_POST['year_level'] ?? ''),
                    trim($_POST['course'] ?? ''),
                ]
            );
            $message = 'Student account created.';
            audit_log($conn, (int) $user['id'], 'STUDENT_CREATED', 'user', (int) $conn->insert_id);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$students = db_fetch_all($conn, "SELECT * FROM users WHERE role = 'student' AND deleted_at IS NULL ORDER BY last_name ASC, first_name ASC");

shell_start('SentryLink | Students', $user, 'admin', 'students', 'Student Management', 'Add or review student accounts.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="panel">
    <h3 class="h5 mb-3">Create Student Account</h3>
    <form method="POST" class="row g-3">
        <div class="col-md-3"><input class="form-control" name="student_id" placeholder="Student ID" required></div>
        <div class="col-md-3"><input class="form-control" name="first_name" placeholder="First Name" required></div>
        <div class="col-md-3"><input class="form-control" name="last_name" placeholder="Last Name" required></div>
        <div class="col-md-3"><input class="form-control" name="email" placeholder="Email" required></div>
        <div class="col-md-3"><input class="form-control" name="course" placeholder="Course"></div>
        <div class="col-md-3"><input class="form-control" name="year_level" placeholder="Year Level"></div>
        <div class="col-md-3"><input class="form-control" name="house" placeholder="House"></div>
        <div class="col-md-3"><input type="password" class="form-control" name="password" value="Password123!" required></div>
        <div class="col-12"><button class="btn btn-primary" name="create_student" value="1">Create Student</button></div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Course / Year</th><th>Email</th><th>House</th><th>Email Verified</th></tr></thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= h($student['last_name'] . ', ' . $student['first_name']) ?><br><small class="text-secondary"><?= h($student['student_id']) ?></small></td>
                    <td><?= h(($student['course'] ?: '-') . ' / ' . ($student['year_level'] ?: '-')) ?></td>
                    <td><?= h($student['email']) ?></td>
                    <td><?= h($student['house']) ?></td>
                    <td><span class="badge text-bg-<?= (int) $student['email_verified'] === 1 ? 'success' : 'warning' ?>"><?= (int) $student['email_verified'] === 1 ? 'Verified' : 'Pending' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
