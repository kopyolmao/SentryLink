<?php

namespace App\Controllers;

use App\Libraries\MailerService;
use App\Libraries\PortalService;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Session\Session;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form', 'portal'];
    protected BaseConnection $db;
    protected Session $session;
    protected PortalService $portal;
    protected MailerService $mailer;
    protected ?array $user = null;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->db      = db_connect();
        $this->session = session();
        helper($this->helpers);
        $this->closeExpiredEvents();
        $this->mailer  = new MailerService();
        $this->portal  = new PortalService($this->db, $this->session, $this->mailer);
        $this->user    = $this->portal->currentUser();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->portal->fetchOne($sql, $params);
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->portal->fetchAll($sql, $params);
    }

    protected function scalar(string $sql, array $params = []): mixed
    {
        return $this->portal->scalar($sql, $params);
    }

    protected function execute(string $sql, array $params = []): bool
    {
        return $this->portal->execute($sql, $params);
    }

    protected function appNow(): \DateTimeImmutable
    {
        return portal_now();
    }

    protected function eventHasEnded(string $eventDate, string $endTime, ?\DateTimeInterface $reference = null): bool
    {
        return event_has_ended([
            'event_date' => $eventDate,
            'end_time'   => $endTime,
        ], $reference);
    }

    protected function closeExpiredEvents(): void
    {
        $now = $this->appNow()->format('Y-m-d H:i:s');

        $this->db->query(
            "UPDATE events
             SET status = 'closed', updated_at = ?
             WHERE deleted_at IS NULL
               AND status IN ('draft', 'open', 'ongoing')
               AND TIMESTAMP(event_date, end_time) <= ?",
            [$now, $now]
        );
    }

    protected function handleAuthenticatedPasswordReset(string $auditAction): array
    {
        $message = '';
        $error   = '';

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('request_password_reset')) {
            $enteredEmail  = strtolower(trim((string) $this->request->getPost('email')));
            $verifiedEmail = strtolower(trim((string) ($this->user['email'] ?? '')));

            if ((int) ($this->user['email_verified'] ?? 0) !== 1 || $verifiedEmail === '') {
                $error = 'A verified email is required before you can request a password reset.';
            } elseif ($enteredEmail === '' || $enteredEmail !== $verifiedEmail) {
                $error = 'Enter the verified email currently bound to this account.';
            } else {
                $result = $this->portal->issuePasswordReset($this->user, $auditAction);
                if ($result['ok']) {
                    $message = $result['message'];
                    $this->user = $this->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $this->user['id']]);
                } else {
                    $error = $result['message'];
                }
            }
        }

        return compact('message', 'error');
    }

    protected function normalizeGateStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'admitted' => 'in',
            'in',
            'out'      => $normalized,
            default    => '',
        };
    }

    /**
     * @return array{
     *     current: string,
     *     current_label: string,
     *     next_action: string,
     *     next_action_label: string,
     *     next_action_copy: string,
     *     badge: string,
     *     last_status_label: ?string,
     *     last_scanned_at: ?string
     * }
     */
    protected function gateStateDetails(?string $latestStatus, ?string $latestScannedAt = null): array
    {
        $normalized = $this->normalizeGateStatus($latestStatus);

        if ($normalized === 'in') {
            return [
                'current'           => 'inside',
                'current_label'     => 'Inside Event',
                'next_action'       => 'out',
                'next_action_label' => 'Out',
                'next_action_copy'  => 'The next successful scan will log this student out of the gate.',
                'badge'             => 'success',
                'last_status_label' => $latestStatus !== null && $latestStatus !== '' ? admission_status_label($latestStatus) : null,
                'last_scanned_at'   => $latestScannedAt !== null && $latestScannedAt !== '' ? $latestScannedAt : null,
            ];
        }

        if ($normalized === 'out') {
            return [
                'current'           => 'outside',
                'current_label'     => 'Outside Gate',
                'next_action'       => 'in',
                'next_action_label' => 'In',
                'next_action_copy'  => 'The next successful scan will log this student back into the event.',
                'badge'             => 'info',
                'last_status_label' => admission_status_label($latestStatus ?? 'out'),
                'last_scanned_at'   => $latestScannedAt !== null && $latestScannedAt !== '' ? $latestScannedAt : null,
            ];
        }

        return [
            'current'           => 'ready',
            'current_label'     => 'Ready for Entry',
            'next_action'       => 'in',
            'next_action_label' => 'In',
            'next_action_copy'  => 'The first successful scan will log this student into the event.',
            'badge'             => 'primary',
            'last_status_label' => null,
            'last_scanned_at'   => null,
        ];
    }
}
