<?php

declare(strict_types=1);

namespace App\Controllers;

class CashierController extends BaseController
{
    public function dashboard(): string
    {
        $message = '';
        $error   = '';

        $selectedEventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id') ?? 0);
        $studentNo       = $this->portal->sanitizePlainInput((string) ($this->request->getPost('student_no') ?? ''), 20);
        $receiptNo       = $this->portal->sanitizePlainInput((string) ($this->request->getPost('receipt_no') ?? ''), 120);

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('encode_paid_ticket')) {
            try {
                if ($selectedEventId <= 0) {
                    throw new \RuntimeException('Please select an event.');
                }

                if ($studentNo === '') {
                    throw new \RuntimeException('Student number is required.');
                }

                if ($receiptNo === '') {
                    throw new \RuntimeException('Receipt number is required.');
                }

                $event = $this->fetchOne(
                    "SELECT id, title, event_date, end_time, status, is_free
                     FROM events
                     WHERE id = ? AND deleted_at IS NULL
                     LIMIT 1",
                    [$selectedEventId]
                );

                if (! $event) {
                    throw new \RuntimeException('Selected event was not found.');
                }

                $eventStatus = strtolower(trim((string) ($event['status'] ?? '')));
                if ((int) ($event['is_free'] ?? 0) === 1) {
                    throw new \RuntimeException('This event is free. No receipt encoding is needed.');
                }
                if (in_array($eventStatus, ['closed', 'cancelled'], true) || event_has_ended($event)) {
                    throw new \RuntimeException('This event is already closed and cannot accept new paid tickets.');
                }

                $student = $this->fetchOne(
                    "SELECT id, first_name, last_name
                     FROM users
                     WHERE student_id = ?
                       AND role = 'student'
                       AND deleted_at IS NULL
                     LIMIT 1",
                    [$studentNo]
                );

                if (! $student) {
                    throw new \RuntimeException('Student number was not found.');
                }

                $studentId = (int) ($student['id'] ?? 0);
                if ($studentId <= 0) {
                    throw new \RuntimeException('Student account is invalid.');
                }

                if ($this->receiptExists($receiptNo, $selectedEventId)) {
                    throw new \RuntimeException('Receipt already exists for this event.');
                }

                if ($this->fetchOne('SELECT id FROM tickets WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL LIMIT 1', [$studentId, $selectedEventId])) {
                    throw new \RuntimeException('This student already has a ticket for the selected event.');
                }

                $this->db->transBegin();

                if ($this->hasReceiptsTable()) {
                    $this->execute(
                        "INSERT INTO receipts (receipt_no, event_id, imported_at, imported_by)
                         VALUES (?, ?, NOW(), ?)",
                        [$receiptNo, $selectedEventId, (int) $this->user['id']]
                    );
                }

                $this->execute(
                    "INSERT INTO tickets (user_id, event_id, ticket_code, receipt_id, payment_status, payment_verified_by, issued_at, updated_at)
                     VALUES (?, ?, ?, ?, 'paid', ?, NOW(), NOW())",
                    [$studentId, $selectedEventId, $this->portal->generateTicketCode(), $receiptNo, (int) $this->user['id']]
                );

                $this->portal->notifyUser(
                    $studentId,
                    'Ticket Ready',
                    'Your paid ticket for ' . (string) ($event['title'] ?? 'the selected event') . ' is now active.',
                    'success'
                );
                $this->portal->auditLog((int) $this->user['id'], 'CASHIER_TICKET_ENCODED', 'event', $selectedEventId);

                $this->db->transCommit();

                $encodedName = trim((string) ($student['first_name'] ?? '') . ' ' . (string) ($student['last_name'] ?? ''));
                $message = 'Ticket encoded for ' . ($encodedName !== '' ? $encodedName : $studentNo) . '.';
                $studentNo = '';
                $receiptNo = '';
            } catch (\Throwable $e) {
                $this->db->transRollback();
                $error = $e->getMessage();
            }
        }

        $events = $this->fetchAll(
            "SELECT id, title, event_date, status
             FROM events
             WHERE deleted_at IS NULL
               AND is_free = 0
               AND status IN ('open', 'ongoing')
             ORDER BY event_date DESC, title ASC"
        );

        $recentEncodedTickets = $this->fetchAll(
            "SELECT t.ticket_code, t.receipt_id, t.issued_at,
                    u.student_id, u.first_name, u.last_name,
                    e.title AS event_title, e.event_date
             FROM tickets t
             INNER JOIN users u ON u.id = t.user_id
             INNER JOIN events e ON e.id = t.event_id
             WHERE t.deleted_at IS NULL
               AND t.payment_status = 'paid'
               AND t.payment_verified_by = ?
             ORDER BY t.issued_at DESC
             LIMIT 30",
            [(int) $this->user['id']]
        );

        return view('cashier/dashboard', compact(
            'message',
            'error',
            'events',
            'selectedEventId',
            'studentNo',
            'receiptNo',
            'recentEncodedTickets'
        ) + ['user' => $this->user]);
    }

    public function settings(): string
    {
        return view('account/settings_password_reset', $this->handleAuthenticatedPasswordReset('CASHIER_PASSWORD_RESET_ISSUED_FROM_SETTINGS') + [
            'title'               => 'SentryLink | Settings',
            'subtitle'            => 'Cashier password resets are delivered to the verified email on file.',
            'role'                => 'cashier',
            'showRuntimeSettings' => false,
            'user'                => $this->user,
        ]);
    }

    private function hasReceiptsTable(): bool
    {
        return $this->db->tableExists('receipts');
    }

    private function receiptExists(string $receiptNo, int $eventId): bool
    {
        if ($this->hasReceiptsTable()) {
            return $this->fetchOne(
                'SELECT id FROM receipts WHERE receipt_no = ? AND event_id = ? LIMIT 1',
                [$receiptNo, $eventId]
            ) !== null;
        }

        return $this->fetchOne(
            'SELECT id FROM tickets WHERE receipt_id = ? AND event_id = ? LIMIT 1',
            [$receiptNo, $eventId]
        ) !== null;
    }
}
