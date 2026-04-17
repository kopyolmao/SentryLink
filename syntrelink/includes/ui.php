<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function shell_nav_items(string $role): array
{
    return match ($role) {
        'student' => [
            'dashboard' => ['Dashboard', app_url('s/dashboard')],
            'tickets' => ['My Tickets', app_url('s/my-tickets')],
            'qr' => ['My QR', app_url('s/my-qr')],
            'notifications' => ['Notifications', app_url('s/notifications')],
            'profile' => ['Profile', app_url('s/profile')],
            'settings' => ['Settings', app_url('s/settings')],
        ],
        'ssg' => [
            'dashboard' => ['Dashboard', app_url('o/dashboard')],
            'scanner' => ['Scanner', app_url('o/scanner')],
            'gate-log' => ['Gate Log', app_url('o/gate-log')],
            'lookup' => ['Manual Lookup', app_url('o/gate-log/lookup')],
        ],
        'admin' => [
            'dashboard' => ['Dashboard', app_url('admin/dashboard')],
            'events' => ['Events', app_url('admin/events')],
            'students' => ['Students', app_url('admin/students')],
            'tickets' => ['Tickets', app_url('admin/tickets')],
            'import' => ['Receipt Import', app_url('admin/tickets/import-receipts')],
            'admissions' => ['Admissions', app_url('admin/admissions')],
            'broadcast' => ['Broadcast', app_url('admin/notifications/broadcast')],
            'reports' => ['Reports', app_url('admin/reports')],
            'audit' => ['Audit Logs', app_url('admin/audit-logs')],
            'admins' => ['Accounts', app_url('admin/admins')],
            'settings' => ['Settings', app_url('admin/settings')],
        ],
        'director' => [
            'dashboard' => ['Dashboard', app_url('director/dashboard')],
            'events' => ['Events', app_url('director/events')],
            'admissions' => ['Admissions', app_url('director/admissions')],
            'reports' => ['Reports', app_url('director/reports')],
            'audit' => ['Audit Logs', app_url('director/audit-logs')],
        ],
        default => [],
    };
}

function shell_role_label(string $role): string
{
    return match ($role) {
        'student' => 'Student',
        'ssg' => 'Officer',
        'admin' => 'Admin',
        'director' => 'Director',
        default => ucfirst($role),
    };
}

function shell_accent(string $role): string
{
    return match ($role) {
        'student' => '#1f66d1',
        'ssg' => '#0f8b8d',
        'admin' => '#9d4edd',
        'director' => '#0f8b8d',
        default => '#1f66d1',
    };
}

function shell_start(string $title, array $user, string $role, string $active, string $pageHeading, string $pageSubheading = ''): void
{
    $accent = shell_accent($role);
    $navItems = shell_nav_items($role);
    $logout = match ($role) {
        'student' => app_url('s/auth/logout'),
        'ssg' => app_url('o/auth/logout'),
        'admin' => app_url('admin/auth/logout'),
        'director' => app_url('director/auth/logout'),
        default => app_url('logout.php'),
    };
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --bg: #0a1020;
    --panel: #121a2d;
    --panel-2: #18223a;
    --border: rgba(255,255,255,0.08);
    --text: #edf3ff;
    --muted: #9fb0cf;
    --accent: <?= $accent ?>;
}
body { margin: 0; background: linear-gradient(180deg, #08101d 0%, #101a2d 100%); color: var(--text); font-family: Arial, Helvetica, sans-serif; }
.app-shell { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
.sidebar { background: rgba(9,15,28,0.96); border-right: 1px solid var(--border); padding: 24px 16px; position: sticky; top: 0; height: 100vh; }
.brand { font-size: 28px; font-weight: 800; margin-bottom: 18px; }
.brand span { color: var(--accent); }
.role-badge { display: inline-block; font-size: 12px; padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 999px; color: var(--accent); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-bottom: 22px; }
.sidebar a { display: block; color: var(--muted); text-decoration: none; padding: 11px 12px; border-radius: 12px; margin-bottom: 6px; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.06); color: #fff; }
.sidebar .logout-link { margin-top: 18px; color: #ffd0d0; }
.content { padding: 28px; }
.topbar { display: flex; justify-content: space-between; align-items: start; gap: 16px; margin-bottom: 24px; }
.heading h1 { margin: 0; font-size: 30px; }
.heading p { margin: 6px 0 0; color: var(--muted); }
.user-card { background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 16px; padding: 14px 16px; min-width: 220px; }
.user-card small { display: block; color: var(--muted); }
.panel { background: rgba(15,22,38,0.92); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 20px; }
.metric { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 18px; padding: 18px; height: 100%; }
.metric .value { font-size: 32px; font-weight: 800; margin-top: 6px; }
.table-wrap { overflow-x: auto; }
.table-dark { --bs-table-bg: transparent; --bs-table-striped-bg: rgba(255,255,255,0.02); --bs-table-border-color: var(--border); margin-bottom: 0; }
.btn-primary { background: var(--accent); border-color: var(--accent); }
.btn-outline-light { border-color: var(--border); color: var(--text); }
.form-control, .form-select, .form-control:focus, .form-select:focus { background: #0d1527; color: #fff; border-color: #2b3959; }
.list-soft { list-style: none; padding: 0; margin: 0; }
.list-soft li { padding: 12px 0; border-bottom: 1px solid var(--border); }
.list-soft li:last-child { border-bottom: none; }
@media (max-width: 992px) {
    .app-shell { grid-template-columns: 1fr; }
    .sidebar { position: relative; height: auto; }
}
</style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Sentry<span>Link</span></div>
        <div class="role-badge"><?= h(shell_role_label($role)) ?></div>
        <?php foreach ($navItems as $key => $item): ?>
            <a class="<?= $key === $active ? 'active' : '' ?>" href="<?= h($item[1]) ?>"><?= h($item[0]) ?></a>
        <?php endforeach; ?>
        <a class="logout-link" href="<?= h($logout) ?>">Logout</a>
    </aside>
    <main class="content">
        <div class="topbar">
            <div class="heading">
                <h1><?= h($pageHeading) ?></h1>
                <?php if ($pageSubheading !== ''): ?>
                    <p><?= h($pageSubheading) ?></p>
                <?php endif; ?>
            </div>
            <div class="user-card">
                <div><?= h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></div>
                <small><?= h($user['email'] ?? '') ?></small>
                <small><?= h($user['student_id'] ?? shell_role_label($role)) ?></small>
            </div>
        </div>
    <?php
}

function shell_end(string $script = ''): void
{
    echo $script;
    ?>
    </main>
</div>
</body>
</html>
    <?php
}
