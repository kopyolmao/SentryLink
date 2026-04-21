<?php

declare(strict_types=1);

namespace App\Controllers;

class AdminController extends BaseController
{
    public function dashboard(): string
    {
        $metrics = [
            'students'   => (int) $this->scalar("SELECT COUNT(*) FROM users WHERE role = 'student' AND deleted_at IS NULL"),
            'events'     => (int) $this->scalar("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL"),
            'tickets'    => (int) $this->scalar('SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL'),
            'admissions' => (int) $this->scalar("SELECT COUNT(*) FROM admissions WHERE DATE(scanned_at) = CURDATE() AND status IN ('admitted', 'in')"),
        ];

        $recentEvents = $this->fetchAll("SELECT id, title, event_date, status FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 6");
        $recentAudit  = $this->fetchAll(
            "SELECT a.action, a.created_at, u.first_name, u.last_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 8"
        );

        return view('admin/dashboard', compact('metrics', 'recentEvents', 'recentAudit') + ['user' => $this->user]);
    }

    public function events(): string
    {
        $message = '';
        $error   = '';
        $editId  = (int) ($this->request->getGet('id') ?? 0);
        $now     = $this->appNow();

        if ($this->request->getMethod() === 'POST') {
            try {
                if ($this->request->getPost('save_event')) {
                    $title       = trim((string) $this->request->getPost('title'));
                    $description = trim((string) $this->request->getPost('description'));
                    $venue       = trim((string) $this->request->getPost('venue'));
                    $eventDate   = (string) $this->request->getPost('event_date');
                    $startTime   = (string) ($this->request->getPost('start_time') ?? '08:00');
                    $endTime     = (string) ($this->request->getPost('end_time') ?? '17:00');
                    $status      = (string) ($this->request->getPost('status') ?? 'draft');
                    $isFree      = $this->request->getPost('is_free') ? 1 : 0;
                    $ticketPriceRaw = trim((string) ($this->request->getPost('ticket_price') ?? ''));
                    $capacityRaw    = trim((string) ($this->request->getPost('max_capacity') ?? ''));
                    $ticketPrice = $isFree ? 0.0 : ($ticketPriceRaw !== '' ? (float) $ticketPriceRaw : 0.0);
                    $capacity    = $capacityRaw !== '' ? (int) $capacityRaw : null;
                    $eventId     = (int) ($this->request->getPost('event_id') ?? 0);
                    $allowedStatuses = ['draft', 'open', 'ongoing', 'closed', 'cancelled'];
                    $statusNotice    = '';

                    if (! in_array($status, $allowedStatuses, true)) {
                        throw new \RuntimeException('Select a valid event status.');
                    }

                    if (! $isFree && $ticketPrice < 0) {
                        throw new \RuntimeException('Ticket price cannot be negative.');
                    }

                    if ($capacity !== null && $capacity < 0) {
                        throw new \RuntimeException('Max capacity cannot be negative.');
                    }

                    if ($this->eventHasEnded($eventDate, $endTime, $now) && in_array($status, ['draft', 'open', 'ongoing'], true)) {
                        $status       = 'closed';
                        $statusNotice = ' The event already ended, so it was automatically closed.';
                    }

                    if ($eventId > 0) {
                        $existingEvent = $this->fetchOne(
                            'SELECT id, is_free, deleted_at FROM events WHERE id = ? LIMIT 1',
                            [$eventId]
                        );

                        if (! $existingEvent || ! empty($existingEvent['deleted_at'])) {
                            throw new \RuntimeException('Event not found.');
                        }

                        $currentIsFree = (int) ($existingEvent['is_free'] ?? 0);
                        if ($currentIsFree !== $isFree) {
                            $existingOperationalData = (int) $this->scalar(
                                "SELECT (
                                    (SELECT COUNT(*) FROM tickets WHERE event_id = ? AND deleted_at IS NULL) +
                                    (SELECT COUNT(*) FROM admissions WHERE event_id = ?) +
                                    (SELECT COUNT(*) FROM event_attendee_cache WHERE event_id = ?)
                                ) AS total_count",
                                [$eventId, $eventId, $eventId]
                            );

                            if ($existingOperationalData > 0) {
                                throw new \RuntimeException('Cannot switch this event between free and paid after tickets or QR gate records already exist. Create a new event instead.');
                            }
                        }

                        $this->execute(
                            "UPDATE events
                             SET title = ?, description = ?, venue = ?, event_date = ?, start_time = ?, end_time = ?,
                                 is_free = ?, ticket_price = ?, max_capacity = ?, status = ?, updated_at = NOW()
                             WHERE id = ?",
                            [$title, $description, $venue, $eventDate, $startTime, $endTime, $isFree, $ticketPrice, $capacity, $status, $eventId]
                        );
                        $this->portal->auditLog((int) $this->user['id'], 'EVENT_UPDATED', 'event', $eventId);
                        $message = 'Event updated.' . $statusNotice;
                    } else {
                        $this->execute(
                            "INSERT INTO events
                                (title, description, venue, event_date, start_time, end_time, is_free, ticket_price, max_capacity, status, created_by, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                            [$title, $description, $venue, $eventDate, $startTime, $endTime, $isFree, $ticketPrice, $capacity, $status, (int) $this->user['id']]
                        );
                        $this->portal->auditLog((int) $this->user['id'], 'EVENT_CREATED', 'event', (int) $this->db->insertID());
                        $message = 'Event created.' . $statusNotice;
                    }
                }

                if ($this->request->getPost('prepare_gate')) {
                    $eventId = (int) $this->request->getPost('event_id');
                    if ($eventId <= 0) {
                        throw new \RuntimeException('Select a valid event.');
                    }

                    $event = $this->fetchOne(
                        'SELECT id, event_date, end_time, status, deleted_at FROM events WHERE id = ? LIMIT 1',
                        [$eventId]
                    );

                    if (! $event || ! empty($event['deleted_at'])) {
                        throw new \RuntimeException('Event not found.');
                    }

                    $status = strtolower(trim((string) ($event['status'] ?? '')));
                    if (in_array($status, ['closed', 'cancelled'], true)) {
                        throw new \RuntimeException('Cannot prepare gate for a closed or cancelled event.');
                    }

                    if ($this->eventHasEnded((string) ($event['event_date'] ?? ''), (string) ($event['end_time'] ?? ''), $now)) {
                        $this->execute('UPDATE events SET status = ?, updated_at = NOW() WHERE id = ? AND status != ?', ['closed', $eventId, 'cancelled']);
                        throw new \RuntimeException('Cannot prepare gate because this event already ended.');
                    }

                    $count   = $this->portal->prepareEventGate($eventId);
                    $this->portal->auditLog((int) $this->user['id'], 'EVENT_GATE_PREPARED', 'event', $eventId);
                    $message = 'Event started. Gate attendees were refreshed for ' . $count . ' students.';
                }

                if ($this->request->getPost('soft_delete_event')) {
                    $eventId = (int) $this->request->getPost('event_id');
                    $this->execute('UPDATE events SET deleted_at = NOW(), status = ? WHERE id = ?', ['cancelled', $eventId]);
                    $this->execute(
                        "DELETE FROM tickets
                         WHERE event_id = ?",
                        [$eventId]
                    );
                    $this->portal->auditLog((int) $this->user['id'], 'EVENT_CANCELLED', 'event', $eventId);
                    $message = 'Event cancelled.';
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $editEvent = $editId > 0 ? $this->fetchOne('SELECT * FROM events WHERE id = ?', [$editId]) : null;
        $events    = $this->fetchAll(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.deleted_at IS NULL) AS ticket_count
             FROM events e
             WHERE e.deleted_at IS NULL
             ORDER BY e.event_date DESC, e.start_time DESC"
        );

        return view('admin/events', compact('message', 'error', 'editEvent', 'events') + [
            'nowIso' => $now->format(\DateTimeInterface::ATOM),
            'user'   => $this->user,
        ]);
    }

    public function activities(int $eventId): string
    {
        $event = $this->fetchOne('SELECT * FROM events WHERE id = ?', [$eventId]);
        if (! $event) {
            return redirect()->to(app_url('admin/events'));
        }

        $message = '';
        if ($this->request->getMethod() === 'POST') {
            $this->execute(
                "INSERT INTO activities (event_id, title, type, house_name, start_time, end_time, venue_area, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $eventId,
                    trim((string) $this->request->getPost('title')),
                    (string) ($this->request->getPost('type') ?? 'other'),
                    trim((string) $this->request->getPost('house_name')),
                    (string) ($this->request->getPost('start_time') ?? '08:00'),
                    (string) ($this->request->getPost('end_time') ?? '09:00'),
                    trim((string) $this->request->getPost('venue_area')),
                    trim((string) $this->request->getPost('description')),
                ]
            );
            $this->portal->auditLog((int) $this->user['id'], 'ACTIVITY_CREATED', 'event', $eventId);
            $message = 'Activity added.';
        }

        $activities = $this->fetchAll(
            'SELECT * FROM activities WHERE event_id = ? AND deleted_at IS NULL ORDER BY start_time ASC',
            [$eventId]
        );

        return view('admin/activities', compact('message', 'event', 'activities') + ['user' => $this->user]);
    }

    public function students(): string
    {
        $message = '';
        $error   = '';

        if ($this->request->getMethod() === 'POST') {
            try {
                if ($this->request->getPost('create_student')) {
                    $password       = (string) ($this->request->getPost('password') ?? 'Password123!');
                    $policyErrors   = $this->portal->passwordPolicyErrors($password);
                    $allowedCourses = ['BSIT', 'BSCS', 'BSBA', 'BSHM', 'BSA', 'BSIS'];
                    $allowedYearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                    $allowedHouses = ['Azul', 'Cahel', 'Giallio', 'Roxxo', 'Vierrdy'];
                    $selectedCourse = trim((string) $this->request->getPost('course_select'));
                    $course         = $selectedCourse;
                    $yearLevel      = trim((string) $this->request->getPost('year_level'));
                    $house          = trim((string) $this->request->getPost('house'));

                    if ($policyErrors !== []) {
                        throw new \RuntimeException(implode(' ', $policyErrors));
                    }

                    if (! in_array($course, $allowedCourses, true)) {
                        throw new \RuntimeException('Please select a valid course.');
                    }

                    if (! in_array($yearLevel, $allowedYearLevels, true)) {
                        throw new \RuntimeException('Please select a valid year level.');
                    }

                    if (! in_array($house, $allowedHouses, true)) {
                        throw new \RuntimeException('Please select a valid house.');
                    }

                    $this->execute(
                        "INSERT INTO users (student_id, first_name, last_name, email, password_hash, role, house, year_level, course, email_verified, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, 0, NOW(), NOW())",
                        [
                            trim((string) $this->request->getPost('student_id')),
                            trim((string) $this->request->getPost('first_name')),
                            trim((string) $this->request->getPost('last_name')),
                            trim((string) $this->request->getPost('email')),
                            password_hash($password, PASSWORD_BCRYPT),
                            $house,
                            $yearLevel,
                            $course,
                        ]
                    );
                    $message = 'Student account created.';
                    $this->portal->auditLog((int) $this->user['id'], 'STUDENT_CREATED', 'user', (int) $this->db->insertID());
                } elseif ($this->request->getPost('delete_student')) {
                    $targetId = (int) ($this->request->getPost('target_id') ?? 0);
                    if ($targetId <= 0) {
                        throw new \RuntimeException('Select a valid student account.');
                    }

                    $student = $this->fetchOne(
                        "SELECT id, first_name, last_name, student_id
                         FROM users
                         WHERE id = ? AND role = 'student' AND deleted_at IS NULL
                         LIMIT 1",
                        [$targetId]
                    );

                    if (! $student) {
                        throw new \RuntimeException('Student account was not found or may already be deleted.');
                    }

                    $this->execute(
                        'UPDATE users SET deleted_at = NOW(), is_active = 0, session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ?',
                        [$targetId]
                    );
                    $this->execute('DELETE FROM event_attendee_cache WHERE user_id = ?', [$targetId]);

                    $this->portal->auditLog((int) $this->user['id'], 'STUDENT_DELETED', 'user', $targetId);
                    $message = 'Student account deleted for ' . trim($student['first_name'] . ' ' . $student['last_name']) . '.';
                } elseif ($this->request->getPost('toggle_student_access')) {
                    $targetId = (int) ($this->request->getPost('target_id') ?? 0);
                    if ($targetId <= 0) {
                        throw new \RuntimeException('Select a valid student account.');
                    }

                    $student = $this->fetchOne(
                        "SELECT id, first_name, last_name, is_active
                         FROM users
                         WHERE id = ? AND role = 'student' AND deleted_at IS NULL
                         LIMIT 1",
                        [$targetId]
                    );

                    if (! $student) {
                        throw new \RuntimeException('Student account was not found or may already be deleted.');
                    }

                    $isCurrentlyActive = (int) ($student['is_active'] ?? 1) === 1;
                    if ($isCurrentlyActive) {
                        $this->execute(
                            'UPDATE users SET is_active = 0, session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ?',
                            [$targetId]
                        );
                        $this->portal->auditLog((int) $this->user['id'], 'STUDENT_DEACTIVATED', 'user', $targetId);
                        $message = 'Student account deactivated for ' . trim($student['first_name'] . ' ' . $student['last_name']) . '.';
                    } else {
                        $this->execute(
                            'UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?',
                            [$targetId]
                        );
                        $this->portal->auditLog((int) $this->user['id'], 'STUDENT_ACTIVATED', 'user', $targetId);
                        $message = 'Student account activated for ' . trim($student['first_name'] . ' ' . $student['last_name']) . '.';
                    }
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $students = $this->fetchAll("SELECT * FROM users WHERE role = 'student' AND deleted_at IS NULL ORDER BY last_name ASC, first_name ASC");

        return view('admin/students', compact('message', 'error', 'students') + ['user' => $this->user]);
    }

    public function tickets()
    {
        $statusFilter = (string) ($this->request->getGet('status') ?? '');
        $eventFilter  = (int) ($this->request->getGet('event_id') ?? 0);
        $where        = 'WHERE t.deleted_at IS NULL';
        $params       = [];

        if ($statusFilter !== '') {
            $where   .= ' AND t.payment_status = ?';
            $params[] = $statusFilter;
        }
        if ($eventFilter > 0) {
            $where   .= ' AND t.event_id = ?';
            $params[] = $eventFilter;
        }

        $tickets = $this->fetchAll(
            "SELECT t.ticket_code, t.receipt_id, t.payment_status, t.issued_at,
                    u.student_id, u.first_name, u.last_name,
                    e.title
             FROM tickets t
             INNER JOIN users u ON u.id = t.user_id
             INNER JOIN events e ON e.id = t.event_id
             $where
             ORDER BY t.issued_at DESC",
            $params
        );
        $events = $this->fetchAll('SELECT id, title FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC');

        if (strtolower(trim((string) $this->request->getGet('export'))) === 'csv') {
            $rows = [];
            foreach ($tickets as $ticket) {
                $rows[] = [
                    (string) ($ticket['ticket_code'] ?? ''),
                    (string) ($ticket['student_id'] ?? ''),
                    trim((string) ($ticket['first_name'] ?? '') . ' ' . (string) ($ticket['last_name'] ?? '')),
                    (string) ($ticket['title'] ?? ''),
                    (string) ($ticket['payment_status'] ?? ''),
                    (string) ($ticket['receipt_id'] ?? ''),
                    (string) ($ticket['issued_at'] ?? ''),
                ];
            }

            if ($rows === []) {
                $redirectParams = [];
                if ($eventFilter > 0) {
                    $redirectParams['event_id'] = (string) $eventFilter;
                }
                if ($statusFilter !== '') {
                    $redirectParams['status'] = $statusFilter;
                }
                $redirectUrl = app_url('admin/tickets');
                if ($redirectParams !== []) {
                    $redirectUrl .= '?' . http_build_query($redirectParams);
                }

                return redirect()->to($redirectUrl)->with('export_error', 'No ticket data found for the selected filters.');
            }

            $this->portal->auditLog((int) $this->user['id'], 'CSV_EXPORT', 'tickets', $eventFilter > 0 ? $eventFilter : null);

            return $this->csvResponse(
                'tickets-export-' . date('Ymd-His') . '.csv',
                ['ticket_code', 'student_id', 'student_name', 'event_title', 'payment_status', 'receipt_id', 'issued_at'],
                $rows
            );
        }

        return view('admin/tickets', compact('tickets', 'events', 'statusFilter', 'eventFilter') + ['user' => $this->user]);
    }

    public function admissions()
    {
        $eventFilter  = (int) ($this->request->getGet('event_id') ?? 0);
        $statusFilter = (string) ($this->request->getGet('status') ?? '');
        $where        = 'WHERE 1=1';
        $params       = [];

        if ($eventFilter > 0) {
            $where   .= ' AND a.event_id = ?';
            $params[] = $eventFilter;
        }
        if ($statusFilter !== '') {
            $where   .= ' AND a.status = ?';
            $params[] = $statusFilter;
        }

        $logs = $this->fetchAll(
            "SELECT a.scanned_at, a.status, a.gate_location,
                    u.student_id, u.first_name, u.last_name,
                    e.title,
                    s.first_name AS officer_first, s.last_name AS officer_last
             FROM admissions a
             INNER JOIN users u ON u.id = a.user_id
             INNER JOIN events e ON e.id = a.event_id
             LEFT JOIN users s ON s.id = a.scanned_by
             $where
             ORDER BY a.scanned_at DESC
             LIMIT 200",
            $params
        );
        $events = $this->fetchAll('SELECT id, title FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC');

        if (strtolower(trim((string) $this->request->getGet('export'))) === 'csv') {
            $rows = [];
            foreach ($logs as $log) {
                $rows[] = [
                    (string) ($log['student_id'] ?? ''),
                    trim((string) ($log['first_name'] ?? '') . ' ' . (string) ($log['last_name'] ?? '')),
                    (string) ($log['title'] ?? ''),
                    (string) ($log['status'] ?? ''),
                    (string) ($log['gate_location'] ?? ''),
                    trim((string) ($log['officer_first'] ?? '') . ' ' . (string) ($log['officer_last'] ?? '')),
                    (string) ($log['scanned_at'] ?? ''),
                ];
            }

            if ($rows === []) {
                $redirectParams = [];
                if ($eventFilter > 0) {
                    $redirectParams['event_id'] = (string) $eventFilter;
                }
                if ($statusFilter !== '') {
                    $redirectParams['status'] = $statusFilter;
                }
                $redirectUrl = app_url('admin/admissions');
                if ($redirectParams !== []) {
                    $redirectUrl .= '?' . http_build_query($redirectParams);
                }

                return redirect()->to($redirectUrl)->with('export_error', 'No admissions data found for the selected filters.');
            }

            $this->portal->auditLog((int) $this->user['id'], 'CSV_EXPORT', 'admissions', $eventFilter > 0 ? $eventFilter : null);

            return $this->csvResponse(
                'admissions-export-' . date('Ymd-His') . '.csv',
                ['student_id', 'student_name', 'event_title', 'status', 'gate_location', 'scanned_by', 'scanned_at'],
                $rows
            );
        }

        return view('admin/admissions', compact('logs', 'events', 'eventFilter', 'statusFilter') + ['user' => $this->user]);
    }

    public function broadcast(): string
    {
        $message = '';

        if ($this->request->getMethod() === 'POST') {
            $roleFilter   = (string) ($this->request->getPost('role') ?? '');
            $courseFilter = trim((string) $this->request->getPost('course'));
            $yearFilter   = trim((string) $this->request->getPost('year_level'));
            $houseFilter  = trim((string) $this->request->getPost('house'));
            $title        = trim((string) $this->request->getPost('title'));
            $body         = trim((string) $this->request->getPost('message'));
            $sql          = 'SELECT id FROM users WHERE is_active = 1 AND deleted_at IS NULL';
            $params       = [];

            if ($roleFilter !== '') {
                $sql     .= ' AND role = ?';
                $params[] = $roleFilter;
            }
            if ($courseFilter !== '') {
                $sql     .= ' AND course = ?';
                $params[] = $courseFilter;
            }
            if ($yearFilter !== '') {
                $sql     .= ' AND year_level = ?';
                $params[] = $yearFilter;
            }
            if ($houseFilter !== '') {
                $sql     .= ' AND house = ?';
                $params[] = $houseFilter;
            }

            $recipients = $this->fetchAll($sql, $params);
            foreach ($recipients as $recipient) {
                $this->portal->notifyUser((int) $recipient['id'], $title, $body, 'info');
            }
            $this->portal->auditLog((int) $this->user['id'], 'BROADCAST_SENT', 'notification', null);
            $message = 'Broadcast sent to ' . count($recipients) . ' user(s).';
        }

        return view('admin/broadcast', compact('message') + ['user' => $this->user]);
    }

    public function reports()
    {
        $reports = $this->fetchAll(
            "SELECT e.id, e.title, e.event_date, e.status,
                    SUM(CASE WHEN t.payment_status IN ('paid','free') THEN 1 ELSE 0 END) AS valid_tickets,
                    (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id AND a.status IN ('admitted', 'in')) AS admitted_count,
                    (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id AND a.status = 'out') AS out_count,
                    (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id) AS scan_count
             FROM events e
             LEFT JOIN tickets t ON t.event_id = e.id AND t.deleted_at IS NULL
             WHERE e.deleted_at IS NULL
             GROUP BY e.id
             ORDER BY e.event_date DESC"
        );

        if (strtolower(trim((string) $this->request->getGet('export'))) === 'csv') {
            $rows = [];
            foreach ($reports as $report) {
                $validTickets = (int) ($report['valid_tickets'] ?? 0);
                $admittedCount = (int) ($report['admitted_count'] ?? 0);
                $attendanceRate = $validTickets > 0 ? round(($admittedCount / $validTickets) * 100, 1) : 0;
                $rows[] = [
                    (string) ($report['title'] ?? ''),
                    (string) ($report['event_date'] ?? ''),
                    (string) ($report['status'] ?? ''),
                    (string) $validTickets,
                    (string) $admittedCount,
                    (string) ((int) ($report['out_count'] ?? 0)),
                    (string) ((int) ($report['scan_count'] ?? 0)),
                    (string) $attendanceRate,
                ];
            }

            if ($rows === []) {
                return redirect()->to(app_url('admin/reports'))->with('export_error', 'No report data is available to export.');
            }

            $this->portal->auditLog((int) $this->user['id'], 'CSV_EXPORT', 'reports', null);

            return $this->csvResponse(
                'reports-export-' . date('Ymd-His') . '.csv',
                ['event_title', 'event_date', 'status', 'valid_tickets', 'admitted_count', 'out_count', 'scan_count', 'attendance_rate_percent'],
                $rows
            );
        }

        return view('admin/reports', compact('reports') + ['user' => $this->user]);
    }

    public function auditLogs(): string
    {
        $message = '';
        $error   = '';

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('clear_audit_logs')) {
            try {
                $this->db->transBegin();

                $this->execute('DELETE FROM audit_logs');
                // Keep a single accountability record that logs were cleared.
                $this->portal->auditLog((int) $this->user['id'], 'AUDIT_LOGS_CLEARED', 'audit_logs', null);

                $this->db->transCommit();
                $message = 'Audit logs were cleared.';
            } catch (\Throwable $e) {
                $this->db->transRollback();
                $error = 'Unable to clear audit logs right now.';
                log_message('error', 'Failed to clear audit logs: {message}', ['message' => $e->getMessage()]);
            }
        }

        $logs = $this->fetchAll(
            "SELECT a.action, a.target_type, a.target_id, a.ip_address, a.created_at, u.first_name, u.last_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 200"
        );

        return view('admin/audit_logs', compact('logs', 'message', 'error') + ['user' => $this->user]);
    }

    public function accounts(): string
    {
        $message = '';
        $error   = '';

        if ($this->request->getMethod() === 'POST') {
            try {
                if ($this->request->getPost('create_account')) {
                    $password     = (string) ($this->request->getPost('password') ?? 'Password123!');
                    $policyErrors = $this->portal->passwordPolicyErrors($password);
                    if ($policyErrors !== []) {
                        throw new \RuntimeException(implode(' ', $policyErrors));
                    }

                    $staffRole = strtolower(trim((string) ($this->request->getPost('role') ?? 'ssg')));
                    if (! in_array($staffRole, ['admin', 'ssg', 'cashier'], true)) {
                        throw new \RuntimeException('Select a valid staff role.');
                    }

                    $this->execute(
                        "INSERT INTO users (student_id, first_name, last_name, email, password_hash, role, email_verified, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
                        [
                            $this->portal->sanitizePlainInput((string) $this->request->getPost('account_id'), 20),
                            $this->portal->sanitizeNameInput((string) $this->request->getPost('first_name'), 100),
                            $this->portal->sanitizeNameInput((string) $this->request->getPost('last_name'), 100),
                            $this->portal->sanitizeEmailInput((string) $this->request->getPost('email')),
                            password_hash($password, PASSWORD_BCRYPT),
                            $staffRole,
                        ]
                    );
                    $this->portal->auditLog((int) $this->user['id'], 'STAFF_ACCOUNT_CREATED', 'user', (int) $this->db->insertID());
                    $message = 'Account created.';
                }

                if ($this->request->getPost('toggle_active')) {
                    $targetId = (int) $this->request->getPost('target_id');
                    $target   = $this->fetchOne('SELECT is_active FROM users WHERE id = ?', [$targetId]);
                    $newState = (int) ($target['is_active'] ?? 0) === 1 ? 0 : 1;
                    $this->execute('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', [$newState, $targetId]);
                    $this->portal->auditLog((int) $this->user['id'], 'STAFF_ACCOUNT_TOGGLED', 'user', $targetId);
                    $message = 'Account status updated.';
                }

                if ($this->request->getPost('delete_account')) {
                    $targetId = (int) ($this->request->getPost('target_id') ?? 0);
                    if ($targetId <= 0) {
                        throw new \RuntimeException('Select a valid staff account.');
                    }

                    $target = $this->fetchOne(
                        "SELECT id, first_name, last_name, student_id, role
                         FROM users
                         WHERE id = ? AND role IN ('admin', 'ssg', 'cashier') AND deleted_at IS NULL
                         LIMIT 1",
                        [$targetId]
                    );

                    if (! $target) {
                        throw new \RuntimeException('Staff account was not found or may already be deleted.');
                    }

                    if ($targetId === (int) $this->user['id']) {
                        throw new \RuntimeException('You cannot delete the account you are currently using.');
                    }

                    if ((string) $target['role'] === 'admin') {
                        $remainingAdmins = (int) $this->scalar(
                            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND deleted_at IS NULL AND id <> ?",
                            [$targetId]
                        );

                        if ($remainingAdmins <= 0) {
                            throw new \RuntimeException('At least one admin account must remain in the system.');
                        }
                    }

                    $this->execute(
                        'UPDATE users SET deleted_at = NOW(), is_active = 0, session_token = NULL, session_last_seen_at = NULL, updated_at = NOW() WHERE id = ?',
                        [$targetId]
                    );

                    $this->portal->auditLog((int) $this->user['id'], 'STAFF_ACCOUNT_DELETED', 'user', $targetId);
                    $message = 'Staff account deleted for ' . trim($target['first_name'] . ' ' . $target['last_name']) . '.';
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $accounts = $this->fetchAll("SELECT * FROM users WHERE role IN ('admin','ssg','cashier') AND deleted_at IS NULL ORDER BY role ASC, last_name ASC");

        return view('admin/accounts', compact('message', 'error', 'accounts') + ['user' => $this->user]);
    }

    public function settings(): string
    {
        $runtimeMessage = '';
        $runtimeError   = '';

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('save_runtime_settings')) {
            $qrSecret = trim((string) $this->request->getPost('qr_secret'));
            $holdSeconds = (int) ($this->request->getPost('qr_hold_seconds') ?? 0);
            $offlineGraceSeconds = (int) ($this->request->getPost('qr_offline_grace_seconds') ?? 0);

            if ($qrSecret === '') {
                $runtimeError = 'QR secret is required.';
            } elseif ($holdSeconds < 5 || $holdSeconds > 8) {
                $runtimeError = 'QR hold seconds must be between 5 and 8.';
            } elseif ($offlineGraceSeconds < 15 || $offlineGraceSeconds > 20) {
                $runtimeError = 'Offline grace seconds must be between 15 and 20.';
            } else {
                try {
                    $this->writeEnvSetting('app.qrSecret', $qrSecret);
                    $this->writeEnvSetting('app.qrHoldSeconds', (string) $holdSeconds);
                    $this->writeEnvSetting('app.qrOfflineGraceSeconds', (string) $offlineGraceSeconds);
                    $this->portal->auditLog((int) $this->user['id'], 'SYSTEM_SETTINGS_UPDATED', 'system', null);
                    $runtimeMessage = 'Runtime settings saved.';
                } catch (\Throwable $e) {
                    $runtimeError = $e->getMessage();
                }
            }
        }

        $passwordResetFeedback = $this->handleAuthenticatedPasswordReset('ADMIN_PASSWORD_RESET_ISSUED_FROM_SETTINGS');

        return view('account/settings_password_reset', $passwordResetFeedback + [
            'title'               => 'SentryLink | Settings',
            'subtitle'            => 'Admin password resets are delivered to the verified email on file.',
            'role'                => 'admin',
            'showRuntimeSettings' => true,
            'runtime'             => $this->runtimeSettings(),
            'runtimeMessage'      => $runtimeMessage,
            'runtimeError'        => $runtimeError,
            'user'                => $this->user,
        ]);
    }

    public function getQrSecret()
    {
        return $this->response->setJSON([
            'qr_secret' => $this->portal->qrSecret(),
        ]);
    }

    public function importReceipts()
    {
        $message = '';
        $error   = '';
        $invalidRows = [];
        $events  = $this->fetchAll("SELECT id, title, event_date FROM events WHERE status IN ('draft', 'open', 'ongoing') AND deleted_at IS NULL ORDER BY event_date DESC");

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('import')) {
            $eventId = (int) ($this->request->getPost('event_id') ?? 0);
            $file    = $this->request->getFile('csv_file');

            if ($eventId <= 0) {
                $error = 'Please select an event.';
            } elseif (! $file || ! $file->isValid()) {
                $error = 'Upload a valid CSV file.';
            } else {
                $handle = fopen($file->getTempName(), 'r');
                $validRows = [];
                $rowNumber = 0;
                $seenReceiptIds = [];
                $seenStudentNos = [];

                if ($handle !== false) {
                    fgetcsv($handle);
                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        $rowNumber++;
                        $receiptId = trim($row[0] ?? '');
                        $studentNo = trim($row[1] ?? '');
                        $receiptKey = strtolower($receiptId);

                        if ($receiptId === '' || $studentNo === '') {
                            $invalidRows[] = "Row {$rowNumber}: Missing receipt_id or student_no.";
                            continue;
                        }

                        if (isset($seenReceiptIds[$receiptKey])) {
                            $invalidRows[] = "Row {$rowNumber}: Receipt {$receiptId} is duplicated inside this CSV.";
                            continue;
                        }

                        if (isset($seenStudentNos[$studentNo])) {
                            $invalidRows[] = "Row {$rowNumber}: Student {$studentNo} appears more than once in this CSV for the selected event.";
                            continue;
                        }

                        $student = $this->fetchOne("SELECT id FROM users WHERE student_id = ? AND role = 'student' AND deleted_at IS NULL", [$studentNo]);
                        if (! $student) {
                            $invalidRows[] = "Row {$rowNumber}: Student {$studentNo} was not found.";
                            continue;
                        }

                        if ($this->receiptExists($receiptId, $eventId)) {
                            $invalidRows[] = "Row {$rowNumber}: Receipt {$receiptId} already exists for this event.";
                            continue;
                        }

                        if ($this->fetchOne('SELECT id FROM tickets WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL', [(int) $student['id'], $eventId])) {
                            $invalidRows[] = "Row {$rowNumber}: Student {$studentNo} already has a ticket for this event.";
                            continue;
                        }

                        $seenReceiptIds[$receiptKey] = true;
                        $seenStudentNos[$studentNo] = true;
                        $validRows[] = [
                            'receipt_id' => $receiptId,
                            'user_id'    => (int) $student['id'],
                            'student_no' => $studentNo,
                        ];
                    }
                    fclose($handle);
                }

                if ($invalidRows !== []) {
                    $error = 'Import blocked. Fix the flagged rows and upload the CSV again.';
                } elseif ($validRows !== []) {
                    $this->session->set('import_data', [
                        'event_id'     => $eventId,
                        'valid_rows'   => $validRows,
                        'invalid_rows' => $invalidRows,
                    ]);

                    return redirect()->to(app_url('admin/tickets/import-receipts/confirm'));
                } else {
                    $error = 'No valid rows were found in the CSV.';
                }
            }
        }

        return view('admin/import_receipts', compact('message', 'error', 'events', 'invalidRows') + ['user' => $this->user]);
    }

    public function importConfirm()
    {
        $importData = $this->session->get('import_data');
        if (! is_array($importData)) {
            return redirect()->to(app_url('admin/tickets/import-receipts'));
        }

        $eventId     = (int) $importData['event_id'];
        $validRows   = $importData['valid_rows'];
        $invalidRows = $importData['invalid_rows'];
        $event       = $this->fetchOne('SELECT * FROM events WHERE id = ?', [$eventId]);
        $error       = '';

        if ($this->request->getMethod() === 'POST') {
            if ($this->request->getPost('cancel')) {
                $this->session->remove('import_data');

                return redirect()->to(app_url('admin/tickets/import-receipts'));
            }

            if ($this->request->getPost('confirm')) {
                $inserted = 0;
                $seenReceiptIds = [];
                $seenUserIds = [];

                try {
                    $this->db->transBegin();

                    foreach ($validRows as $row) {
                        $userId = (int) $row['user_id'];
                        $receiptId = trim((string) $row['receipt_id']);
                        $studentNo = trim((string) $row['student_no']);
                        $receiptKey = strtolower($receiptId);

                        if (isset($seenReceiptIds[$receiptKey])) {
                            throw new \RuntimeException("Import cancelled: receipt {$receiptId} is duplicated inside this CSV.");
                        }

                        if (isset($seenUserIds[$userId])) {
                            throw new \RuntimeException("Import cancelled: student {$studentNo} appears more than once in this CSV for the selected event.");
                        }

                        if ($this->receiptExists($receiptId, $eventId)) {
                            throw new \RuntimeException("Import cancelled: receipt {$receiptId} already exists for this event.");
                        }

                        if ($this->fetchOne('SELECT id FROM tickets WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL', [$userId, $eventId])) {
                            throw new \RuntimeException("Import cancelled: student {$studentNo} already has a ticket for this event.");
                        }

                        $seenReceiptIds[$receiptKey] = true;
                        $seenUserIds[$userId] = true;

                        if ($this->hasReceiptsTable()) {
                            $this->execute(
                                "INSERT INTO receipts (receipt_no, event_id, imported_at, imported_by)
                                 VALUES (?, ?, NOW(), ?)",
                                [$receiptId, $eventId, (int) $this->user['id']]
                            );
                        }

                        $this->execute(
                            "INSERT INTO tickets (user_id, event_id, ticket_code, receipt_id, payment_status, issued_at, updated_at)
                             VALUES (?, ?, ?, ?, 'paid', NOW(), NOW())",
                            [$userId, $eventId, $this->portal->generateTicketCode(), $receiptId]
                        );
                        $inserted++;
                        $this->portal->notifyUser($userId, 'Ticket Ready', 'Your ticket for ' . $event['title'] . ' is now active.', 'success');
                    }

                    $this->db->transCommit();
                } catch (\Throwable $e) {
                    $this->db->transRollback();
                    $error = $e->getMessage();
                    $invalidRows[] = $error;

                    return view('admin/import_confirm', compact('event', 'validRows', 'invalidRows', 'error') + ['user' => $this->user]);
                }

                $this->portal->auditLog((int) $this->user['id'], 'CSV_RECEIPT_IMPORT', 'event', $eventId);
                $this->session->set('import_success', ['inserted' => $inserted, 'event_title' => $event['title']]);
                $this->session->remove('import_data');

                return redirect()->to(app_url('admin/tickets/import-receipts/success'));
            }
        }

        return view('admin/import_confirm', compact('event', 'validRows', 'invalidRows', 'error') + ['user' => $this->user]);
    }

    public function importSuccess()
    {
        $success = $this->session->get('import_success');
        if (! is_array($success)) {
            return redirect()->to(app_url('admin/tickets/import-receipts'));
        }

        $this->session->remove('import_success');

        return view('admin/import_success', compact('success') + ['user' => $this->user]);
    }

    private function hasReceiptsTable(): bool
    {
        return $this->db->tableExists('receipts');
    }

    private function receiptExists(string $receiptId, int $eventId): bool
    {
        if ($this->hasReceiptsTable()) {
            return $this->fetchOne(
                'SELECT id FROM receipts WHERE receipt_no = ? AND event_id = ? LIMIT 1',
                [$receiptId, $eventId]
            ) !== null;
        }

        return $this->fetchOne('SELECT id FROM tickets WHERE receipt_id = ? LIMIT 1', [$receiptId]) !== null;
    }

    private function runtimeSettings(): array
    {
        return [
            'qr_secret'                => (string) env('app.qrSecret', 'syntrelink_qr_secret_key_2026'),
            'qr_hold_seconds'          => (int) env('app.qrHoldSeconds', 6),
            'qr_offline_grace_seconds' => (int) env('app.qrOfflineGraceSeconds', 18),
        ];
    }

    private function writeEnvSetting(string $key, string $value): void
    {
        $envPath = ROOTPATH . '.env';
        if (! is_file($envPath)) {
            $envPath = ROOTPATH . 'env';
        }

        if (! is_file($envPath)) {
            throw new \RuntimeException('Unable to locate environment file.');
        }

        $content = (string) file_get_contents($envPath);
        if ($content === '' && filesize($envPath) > 0) {
            throw new \RuntimeException('Unable to read environment file.');
        }

        $normalizedValue = trim($value);
        $line = $key . ' = ' . $normalizedValue;
        $pattern = '/^' . preg_quote($key, '/') . '\s*=.*$/m';

        if (preg_match($pattern, $content) === 1) {
            $updated = (string) preg_replace($pattern, $line, $content);
        } else {
            $suffix = $content !== '' && ! str_ends_with($content, PHP_EOL) ? PHP_EOL : '';
            $updated = $content . $suffix . $line . PHP_EOL;
        }

        if (file_put_contents($envPath, $updated) === false) {
            throw new \RuntimeException('Failed to persist environment setting: ' . $key);
        }
    }

    private function csvResponse(string $filename, array $headers, array $rows)
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Failed to create export stream.');
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }
}
