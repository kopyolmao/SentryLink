<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('dashboard', 'Home::index', ['filter' => 'role:student,ssg,admin,director']);
$routes->get('login', static fn () => redirect()->to(site_url('s/auth/login')));
$routes->get('logout', static fn () => redirect()->to(site_url('s/auth/logout')));
$routes->get('forgot-password', static fn () => redirect()->to(site_url('s/auth/forgot-password')));
$routes->post('auth/login-captcha-refresh', 'AuthController::refreshLoginCaptcha', ['filter' => 'guest']);
$routes->post('auth/password-reset-captcha-refresh', 'AuthController::refreshPasswordResetCaptcha', ['filter' => 'role:student,ssg,admin,director']);

// Legacy plain-PHP entry points
$routes->get('login.php', static fn () => redirect()->to(site_url('s/auth/login')));
$routes->get('loginadmin.php', static fn () => redirect()->to(site_url('admin/auth/login')));
$routes->match(['GET', 'POST'], 'forgot_password.php', static fn () => redirect()->to(site_url('s/auth/forgot-password')));
$routes->get('logout.php', static fn () => redirect()->to(site_url('s/auth/logout')));
$routes->get('officers/loginofficers.php', static fn () => redirect()->to(site_url('o/auth/login')));

// Apache/XAMPP may pass the project folder into the route path when served from /syntrelink
$routes->get('syntrelink/login.php', static fn () => redirect()->to(site_url('s/auth/login')));
$routes->get('syntrelink/loginadmin.php', static fn () => redirect()->to(site_url('admin/auth/login')));
$routes->match(['GET', 'POST'], 'syntrelink/forgot_password.php', static fn () => redirect()->to(site_url('s/auth/forgot-password')));
$routes->get('syntrelink/logout.php', static fn () => redirect()->to(site_url('s/auth/logout')));
$routes->get('syntrelink/officers/loginofficers.php', static fn () => redirect()->to(site_url('o/auth/login')));

$routes->group('s', static function ($routes): void {
    $routes->get('/', static fn () => redirect()->to(site_url('s/my-qr')));
    $routes->get('auth/login', 'AuthController::studentLogin', ['filter' => 'guest']);
    $routes->post('auth/login', 'AuthController::studentLoginPost', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/forgot-password', 'AuthController::forgotPassword/student', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/reset-password/(:segment)', 'AuthController::resetPassword/student/$1', ['filter' => 'guest']);
    $routes->get('auth/logout', 'AuthController::logout');

    $routes->get('dashboard', 'StudentController::dashboard', ['filter' => 'role:student']);
    $routes->get('my-qr', 'StudentController::myQr', ['filter' => 'role:student']);
    $routes->get('my-tickets', 'StudentController::myTickets', ['filter' => 'role:student']);
    $routes->match(['GET', 'POST'], 'account', 'StudentController::account', ['filter' => 'role:student']);
    $routes->match(['GET', 'POST'], 'notifications', 'StudentController::notifications', ['filter' => 'role:student']);
    $routes->match(['GET', 'POST'], 'profile', 'StudentController::profile', ['filter' => 'role:student']);
    $routes->get('settings', 'StudentController::settings', ['filter' => 'role:student']);
    $routes->match(['GET', 'POST'], 'settings/reset-password', 'StudentController::resetPassword', ['filter' => 'role:student']);
    $routes->match(['GET', 'POST'], 'settings/email-setup', 'AuthController::emailSetup', ['filter' => 'guest']);
});

$routes->group('o', static function ($routes): void {
    $routes->get('/', static fn () => redirect()->to(site_url('o/scanner')));
    $routes->get('auth/login', 'AuthController::officerLogin', ['filter' => 'guest']);
    $routes->post('auth/login', 'AuthController::officerLoginPost', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/forgot-password', 'AuthController::forgotPassword/ssg', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/reset-password/(:segment)', 'AuthController::resetPassword/ssg/$1', ['filter' => 'guest']);
    $routes->get('auth/logout', 'AuthController::logout');

    $routes->get('dashboard', 'OfficerController::dashboard', ['filter' => 'role:ssg']);
    $routes->get('scanner', 'OfficerController::scanner', ['filter' => 'role:ssg']);
    $routes->get('gate-log', 'OfficerController::gateLog', ['filter' => 'role:ssg']);
    $routes->get('gate-log/lookup', 'OfficerController::manualLookup', ['filter' => 'role:ssg']);
    $routes->match(['GET', 'POST'], 'settings', 'OfficerController::settings', ['filter' => 'role:ssg']);
});

$routes->group('admin', static function ($routes): void {
    $routes->get('/', static fn () => redirect()->to(site_url('admin/dashboard')));
    $routes->get('auth/login', 'AuthController::adminLogin', ['filter' => 'guest']);
    $routes->post('auth/login', 'AuthController::adminLoginPost', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/forgot-password', 'AuthController::forgotPassword/admin', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/reset-password/(:segment)', 'AuthController::resetPassword/admin/$1', ['filter' => 'guest']);
    $routes->get('auth/logout', 'AuthController::logout');

    $routes->get('dashboard', 'AdminController::dashboard', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'events', 'AdminController::events', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'events/(:num)/activities', 'AdminController::activities/$1', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'students', 'AdminController::students', ['filter' => 'role:admin']);
    $routes->get('tickets', 'AdminController::tickets', ['filter' => 'role:admin']);
    $routes->get('admissions', 'AdminController::admissions', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'notifications/broadcast', 'AdminController::broadcast', ['filter' => 'role:admin']);
    $routes->get('reports', 'AdminController::reports', ['filter' => 'role:admin']);
    $routes->get('audit-logs', 'AdminController::auditLogs', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'admins', 'AdminController::accounts', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'settings', 'AdminController::settings', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'tickets/import-receipts', 'AdminController::importReceipts', ['filter' => 'role:admin']);
    $routes->match(['GET', 'POST'], 'tickets/import-receipts/confirm', 'AdminController::importConfirm', ['filter' => 'role:admin']);
    $routes->get('tickets/import-receipts/success', 'AdminController::importSuccess', ['filter' => 'role:admin']);
});

$routes->group('director', static function ($routes): void {
    $routes->get('/', static fn () => redirect()->to(site_url('director/dashboard')));
    $routes->get('auth/login', 'AuthController::directorLogin', ['filter' => 'guest']);
    $routes->post('auth/login', 'AuthController::directorLoginPost', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/forgot-password', 'AuthController::forgotPassword/director', ['filter' => 'guest']);
    $routes->match(['GET', 'POST'], 'auth/reset-password/(:segment)', 'AuthController::resetPassword/director/$1', ['filter' => 'guest']);
    $routes->get('auth/logout', 'AuthController::logout');

    $routes->get('dashboard', 'DirectorController::dashboard', ['filter' => 'role:director']);
    $routes->get('events', 'DirectorController::events', ['filter' => 'role:director']);
    $routes->get('admissions', 'DirectorController::admissions', ['filter' => 'role:director']);
    $routes->get('reports', 'DirectorController::reports', ['filter' => 'role:director']);
    $routes->get('audit-logs', 'DirectorController::auditLogs', ['filter' => 'role:director']);
    $routes->match(['GET', 'POST'], 'settings', 'DirectorController::settings', ['filter' => 'role:director']);
});

$routes->group('api', static function ($routes): void {
    $routes->match(['GET', 'POST'], 'qr/generate', 'StudentController::generateQr', ['filter' => 'role:student']);
    $routes->post('qr/hold', 'StudentController::holdQr', ['filter' => 'role:student']);
    $routes->post('qr/heartbeat', 'StudentController::heartbeat', ['filter' => 'role:student']);
    $routes->get('student/ticket-state', 'StudentController::ticketState', ['filter' => 'role:student']);
    $routes->post('qr/validate', 'OfficerController::validateQr', ['filter' => 'role:ssg']);
    $routes->post('offline/sync', 'OfficerController::offlineSync', ['filter' => 'role:ssg']);
    $routes->get('gate-activity-state', 'OfficerController::gateActivityState', ['filter' => 'role:ssg,admin,director']);
    $routes->post('gate-activity-state', 'OfficerController::gateActivityState', ['filter' => 'role:ssg']);
    $routes->get('gate-log/(:num)', 'OfficerController::gateLogFeed/$1', ['filter' => 'role:ssg,admin,director']);
    $routes->get('notifications', 'StudentController::notificationsFeed', ['filter' => 'role:student,ssg,admin,director']);
    $routes->post('notifications/read', 'StudentController::markNotificationsRead', ['filter' => 'role:student,ssg,admin,director']);
    $routes->get('events/(:num)/attendee-cache', 'OfficerController::attendeeCache/$1', ['filter' => 'role:ssg']);
    $routes->get('qr-secret', 'AdminController::getQrSecret', ['filter' => 'role:admin']);
});
