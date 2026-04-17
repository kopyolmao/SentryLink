<?php

declare(strict_types=1);

namespace App\Controllers;

class DirectorController extends BaseController
{
    public function dashboard(): string
    {
        $metrics = [
            'events'     => (int) $this->scalar("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL"),
            'ongoing'    => (int) $this->scalar("SELECT COUNT(*) FROM events WHERE status = 'ongoing' AND deleted_at IS NULL"),
            'tickets'    => (int) $this->scalar("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL"),
            'admissions' => (int) $this->scalar("SELECT COUNT(*) FROM admissions WHERE status IN ('admitted', 'in')"),
        ];

        $events = $this->fetchAll("SELECT title, event_date, status, venue FROM events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 8");
        $recentAdmissions = $this->fetchAll(
            "SELECT a.scanned_at, a.status, u.student_id, u.first_name, u.last_name, e.title
             FROM admissions a
             INNER JOIN users u ON u.id = a.user_id
             INNER JOIN events e ON e.id = a.event_id
             ORDER BY a.scanned_at DESC
             LIMIT 10"
        );

        return view('director/dashboard', compact('metrics', 'events', 'recentAdmissions') + ['user' => $this->user]);
    }

    public function events(): string
    {
        $status = (string) ($this->request->getGet('status') ?? '');
        $where  = 'WHERE deleted_at IS NULL';
        $params = [];

        if ($status !== '') {
            $where   .= ' AND status = ?';
            $params[] = $status;
        }

        $events = $this->fetchAll("SELECT * FROM events $where ORDER BY event_date DESC", $params);

        return view('director/events', compact('events', 'status') + ['user' => $this->user]);
    }

    public function admissions(): string
    {
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);
        $where   = 'WHERE 1=1';
        $params  = [];

        if ($eventId > 0) {
            $where   .= ' AND a.event_id = ?';
            $params[] = $eventId;
        }

        $logs = $this->fetchAll(
            "SELECT a.scanned_at, a.status, a.gate_location,
                    u.student_id, u.first_name, u.last_name, u.course, u.year_level,
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

        return view('director/admissions', compact('logs', 'events', 'eventId') + ['user' => $this->user]);
    }

    public function reports(): string
    {
        $reports = $this->fetchAll(
            "SELECT e.title, e.event_date, e.status,
                    SUM(CASE WHEN t.payment_status IN ('paid','free') THEN 1 ELSE 0 END) AS valid_tickets,
                    (SELECT COUNT(*) FROM admissions a WHERE a.event_id = e.id AND a.status IN ('admitted', 'in')) AS admitted_count
             FROM events e
             LEFT JOIN tickets t ON t.event_id = e.id AND t.deleted_at IS NULL
             WHERE e.deleted_at IS NULL
             GROUP BY e.id
             ORDER BY e.event_date DESC"
        );

        return view('director/reports', compact('reports') + ['user' => $this->user]);
    }

    public function auditLogs(): string
    {
        $logs = $this->fetchAll(
            "SELECT a.action, a.target_type, a.target_id, a.created_at, u.first_name, u.last_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 200"
        );

        return view('director/audit_logs', compact('logs') + ['user' => $this->user]);
    }

    public function settings(): string
    {
        return view('account/settings_password_reset', $this->handleAuthenticatedPasswordReset('DIRECTOR_PASSWORD_RESET_ISSUED_FROM_SETTINGS') + [
            'title'               => 'SentryLink | Settings',
            'subtitle'            => 'Director password resets are delivered to the verified email on file.',
            'role'                => 'director',
            'showRuntimeSettings' => false,
            'user'                => $this->user,
        ]);
    }
}
