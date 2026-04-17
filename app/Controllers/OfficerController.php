<?php

declare(strict_types=1);

namespace App\Controllers;

use Config\Services;

class OfficerController extends BaseController
{
    public function dashboard(): string
    {
        $userId = (int) $this->user['id'];

        $stats = [
            'today'   => (int) $this->scalar('SELECT COUNT(*) FROM admissions WHERE scanned_by = ? AND DATE(scanned_at) = CURDATE()', [$userId]),
            'total'   => (int) $this->scalar('SELECT COUNT(*) FROM admissions WHERE scanned_by = ?', [$userId]),
            'ongoing' => (int) $this->scalar("SELECT COUNT(*) FROM events WHERE status = 'ongoing' AND deleted_at IS NULL"),
        ];

        $recentLogs = $this->fetchAll(
            "SELECT a.scanned_at, a.status, a.gate_location, u.student_id, u.first_name, u.last_name, e.title
             FROM admissions a
             INNER JOIN users u ON u.id = a.user_id
             INNER JOIN events e ON e.id = a.event_id
             WHERE a.scanned_by = ?
             ORDER BY a.scanned_at DESC
             LIMIT 10",
            [$userId]
        );

        $activeEvents = $this->fetchAll(
            "SELECT id, title, event_date FROM events WHERE status = 'ongoing' AND deleted_at IS NULL ORDER BY event_date ASC"
        );

        return view('officer/dashboard', compact('stats', 'recentLogs', 'activeEvents') + ['user' => $this->user]);
    }

    public function scanner(): string
    {
        $selectedEvent = (int) ($this->request->getGet('event_id') ?? 0);
        $events        = $this->fetchAll(
            "SELECT id, title, event_date FROM events WHERE status = 'ongoing' AND deleted_at IS NULL ORDER BY event_date DESC"
        );
        $selectedEventInfo = $selectedEvent > 0
            ? $this->fetchOne('SELECT id, title, event_date FROM events WHERE id = ? AND deleted_at IS NULL', [$selectedEvent])
            : null;
        $scanCount = $selectedEvent > 0
            ? (int) $this->scalar('SELECT COUNT(*) FROM admissions WHERE event_id = ?', [$selectedEvent])
            : 0;

        return view('officer/scanner', compact('events', 'selectedEvent', 'selectedEventInfo', 'scanCount') + ['user' => $this->user]);
    }

    public function gateLog(): string
    {
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        $events  = $this->fetchAll("SELECT id, title, event_date FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC");
        $logs    = $eventId > 0
            ? $this->fetchAll(
                "SELECT a.scanned_at, a.status, a.gate_location, u.student_id, u.first_name, u.last_name, e.title
                 FROM admissions a
                 INNER JOIN users u ON u.id = a.user_id
                 INNER JOIN events e ON e.id = a.event_id
                 WHERE a.event_id = ?
                 ORDER BY a.scanned_at DESC
                 LIMIT 100",
                [$eventId]
            )
            : [];

        return view('officer/gate_log', compact('events', 'eventId', 'logs') + ['user' => $this->user]);
    }

    public function manualLookup(): string
    {
        $eventId   = (int) ($this->request->getGet('event_id') ?? 0);
        $studentId = trim((string) ($this->request->getGet('student_id') ?? ''));
        $events    = $this->fetchAll("SELECT id, title, event_date FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC");
        $result    = null;

        if ($eventId > 0 && $studentId !== '') {
            $result = $this->fetchOne(
                "SELECT u.student_id, u.first_name, u.last_name, u.course, u.year_level,
                        t.payment_status, e.title,
                        (SELECT COUNT(*)
                         FROM admissions a
                         WHERE a.user_id = u.id AND a.event_id = e.id) AS admission_count,
                        (SELECT a.status
                         FROM admissions a
                         WHERE a.user_id = u.id AND a.event_id = e.id
                         ORDER BY a.scanned_at DESC, a.id DESC
                         LIMIT 1) AS latest_gate_status,
                        (SELECT a.scanned_at
                         FROM admissions a
                         WHERE a.user_id = u.id AND a.event_id = e.id
                         ORDER BY a.scanned_at DESC, a.id DESC
                         LIMIT 1) AS latest_gate_at
                 FROM users u
                 INNER JOIN tickets t ON t.user_id = u.id
                 INNER JOIN events e ON e.id = t.event_id
                 WHERE e.id = ?
                   AND u.student_id = ?
                   AND u.deleted_at IS NULL
                   AND u.is_active = 1
                   AND t.deleted_at IS NULL
                 LIMIT 1",
                [$eventId, $studentId]
            );

            if ($result) {
                $result['gate_state'] = $this->gateStateDetails(
                    (string) ($result['latest_gate_status'] ?? ''),
                    (string) ($result['latest_gate_at'] ?? '')
                );
            }
        }

        return view('officer/manual_lookup', compact('events', 'eventId', 'studentId', 'result') + ['user' => $this->user]);
    }

    public function settings(): string
    {
        return view('account/settings_password_reset', $this->handleAuthenticatedPasswordReset('OFFICER_PASSWORD_RESET_ISSUED_FROM_SETTINGS') + [
            'title'               => 'SentryLink | Settings',
            'subtitle'            => 'Officer password resets are delivered to the verified email on file.',
            'role'                => 'ssg',
            'showRuntimeSettings' => false,
            'user'                => $this->user,
        ]);
    }

    public function validateQr()
    {
        $payloadData = $this->request->getJSON(true) ?? [];
        $token       = trim((string) ($payloadData['token'] ?? ''));
        $eventId     = (int) ($payloadData['event_id'] ?? 0);
        $officerId   = (int) $this->user['id'];

        if ($token === '' || $eventId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Missing token or event.']);
        }

        $validated = $this->portal->validateQrToken($token);
        $decoded         = $validated['payload'] ?? [];
        $mode            = (string) ($validated['mode'] ?? 'live');
        $decodedEventId  = (int) ($decoded['eid'] ?? 0);
        $userId          = (int) ($decoded['uid'] ?? 0);
        $jti             = (string) ($decoded['jti'] ?? '');
        $downloadKey     = (string) ($decoded['ptk'] ?? '');
        $decodedTicketId = (int) ($decoded['tid'] ?? 0);
        $gateLocation    = trim((string) ($payloadData['gate_location'] ?? 'Main Gate'));
        if ($gateLocation === '') {
            $gateLocation = 'Main Gate';
        }

        if (! ($validated['ok'] ?? false)) {
            if (($validated['error'] ?? '') !== 'expired' || $decodedEventId <= 0 || $userId <= 0) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => (string) ($validated['message'] ?? 'Invalid QR token.'),
                ]);
            }

            if ($decodedEventId !== $eventId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'QR does not match the selected event.']);
            }

            if ($this->isHeldTokenValid($userId, $eventId, $jti, $token)) {
                $mode = 'hold';
            } elseif ($this->isOfflineGraceTokenValid($userId, $eventId, $jti, $token)) {
                $mode = 'offline_grace';
            } else {
                return $this->response->setJSON([
                    'status'  => 'expired',
                    'message' => 'QR expired and is outside the hold/offline grace window.',
                ]);
            }
        }

        if ($decodedEventId !== $eventId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'QR does not match the selected event.']);
        }

        $event = $this->fetchOne(
            'SELECT id, title, event_date, end_time, status FROM events WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$eventId]
        );

        if (! $event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'The selected event was not found.']);
        }

        if ((string) ($event['status'] ?? '') !== 'ongoing' || $this->eventHasEnded((string) ($event['event_date'] ?? ''), (string) ($event['end_time'] ?? ''))) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'This event is already closed for gate scanning.']);
        }

        $ticket = $this->fetchOne(
            "SELECT t.id, t.download_qr_key, u.student_id, u.first_name, u.last_name, u.course, u.year_level
             FROM tickets t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.user_id = ?
               AND t.event_id = ?
               AND u.deleted_at IS NULL
               AND u.is_active = 1
               AND t.payment_status IN ('paid', 'free')
               AND t.deleted_at IS NULL
             LIMIT 1",
            [$userId, $eventId]
        );

        if (! $ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid ticket for this event.']);
        }

        if ($mode === 'download') {
            $storedDownloadKey = trim((string) ($ticket['download_qr_key'] ?? ''));

            if ($decodedTicketId !== (int) $ticket['id'] || $downloadKey === '' || $storedDownloadKey === '' || ! hash_equals($storedDownloadKey, $downloadKey)) {
                return $this->response->setJSON([
                    'status'  => 'duplicate',
                    'message' => 'This downloaded QR is no longer available. Ask the student to open a fresh QR page if needed.',
                ]);
            }
        }

        $latestGateLog = $this->fetchOne(
            "SELECT id, status, scanned_at
             FROM admissions
             WHERE user_id = ? AND event_id = ?
             ORDER BY scanned_at DESC, id DESC
             LIMIT 1",
            [$userId, $eventId]
        );
        $gateState = $this->gateStateDetails(
            (string) ($latestGateLog['status'] ?? ''),
            (string) ($latestGateLog['scanned_at'] ?? '')
        );
        $statusToRecord = $gateState['next_action'];

        if (! in_array($statusToRecord, ['in', 'out'], true)) {
            $statusToRecord = $this->normalizeGateStatus((string) ($latestGateLog['status'] ?? '')) === 'in' ? 'out' : 'in';
        }

        if ($mode !== 'download') {
            if ($jti === '') {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid QR payload.']);
            }

            $alreadyUsed = (int) $this->scalar(
                'SELECT id FROM qr_blacklist WHERE token_jti = ? LIMIT 1',
                [$jti]
            );
            if ($alreadyUsed > 0) {
                return $this->response->setJSON(['status' => 'duplicate', 'message' => 'This QR has already been used.']);
            }
        }

        $this->db->transBegin();

        try {
            if ($mode === 'download') {
                $this->db->query(
                    'UPDATE tickets SET download_qr_key = NULL, download_qr_created_at = NULL, updated_at = NOW() WHERE id = ? AND download_qr_key = ?',
                    [(int) $ticket['id'], $downloadKey]
                );

                if ($this->db->affectedRows() !== 1) {
                    throw new \RuntimeException('DOWNLOAD_QR_ALREADY_USED');
                }
            } else {
                $this->execute(
                    'INSERT INTO qr_blacklist (token_jti, user_id, event_id, used_at) VALUES (?, ?, ?, NOW())',
                    [$jti, $userId, $eventId]
                );
            }

            $this->execute(
                "INSERT INTO admissions (ticket_id, user_id, event_id, scanned_by, scanned_at, gate_location, status)
                 VALUES (?, ?, ?, ?, NOW(), ?, ?)",
                [(int) $ticket['id'], $userId, $eventId, $officerId, $gateLocation, $statusToRecord]
            );
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            if ($e->getMessage() === 'DOWNLOAD_QR_ALREADY_USED' || str_contains($e->getMessage(), 'Duplicate entry')) {
                return $this->response->setJSON(['status' => 'duplicate', 'message' => 'This QR has already been used.']);
            }

            return $this->response->setJSON(['status' => 'error', 'message' => 'Gate scan failed: ' . $e->getMessage()]);
        }

        return $this->response->setJSON([
            'status'  => $statusToRecord,
            'message' => $statusToRecord === 'out' ? 'Student checked out.' : 'Student checked in.',
            'mode'    => $mode,
            'student' => [
                'name'       => $ticket['first_name'] . ' ' . $ticket['last_name'],
                'student_id' => $ticket['student_id'],
                'course'     => $ticket['course'],
                'year'       => $ticket['year_level'],
            ],
        ]);
    }

    public function gateActivityStateFeed()
    {
        if (strtoupper($this->request->getMethod()) === 'POST') {
            return $this->toggleGateActivity();
        }

        $eventId      = (int) ($this->request->getGet('event_id') ?? 0);
        $statusFilter = trim((string) ($this->request->getGet('status') ?? ''));
        $studentId    = trim((string) ($this->request->getGet('student_id') ?? ''));
        $ownOnly      = (bool) ((int) ($this->request->getGet('own') ?? 0));
        $detailMode   = (bool) ((int) ($this->request->getGet('detail') ?? 0));

        if ($detailMode && $eventId > 0 && $studentId !== '') {
            return $this->gateActivityDetails($eventId, $studentId);
        }

        $where  = 'WHERE 1=1';
        $params = [];

        if ($ownOnly) {
            $where   .= ' AND a.scanned_by = ?';
            $params[] = (int) $this->user['id'];
        }

        if ($eventId > 0) {
            $where   .= ' AND a.event_id = ?';
            $params[] = $eventId;
        }

        if ($statusFilter !== '') {
            $where   .= ' AND a.status = ?';
            $params[] = $statusFilter;
        }

        if ($studentId !== '') {
            $where   .= ' AND u.student_id = ?';
            $params[] = $studentId;
        }

        $summary = $this->fetchOne(
            "SELECT COUNT(*) AS total_logs,
                    COALESCE(MAX(a.id), 0) AS latest_id,
                    COALESCE(MAX(a.scanned_at), '') AS latest_scanned_at,
                    COALESCE(SUM(CASE WHEN a.status IN ('admitted', 'in') THEN 1 ELSE 0 END), 0) AS total_check_ins,
                    COALESCE(SUM(CASE WHEN a.status = 'out' THEN 1 ELSE 0 END), 0) AS total_check_outs
             FROM admissions a
             INNER JOIN users u ON u.id = a.user_id
             $where",
            $params
        ) ?? [];

        $payload = [
            'event_id'         => $eventId,
            'status'           => $statusFilter,
            'student_id'       => $studentId,
            'own'              => $ownOnly,
            'total_logs'       => (int) ($summary['total_logs'] ?? 0),
            'latest_id'        => (int) ($summary['latest_id'] ?? 0),
            'latest_scanned_at'=> (string) ($summary['latest_scanned_at'] ?? ''),
            'total_check_ins'  => (int) ($summary['total_check_ins'] ?? 0),
            'total_check_outs' => (int) ($summary['total_check_outs'] ?? 0),
        ];

        return $this->response->setJSON([
            'state_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)),
        ]);
    }

    public function gateActivityState()
    {
        return $this->gateActivityStateFeed();
    }

    public function gateLogFeed(int $eventId)
    {
        $logs = $this->fetchAll(
            "SELECT a.scanned_at, a.status, a.gate_location,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.student_id,
                    e.title AS event_title
             FROM admissions a
             INNER JOIN users u ON u.id = a.user_id
             INNER JOIN events e ON e.id = a.event_id
             WHERE a.event_id = ?
             ORDER BY a.scanned_at DESC
             LIMIT 100",
            [$eventId]
        );

        foreach ($logs as &$log) {
            $log['badge']  = admission_status_badge($log['status']);
            $log['status'] = admission_status_label($log['status']);
        }

        return $this->response->setJSON(['logs' => $logs]);
    }

    public function attendeeCache(int $eventId)
    {
        $rows = $this->fetchAll(
            'SELECT student_id, full_name, course, year_level, payment_status, generated_at FROM event_attendee_cache WHERE event_id = ? ORDER BY full_name ASC',
            [$eventId]
        );

        return $this->response->setJSON(['attendees' => $rows]);
    }

    public function offlineSync()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $eventId = (int) ($payload['event_id'] ?? 0);
        $rows = $payload['entries'] ?? $payload['scans'] ?? [];
        $officerId = (int) $this->user['id'];

        if ($eventId <= 0 || ! is_array($rows)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'Missing event_id or entries payload.',
            ]);
        }

        $event = $this->fetchOne(
            "SELECT id, status FROM events WHERE id = ? AND deleted_at IS NULL LIMIT 1",
            [$eventId]
        );
        if (! $event) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok'      => false,
                'message' => 'Selected event was not found.',
            ]);
        }

        $synced = 0;
        $skipped = 0;
        $errors = [];
        $this->db->transBegin();

        try {
            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    $skipped++;
                    continue;
                }

                $userId = (int) ($row['uid'] ?? $row['user_id'] ?? 0);
                $tokenJti = trim((string) ($row['jti'] ?? $row['token_jti'] ?? ''));
                $gateLocation = trim((string) ($row['gate_location'] ?? 'Offline Device'));
                $status = strtolower(trim((string) ($row['status'] ?? 'in')));
                $status = in_array($status, ['in', 'out', 'admitted'], true) ? $status : 'in';
                $scannedAt = trim((string) ($row['scanned_at'] ?? ''));

                if ($userId <= 0) {
                    $skipped++;
                    continue;
                }

                $ticket = $this->fetchOne(
                    "SELECT t.id
                     FROM tickets t
                     INNER JOIN users u ON u.id = t.user_id
                     WHERE t.user_id = ?
                       AND t.event_id = ?
                       AND u.deleted_at IS NULL
                       AND u.is_active = 1
                       AND t.payment_status IN ('paid', 'free')
                       AND t.deleted_at IS NULL
                     LIMIT 1",
                    [$userId, $eventId]
                );
                if (! $ticket) {
                    $skipped++;
                    continue;
                }

                if ($tokenJti !== '') {
                    try {
                        $this->execute(
                            'INSERT INTO qr_blacklist (token_jti, user_id, event_id, used_at) VALUES (?, ?, ?, NOW())',
                            [$tokenJti, $userId, $eventId]
                        );
                    } catch (\Throwable $e) {
                        if (! str_contains($e->getMessage(), 'Duplicate entry')) {
                            throw $e;
                        }
                    }
                }

                if ($scannedAt === '' || strtotime($scannedAt) === false) {
                    $scannedAt = date('Y-m-d H:i:s');
                } else {
                    $scannedAt = date('Y-m-d H:i:s', strtotime($scannedAt));
                }

                $this->execute(
                    "INSERT INTO admissions (ticket_id, user_id, event_id, scanned_by, scanned_at, gate_location, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [(int) $ticket['id'], $userId, $eventId, $officerId, $scannedAt, $gateLocation !== '' ? $gateLocation : 'Offline Device', $status]
                );

                $synced++;
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $errors[] = $e->getMessage();
        }

        return $this->response->setJSON([
            'ok'      => $errors === [],
            'event_id'=> $eventId,
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);
    }

    private function isHeldTokenValid(int $userId, int $eventId, string $jti, string $token): bool
    {
        if ($jti === '') {
            return false;
        }

        $hold = Services::cache()->get($this->qrHoldCacheKey($userId, $eventId));
        if (! is_array($hold)) {
            return false;
        }

        if ((int) ($hold['expires_at'] ?? 0) < time()) {
            return false;
        }

        return hash_equals((string) ($hold['jti'] ?? ''), $jti)
            && hash_equals((string) ($hold['token_sha256'] ?? ''), hash('sha256', $token));
    }

    private function isOfflineGraceTokenValid(int $userId, int $eventId, string $jti, string $token): bool
    {
        $cache = Services::cache();
        $current = $cache->get($this->qrCurrentTokenCacheKey($userId, $eventId));
        if (! is_array($current)) {
            return false;
        }

        if (! hash_equals((string) ($current['token_sha256'] ?? ''), hash('sha256', $token))) {
            return false;
        }

        $currentJti = (string) ($current['jti'] ?? '');
        if ($jti === '' || $currentJti === '' || ! hash_equals($currentJti, $jti)) {
            return false;
        }

        $heartbeat = $cache->get($this->qrHeartbeatCacheKey($userId, $eventId));
        $graceUntil = is_array($heartbeat)
            ? (int) ($heartbeat['offline_grace'] ?? 0)
            : (int) ($current['offline_grace'] ?? 0);

        return $graceUntil >= time();
    }

    private function gateActivityDetails(int $eventId, string $studentId)
    {
        $student = $this->fetchOne(
            "SELECT u.id, u.student_id, u.first_name, u.last_name, u.course, u.year_level
             FROM users u
             WHERE u.student_id = ?
               AND u.role = 'student'
               AND u.deleted_at IS NULL
               AND u.is_active = 1
             LIMIT 1",
            [$studentId]
        );
        if (! $student) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok'      => false,
                'status'  => 'not_found',
                'message' => 'Student was not found.',
            ]);
        }

        $ticket = $this->fetchOne(
            "SELECT id, payment_status
             FROM tickets
             WHERE user_id = ?
               AND event_id = ?
               AND payment_status IN ('paid','free')
               AND deleted_at IS NULL
             LIMIT 1",
            [(int) $student['id'], $eventId]
        );
        if (! $ticket) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok'      => false,
                'status'  => 'no_ticket',
                'message' => 'No valid paid/free ticket found for this event.',
            ]);
        }

        $latest = $this->fetchOne(
            "SELECT id, status, scanned_at
             FROM admissions
             WHERE user_id = ? AND event_id = ?
             ORDER BY scanned_at DESC, id DESC
             LIMIT 1",
            [(int) $student['id'], $eventId]
        );

        $gateState = $this->gateStateDetails(
            (string) ($latest['status'] ?? ''),
            (string) ($latest['scanned_at'] ?? '')
        );

        return $this->response->setJSON([
            'ok' => true,
            'status' => $gateState['current'],
            'next_action' => $gateState['next_action'],
            'student' => [
                'id'         => (int) $student['id'],
                'student_id' => (string) $student['student_id'],
                'name'       => trim((string) $student['first_name'] . ' ' . (string) $student['last_name']),
                'course'     => (string) ($student['course'] ?? ''),
                'year_level' => (string) ($student['year_level'] ?? ''),
            ],
            'ticket' => [
                'id'             => (int) $ticket['id'],
                'payment_status' => (string) $ticket['payment_status'],
            ],
            'last_status' => (string) ($latest['status'] ?? ''),
            'last_scanned_at' => (string) ($latest['scanned_at'] ?? ''),
        ]);
    }

    private function toggleGateActivity()
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $eventId = (int) ($payload['event_id'] ?? 0);
        $studentId = trim((string) ($payload['student_id'] ?? ''));
        $gateLocation = trim((string) ($payload['gate_location'] ?? 'Manual Lookup'));

        if ($eventId <= 0 || $studentId === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'event_id and student_id are required.',
            ]);
        }

        $event = $this->fetchOne(
            'SELECT id, event_date, end_time, status FROM events WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$eventId]
        );
        if (! $event) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Event was not found.']);
        }

        if ((string) ($event['status'] ?? '') !== 'ongoing' || $this->eventHasEnded((string) ($event['event_date'] ?? ''), (string) ($event['end_time'] ?? ''))) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Event is not open for gate scanning.']);
        }

        $detailsResponse = $this->gateActivityDetails($eventId, $studentId);
        $detailsPayload = json_decode((string) $detailsResponse->getBody(), true);
        if (! is_array($detailsPayload) || ! ($detailsPayload['ok'] ?? false)) {
            return $detailsResponse;
        }

        $student = $detailsPayload['student'] ?? [];
        $ticket  = $detailsPayload['ticket'] ?? [];
        $nextAction = (string) ($detailsPayload['next_action'] ?? 'in');
        if (! in_array($nextAction, ['in', 'out'], true)) {
            $nextAction = 'in';
        }

        $this->execute(
            "INSERT INTO admissions (ticket_id, user_id, event_id, scanned_by, scanned_at, gate_location, status)
             VALUES (?, ?, ?, ?, NOW(), ?, ?)",
            [
                (int) ($ticket['id'] ?? 0),
                (int) ($student['id'] ?? 0),
                $eventId,
                (int) $this->user['id'],
                $gateLocation !== '' ? $gateLocation : 'Manual Lookup',
                $nextAction,
            ]
        );

        return $this->response->setJSON([
            'ok'      => true,
            'status'  => $nextAction,
            'message' => $nextAction === 'out' ? 'Student checked out.' : 'Student checked in.',
            'student' => [
                'name'       => (string) ($student['name'] ?? ''),
                'student_id' => (string) ($student['student_id'] ?? ''),
                'course'     => (string) ($student['course'] ?? ''),
                'year'       => (string) ($student['year_level'] ?? ''),
            ],
        ]);
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
}
