<?php

declare(strict_types=1);

namespace App\Controllers;

class AuthController extends BaseController
{
    public function studentLogin()
    {
        $modalState = $this->resolveLoginCaptchaModalState(['student']);

        return view('auth/login', $this->buildLoginViewData([
            'title'       => 'SentryLink | Student Login',
            'badge'       => 'Student Login',
            'portalLabel' => 'Student',
            'heroBadge'   => 'ACLC Mandaue',
            'heroTitle'   => 'SentryLink',
            'heroText'    => 'Student access for event tickets, live QR refresh, and attendance history.',
            'submitLabel' => 'Open Student Portal',
            'actionUrl'   => app_url('s/auth/login'),
            'forgotUrl'   => app_url('s/auth/forgot-password'),
            'error'       => session()->getFlashdata('error') ?? '',
            'showCaptchaModal' => $modalState['show'],
            'pendingLoginEmail' => $modalState['email'],
            'pendingLoginRole'  => 'student',
            'captchaRefreshUrl' => app_url('auth/login-captcha-refresh'),
        ], $modalState['show']));
    }

    public function studentLoginPost()
    {
        return $this->handleLogin(['student']);
    }

    public function officerLogin()
    {
        $modalState = $this->resolveLoginCaptchaModalState(['ssg']);

        return view('auth/login', $this->buildLoginViewData([
            'title'       => 'SentryLink | Officer Login',
            'badge'       => 'Officer Login',
            'portalLabel' => 'Officer',
            'heroBadge'   => 'Gate Security',
            'heroTitle'   => 'SentryLink',
            'heroText'    => 'Officer access for live QR scanning, gate logs, and manual attendee lookups.',
            'submitLabel' => 'Open Officer Portal',
            'actionUrl'   => app_url('o/auth/login'),
            'forgotUrl'   => app_url('o/auth/forgot-password'),
            'error'       => session()->getFlashdata('error') ?? '',
            'showCaptchaModal' => $modalState['show'],
            'pendingLoginEmail' => $modalState['email'],
            'pendingLoginRole'  => 'ssg',
            'captchaRefreshUrl' => app_url('auth/login-captcha-refresh'),
        ], $modalState['show']));
    }

    public function officerLoginPost()
    {
        return $this->handleLogin(['ssg']);
    }

    public function adminLogin()
    {
        $modalState = $this->resolveLoginCaptchaModalState(['admin']);

        return view('auth/login', $this->buildLoginViewData([
            'title'       => 'SentryLink | Admin Login',
            'badge'       => 'Admin Login',
            'portalLabel' => 'Admin',
            'heroBadge'   => 'Operations',
            'heroTitle'   => 'SentryLink',
            'heroText'    => 'Admin access for event management, ticket imports, reports, and broadcasting.',
            'submitLabel' => 'Open Admin Portal',
            'actionUrl'   => app_url('admin/auth/login'),
            'forgotUrl'   => app_url('admin/auth/forgot-password'),
            'error'       => session()->getFlashdata('error') ?? '',
            'showCaptchaModal' => $modalState['show'],
            'pendingLoginEmail' => $modalState['email'],
            'pendingLoginRole'  => 'admin',
            'captchaRefreshUrl' => app_url('auth/login-captcha-refresh'),
        ], $modalState['show']));
    }

    public function adminLoginPost()
    {
        return $this->handleLogin(['admin']);
    }

    public function directorLogin()
    {
        $modalState = $this->resolveLoginCaptchaModalState(['director']);

        return view('auth/login', $this->buildLoginViewData([
            'title'       => 'SentryLink | Director Login',
            'badge'       => 'Director Login',
            'portalLabel' => 'Director',
            'heroBadge'   => 'Leadership',
            'heroTitle'   => 'SentryLink',
            'heroText'    => 'Director access for high-level visibility into events, admissions, and audit trails.',
            'submitLabel' => 'Open Director Portal',
            'actionUrl'   => app_url('director/auth/login'),
            'forgotUrl'   => app_url('director/auth/forgot-password'),
            'error'       => session()->getFlashdata('error') ?? '',
            'showCaptchaModal' => $modalState['show'],
            'pendingLoginEmail' => $modalState['email'],
            'pendingLoginRole'  => 'director',
            'captchaRefreshUrl' => app_url('auth/login-captcha-refresh'),
        ], $modalState['show']));
    }

    public function directorLoginPost()
    {
        return $this->handleLogin(['director']);
    }

    public function forgotPassword(string $role = 'student')
    {
        $roleConfig = [
            'student'  => ['label' => 'Student', 'accent' => '#1f66d1'],
            'ssg'      => ['label' => 'Officer', 'accent' => '#0f8b8d'],
            'admin'    => ['label' => 'Admin', 'accent' => '#9d4edd'],
            'director' => ['label' => 'Director', 'accent' => '#0f8b8d'],
        ];

        if (! isset($roleConfig[$role])) {
            $role = 'student';
        }

        $message = '';
        $status  = '';

        if ($this->request->getMethod() === 'POST') {
            $captchaError = $this->verifyTurnstile();
            if ($captchaError !== null) {
                $message = $captchaError;
                $status  = 'danger';
            } else {
            $email = trim((string) $this->request->getPost('email'));
            $user  = $this->fetchOne(
                'SELECT * FROM users WHERE email = ? AND role = ? AND deleted_at IS NULL LIMIT 1',
                [$email, $role]
            );

            if (! $user || (int) ($user['is_active'] ?? 1) !== 1 || (int) ($user['email_verified'] ?? 0) !== 1) {
                $message = 'No active verified account was found for that email and role.';
                $status  = 'danger';
            } else {
                $result = $this->portal->issuePasswordResetLink($user, 'PASSWORD_RESET_LINK_ISSUED');
                $message = $result['message'];
                $status  = $result['ok'] ? 'success' : 'danger';
            }
            }
        }

        return view('auth/forgot_password', [
            'role'       => $role,
            'roleConfig' => $roleConfig,
            'message'    => $message,
            'status'     => $status,
            'turnstileSiteKey' => (string) env('captcha.turnstileSiteKey', ''),
        ]);
    }

    public function resetPassword(string $role = 'student', string $token = '')
    {
        $roleConfig = [
            'student'  => ['label' => 'Student', 'accent' => '#1f66d1', 'login' => 's/auth/login'],
            'ssg'      => ['label' => 'Officer', 'accent' => '#0f8b8d', 'login' => 'o/auth/login'],
            'admin'    => ['label' => 'Admin', 'accent' => '#9d4edd', 'login' => 'admin/auth/login'],
            'director' => ['label' => 'Director', 'accent' => '#0f8b8d', 'login' => 'director/auth/login'],
        ];

        if (! isset($roleConfig[$role])) {
            $role = 'student';
        }

        $token = trim($token);
        $resetRequest = $token !== '' ? $this->portal->passwordResetRequest($token, $role) : null;
        $message = '';
        $status = '';
        $completed = false;

        if (! $resetRequest) {
            $message = 'This reset link is invalid or expired.';
            $status = 'danger';
        } elseif ($this->request->getMethod() === 'POST') {
            $captchaError = $this->verifyTurnstile();
            $password = (string) $this->request->getPost('password');
            $confirm = (string) $this->request->getPost('confirm_password');

            if ($captchaError !== null) {
                $message = $captchaError;
                $status = 'danger';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters.';
                $status = 'danger';
            } elseif ($password !== $confirm) {
                $message = 'Passwords do not match.';
                $status = 'danger';
            } else {
                $result = $this->portal->consumePasswordResetToken($token, $role, $password);
                $message = $result['message'];
                $status = $result['ok'] ? 'success' : 'danger';
                $completed = (bool) $result['ok'];
            }
        }

        return view('auth/reset_password', [
            'role'       => $role,
            'roleConfig' => $roleConfig,
            'token'      => $token,
            'message'    => $message,
            'status'     => $status,
            'completed'  => $completed,
            'canReset'   => $resetRequest !== null && ! $completed,
            'loginUrl'   => app_url($roleConfig[$role]['login']),
            'turnstileSiteKey' => (string) env('captcha.turnstileSiteKey', ''),
        ]);
    }

    public function emailSetup()
    {
        if (! $this->session->has('pending_user_id')) {
            return redirect()->to(app_url('s/auth/login'));
        }

        $error   = '';
        $success = '';

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('send_code') !== null) {
            $email = trim((string) $this->request->getPost('email'));
            $user  = $this->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $this->session->get('pending_user_id')]);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $existing = $this->fetchOne(
                    'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1',
                    [$email, (int) $this->session->get('pending_user_id')]
                );

                if ($existing) {
                    $error = 'That email is already assigned to another account.';
                } else {
                    $code       = $this->portal->generateVerificationCode();
                    $userName   = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                    $mailResult = $this->portal->sendVerificationCodeEmail($email, $userName !== '' ? $userName : 'Student', $code);

                    if (! $mailResult['ok']) {
                        $error = $mailResult['message'];
                    } else {
                        $this->session->set([
                            'verification_email' => $email,
                            'verification_code'  => $code,
                            'code_expiry'        => time() + 600,
                        ]);
                        $success = 'Verification code sent to your email address.';
                    }
                }
            }
        }

        if ($this->request->getMethod() === 'POST' && $this->request->getPost('verify_code') !== null) {
            $enteredCode = trim((string) $this->request->getPost('code'));

            if (! $this->session->has('verification_code') || ! $this->session->has('verification_email') || ! $this->session->has('code_expiry')) {
                $error = 'Request a new verification code first.';
            } elseif (time() > (int) $this->session->get('code_expiry')) {
                $error = 'Verification code expired. Request a new one.';
                $this->session->remove(['verification_code', 'verification_email', 'code_expiry']);
            } elseif ($enteredCode !== $this->session->get('verification_code')) {
                $error = 'Invalid verification code.';
            } else {
                $userId = (int) $this->session->get('pending_user_id');

                $this->execute(
                    'UPDATE users SET email = ?, email_verified = 1, updated_at = NOW() WHERE id = ?',
                    [(string) $this->session->get('verification_email'), $userId]
                );

                $user = $this->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId]);
                $this->portal->establishAuthenticatedSession($user);
                $this->session->remove([
                    'pending_user_id',
                    'email_setup_required',
                    'verification_email',
                    'verification_code',
                    'code_expiry',
                ]);
                $this->portal->auditLog($userId, 'EMAIL_VERIFIED', 'user', $userId);

                return redirect()->to(app_url('s/my-qr'));
            }
        }

        return view('auth/email_setup', [
            'error'   => $error,
            'success' => $success,
        ]);
    }

    public function logout()
    {
        $this->portal->logout();

        return redirect()->to(app_url('s/auth/login'));
    }

    public function refreshLoginCaptcha()
    {
        $role = strtolower(trim((string) $this->request->getPost('role')));
        $role = match ($role) {
            'officer' => 'ssg',
            default   => $role,
        };

        if (! in_array($role, ['student', 'ssg', 'admin', 'director'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'Invalid login role.',
            ]);
        }

        if ($this->pendingLoginForRoles([$role]) === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'Login session expired. Please enter credentials again.',
            ]);
        }

        $siteKey   = trim((string) env('captcha.turnstileSiteKey', ''));
        $secretKey = trim((string) env('captcha.turnstileSecretKey', ''));

        if ($siteKey !== '' && $secretKey !== '') {
            return $this->response->setJSON([
                'ok'   => true,
                'mode' => 'turnstile',
            ]);
        }

        $captcha = $this->buildLocalTextCaptcha();
        $this->session->set([
            'login_captcha_token'   => $captcha['token'],
            'login_captcha_hash'    => $captcha['hash'],
            'login_captcha_expires' => $captcha['expiry'],
        ]);

        return $this->response->setJSON([
            'ok'    => true,
            'mode'  => 'local_image_text',
            'token' => $captcha['token'],
            'image' => $captcha['image'],
        ]);
    }

    public function refreshPasswordResetCaptcha()
    {
        if (! is_array($this->user) || (int) ($this->user['id'] ?? 0) <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok'      => false,
                'message' => 'Session expired. Please sign in again.',
            ]);
        }

        $verifiedEmail = strtolower(trim((string) ($this->user['email'] ?? '')));
        if ((int) ($this->user['email_verified'] ?? 0) !== 1 || $verifiedEmail === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'      => false,
                'message' => 'A verified email is required before requesting a password reset.',
            ]);
        }

        $captcha = $this->issueAuthenticatedPasswordResetCaptcha();

        return $this->response->setJSON([
            'ok'    => true,
            'mode'  => 'local_image_text',
            'token' => $captcha['token'],
            'image' => $captcha['image'],
        ]);
    }

    private function handleLogin(array $allowedRoles)
    {
        $captchaStep = strtolower(trim((string) $this->request->getPost('captcha_step')));

        if ($captchaStep === 'cancel') {
            $this->clearPendingLogin();

            return redirect()->back();
        }

        if ($captchaStep === 'verify') {
            $pendingLogin = $this->pendingLoginForRoles($allowedRoles);
            if ($pendingLogin === null) {
                session()->setFlashdata('error', 'Login session expired. Please enter your credentials again.');

                return redirect()->back();
            }

            $captchaError = $this->verifyLoginCaptcha();
            if ($captchaError !== null) {
                session()->setFlashdata('error', $captchaError);

                return redirect()->back();
            }

            $this->clearPendingLogin();

            $result = $this->portal->login($pendingLogin['email'], $pendingLogin['password'], $allowedRoles);

            if ($result['ok']) {
                return redirect()->to($result['redirect']);
            }

            session()->setFlashdata('error', $result['message']);

            return redirect()->back()->withInput();
        }

        $email    = $this->portal->sanitizeEmailInput((string) $this->request->getPost('email'));
        $password = $this->portal->sanitizePasswordInput((string) $this->request->getPost('password'));

        if ($email === '' || $password === '') {
            session()->setFlashdata('error', 'Email and password are required.');

            return redirect()->back()->withInput();
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('error', 'Enter a valid email address.');

            return redirect()->back()->withInput();
        }

        $this->setPendingLogin($email, $password, $allowedRoles);

        return redirect()->back()->withInput();
    }

    private function buildLoginViewData(array $data, bool $captchaRequired = false): array
    {
        $siteKey   = trim((string) env('captcha.turnstileSiteKey', ''));
        $secretKey = trim((string) env('captcha.turnstileSecretKey', ''));

        if (! $captchaRequired) {
            $this->session->remove(['login_captcha_token', 'login_captcha_hash', 'login_captcha_expires']);

            return $data + [
                'turnstileSiteKey'   => '',
                'loginCaptchaMode'   => 'none',
                'localCaptchaToken'  => '',
                'localCaptchaImage'  => '',
            ];
        }

        if ($siteKey !== '' && $secretKey !== '') {
            $this->session->remove(['login_captcha_token', 'login_captcha_hash', 'login_captcha_expires']);

            return $data + [
                'turnstileSiteKey'   => $siteKey,
                'loginCaptchaMode'   => 'turnstile',
                'localCaptchaToken'  => '',
                'localCaptchaImage'  => '',
            ];
        }

        $captcha = $this->buildLocalTextCaptcha();

        $this->session->set([
            'login_captcha_token'   => $captcha['token'],
            'login_captcha_hash'    => $captcha['hash'],
            'login_captcha_expires' => $captcha['expiry'],
        ]);

        return $data + [
            'turnstileSiteKey'   => '',
            'loginCaptchaMode'   => 'local_image_text',
            'localCaptchaToken'  => $captcha['token'],
            'localCaptchaImage'  => $captcha['image'],
        ];
    }

    private function verifyLoginCaptcha(): ?string
    {
        $siteKey   = trim((string) env('captcha.turnstileSiteKey', ''));
        $secretKey = trim((string) env('captcha.turnstileSecretKey', ''));

        if ($siteKey !== '' && $secretKey !== '') {
            return $this->verifyTurnstile();
        }

        $postedToken = trim((string) $this->request->getPost('local_captcha_token'));
        $postedAnswer = trim((string) $this->request->getPost('local_captcha_answer'));
        $sessionToken = trim((string) $this->session->get('login_captcha_token'));
        $sessionHash = trim((string) $this->session->get('login_captcha_hash'));
        $expiresAt = (int) ($this->session->get('login_captcha_expires') ?? 0);

        $this->session->remove(['login_captcha_token', 'login_captcha_hash', 'login_captcha_expires']);

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

    private function resolveLoginCaptchaModalState(array $allowedRoles): array
    {
        $pendingLogin = $this->pendingLoginForRoles($allowedRoles);

        if ($pendingLogin === null) {
            return ['show' => false, 'email' => ''];
        }

        return [
            'show'  => true,
            'email' => (string) ($pendingLogin['email'] ?? ''),
        ];
    }

    private function setPendingLogin(string $email, string $password, array $allowedRoles): void
    {
        $this->session->set('pending_login', [
            'email'       => $email,
            'password'    => $password,
            'allowed'     => array_values($allowedRoles),
            'expires_at'  => time() + 300,
        ]);
    }

    private function pendingLoginForRoles(array $allowedRoles): ?array
    {
        $pending = $this->session->get('pending_login');
        if (! is_array($pending)) {
            return null;
        }

        $expiresAt = (int) ($pending['expires_at'] ?? 0);
        if ($expiresAt <= 0 || time() > $expiresAt) {
            $this->clearPendingLogin();

            return null;
        }

        $pendingAllowed = $pending['allowed'] ?? [];
        if (! is_array($pendingAllowed) || $pendingAllowed !== array_values($allowedRoles)) {
            $this->clearPendingLogin();

            return null;
        }

        $email = trim((string) ($pending['email'] ?? ''));
        $password = (string) ($pending['password'] ?? '');
        if ($email === '' || $password === '') {
            $this->clearPendingLogin();

            return null;
        }

        return [
            'email'    => $email,
            'password' => $password,
        ];
    }

    private function clearPendingLogin(): void
    {
        $this->session->remove(['pending_login', 'login_captcha_token', 'login_captcha_hash', 'login_captcha_expires']);
    }

    /**
     * @return array{token: string, hash: string, expiry: int, image: string}
     */
    private function buildLocalTextCaptcha(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $text = '';
        for ($i = 0; $i < 6; $i++) {
            $text .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $token = bin2hex(random_bytes(16));
        $hash  = hash('sha256', $token . '|' . $text);
        $expiry = time() + 600;
        $image = $this->buildCaptchaSvgDataUri($text);

        return [
            'token'  => $token,
            'hash'   => $hash,
            'expiry' => $expiry,
            'image'  => $image,
        ];
    }

    private function buildCaptchaSvgDataUri(string $text): string
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

    private function verifyTurnstile(): ?string
    {
        $siteKey = trim((string) env('captcha.turnstileSiteKey', ''));
        $secretKey = trim((string) env('captcha.turnstileSecretKey', ''));

        if ($siteKey === '' && $secretKey === '') {
            return null;
        }

        if ($siteKey === '' || $secretKey === '') {
            return 'Captcha is not configured correctly. Please contact support.';
        }

        $responseToken = trim((string) $this->request->getPost('cf-turnstile-response'));
        if ($responseToken === '') {
            return 'Please complete the captcha challenge.';
        }

        try {
            $client = service('curlrequest');
            $result = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret'   => $secretKey,
                    'response' => $responseToken,
                    'remoteip' => $this->request->getIPAddress(),
                ],
            ]);
            $payload = json_decode((string) $result->getBody(), true);
        } catch (\Throwable) {
            return 'Captcha verification failed. Please try again.';
        }

        if (! is_array($payload) || ! ($payload['success'] ?? false)) {
            return 'Captcha verification failed. Please try again.';
        }

        return null;
    }
}
