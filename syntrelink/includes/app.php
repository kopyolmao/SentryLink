<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/db.php';

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', '/syntrelink');
}

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? APP_BASE_URL : APP_BASE_URL . '/' . $path;
}

function redirect_to(string $path = ''): void
{
    header('Location: ' . app_url($path));
    exit;
}

function send_no_cache_headers(): void
{
    header('Cache-Control: no-store, no-cache, max-age=0, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user_id(): ?int
{
    $user = current_authenticated_user();

    return $user ? (int) $user['id'] : null;
}

function current_user_role(): ?string
{
    $user = current_authenticated_user();

    return $user['role'] ?? null;
}

function role_home(string $role): string
{
    return match ($role) {
        'student' => 's/dashboard',
        'ssg' => 'o/dashboard',
        'admin' => 'admin/dashboard',
        'director' => 'director/dashboard',
        default => 'login.php',
    };
}

function role_login(string $role): string
{
    return match ($role) {
        'student' => 's/auth/login',
        'ssg' => 'o/auth/login',
        'admin' => 'admin/auth/login',
        'director' => 'director/auth/login',
        default => 'login.php',
    };
}

function redirect_if_authenticated(): void
{
    if (current_user_id() && current_user_role()) {
        redirect_to(role_home((string) current_user_role()));
    }
}

function require_role(array $roles): array
{
    $user = current_authenticated_user();

    if (!$user || !in_array((string) $user['role'], $roles, true)) {
        redirect_to(role_login($roles[0] ?? 'student'));
    }

    return $user;
}

function current_session_token(): ?string
{
    $token = $_SESSION['session_token'] ?? null;

    return is_string($token) && $token !== '' ? $token : null;
}

function current_authenticated_user(): ?array
{
    $userId = $_SESSION['user'] ?? null;
    $role = $_SESSION['role'] ?? null;
    $sessionToken = current_session_token();

    if ($userId === null || !is_string($role) || $role === '') {
        return null;
    }

    if ($sessionToken === null) {
        clear_auth_session();

        return null;
    }

    global $conn;

    $user = db_fetch_one(
        $conn,
        'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL AND is_active = 1',
        'i',
        [(int) $userId]
    );

    if (!$user) {
        clear_auth_session();

        return null;
    }

    $dbToken = trim((string) ($user['session_token'] ?? ''));
    if ($dbToken === '' || !hash_equals($dbToken, $sessionToken) || (string) $user['role'] !== $role) {
        clear_auth_session();

        return null;
    }

    $_SESSION['user_data'] = $user;

    return $user;
}

function generate_session_token(): string
{
    return bin2hex(random_bytes(32));
}

function establish_user_session(array $user): void
{
    global $conn;

    $token = generate_session_token();
    db_execute(
        $conn,
        'UPDATE users SET session_token = ?, updated_at = NOW() WHERE id = ?',
        'si',
        [$token, (int) $user['id']]
    );

    session_regenerate_id(true);
    $_SESSION['user'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['session_token'] = $token;
    $_SESSION['user_data'] = array_merge($user, ['session_token' => $token]);
}

function clear_current_user_session_token(): void
{
    global $conn;

    $userId = $_SESSION['user'] ?? null;
    $sessionToken = current_session_token();

    if ($userId === null || $sessionToken === null) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE users SET session_token = NULL, updated_at = NOW() WHERE id = ? AND session_token = ?',
        'is',
        [(int) $userId, $sessionToken]
    );
}

function clear_auth_session(): void
{
    unset(
        $_SESSION['user'],
        $_SESSION['role'],
        $_SESSION['session_token'],
        $_SESSION['user_data'],
        $_SESSION['pending_user_id'],
        $_SESSION['email_setup_required'],
        $_SESSION['verification_email'],
        $_SESSION['verification_code'],
        $_SESSION['code_expiry'],
    );
    session_destroy();
}

function logout_current_session(): void
{
    clear_current_user_session_token();
    clear_auth_session();
}

function db_prepare(mysqli $conn, string $sql): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare query: ' . $conn->error);
    }

    return $stmt;
}

function db_bind(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
}

function db_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_prepare($conn, $sql);
    db_bind($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function db_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = db_prepare($conn, $sql);
    db_bind($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function db_execute(mysqli $conn, string $sql, string $types = '', array $params = []): bool
{
    $stmt = db_prepare($conn, $sql);
    db_bind($stmt, $types, $params);
    $ok = $stmt->execute();
    if (!$ok) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException($error);
    }
    $stmt->close();
    return true;
}

function db_scalar(mysqli $conn, string $sql, string $types = '', array $params = []): mixed
{
    $row = db_fetch_one($conn, $sql, $types, $params);
    return $row ? array_values($row)[0] : null;
}

function audit_log(mysqli $conn, ?int $userId, string $action, ?string $targetType = null, ?int $targetId = null): void
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

    db_execute(
        $conn,
        'INSERT INTO audit_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())',
        'ississ',
        [$userId, $action, $targetType, $targetId, $ipAddress, $userAgent]
    );
}

function notify_user(mysqli $conn, int $userId, string $title, string $message, string $type = 'info'): void
{
    db_execute(
        $conn,
        'INSERT INTO notifications (user_id, title, message, type, created_at)
         VALUES (?, ?, ?, ?, NOW())',
        'isss',
        [$userId, $title, $message, $type]
    );
}

function generate_ticket_code(mysqli $conn): string
{
    do {
        $code = 'TKT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $exists = db_fetch_one($conn, 'SELECT id FROM tickets WHERE ticket_code = ?', 's', [$code]);
    } while ($exists);

    return $code;
}

function prepare_event_gate(mysqli $conn, int $eventId): int
{
    $conn->begin_transaction();

    try {
        db_execute($conn, 'DELETE FROM event_attendee_cache WHERE event_id = ?', 'i', [$eventId]);

        $attendees = db_fetch_all(
            $conn,
            "SELECT u.id AS user_id,
                    u.student_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.course,
                    u.year_level,
                    t.payment_status
             FROM tickets t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.event_id = ?
               AND t.payment_status IN ('paid', 'free')
               AND t.deleted_at IS NULL
             ORDER BY u.last_name, u.first_name",
            'i',
            [$eventId]
        );

        foreach ($attendees as $attendee) {
            db_execute(
                $conn,
                'INSERT INTO event_attendee_cache (
                    event_id, user_id, student_id, full_name, course, year_level, payment_status, generated_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                'iisssss',
                [
                    $eventId,
                    (int) $attendee['user_id'],
                    $attendee['student_id'],
                    $attendee['full_name'],
                    $attendee['course'],
                    $attendee['year_level'],
                    $attendee['payment_status'],
                ]
            );
        }

        db_execute($conn, "UPDATE events SET status = 'ongoing' WHERE id = ?", 'i', [$eventId]);
        $conn->commit();

        return count($attendees);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function ticket_status_badge(string $status): array
{
    return match ($status) {
        'paid' => ['Paid', 'success'],
        'free' => ['Free', 'success'],
        'pending' => ['Pending', 'warning'],
        'cancelled' => ['Cancelled', 'danger'],
        default => [ucfirst($status), 'secondary'],
    };
}

function event_status_badge(string $status): string
{
    return match ($status) {
        'draft' => 'secondary',
        'open' => 'info',
        'ongoing' => 'success',
        'closed' => 'dark',
        'cancelled' => 'danger',
        default => 'secondary',
    };
}

function admission_status_badge(string $status): string
{
    return match ($status) {
        'admitted' => 'success',
        'duplicate' => 'warning',
        'rejected' => 'danger',
        default => 'secondary',
    };
}

function password_policy_errors(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must include at least one symbol.';
    }

    return $errors;
}

function send_role_login(string $email, string $password, ?array $allowedRoles = null): array
{
    global $conn;

    $user = db_fetch_one(
        $conn,
        'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
        's',
        [$email]
    );

    if (!$user || !(int) $user['is_active']) {
        return ['ok' => false, 'message' => 'Account not found or inactive.'];
    }

    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        return ['ok' => false, 'message' => 'This account is not allowed on this login page.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'message' => 'Incorrect password.'];
    }

    if ($user['role'] === 'student' && (int) $user['email_verified'] === 0) {
        $_SESSION['pending_user_id'] = (int) $user['id'];
        $_SESSION['email_setup_required'] = true;
        return ['ok' => true, 'redirect' => app_url('s/settings/email-setup')];
    }

    establish_user_session($user);

    return ['ok' => true, 'redirect' => app_url(role_home($user['role']))];
}
