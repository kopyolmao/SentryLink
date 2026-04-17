<?php

declare(strict_types=1);

namespace App\Libraries;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailerService
{
    private static bool $loaded = false;

    public function __construct()
    {
        $this->loadPhpMailer();
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
    {
        $fromEmail = trim((string) env('mail.fromEmail', ''));
        $fromName  = trim((string) env('mail.fromName', 'SentryLink'));
        $host      = trim((string) env('mail.smtpHost', ''));
        $username  = trim((string) env('mail.smtpUser', ''));
        $password  = preg_replace('/\s+/', '', (string) env('mail.smtpPass', '')) ?? '';
        $crypto    = trim((string) env('mail.smtpCrypto', 'tls'));
        $port      = (int) env('mail.smtpPort', 587);

        if ($fromEmail === '' || $host === '' || $username === '' || $password === '') {
            return ['ok' => false, 'message' => 'Email delivery is not configured.'];
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;
            $mail->SMTPDebug  = 0;
            $mail->SMTPKeepAlive = false;

            if ($crypto !== '') {
                $mail->SMTPSecure = $crypto;
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->send();

            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'Email could not be sent: ' . $e->getMessage()];
        }
    }

    private function loadPhpMailer(): void
    {
        if (self::$loaded) {
            return;
        }

        require_once ROOTPATH . 'syntrelink/PHPMailer/src/Exception.php';
        require_once ROOTPATH . 'syntrelink/PHPMailer/src/PHPMailer.php';
        require_once ROOTPATH . 'syntrelink/PHPMailer/src/SMTP.php';

        self::$loaded = true;
    }
}
