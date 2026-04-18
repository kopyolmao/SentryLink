<?php

declare(strict_types=1);

if (! function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        return site_url(ltrim($path, '/'));
    }
}

if (! function_exists('h')) {
    function h(mixed $value): string
    {
        return esc((string) $value);
    }
}

if (! function_exists('portal_timezone')) {
    function portal_timezone(): \DateTimeZone
    {
        $configured = config('App')->appTimezone ?? date_default_timezone_get();
        $timezone   = is_string($configured) && trim($configured) !== '' ? trim($configured) : date_default_timezone_get();

        try {
            return new \DateTimeZone($timezone);
        } catch (\Throwable) {
            return new \DateTimeZone(date_default_timezone_get());
        }
    }
}

if (! function_exists('portal_now')) {
    function portal_now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', portal_timezone());
    }
}

if (! function_exists('event_has_ended')) {
    function event_has_ended(array $event, ?\DateTimeInterface $reference = null): bool
    {
        $eventDate = trim((string) ($event['event_date'] ?? ''));
        $endTime   = trim((string) ($event['end_time'] ?? ''));

        if ($eventDate === '' || $endTime === '') {
            return false;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $endTime) === 1) {
            $endTime .= ':00';
        }

        $timezone = portal_timezone();
        $eventEnd = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $eventDate . ' ' . $endTime, $timezone);

        if (! ($eventEnd instanceof \DateTimeImmutable)) {
            try {
                $eventEnd = new \DateTimeImmutable($eventDate . ' ' . $endTime, $timezone);
            } catch (\Throwable) {
                return false;
            }
        }

        $current = $reference !== null
            ? \DateTimeImmutable::createFromInterface($reference)->setTimezone($timezone)
            : portal_now();

        return $current >= $eventEnd;
    }
}

if (! function_exists('shell_nav_items')) {
    function shell_nav_items(string $role): array
    {
        return match ($role) {
            'student' => [
                'qr'            => ['My QR', app_url('s/my-qr')],
                'tickets'       => ['My Tickets', app_url('s/my-tickets')],
                'account'       => ['Account', app_url('s/account')],
                'notifications' => ['Notifications', app_url('s/notifications')],
            ],
            'ssg' => [
                'dashboard' => ['Dashboard', app_url('o/dashboard')],
                'scanner'   => ['Scanner', app_url('o/scanner')],
                'gate-log'  => ['Gate Log', app_url('o/gate-log')],
                'lookup'    => ['Manual Lookup', app_url('o/gate-log/lookup')],
                'settings'  => ['Settings', app_url('o/settings')],
            ],
            'admin' => [
                'dashboard'  => ['Dashboard', app_url('admin/dashboard')],
                'events'     => ['Events', app_url('admin/events')],
                'students'   => ['Students', app_url('admin/students')],
                'tickets'    => ['Tickets', app_url('admin/tickets')],
                'import'     => ['Receipt Import', app_url('admin/tickets/import-receipts')],
                'admissions' => ['Admissions', app_url('admin/admissions')],
                'broadcast'  => ['Broadcast', app_url('admin/notifications/broadcast')],
                'reports'    => ['Reports', app_url('admin/reports')],
                'audit'      => ['Audit Logs', app_url('admin/audit-logs')],
                'admins'     => ['Accounts', app_url('admin/admins')],
                'settings'   => ['Settings', app_url('admin/settings')],
            ],
            'director' => [
                'dashboard'  => ['Dashboard', app_url('director/dashboard')],
                'events'     => ['Events', app_url('director/events')],
                'admissions' => ['Admissions', app_url('director/admissions')],
                'reports'    => ['Reports', app_url('director/reports')],
                'audit'      => ['Audit Logs', app_url('director/audit-logs')],
                'settings'   => ['Settings', app_url('director/settings')],
            ],
            default => [],
        };
    }
}

if (! function_exists('shell_role_label')) {
    function shell_role_label(string $role): string
    {
        return match ($role) {
            'student'  => 'Student',
            'ssg'      => 'Officer',
            'admin'    => 'Admin',
            'director' => 'Director',
            default    => ucfirst($role),
        };
    }
}

if (! function_exists('shell_accent')) {
    function shell_accent(string $role): string
    {
        return match ($role) {
            'student'  => '#1f66d1',
            'ssg'      => '#0f8b8d',
            'admin'    => '#9d4edd',
            'director' => '#0f8b8d',
            default    => '#1f66d1',
        };
    }
}

if (! function_exists('shell_start')) {
    function shell_start(string $title, array $user, string $role, string $active, string $pageHeading, string $pageSubheading = ''): void
    {
        $accent   = shell_accent($role);
        $navItems = shell_nav_items($role);
        $logout   = match ($role) {
            'student'  => app_url('s/auth/logout'),
            'ssg'      => app_url('o/auth/logout'),
            'admin'    => app_url('admin/auth/logout'),
            'director' => app_url('director/auth/logout'),
            default    => app_url('logout'),
        };
        $userPhotoPath = trim((string) ($user['profile_photo'] ?? ''));
        $userPhotoUrl = '';
        if ($userPhotoPath !== '') {
            $userPhotoUrl = preg_match('/^https?:\/\//i', $userPhotoPath) === 1
                ? $userPhotoPath
                : app_url(ltrim($userPhotoPath, '/'));
        }
        $userInitial = strtoupper(substr(trim((string) ($user['first_name'] ?? shell_role_label($role))), 0, 1));
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0a1020;
    --panel: #121a2d;
    --panel-2: #18223a;
    --border: rgba(255,255,255,0.08);
    --text: #edf3ff;
    --muted: #9fb0cf;
    --accent: <?= $accent ?>;
    --font-body: "Manrope", sans-serif;
    --font-headline: "Newsreader", serif;
    --scroll-track: rgba(8, 14, 26, 0.92);
    --scroll-track-hidden: rgba(8, 14, 26, 0);
    --scroll-thumb: rgba(159, 176, 207, 0.44);
    --scroll-thumb-hidden: rgba(159, 176, 207, 0);
    --scroll-thumb-strong: rgba(204, 189, 255, 0.66);
}
body { margin: 0; background: linear-gradient(180deg, #08101d 0%, #101a2d 100%); color: var(--text); font-family: var(--font-body); }
h1, h2, h3, h4, .brand, .heading h1 { font-family: var(--font-headline); }
html { scrollbar-width: thin; scrollbar-color: transparent transparent; }
*::-webkit-scrollbar { width: 10px; height: 10px; }
*::-webkit-scrollbar-track {
    background: var(--scroll-track-hidden);
    transition: background-color 220ms ease;
}
*::-webkit-scrollbar-thumb {
    background: var(--scroll-thumb-hidden);
    border-radius: 999px;
    border: 2px solid transparent;
    transition: background-color 220ms ease, border-color 220ms ease;
}
*::-webkit-scrollbar-thumb:hover {
    background: var(--scroll-thumb-hidden);
}
*::-webkit-scrollbar-corner { background: var(--scroll-track-hidden); }
html.scrollbar-active { scrollbar-color: var(--scroll-thumb) var(--scroll-track); }
html.scrollbar-active *::-webkit-scrollbar-track { background: var(--scroll-track); }
html.scrollbar-active *::-webkit-scrollbar-thumb {
    background: var(--scroll-thumb);
    border-color: var(--scroll-track);
}
html.scrollbar-active *::-webkit-scrollbar-thumb:hover { background: var(--scroll-thumb-strong); }
html.scrollbar-active *::-webkit-scrollbar-corner { background: var(--scroll-track); }
.sidebar,
.table-wrap {
    scrollbar-width: thin;
    scrollbar-color: transparent transparent;
}
.sidebar:hover,
.sidebar.scrollbar-active-local,
.table-wrap:hover,
.table-wrap.scrollbar-active-local {
    scrollbar-color: var(--scroll-thumb) var(--scroll-track);
}
.sidebar:hover::-webkit-scrollbar-track,
.sidebar.scrollbar-active-local::-webkit-scrollbar-track,
.table-wrap:hover::-webkit-scrollbar-track,
.table-wrap.scrollbar-active-local::-webkit-scrollbar-track {
    background: var(--scroll-track);
}
.sidebar:hover::-webkit-scrollbar-thumb,
.sidebar.scrollbar-active-local::-webkit-scrollbar-thumb,
.table-wrap:hover::-webkit-scrollbar-thumb,
.table-wrap.scrollbar-active-local::-webkit-scrollbar-thumb {
    background: var(--scroll-thumb);
    border-color: var(--scroll-track);
}
.sidebar:hover::-webkit-scrollbar-thumb:hover,
.sidebar.scrollbar-active-local::-webkit-scrollbar-thumb:hover,
.table-wrap:hover::-webkit-scrollbar-thumb:hover,
.table-wrap.scrollbar-active-local::-webkit-scrollbar-thumb:hover {
    background: var(--scroll-thumb-strong);
}
.sidebar:hover::-webkit-scrollbar-corner,
.sidebar.scrollbar-active-local::-webkit-scrollbar-corner,
.table-wrap:hover::-webkit-scrollbar-corner,
.table-wrap.scrollbar-active-local::-webkit-scrollbar-corner {
    background: var(--scroll-track);
}
.app-shell { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
.sidebar {
    background: rgba(9,15,28,0.96);
    border-right: 1px solid var(--border);
    padding: 24px 16px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
}
.brand { font-size: 28px; font-weight: 800; margin-bottom: 18px; }
.brand span { color: var(--accent); }
.role-badge { display: inline-block; font-size: 12px; padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 999px; color: var(--accent); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-bottom: 22px; }
.sidebar a { display: block; color: var(--muted); text-decoration: none; padding: 11px 12px; border-radius: 12px; margin-bottom: 6px; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.06); color: #fff; }
.sidebar .logout-link {
    margin-top: 18px;
    color: #ff7a7a;
    background: rgba(255, 72, 72, 0.14);
    border: 1px solid rgba(255, 109, 109, 0.34);
}
.sidebar .logout-link:hover,
.sidebar .logout-link:focus {
    color: #ffe2e2;
    background: rgba(255, 72, 72, 0.26);
    border-color: rgba(255, 125, 125, 0.5);
}
.content { padding: 28px; }
.topbar { display: flex; justify-content: space-between; align-items: start; gap: 16px; margin-bottom: 24px; }
.heading h1 { margin: 0; font-size: 30px; }
.heading p { margin: 6px 0 0; color: var(--muted); }
.user-card { background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 16px; padding: 14px 16px; min-width: 220px; display: flex; gap: 12px; align-items: center; }
.user-card small { display: block; color: var(--muted); }
.user-card-body { min-width: 0; }
.user-avatar, .user-avatar-fallback { width: 46px; height: 46px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, 0.16); flex-shrink: 0; }
.user-avatar { object-fit: cover; }
.user-avatar-fallback { display: grid; place-items: center; font-weight: 700; background: rgba(255,255,255,0.08); color: #fff; }
.panel { background: rgba(15,22,38,0.92); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 20px; }
.metric { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 18px; padding: 18px; height: 100%; }
.metric .value { font-size: 32px; font-weight: 800; margin-top: 6px; }
.table-wrap { overflow-x: auto; }
.table-dark { --bs-table-bg: transparent; --bs-table-striped-bg: rgba(255,255,255,0.02); --bs-table-border-color: var(--border); margin-bottom: 0; }
.btn-primary { background: var(--accent); border-color: var(--accent); }
.btn-outline-light { border-color: var(--border); color: var(--text); }
.form-control, .form-select, .form-control:focus, .form-select:focus { background: #0d1527; color: #fff; border-color: #2b3959; }
.form-control:disabled, .form-control[readonly], .form-select:disabled {
    background: #121a2d;
    color: #cfd8ee;
    -webkit-text-fill-color: #cfd8ee;
    border-color: #2b3959;
    opacity: 1;
}
.form-check-input { background-color: #0d1527; border-color: #2b3959; }
.list-soft { list-style: none; padding: 0; margin: 0; }
.list-soft li { padding: 12px 0; border-bottom: 1px solid var(--border); }
.list-soft li:last-child { border-bottom: none; }
code { color: #bfd3ff; }
@media (max-width: 992px) {
    .app-shell { grid-template-columns: 1fr; }
    .sidebar { position: relative; height: auto; overflow-y: visible; }
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
                <?php if ($userPhotoUrl !== ''): ?>
                    <img class="user-avatar" src="<?= h($userPhotoUrl) ?>" alt="User photo">
                <?php else: ?>
                    <div class="user-avatar-fallback" aria-hidden="true"><?= h($userInitial !== '' ? $userInitial : 'U') ?></div>
                <?php endif; ?>
                <div class="user-card-body">
                    <div><?= h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></div>
                    <small><?= h($user['email'] ?? '') ?></small>
                    <small><?= h($user['student_id'] ?? shell_role_label($role)) ?></small>
                </div>
            </div>
        </div>
        <?php
    }
}

if (! function_exists('shell_end')) {
    function shell_end(string $script = ''): void
    {
        $scrollScript = <<<'HTML'
<script>
(() => {
    const root = document.documentElement;
    let rootTimer = null;

    const activateRootScrollbar = () => {
        root.classList.add("scrollbar-active");
        clearTimeout(rootTimer);
        rootTimer = setTimeout(() => root.classList.remove("scrollbar-active"), 700);
    };

    window.addEventListener("scroll", activateRootScrollbar, { passive: true });
    window.addEventListener("wheel", activateRootScrollbar, { passive: true });
    window.addEventListener("touchmove", activateRootScrollbar, { passive: true });

    const bindLocalScrollbar = (el) => {
        if (!el) {
            return;
        }

        let localTimer = null;
        const activateLocalScrollbar = () => {
            el.classList.add("scrollbar-active-local");
            clearTimeout(localTimer);
            localTimer = setTimeout(() => el.classList.remove("scrollbar-active-local"), 700);
        };

        el.addEventListener("scroll", activateLocalScrollbar, { passive: true });
        el.addEventListener("wheel", activateLocalScrollbar, { passive: true });
        el.addEventListener("touchmove", activateLocalScrollbar, { passive: true });
    };

    bindLocalScrollbar(document.querySelector(".sidebar"));
    document.querySelectorAll(".table-wrap").forEach(bindLocalScrollbar);
})();
</script>
HTML;

        echo $scrollScript;
        echo $script;
        ?>
    </main>
</div>
</body>
</html>
        <?php
    }
}

if (! function_exists('ticket_status_badge')) {
    function ticket_status_badge(string $status): array
    {
        return match ($status) {
            'paid'      => ['Paid', 'success'],
            'free'      => ['Free', 'success'],
            'pending'   => ['Pending', 'warning'],
            'cancelled' => ['Cancelled', 'danger'],
            default     => [ucfirst($status), 'secondary'],
        };
    }
}

if (! function_exists('ticket_status_label')) {
    function ticket_status_label(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'paid'      => 'Paid',
            'free'      => 'Free',
            'pending'   => 'Pending',
            'cancelled' => 'Cancelled',
            default     => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Unknown',
        };
    }
}

if (! function_exists('event_status_badge')) {
    function event_status_badge(string $status): string
    {
        return match ($status) {
            'draft'     => 'secondary',
            'open'      => 'info',
            'ongoing'   => 'success',
            'closed'    => 'dark',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}

if (! function_exists('event_status_label')) {
    function event_status_label(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'draft'     => 'Draft',
            'open'      => 'Open',
            'ongoing'   => 'Ongoing',
            'closed'    => 'Closed',
            'cancelled' => 'Cancelled',
            default     => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Unknown',
        };
    }
}

if (! function_exists('admission_status_badge')) {
    function admission_status_badge(string $status): string
    {
        return match (strtolower(trim($status))) {
            'in', 'admitted' => 'success',
            'out'            => 'info',
            'duplicate'      => 'warning',
            'rejected'       => 'danger',
            default     => 'secondary',
        };
    }
}

if (! function_exists('admission_status_label')) {
    function admission_status_label(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'in'        => 'In',
            'out'       => 'Out',
            'admitted'  => 'Admitted',
            'duplicate' => 'Duplicate',
            'rejected'  => 'Rejected',
            default     => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Unknown',
        };
    }
}
