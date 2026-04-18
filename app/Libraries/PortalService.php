<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Session\Session;
use Config\Services;

class PortalService
{
    private bool $authResolved = false;
    private ?array $resolvedUser = null;

    public function __construct(
        private readonly BaseConnection $db,
        private readonly Session $session,
        private readonly MailerService $mailer,
    ) {
    }

    public function roleHome(string $role): string
    {
        return match ($this->normalizeRole($role)) {
            'student'  => 's/my-qr',
            'ssg'      => 'o/scanner',
            'admin'    => 'admin/dashboard',
            'director' => 'director/dashboard',
            default    => 's/auth/login',
        };
    }

    public function roleLogin(string $role): string
    {
        return match ($this->normalizeRole($role)) {
            'student'  => 's/auth/login',
            'ssg'      => 'o/auth/login',
            'admin'    => 'admin/auth/login',
            'director' => 'director/auth/login',
            default    => 's/auth/login',
        };
    }

    public function roleLabel(string $role): string
    {
        return match ($this->normalizeRole($role)) {
            'student'  => 'Student',
            'ssg'      => 'Officer',
            'admin'    => 'Admin',
            'director' => 'Director',
            default    => ucfirst($role),
        };
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->db->query($sql, $params)->getRowArray();

        return $row ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->query($sql, $params)->getResultArray();
    }

    public function scalar(string $sql, array $params = []): mixed
    {
        $row = $this->fetchOne($sql, $params);

        return $row ? array_values($row)[0] : null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        return (bool) $this->db->query($sql, $params);
    }

    public function currentUserId(): ?int
    {
        $user = $this->resolveAuthenticatedUser();

        return $user !== null ? (int) $user['id'] : null;
    }

    public function currentRole(): ?string
    {
        $user = $this->resolveAuthenticatedUser();

        return $user !== null ? $this->normalizeRole((string) $user['role']) : null;
    }

    public function currentUser(): ?array
    {
        return $this->resolveAuthenticatedUser();
    }

    public function sanitizeEmailInput(string $email): string
    {
        $email = $this->sanitizePlainInput($email, 254, true, true);

        return strtolower($email);
    }

    public function sanitizePasswordInput(string $password): string
    {
        $password = str_replace("\0", '', $password);
        $password = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $password) ?? $password;

        return function_exists('mb_substr') ? mb_substr($password, 0, 255) : substr($password, 0, 255);
    }

    public function sanitizeNameInput(string $value, int $maxLength = 50): string
    {
        $value = $this->sanitizePlainInput($value, $maxLength, true, true);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    public function sanitizePlainInput(string $value, int $maxLength = 255, bool $stripTags = true, bool $trim = true): string
    {
        $clean = str_replace("\0", '', $value);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? $clean;
        if ($stripTags) {
            $clean = strip_tags($clean);
        }
        if ($trim) {
            $clean = trim($clean);
        }

        return function_exists('mb_substr') ? mb_substr($clean, 0, $maxLength) : substr($clean, 0, $maxLength);
    }

    public function isValidPersonName(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return preg_match("/^[a-zA-Z][a-zA-Z .'-]{0,49}$/", $value) === 1;
    }

    public function login(string $email, string $password, ?array $allowedRoles = null): array
    {
        $email = $this->sanitizeEmailInput($email);
        $password = $this->sanitizePasswordInput($password);
        $identifier = $email;
        $ipAddress  = $this->requestIpAddress();
        $lockSeconds = $this->activeLoginLockSeconds($identifier, $ipAddress);

        if ($lockSeconds > 0) {
            return [
                'ok'      => false,
                'message' => 'Too many failed login attempts. Try again in ' . $lockSeconds . ' second(s).',
            ];
        }

        $user = $this->fetchOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [$email]
        );

        if (! $user || (int) ($user['is_active'] ?? 1) !== 1) {
            $this->recordFailedLoginAttempt($identifier, $ipAddress);

            return ['ok' => false, 'message' => 'Account not found or inactive.'];
        }

        $normalizedRole = $this->normalizeRole((string) $user['role']);

        if ($allowedRoles !== null && ! in_array($normalizedRole, $allowedRoles, true)) {
            return ['ok' => false, 'message' => 'This account is not allowed on this login page.'];
        }

        $hash = $user['password_hash'] ?? '';
        if (! password_verify($password, $hash)) {
            $this->recordFailedLoginAttempt($identifier, $ipAddress);

            return ['ok' => false, 'message' => 'Incorrect password.'];
        }

        if ($this->hasActiveSessionConflict($user)) {
            return [
                'ok'      => false,
                'message' => 'This account is already logged in on another device. Please log out there first and try again.',
            ];
        }

        if ($normalizedRole === 'student' && (int) ($user['email_verified'] ?? 0) === 0) {
            $this->clearLoginAttempts($identifier, $ipAddress);
            $this->session->set([
                'pending_user_id'      => (int) $user['id'],
                'email_setup_required' => true,
            ]);

            return ['ok' => true, 'redirect' => site_url('s/settings/email-setup')];
        }

        $this->establishAuthenticatedSession($user);
        $this->clearLoginAttempts($identifier, $ipAddress);

        return ['ok' => true, 'redirect' => site_url($this->roleHome($normalizedRole))];
    }

    public function logout(): void
    {
        $this->clearCurrentDatabaseSession();
        $this->clearSessionState();
    }

    public function establishAuthenticatedSession(array $user): void
    {
        $token          = bin2hex(random_bytes(32));
        $normalizedRole = $this->normalizeRole((string) ($user['role'] ?? 'student'));
        $heartbeatAt    = date('Y-m-d H:i:s');

        $this->execute(
            'UPDATE users SET session_token = ?, session_last_seen_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$token, (int) $user['id']]
        );

        $user['role'] = $normalizedRole;
        $user['session_token'] = $token;
        $user['session_last_seen_at'] = $heartbeatAt;

        if (session_status() === PHP_SESSION_ACTIVE) {
            try {
                $this->session->regenerate(true);
            } catch (\Throwable) {
                // Do not block login if session regeneration fails unexpectedly.
            }
        }

        $this->session->set([
            'user'      => (int) $user['id'],
            'role'      => $normalizedRole,
            'user_id'   => (int) $user['id'],
            'user_role' => $normalizedRole,
            'session_token' => $token,
            'user_data' => $user,
        ]);

        $this->authResolved = true;
        $this->resolvedUser = $user;
    }

    public function passwordPolicyErrors(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include at least one uppercase letter.';
        }
        if (! preg_match('/\d/', $password)) {
            $errors[] = 'Password must include at least one number.';
        }
        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must include at least one symbol.';
        }

        return $errors;
    }

    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $symbols  = '!@#$%&*?';
        $password = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, strlen('ABCDEFGHJKLMNPQRSTUVWXYZ') - 1)],
            'abcdefghijkmnopqrstuvwxyz'[random_int(0, strlen('abcdefghijkmnopqrstuvwxyz') - 1)],
            (string) random_int(2, 9),
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        while (count($password) < $length) {
            $pool       = $alphabet . $symbols;
            $password[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }

    public function activeSessionConflictMessage(array $user): ?string
    {
        if (! $this->hasActiveSessionConflict($user)) {
            return null;
        }

        return 'This account is already logged in on another device. Please log out there first and try again.';
    }

    public function sendVerificationCodeEmail(string $email, string $name, string $code): array
    {
        $subject  = 'SentryLink Email Verification Code';
        $htmlBody = '<p>Hello ' . esc($name) . ',</p>'
            . '<p>Your SentryLink verification code is:</p>'
            . '<p style="font-size:24px;font-weight:700;letter-spacing:4px;">' . esc($code) . '</p>'
            . '<p>This code expires in 10 minutes.</p>';
        $textBody = "Hello {$name},\n\nYour SentryLink verification code is: {$code}\n\nThis code expires in 10 minutes.";

        return $this->mailer->send($email, $name, $subject, $htmlBody, $textBody);
    }

    public function issuePasswordReset(array $user, string $auditAction): array
    {
        $email = trim((string) ($user['email'] ?? ''));

        if ($email === '' || (int) ($user['email_verified'] ?? 0) !== 1) {
            return ['ok' => false, 'message' => 'This account does not have a verified email address.'];
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $fullName          = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        $fullName          = $fullName !== '' ? $fullName : $this->roleLabel((string) ($user['role'] ?? 'User'));
        $roleLabel         = $this->roleLabel((string) ($user['role'] ?? ''));
        $subject           = 'SentryLink Password Reset';
        $htmlBody          = '<p>Hello ' . esc($fullName) . ',</p>'
            . '<p>A new password was generated for your ' . esc($roleLabel) . ' account.</p>'
            . '<p style="font-size:22px;font-weight:700;">' . esc($temporaryPassword) . '</p>'
            . '<p>Use this password to sign in, then request a new reset any time you need one.</p>';
        $textBody          = "Hello {$fullName},\n\nA new password was generated for your {$roleLabel} account.\n\n{$temporaryPassword}\n\nUse this password to sign in.";

        $mailResult = $this->mailer->send($email, $fullName, $subject, $htmlBody, $textBody);
        if (! $mailResult['ok']) {
            return $mailResult;
        }

        $this->execute(
            'UPDATE users SET password_hash = ?, session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ?',
            [password_hash($temporaryPassword, PASSWORD_BCRYPT), (int) $user['id']]
        );
        $this->auditLog((int) $user['id'], $auditAction, 'user', (int) $user['id']);
        $this->notifyUser((int) $user['id'], 'Password Reset', 'A new password was sent to your verified email.', 'info');

        return ['ok' => true, 'message' => 'A new password has been sent to your verified email.'];
    }

    public function issuePasswordResetLink(array $user, string $auditAction): array
    {
        $email = trim((string) ($user['email'] ?? ''));

        if ($email === '' || (int) ($user['email_verified'] ?? 0) !== 1) {
            return ['ok' => false, 'message' => 'This account does not have a verified email address.'];
        }

        $this->ensurePasswordResetTable();

        $userId    = (int) $user['id'];
        $role      = $this->normalizeRole((string) ($user['role'] ?? 'student'));
        $token     = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->execute(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND role = ? AND used_at IS NULL',
            [$userId, $role]
        );
        $this->execute(
            'INSERT INTO password_reset_tokens (user_id, role, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$userId, $role, $tokenHash, $expiresAt]
        );

        $fullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        $fullName = $fullName !== '' ? $fullName : $this->roleLabel($role);
        $roleLabel = $this->roleLabel($role);
        $resetUrl = site_url($this->resetPasswordPath($role, $token));
        $subject = 'SentryLink Password Reset Link';
        $htmlBody = '<p>Hello ' . esc($fullName) . ',</p>'
            . '<p>Use this secure link to reset your ' . esc($roleLabel) . ' account password:</p>'
            . '<p><a href="' . esc($resetUrl) . '">' . esc($resetUrl) . '</a></p>'
            . '<p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>';
        $textBody = "Hello {$fullName},\n\nUse this secure link to reset your {$roleLabel} account password:\n\n{$resetUrl}\n\nThis link expires in 1 hour. If you did not request this, you can ignore this email.";

        $mailResult = $this->mailer->send($email, $fullName, $subject, $htmlBody, $textBody);
        if (! $mailResult['ok']) {
            $this->execute('DELETE FROM password_reset_tokens WHERE token_hash = ?', [$tokenHash]);

            return $mailResult;
        }

        $this->auditLog($userId, $auditAction, 'user', $userId);

        return ['ok' => true, 'message' => 'A password reset link has been sent to your verified email.'];
    }

    public function passwordResetRequest(string $token, string $role): ?array
    {
        $tokenHash = hash('sha256', $token);
        $role = $this->normalizeRole($role);

        $this->ensurePasswordResetTable();

        return $this->fetchOne(
            "SELECT prt.id, prt.user_id, prt.role, prt.expires_at, u.email, u.first_name, u.last_name, u.role AS user_role
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = ?
               AND prt.role = ?
               AND prt.used_at IS NULL
               AND prt.expires_at >= NOW()
               AND u.deleted_at IS NULL
               AND u.is_active = 1
             LIMIT 1",
            [$tokenHash, $role]
        );
    }

    public function consumePasswordResetToken(string $token, string $role, string $newPassword): array
    {
        $request = $this->passwordResetRequest($token, $role);

        if (! $request) {
            return ['ok' => false, 'message' => 'This reset link is invalid or expired.'];
        }

        $userId = (int) $request['user_id'];
        $tokenHash = hash('sha256', $token);

        $this->execute(
            'UPDATE users SET password_hash = ?, session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ?',
            [password_hash($newPassword, PASSWORD_BCRYPT), $userId]
        );
        $this->execute('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?', [$tokenHash]);
        $this->auditLog($userId, 'PASSWORD_RESET_COMPLETED', 'user', $userId);
        $this->notifyUser($userId, 'Password Reset', 'Your password was changed through a secure reset link.', 'success');

        return ['ok' => true, 'message' => 'Your password has been updated. You can now sign in.'];
    }

    public function auditLog(?int $userId, string $action, ?string $targetType = null, ?int $targetId = null): void
    {
        $request   = Services::request();
        $ipAddress = $request->getIPAddress() ?: '127.0.0.1';
        $userAgent = $request->getUserAgent()?->getAgentString() ?: 'CLI';

        $this->execute(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$userId, $action, $targetType, $targetId, $ipAddress, $userAgent]
        );
    }

    public function notifyUser(int $userId, string $title, string $message, string $type = 'info'): void
    {
        $this->execute(
            'INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$userId, $title, $message, $type]
        );
    }

    public function generateTicketCode(): string
    {
        do {
            $code   = 'TKT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $exists = $this->fetchOne('SELECT id FROM tickets WHERE ticket_code = ? LIMIT 1', [$code]);
        } while ($exists !== null);

        return $code;
    }

    public function prepareEventGate(int $eventId): int
    {
        helper('portal');

        $event = $this->fetchOne(
            'SELECT id, title, event_date, end_time, status, is_free FROM events WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$eventId]
        );

        if (! $event) {
            throw new \RuntimeException('Event not found.');
        }

        $status = strtolower(trim((string) ($event['status'] ?? 'draft')));

        if ($status === 'cancelled') {
            throw new \RuntimeException('Cancelled events cannot be started.');
        }

        if ($status === 'closed' || event_has_ended($event)) {
            $now = portal_now()->format('Y-m-d H:i:s');
            $this->execute("UPDATE events SET status = 'closed', updated_at = ? WHERE id = ?", [$now, $eventId]);

            throw new \RuntimeException('This event has already ended and cannot be started.');
        }

        $this->db->transBegin();

        try {
            if ((int) ($event['is_free'] ?? 0) === 1) {
                $freeUsers = $this->fetchAll(
                    "SELECT u.id AS user_id
                     FROM users u
                     LEFT JOIN tickets t
                       ON t.user_id = u.id
                      AND t.event_id = ?
                      AND t.deleted_at IS NULL
                     WHERE u.role = 'student'
                       AND u.deleted_at IS NULL
                       AND u.is_active = 1
                       AND t.id IS NULL
                     ORDER BY u.id ASC",
                    [$eventId]
                );

                foreach ($freeUsers as $row) {
                    $this->execute(
                        "INSERT INTO tickets (user_id, event_id, ticket_code, receipt_id, payment_status, issued_at, updated_at)
                         VALUES (?, ?, ?, NULL, 'free', NOW(), NOW())",
                        [(int) $row['user_id'], $eventId, $this->generateTicketCode()]
                    );
                    $this->notifyUser(
                        (int) $row['user_id'],
                        'Ticket Ready',
                        'A free ticket for ' . (string) ($event['title'] ?? 'the selected event') . ' has been issued.',
                        'success'
                    );
                }
            }

            $this->execute('DELETE FROM event_attendee_cache WHERE event_id = ?', [$eventId]);
            $this->execute('DELETE FROM qr_blacklist WHERE event_id = ?', [$eventId]);
            $this->execute('DELETE FROM admissions WHERE event_id = ?', [$eventId]);

            $attendees = $this->fetchAll(
                "SELECT u.id AS user_id,
                        u.student_id,
                        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                        u.course,
                        u.year_level,
                        t.payment_status
                 FROM tickets t
                 INNER JOIN users u ON u.id = t.user_id
                 WHERE t.event_id = ?
                   AND u.deleted_at IS NULL
                   AND u.is_active = 1
                   AND t.payment_status IN ('paid', 'free')
                   AND t.deleted_at IS NULL
                 ORDER BY u.last_name, u.first_name",
                [$eventId]
            );

            foreach ($attendees as $attendee) {
                $this->execute(
                    'INSERT INTO event_attendee_cache (
                        event_id, user_id, student_id, full_name, course, year_level, payment_status, generated_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
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

            $this->execute("UPDATE events SET status = 'ongoing', updated_at = ? WHERE id = ?", [portal_now()->format('Y-m-d H:i:s'), $eventId]);
            $this->db->transCommit();

            return count($attendees);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function qrSecret(): string
    {
        return (string) env('app.qrSecret', 'syntrelink_qr_secret_key_2026');
    }

    public function createQrToken(int $userId, string $studentId, int $eventId): string
    {
        $payload = [
            'kind' => 'live',
            'uid' => $userId,
            'sid' => $studentId,
            'eid' => $eventId,
            'jti' => bin2hex(random_bytes(8)),
            'iat' => time(),
            'exp' => time() + 10,
        ];

        return $this->encodeQrPayload($payload);
    }

    public function ensureDownloadQrToken(
        int $ticketId,
        int $userId,
        string $studentId,
        int $eventId,
        ?string $existingKey = null,
    ): array {
        $downloadKey = is_string($existingKey) ? trim($existingKey) : '';

        if ($downloadKey === '') {
            $downloadKey = bin2hex(random_bytes(32));
            $this->execute(
                'UPDATE tickets SET download_qr_key = ?, download_qr_created_at = NOW(), updated_at = NOW() WHERE id = ?',
                [$downloadKey, $ticketId]
            );
        }

        return [
            'key'   => $downloadKey,
            'token' => $this->encodeQrPayload([
                'kind' => 'download',
                'uid'  => $userId,
                'sid'  => $studentId,
                'eid'  => $eventId,
                'tid'  => $ticketId,
                'ptk'  => $downloadKey,
                'iat'  => time(),
            ]),
        ];
    }

    public function validateQrToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['ok' => false, 'message' => 'Invalid QR format.'];
        }

        [$payloadB64, $signatureB64] = $parts;
        $expected = rtrim(
            strtr(base64_encode(hash_hmac('sha256', $payloadB64, $this->qrSecret(), true)), '+/', '-_'),
            '='
        );

        if (! hash_equals($expected, $signatureB64)) {
            return ['ok' => false, 'message' => 'Invalid QR signature.'];
        }

        $decodedJson = base64_decode(strtr($payloadB64, '-_', '+/'));
        $decoded     = json_decode((string) $decodedJson, true);
        $kind        = is_array($decoded) ? (string) ($decoded['kind'] ?? 'live') : '';

        if ($kind === 'download') {
            if (! is_array($decoded) || ! isset($decoded['uid'], $decoded['eid'], $decoded['tid'], $decoded['ptk'])) {
                return ['ok' => false, 'message' => 'Invalid downloadable QR payload.'];
            }

            return ['ok' => true, 'mode' => 'download', 'payload' => $decoded];
        }

        if (! is_array($decoded) || ! isset($decoded['uid'], $decoded['jti'], $decoded['exp'])) {
            return ['ok' => false, 'message' => 'Invalid QR payload.'];
        }

        if (((int) $decoded['exp']) + 5 < time()) {
            return [
                'ok'      => false,
                'mode'    => 'live',
                'error'   => 'expired',
                'message' => 'QR expired. Ask the student to refresh.',
                'payload' => $decoded,
            ];
        }

        return ['ok' => true, 'mode' => 'live', 'payload' => $decoded];
    }

    public function qrImage(string $token, int $size = 300): string
    {
        $encoded = urlencode($token);

        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded}&choe=UTF-8";
    }

    private function encodeQrPayload(array $payload): string
    {
        $payloadJson  = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $payloadB64   = rtrim(strtr(base64_encode((string) $payloadJson), '+/', '-_'), '=');
        $signature    = hash_hmac('sha256', $payloadB64, $this->qrSecret(), true);
        $signatureB64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $payloadB64 . '.' . $signatureB64;
    }

    private function normalizeRole(string $role): string
    {
        return $role === 'staff' ? 'ssg' : $role;
    }

    private function resetPasswordPath(string $role, string $token): string
    {
        return match ($this->normalizeRole($role)) {
            'ssg'      => 'o/auth/reset-password/' . $token,
            'admin'    => 'admin/auth/reset-password/' . $token,
            'director' => 'director/auth/reset-password/' . $token,
            default    => 's/auth/reset-password/' . $token,
        };
    }

    private function ensurePasswordResetTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NOT NULL,
                role VARCHAR(20) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_password_reset_token_hash (token_hash),
                KEY idx_password_reset_user (user_id),
                KEY idx_password_reset_expires (expires_at),
                KEY idx_password_reset_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function resolveAuthenticatedUser(): ?array
    {
        if ($this->authResolved) {
            return $this->resolvedUser;
        }

        $this->authResolved = true;

        $userId       = $this->rawSessionUserId();
        $sessionRole  = $this->rawSessionRole();
        $sessionToken = $this->rawSessionToken();

        if ($userId === null || $sessionRole === null) {
            $this->resolvedUser = null;

            return null;
        }

        if ($sessionToken === null || $sessionToken === '') {
            $this->clearSessionState();

            return null;
        }

        $user = $this->fetchOne(
            'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1',
            [$userId]
        );

        if (! $user) {
            $this->clearSessionState();

            return null;
        }

        $databaseToken = trim((string) ($user['session_token'] ?? ''));
        $databaseRole  = $this->normalizeRole((string) ($user['role'] ?? ''));

        if ($databaseToken === '' || ! hash_equals($databaseToken, $sessionToken) || $databaseRole !== $sessionRole) {
            $this->clearSessionState();

            return null;
        }

        $user['role'] = $databaseRole;
        $user['session_token'] = $databaseToken;
        $user = $this->refreshSessionHeartbeat($user);
        $this->session->set('user_data', $user);
        $this->resolvedUser = $user;

        return $user;
    }

    private function rawSessionUserId(): ?int
    {
        $value = $this->session->get('user_id') ?? $this->session->get('user');

        return $value !== null ? (int) $value : null;
    }

    private function rawSessionRole(): ?string
    {
        $role = $this->session->get('user_role') ?? $this->session->get('role');

        return is_string($role) && $role !== '' ? $this->normalizeRole($role) : null;
    }

    private function rawSessionToken(): ?string
    {
        $token = $this->session->get('session_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function clearCurrentDatabaseSession(): void
    {
        $userId       = $this->rawSessionUserId();
        $sessionToken = $this->rawSessionToken();

        if ($userId === null || $sessionToken === null || $sessionToken === '') {
            return;
        }

        $this->execute(
            'UPDATE users SET session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ? AND session_token = ?',
            [$userId, $sessionToken]
        );
    }

    private function hasActiveSessionConflict(array $user): bool
    {
        $databaseToken = trim((string) ($user['session_token'] ?? ''));
        if ($databaseToken === '') {
            return false;
        }

        $currentToken = $this->rawSessionToken();
        if ($currentToken !== null && hash_equals($databaseToken, $currentToken)) {
            return false;
        }

        $lastSeenAt = $this->sessionLastSeenAt($user);
        if ($lastSeenAt === null) {
            return false;
        }

        return $lastSeenAt >= (time() - $this->activeSessionWindowSeconds());
    }

    private function refreshSessionHeartbeat(array $user): array
    {
        $lastSeenAt = $this->sessionLastSeenAt($user);
        if ($lastSeenAt !== null && $lastSeenAt >= (time() - 60)) {
            return $user;
        }

        $this->execute(
            'UPDATE users SET session_last_seen_at = NOW() WHERE id = ? AND session_token = ?',
            [(int) $user['id'], (string) $user['session_token']]
        );
        $user['session_last_seen_at'] = date('Y-m-d H:i:s');

        return $user;
    }

    private function sessionLastSeenAt(array $user): ?int
    {
        $raw = trim((string) ($user['session_last_seen_at'] ?? ''));
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);

        return $timestamp !== false ? $timestamp : null;
    }

    private function activeSessionWindowSeconds(): int
    {
        $config = config(\Config\Session::class);
        $expiration = (int) ($config->expiration ?? 0);

        return $expiration > 0 ? $expiration : 7200;
    }

    private function clearSessionState(): void
    {
        $this->session->remove([
            'user',
            'role',
            'user_id',
            'user_role',
            'session_token',
            'user_data',
            'pending_user_id',
            'email_setup_required',
            'verification_email',
            'verification_code',
            'code_expiry',
            'import_data',
            'import_success',
        ]);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->session->destroy();
        }

        $this->authResolved = true;
        $this->resolvedUser = null;
    }

    private function requestIpAddress(): string
    {
        $request = Services::request();
        $ip = trim((string) $request->getIPAddress());

        return $ip !== '' ? $ip : '127.0.0.1';
    }

    private function activeLoginLockSeconds(string $identifier, string $ipAddress): int
    {
        try {
            $attempt = $this->fetchOne(
                'SELECT locked_until FROM login_attempts WHERE ip_address = ? AND identifier = ? LIMIT 1',
                [$ipAddress, $identifier]
            );
        } catch (\Throwable) {
            return 0;
        }

        if (! $attempt) {
            return 0;
        }

        $lockedUntil = trim((string) ($attempt['locked_until'] ?? ''));
        if ($lockedUntil === '') {
            return 0;
        }

        $timestamp = strtotime($lockedUntil);
        if ($timestamp === false || $timestamp <= time()) {
            return 0;
        }

        return (int) max(1, $timestamp - time());
    }

    private function recordFailedLoginAttempt(string $identifier, string $ipAddress): void
    {
        if ($identifier === '') {
            return;
        }

        try {
            $attempt = $this->fetchOne(
                'SELECT id, attempt_count FROM login_attempts WHERE ip_address = ? AND identifier = ? LIMIT 1',
                [$ipAddress, $identifier]
            );

            $nextCount = ((int) ($attempt['attempt_count'] ?? 0)) + 1;
            $lockUntil = $nextCount >= 5 ? date('Y-m-d H:i:s', time() + (15 * 60)) : null;

            if ($attempt) {
                $this->execute(
                    'UPDATE login_attempts
                     SET attempt_count = ?, last_attempt = NOW(), locked_until = ?
                     WHERE id = ?',
                    [$nextCount, $lockUntil, (int) $attempt['id']]
                );

                return;
            }

            $this->execute(
                'INSERT INTO login_attempts (ip_address, identifier, attempt_count, last_attempt, locked_until)
                 VALUES (?, ?, ?, NOW(), ?)',
                [$ipAddress, $identifier, 1, null]
            );
        } catch (\Throwable) {
        }
    }

    private function clearLoginAttempts(string $identifier, string $ipAddress): void
    {
        if ($identifier === '') {
            return;
        }

        try {
            $this->execute(
                'DELETE FROM login_attempts WHERE ip_address = ? AND identifier = ?',
                [$ipAddress, $identifier]
            );
        } catch (\Throwable) {
        }
    }
}
