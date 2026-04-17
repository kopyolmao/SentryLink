<?php

declare(strict_types=1);

namespace App\Controllers;

use Config\Services;

class StudentController extends BaseController
{
    public function dashboard(): string
    {
        $userId = (int) $this->user['id'];

        $stats = [
            'tickets'       => (int) $this->scalar('SELECT COUNT(*) FROM tickets WHERE user_id = ? AND deleted_at IS NULL', [$userId]),
            'active'        => (int) $this->scalar("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND payment_status IN ('paid','free') AND deleted_at IS NULL", [$userId]),
            'notifications' => (int) $this->scalar('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]),
        ];

        $upcoming = $this->fetchAll(
            "SELECT t.id, t.payment_status, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.status,
                    (SELECT a.status
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_status,
                    (SELECT a.scanned_at
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_at
             FROM tickets t
             INNER JOIN events e ON e.id = t.event_id
             WHERE t.user_id = ? AND t.deleted_at IS NULL
             ORDER BY e.event_date ASC, e.start_time ASC
             LIMIT 6",
            [$userId]
        );

        foreach ($upcoming as &$ticket) {
            $ticket['gate_state'] = $this->gateStateDetails(
                (string) ($ticket['latest_gate_status'] ?? ''),
                (string) ($ticket['latest_gate_at'] ?? '')
            );
        }
        unset($ticket);

        $ticketStateHash = $this->ticketStateHash($userId);

        return view('student/dashboard', compact('stats', 'upcoming', 'ticketStateHash') + ['user' => $this->user]);
    }

    public function myTickets(): string
    {
        $userId = (int) $this->user['id'];
        $tickets = $this->fetchAll(
            "SELECT t.ticket_code, t.receipt_id, t.payment_status, t.issued_at,
                    e.title, e.event_date, e.start_time, e.end_time, e.venue, e.status,
                    (SELECT a.status
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_status,
                    (SELECT a.scanned_at
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_at
             FROM tickets t
             INNER JOIN events e ON e.id = t.event_id
             WHERE t.user_id = ? AND t.deleted_at IS NULL
             ORDER BY e.event_date DESC, e.start_time DESC",
            [$userId]
        );

        foreach ($tickets as &$ticket) {
            $ticket['gate_state'] = $this->gateStateDetails(
                (string) ($ticket['latest_gate_status'] ?? ''),
                (string) ($ticket['latest_gate_at'] ?? '')
            );
        }
        unset($ticket);

        $ticketStateHash = $this->ticketStateHash($userId);

        return view('student/my_tickets', ['tickets' => $tickets, 'ticketStateHash' => $ticketStateHash, 'user' => $this->user]);
    }

    public function myQr(): string
    {
        $ticket = $this->currentLiveQrTicket();
        $gateState = $ticket['gate_state'] ?? null;

        $ticketStateHash = $this->ticketStateHash((int) $this->user['id']);

        return view('student/my_qr', [
            'ticket'          => $ticket,
            'gateState'       => $gateState,
            'ticketStateHash' => $ticketStateHash,
            'user'            => $this->user,
        ]);
    }

    public function notifications(): string
    {
        if ($this->request->getMethod() === 'POST' && $this->request->getPost('mark_all_read')) {
            $this->execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [(int) $this->user['id']]);
        }

        $notifications = $this->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
            [(int) $this->user['id']]
        );

        return view('student/notifications', ['notifications' => $notifications, 'user' => $this->user]);
    }

    public function profile(): string
    {
        $message = '';

        if ($this->request->getMethod() === 'POST') {
            $firstName = trim((string) $this->request->getPost('first_name'));
            $lastName  = trim((string) $this->request->getPost('last_name'));
            $course    = trim((string) $this->request->getPost('course'));
            $yearLevel = trim((string) $this->request->getPost('year_level'));
            $house     = trim((string) $this->request->getPost('house'));

            $this->execute(
                'UPDATE users SET first_name = ?, last_name = ?, course = ?, year_level = ?, house = ?, updated_at = NOW() WHERE id = ?',
                [$firstName, $lastName, $course, $yearLevel, $house, (int) $this->user['id']]
            );
            $this->portal->auditLog((int) $this->user['id'], 'PROFILE_UPDATED', 'user', (int) $this->user['id']);
            $this->user = $this->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $this->user['id']]);
            $message    = 'Profile updated.';
        }

        return view('student/profile', ['message' => $message, 'user' => $this->user]);
    }

    public function account(): string
    {
        return $this->profile();
    }

    public function settings(): string
    {
        return view('student/settings', ['user' => $this->user]);
    }

    public function resetPassword(): string
    {
        return view('account/settings_password_reset', $this->handleAuthenticatedPasswordReset('PASSWORD_RESET_ISSUED_FROM_SETTINGS') + [
            'title'               => 'SentryLink | Reset Password',
            'subtitle'            => 'Request a generated password through your verified email.',
            'role'                => 'student',
            'showRuntimeSettings' => false,
            'user'                => $this->user,
        ]);
    }

    public function generateQr()
    {
        $ticket = $this->currentLiveQrTicket();

        if (! $ticket) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No active event ticket found.']);
        }

        $userId  = (int) $this->user['id'];
        $eventId = (int) $ticket['event_id'];
        $token   = $this->portal->createQrToken($userId, (string) $this->user['student_id'], $eventId);
        $downloadToken = null;
        $downloadFileName = null;
        $gateState = $ticket['gate_state'] ?? $this->gateStateDetails(null);
        $cache = Services::cache();
        $versionKey = $this->qrVersionCacheKey($userId, $eventId);
        $tokenVersion = max(1, (int) $cache->get($versionKey) + 1);
        $cache->save($versionKey, $tokenVersion, 6 * 3600);

        $payload = $this->qrPayloadFromToken($token);
        $cache->save(
            $this->qrCurrentTokenCacheKey($userId, $eventId),
            [
                'token_sha256'   => hash('sha256', $token),
                'token_version'  => $tokenVersion,
                'jti'            => (string) ($payload['jti'] ?? ''),
                'issued_at'      => time(),
                'expires_at'     => time() + 10,
                'offline_grace'  => time() + $this->offlineGraceSeconds(),
            ],
            180
        );

        if ((int) ($ticket['gate_log_count'] ?? 0) > 0) {
            if (trim((string) ($ticket['download_qr_key'] ?? '')) !== '') {
                $this->execute(
                    'UPDATE tickets SET download_qr_key = NULL, download_qr_created_at = NULL, updated_at = NOW() WHERE id = ?',
                    [(int) $ticket['id']]
                );
            }
        } else {
            $downloadData = $this->portal->ensureDownloadQrToken(
                (int) $ticket['id'],
                (int) $this->user['id'],
                (string) $this->user['student_id'],
                (int) $ticket['event_id'],
                (string) ($ticket['download_qr_key'] ?? '')
            );
            $downloadToken = $downloadData['token'];
            $downloadFileName = $this->downloadQrFileName((string) $ticket['title'], (string) $this->user['student_id']);
        }

        return $this->response->setJSON([
            'token'              => $token,
            'qr_image'           => $this->portal->qrImage($token),
            'event_title'        => $ticket['title'],
            'event_id'           => $eventId,
            'token_version'      => $tokenVersion,
            'expires_in'         => 10,
            'download_token'     => $downloadToken,
            'download_file_name' => $downloadFileName,
            'download_available' => $downloadToken !== null,
            'gate_state'         => $gateState['current'],
            'gate_state_label'   => $gateState['current_label'],
            'gate_state_badge'   => $gateState['badge'],
            'next_action'        => $gateState['next_action'],
            'next_action_label'  => $gateState['next_action_label'],
            'next_action_copy'   => $gateState['next_action_copy'],
            'last_status_label'  => $gateState['last_status_label'],
            'last_scanned_at'    => $gateState['last_scanned_at'],
        ]);
    }

    public function holdQr()
    {
        $ticket = $this->currentLiveQrTicket();
        if (! $ticket) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'No active ticket is available for hold.']);
        }

        $payloadData = $this->request->getJSON(true) ?? [];
        $token = trim((string) ($payloadData['token'] ?? ''));

        if ($token === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Token is required.']);
        }

        $validated = $this->portal->validateQrToken($token);
        if (! ($validated['ok'] ?? false)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => (string) ($validated['message'] ?? 'Invalid QR token.'),
            ]);
        }

        $payload = $validated['payload'] ?? [];
        $userId  = (int) $this->user['id'];
        $eventId = (int) $ticket['event_id'];
        $tokenUserId  = (int) ($payload['uid'] ?? 0);
        $tokenEventId = (int) ($payload['eid'] ?? 0);
        $tokenJti     = (string) ($payload['jti'] ?? '');

        if ($tokenUserId !== $userId || $tokenEventId !== $eventId || $tokenJti === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'This QR token does not match your current active event.',
            ]);
        }

        $holdSeconds = $this->holdDurationSeconds();
        $expiresAt   = time() + $holdSeconds;
        $cache       = Services::cache();

        $cache->save(
            $this->qrHoldCacheKey($userId, $eventId),
            [
                'jti'          => $tokenJti,
                'token_sha256' => hash('sha256', $token),
                'created_at'   => time(),
                'expires_at'   => $expiresAt,
            ],
            $holdSeconds + 2
        );

        return $this->response->setJSON([
            'ok'               => true,
            'event_id'         => $eventId,
            'hold_seconds'     => $holdSeconds,
            'hold_expires_at'  => date('c', $expiresAt),
            'token_version'    => (int) ($cache->get($this->qrVersionCacheKey($userId, $eventId)) ?? 0),
            'server_time'      => date('c'),
        ]);
    }

    public function heartbeat()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $userId  = (int) $this->user['id'];
        $ticket  = $this->currentLiveQrTicket();
        $eventId = (int) ($ticket['event_id'] ?? 0);
        $tokenVersion = max(0, (int) ($payload['token_version'] ?? 0));
        $rttMs = max(0, (int) round((float) ($payload['rtt_ms'] ?? 0)));
        $now = time();

        $this->execute(
            'UPDATE users SET session_last_seen_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$userId]
        );

        $cache = Services::cache();
        $heartbeatKey = $this->qrHeartbeatCacheKey($userId, $eventId);
        $last = $cache->get($heartbeatKey);
        $lastSeen = is_array($last) ? (int) ($last['last_seen_at'] ?? 0) : 0;
        $gap = $lastSeen > 0 ? max(0, $now - $lastSeen) : 0;

        $latencyState = 'healthy';
        if ($gap > 15) {
            $latencyState = 'offline';
        } elseif ($rttMs > 2000 || $gap > 12) {
            $latencyState = 'slow';
        } elseif ($rttMs >= 800 || $gap > 8) {
            $latencyState = 'unstable';
        }

        $offlineGraceSeconds = $this->offlineGraceSeconds();
        $offlineGraceExpiry = $now + $offlineGraceSeconds;

        $cache->save(
            $heartbeatKey,
            [
                'last_seen_at'    => $now,
                'rtt_ms'          => $rttMs,
                'token_version'   => $tokenVersion,
                'offline_grace'   => $offlineGraceExpiry,
            ],
            $offlineGraceSeconds + 120
        );

        if ($eventId > 0) {
            $currentToken = $cache->get($this->qrCurrentTokenCacheKey($userId, $eventId));
            if (is_array($currentToken)) {
                $currentToken['offline_grace'] = $offlineGraceExpiry;
                $cache->save($this->qrCurrentTokenCacheKey($userId, $eventId), $currentToken, 180);
            }
        }

        return $this->response->setJSON([
            'ok'                       => true,
            'event_id'                 => $eventId,
            'has_live_ticket'          => $ticket !== null,
            'server_time'              => date('c', $now),
            'latency_state'            => $latencyState,
            'offline_grace_expires_at' => date('c', $offlineGraceExpiry),
            'token_version'            => $tokenVersion,
        ]);
    }

    public function notificationsFeed()
    {
        $notifications = $this->fetchAll(
            'SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
            [(int) $this->portal->currentUserId()]
        );
        $unread = (int) $this->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [(int) $this->portal->currentUserId()]
        );

        return $this->response->setJSON([
            'unread'        => $unread,
            'notifications' => $notifications,
        ]);
    }

    public function ticketStateFeed()
    {
        $userId  = (int) $this->portal->currentUserId();
        $payload = $this->buildTicketStatePayload($userId);

        return $this->response->setJSON([
            'state_hash'      => $this->hashTicketStatePayload($payload),
            'has_live_qr'     => $payload['has_live_qr'],
            'ticket_count'    => $payload['ticket_count'],
            'gate_ready_count' => $payload['gate_ready_count'],
        ]);
    }

    public function ticketState()
    {
        return $this->ticketStateFeed();
    }

    public function markNotificationsRead()
    {
        $input          = $this->request->getJSON(true) ?? [];
        $markAll        = (bool) ($input['all'] ?? false);
        $notificationId = (int) ($input['id'] ?? 0);
        $userId         = (int) $this->portal->currentUserId();

        if ($markAll) {
            $this->execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
        } elseif ($notificationId > 0) {
            $this->execute('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$notificationId, $userId]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    private function currentLiveQrTicket(): ?array
    {
        $ticket = $this->fetchOne(
            "SELECT t.id, t.download_qr_key, t.download_qr_created_at, e.id AS event_id, e.title, e.event_date, e.start_time, e.end_time, e.status,
                    (SELECT a.status
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_status,
                    (SELECT a.scanned_at
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_at,
                    (SELECT COUNT(*) FROM admissions a WHERE a.ticket_id = t.id) AS gate_log_count
             FROM tickets t
             INNER JOIN events e ON e.id = t.event_id
             WHERE t.user_id = ?
               AND t.payment_status IN ('paid', 'free')
               AND t.deleted_at IS NULL
               AND e.status = 'ongoing'
             ORDER BY e.event_date DESC, e.start_time DESC
             LIMIT 1",
            [(int) $this->user['id']]
        );

        if (! $ticket) {
            return null;
        }

        $ticket['gate_state'] = $this->gateStateDetails(
            (string) ($ticket['latest_gate_status'] ?? ''),
            (string) ($ticket['latest_gate_at'] ?? '')
        );

        return $ticket;
    }

    private function downloadQrFileName(string $eventTitle, string $studentId): string
    {
        $eventSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($eventTitle)));
        $eventSlug = trim((string) $eventSlug, '-');
        $studentSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($studentId)));
        $studentSlug = trim((string) $studentSlug, '-');

        return 'sentrylink-entry-qr-' . ($eventSlug !== '' ? $eventSlug : 'event') . '-' . ($studentSlug !== '' ? $studentSlug : 'student') . '.png';
    }

    private function ticketStateHash(int $userId): string
    {
        return $this->hashTicketStatePayload($this->buildTicketStatePayload($userId));
    }

    private function buildTicketStatePayload(int $userId): array
    {
        $rows = $this->fetchAll(
            "SELECT t.ticket_code, t.receipt_id, t.payment_status,
                    e.id AS event_id, e.status, e.event_date, e.start_time, e.end_time,
                    (SELECT a.status
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_status,
                    (SELECT a.scanned_at
                     FROM admissions a
                     WHERE a.ticket_id = t.id
                     ORDER BY a.scanned_at DESC, a.id DESC
                     LIMIT 1) AS latest_gate_at
             FROM tickets t
             INNER JOIN events e ON e.id = t.event_id
             WHERE t.user_id = ? AND t.deleted_at IS NULL
             ORDER BY e.event_date DESC, e.start_time DESC",
            [$userId]
        );

        $hasLiveQr      = false;
        $gateReadyCount = 0;
        $tickets        = [];

        foreach ($rows as $row) {
            $paymentStatus = (string) ($row['payment_status'] ?? '');
            $eventStatus   = (string) ($row['status'] ?? '');
            $isGateReady   = in_array($paymentStatus, ['paid', 'free'], true);

            if ($isGateReady) {
                $gateReadyCount++;
            }

            if ($isGateReady && $eventStatus === 'ongoing') {
                $hasLiveQr = true;
            }

            $tickets[] = [
                'ticket_code'    => (string) ($row['ticket_code'] ?? ''),
                'receipt_id'     => (string) ($row['receipt_id'] ?? ''),
                'payment_status' => $paymentStatus,
                'event_id'       => (int) ($row['event_id'] ?? 0),
                'event_status'   => $eventStatus,
                'event_date'     => (string) ($row['event_date'] ?? ''),
                'start_time'     => (string) ($row['start_time'] ?? ''),
                'end_time'       => (string) ($row['end_time'] ?? ''),
                'latest_gate_status' => (string) ($row['latest_gate_status'] ?? ''),
                'latest_gate_at'     => (string) ($row['latest_gate_at'] ?? ''),
                'gate_state'         => $this->gateStateDetails(
                    (string) ($row['latest_gate_status'] ?? ''),
                    (string) ($row['latest_gate_at'] ?? '')
                )['current'],
            ];
        }

        return [
            'ticket_count'     => count($tickets),
            'gate_ready_count' => $gateReadyCount,
            'has_live_qr'      => $hasLiveQr,
            'tickets'          => $tickets,
        ];
    }

    private function hashTicketStatePayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function holdDurationSeconds(): int
    {
        $seconds = (int) env('app.qrHoldSeconds', 6);

        return max(5, min(8, $seconds));
    }

    private function offlineGraceSeconds(): int
    {
        $seconds = (int) env('app.qrOfflineGraceSeconds', 18);

        return max(15, min(20, $seconds));
    }

    private function qrCurrentTokenCacheKey(int $userId, int $eventId): string
    {
        return 'qr_current_' . $eventId . '_' . $userId;
    }

    private function qrHoldCacheKey(int $userId, int $eventId): string
    {
        return 'qr_hold_' . $eventId . '_' . $userId;
    }

    private function qrHeartbeatCacheKey(int $userId, int $eventId): string
    {
        return 'qr_heartbeat_' . $eventId . '_' . $userId;
    }

    private function qrVersionCacheKey(int $userId, int $eventId): string
    {
        return 'qr_version_' . $eventId . '_' . $userId;
    }

    private function qrPayloadFromToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'));
        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) ? $payload : null;
    }
}
