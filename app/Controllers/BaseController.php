<?php

namespace App\Controllers;

use App\Libraries\MailerService;
use App\Libraries\PortalService;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
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
        $this->cleanupTicketsForRemovedEvents();
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
        try {
            $this->db->query(
                "UPDATE events
                 SET status = 'closed', updated_at = ?
                 WHERE deleted_at IS NULL
                   AND status IN ('draft', 'open', 'ongoing')
                   AND TIMESTAMP(event_date, end_time) <= ?",
                [$now, $now]
            );
        } catch (DatabaseException $e) {
            log_message('error', 'Database unavailable while closing expired events: {message}', [
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Unexpected error while closing expired events: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function cleanupTicketsForRemovedEvents(): void
    {
        try {
            // Hard-delete tickets tied to soft-deleted events.
            $this->db->query(
                "DELETE t
                 FROM tickets t
                 INNER JOIN events e ON e.id = t.event_id
                 WHERE e.deleted_at IS NOT NULL"
            );

            // Safety net for orphan records if an event was physically removed without cascade.
            $this->db->query(
                "DELETE t
                 FROM tickets t
                 LEFT JOIN events e ON e.id = t.event_id
                 WHERE e.id IS NULL"
            );
        } catch (DatabaseException $e) {
            log_message('error', 'Database unavailable while cleaning tickets for removed events: {message}', [
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Unexpected error while cleaning tickets for removed events: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function handleAuthenticatedPasswordReset(string $auditAction): array
    {
        $message = '';
        $error   = '';

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('request_password_reset')) {
            $verifiedEmail = strtolower(trim((string) ($this->user['email'] ?? '')));

            if ((int) ($this->user['email_verified'] ?? 0) !== 1 || $verifiedEmail === '') {
                $error = 'A verified email is required before you can request a password reset.';
            } else {
                $captchaError = $this->verifyAuthenticatedPasswordResetCaptcha();
                if ($captchaError !== null) {
                    $error = $captchaError;
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
        }

        $captcha = $this->issueAuthenticatedPasswordResetCaptcha();

        return compact('message', 'error') + [
            'passwordResetCaptchaToken' => $captcha['token'],
            'passwordResetCaptchaImage' => $captcha['image'],
        ];
    }

    /**
     * @return array{token: string, image: string}
     */
    protected function issueAuthenticatedPasswordResetCaptcha(): array
    {
        $captcha = $this->buildSettingsResetCaptchaChallenge();

        $this->session->set([
            'settings_reset_captcha_token'   => $captcha['token'],
            'settings_reset_captcha_hash'    => $captcha['hash'],
            'settings_reset_captcha_expires' => $captcha['expiry'],
        ]);

        return [
            'token' => $captcha['token'],
            'image' => $captcha['image'],
        ];
    }

    protected function verifyAuthenticatedPasswordResetCaptcha(): ?string
    {
        $postedToken  = trim((string) $this->request->getPost('reset_captcha_token'));
        $postedAnswer = trim((string) $this->request->getPost('reset_captcha_answer'));
        $sessionToken = trim((string) $this->session->get('settings_reset_captcha_token'));
        $sessionHash  = trim((string) $this->session->get('settings_reset_captcha_hash'));
        $expiresAt    = (int) ($this->session->get('settings_reset_captcha_expires') ?? 0);

        $this->session->remove(['settings_reset_captcha_token', 'settings_reset_captcha_hash', 'settings_reset_captcha_expires']);

        if ($sessionToken === '' || $sessionHash === '' || $expiresAt <= 0) {
            return 'Captcha challenge expired. Please try again.';
        }

        if (time() > $expiresAt) {
            return 'Captcha challenge expired. Please try again.';
        }

        if ($postedToken === '' || $postedAnswer === '') {
            return 'Please complete the captcha challenge.';
        }

        if (preg_match('/^[a-zA-Z0-9]{4,10}$/', $postedAnswer) !== 1) {
            return 'Invalid captcha answer format.';
        }

        if (! hash_equals($sessionToken, $postedToken)) {
            return 'Captcha verification failed. Please try again.';
        }

        $postedHash = hash('sha256', $postedToken . '|' . strtoupper($postedAnswer));
        if (! hash_equals($sessionHash, $postedHash)) {
            return 'Captcha verification failed. Please try again.';
        }

        return null;
    }

    /**
     * @return array{token: string, hash: string, expiry: int, image: string}
     */
    protected function buildSettingsResetCaptchaChallenge(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $text = '';
        for ($i = 0; $i < 6; $i++) {
            $text .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $token  = bin2hex(random_bytes(16));
        $hash   = hash('sha256', $token . '|' . $text);
        $expiry = time() + 600;
        $image  = $this->buildSettingsResetCaptchaSvgDataUri($text);

        return [
            'token'  => $token,
            'hash'   => $hash,
            'expiry' => $expiry,
            'image'  => $image,
        ];
    }

    protected function buildSettingsResetCaptchaSvgDataUri(string $text): string
    {
        $chars = str_split($text);
        $charNodes = '';
        $x = 18;

        foreach ($chars as $char) {
            $y = random_int(30, 46);
            $rotate = random_int(-20, 20);
            $charNodes .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotate . ' ' . $x . ' ' . $y . ')"'
                . ' font-family="monospace" font-size="28" font-weight="700" fill="#0b1020">' . $char . '</text>';
            $x += 26;
        }

        $noise = '';
        for ($i = 0; $i < 10; $i++) {
            $x1 = random_int(0, 200);
            $y1 = random_int(0, 60);
            $x2 = random_int(0, 200);
            $y2 = random_int(0, 60);
            $stroke = random_int(140, 220);
            $noise .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgb(' . $stroke . ',' . ($stroke - 30) . ',' . ($stroke - 60) . ')" stroke-width="1.2" opacity="0.55" />';
        }

        for ($i = 0; $i < 35; $i++) {
            $cx = random_int(0, 200);
            $cy = random_int(0, 60);
            $r = random_int(1, 2);
            $noise .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="rgba(255,255,255,0.45)" />';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">'
            . '<rect width="200" height="60" rx="10" fill="#d9def0" />'
            . $noise
            . $charNodes
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
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
