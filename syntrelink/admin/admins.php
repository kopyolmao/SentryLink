<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['admin']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_account'])) {
            $password = $_POST['password'] ?? 'Password123!';
            $policyErrors = password_policy_errors($password);
            if ($policyErrors) {
                throw new RuntimeException(implode(' ', $policyErrors));
            }

            db_execute(
                $conn,
                "INSERT INTO users (student_id, first_name, last_name, email, password_hash, role, email_verified, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
                'ssssss',
                [
                    trim($_POST['account_id'] ?? ''),
                    trim($_POST['first_name'] ?? ''),
                    trim($_POST['last_name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    password_hash($password, PASSWORD_BCRYPT),
                    $_POST['role'] ?? 'ssg',
                ]
            );
            audit_log($conn, (int) $user['id'], 'STAFF_ACCOUNT_CREATED', 'user', (int) $conn->insert_id);
            $message = 'Account created.';
        }

        if (isset($_POST['toggle_active'])) {
            $targetId = (int) $_POST['target_id'];
            $target = db_fetch_one($conn, 'SELECT is_active FROM users WHERE id = ?', 'i', [$targetId]);
            $newState = (int) $target['is_active'] === 1 ? 0 : 1;
            db_execute($conn, 'UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', 'ii', [$newState, $targetId]);
            audit_log($conn, (int) $user['id'], 'STAFF_ACCOUNT_TOGGLED', 'user', $targetId);
            $message = 'Account status updated.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$accounts = db_fetch_all($conn, "SELECT * FROM users WHERE role IN ('admin','ssg') AND deleted_at IS NULL ORDER BY role ASC, last_name ASC");

shell_start('SentryLink | Accounts', $user, 'admin', 'admins', 'Admin and Officer Accounts', 'Create or activate staff accounts.');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="panel">
    <h3 class="h5 mb-3">Create Staff Account</h3>
    <form method="POST" class="row g-3">
        <div class="col-md-2"><input class="form-control" name="account_id" placeholder="ID" required></div>
        <div class="col-md-2"><input class="form-control" name="first_name" placeholder="First Name" required></div>
        <div class="col-md-2"><input class="form-control" name="last_name" placeholder="Last Name" required></div>
        <div class="col-md-3"><input class="form-control" name="email" placeholder="Email" required></div>
        <div class="col-md-1">
            <select class="form-select" name="role">
                <option value="ssg">Officer</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" type="password" name="password" value="Password123!" required></div>
        <div class="col-12"><button class="btn btn-primary" name="create_account" value="1">Create Account</button></div>
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
                    <td>
                        <form method="POST">
                            <input type="hidden" name="target_id" value="<?= $account['id'] ?>">
                            <button class="btn btn-outline-light btn-sm" name="toggle_active" value="1"><?= (int) $account['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php shell_end(); ?>
