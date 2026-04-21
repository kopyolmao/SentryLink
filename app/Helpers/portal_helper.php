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
                'tickets'       => ['My Transactions', app_url('s/my-tickets')],
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
            'cashier' => [
                'dashboard' => ['Dashboard', app_url('cashier/dashboard')],
                'settings'  => ['Settings', app_url('cashier/settings')],
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
            'cashier'  => 'Cashier',
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
            'cashier'  => '#d14f27',
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
            'cashier'  => app_url('cashier/auth/logout'),
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
<script>document.documentElement.classList.add('page-loading');</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
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
.initial-loader {
    position: fixed;
    inset: 0;
    z-index: 2600;
    display: grid;
    place-items: center;
    background: linear-gradient(180deg, #08101d 0%, #101a2d 100%);
    opacity: 0;
    visibility: hidden;
    transition: opacity 220ms ease, visibility 220ms ease;
}
.initial-loader-card {
    width: min(92vw, 560px);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(15,22,38,0.92);
    padding: 1rem;
}
.initial-loader-row {
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(255,255,255,0.07), rgba(255,255,255,0.2), rgba(255,255,255,0.07));
    background-size: 200% 100%;
    animation: loaderPulse 1.2s linear infinite;
}
.initial-loader-row + .initial-loader-row {
    margin-top: 0.7rem;
}
.initial-loader-row.w-80 { width: 80%; }
.initial-loader-row.w-60 { width: 60%; }
.initial-loader-row.w-40 { width: 40%; }
@keyframes loaderPulse {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
html.page-loading .initial-loader {
    opacity: 1;
    visibility: visible;
}
html.page-loading .app-shell {
    visibility: hidden;
}
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
.content.is-navigating {
    opacity: 0.72;
    pointer-events: none;
    transition: opacity 180ms ease;
}
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
.filter-actions {
    margin-top: 0.55rem;
}
.table-dark { --bs-table-bg: transparent; --bs-table-striped-bg: rgba(255,255,255,0.02); --bs-table-border-color: var(--border); margin-bottom: 0; }
.btn {
    transition: background-color 140ms ease, border-color 140ms ease, box-shadow 140ms ease, transform 120ms ease;
}
.btn:active {
    transform: translateY(1px);
}
.btn-primary {
    background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 88%, white 12%), var(--accent));
    border-color: var(--accent);
    color: #f5f7ff;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.24);
}
.btn-primary:hover,
.btn-primary:focus {
    background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 82%, white 18%), color-mix(in srgb, var(--accent) 92%, black 8%));
    border-color: color-mix(in srgb, var(--accent) 80%, white 20%);
    color: #fff;
}
.btn-success {
    background: linear-gradient(180deg, #2ca46a, #228653);
    border-color: #2ca46a;
    color: #f3fff8;
}
.btn-success:hover,
.btn-success:focus {
    background: linear-gradient(180deg, #39b978, #2ca46a);
    border-color: #43c182;
    color: #fff;
}
.btn-warning {
    background: linear-gradient(180deg, #f4bf58, #d89d2a);
    border-color: #f4bf58;
    color: #221a08;
}
.btn-warning:hover,
.btn-warning:focus {
    background: linear-gradient(180deg, #ffd06f, #e4af44);
    border-color: #ffd06f;
    color: #1f1808;
}
.btn-info {
    background: linear-gradient(180deg, #4f92db, #356fb2);
    border-color: #4f92db;
    color: #f4f9ff;
}
.btn-info:hover,
.btn-info:focus {
    background: linear-gradient(180deg, #61a5ee, #427fca);
    border-color: #61a5ee;
    color: #fff;
}
.btn-danger {
    background: linear-gradient(180deg, #e2676f, #bf434d);
    border-color: #e2676f;
    color: #fff4f5;
}
.btn-danger:hover,
.btn-danger:focus {
    background: linear-gradient(180deg, #f07a82, #cf515c);
    border-color: #f07a82;
    color: #fff;
}
.btn-outline-light { border-color: var(--border); color: var(--text); }
.form-control, .form-select, .form-control:focus, .form-select:focus { background: #0d1527; color: #fff; border-color: #2b3959; }
.form-control.is-invalid,
.form-select.is-invalid,
.form-control.is-invalid:focus,
.form-select.is-invalid:focus {
    border-color: rgba(240, 122, 130, 0.92);
    box-shadow: 0 0 0 .2rem rgba(207, 81, 92, 0.18);
}
.field-validation-message {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    line-height: 1.35;
    color: #f6aab1;
}
.form-control[type="number"] {
    color-scheme: dark;
}
.form-control[type="number"]::-webkit-outer-spin-button,
.form-control[type="number"]::-webkit-inner-spin-button {
    margin: 0;
    opacity: 1;
    border-left: 1px solid #2b3959;
    background: linear-gradient(180deg, rgba(157, 78, 221, 0.34) 0%, rgba(31, 102, 209, 0.26) 100%);
}
.form-control[type="number"]::-webkit-inner-spin-button:hover,
.form-control[type="number"]::-webkit-outer-spin-button:hover {
    background: linear-gradient(180deg, rgba(157, 78, 221, 0.52) 0%, rgba(31, 102, 209, 0.38) 100%);
}
.form-control:disabled, .form-control[readonly], .form-select:disabled {
    background: #121a2d;
    color: #cfd8ee;
    -webkit-text-fill-color: #cfd8ee;
    border-color: #2b3959;
    opacity: 1;
}
.form-check-input { background-color: #0d1527; border-color: #2b3959; }
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}
.password-field {
    position: relative;
}
.password-field .js-password-input {
    padding-right: 2.9rem;
}
.password-toggle {
    position: absolute;
    right: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: rgba(202, 195, 217, 0.45);
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 140ms ease;
}
.password-toggle:hover,
.password-toggle:focus {
    color: var(--accent);
}
.password-toggle .js-password-icon {
    font-size: 1.25rem;
}
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
<div class="initial-loader" id="initialPageLoader" aria-hidden="true">
    <div class="initial-loader-card">
        <div class="initial-loader-row w-40"></div>
        <div class="initial-loader-row w-80"></div>
        <div class="initial-loader-row w-60"></div>
        <div class="initial-loader-row w-80"></div>
    </div>
</div>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Sentry<span>Link</span></div>
        <div class="role-badge"><?= h(shell_role_label($role)) ?></div>
        <?php foreach ($navItems as $key => $item): ?>
            <a class="<?= $key === $active ? 'active' : '' ?>" href="<?= h($item[1]) ?>"><?= h($item[0]) ?></a>
        <?php endforeach; ?>
        <a class="logout-link" href="<?= h($logout) ?>" data-no-ajax="1">Logout</a>
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
        $loaderScript = <<<'HTML'
<script>
(() => {
    const finishInitialLoader = () => {
        document.documentElement.classList.remove("page-loading");
        const loader = document.getElementById("initialPageLoader");
        if (loader) {
            loader.setAttribute("aria-hidden", "true");
            setTimeout(() => loader.remove(), 260);
        }
    };

    if (document.readyState === "complete") {
        finishInitialLoader();
        return;
    }

    window.addEventListener("load", finishInitialLoader, { once: true });
    setTimeout(finishInitialLoader, 4500);
})();
</script>
HTML;

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

        $dynamicShellScript = <<<'HTML'
<script>
(() => {
    const state = window.__sentrylinkDynamicState || (window.__sentrylinkDynamicState = {
        initialized: false,
        bound: false,
        navigating: false,
        currentScope: 0,
        scopes: new Map(),
        loadedScripts: new Set(),
    });

    const ensureScope = (scopeId) => {
        if (!state.scopes.has(scopeId)) {
            state.scopes.set(scopeId, {
                intervals: new Set(),
                timeouts: new Set(),
                listeners: [],
            });
        }

        return state.scopes.get(scopeId);
    };

    if (!state.initialized) {
        state.initialized = true;

        state.native = {
            setInterval: window.setInterval.bind(window),
            clearInterval: window.clearInterval.bind(window),
            setTimeout: window.setTimeout.bind(window),
            clearTimeout: window.clearTimeout.bind(window),
            windowAdd: window.addEventListener.bind(window),
            windowRemove: window.removeEventListener.bind(window),
            documentAdd: document.addEventListener.bind(document),
            documentRemove: document.removeEventListener.bind(document),
        };

        document.querySelectorAll("script[src]").forEach((script) => {
            try {
                state.loadedScripts.add(new URL(script.getAttribute("src") || "", window.location.href).href);
            } catch (error) {
            }
        });

        ensureScope(state.currentScope);

        window.setInterval = (handler, timeout, ...args) => {
            const intervalId = state.native.setInterval(handler, timeout, ...args);
            ensureScope(state.currentScope).intervals.add(intervalId);
            return intervalId;
        };

        window.clearInterval = (intervalId) => {
            state.scopes.forEach((scope) => {
                if (scope.intervals.has(intervalId)) {
                    scope.intervals.delete(intervalId);
                }
            });
            return state.native.clearInterval(intervalId);
        };

        window.setTimeout = (handler, timeout, ...args) => {
            const timeoutId = state.native.setTimeout(handler, timeout, ...args);
            ensureScope(state.currentScope).timeouts.add(timeoutId);
            return timeoutId;
        };

        window.clearTimeout = (timeoutId) => {
            state.scopes.forEach((scope) => {
                if (scope.timeouts.has(timeoutId)) {
                    scope.timeouts.delete(timeoutId);
                }
            });
            return state.native.clearTimeout(timeoutId);
        };

        window.addEventListener = (type, listener, options) => {
            state.native.windowAdd(type, listener, options);
            ensureScope(state.currentScope).listeners.push({
                target: "window",
                type,
                listener,
                options,
            });
        };

        document.addEventListener = (type, listener, options) => {
            state.native.documentAdd(type, listener, options);
            ensureScope(state.currentScope).listeners.push({
                target: "document",
                type,
                listener,
                options,
            });
        };
    }

    const cleanupScope = (scopeId) => {
        const scope = state.scopes.get(scopeId);
        if (!scope) {
            return;
        }

        scope.intervals.forEach((intervalId) => {
            state.native.clearInterval(intervalId);
        });
        scope.intervals.clear();

        scope.timeouts.forEach((timeoutId) => {
            state.native.clearTimeout(timeoutId);
        });
        scope.timeouts.clear();

        scope.listeners.forEach((entry) => {
            if (entry.target === "window") {
                state.native.windowRemove(entry.type, entry.listener, entry.options);
                return;
            }

            state.native.documentRemove(entry.type, entry.listener, entry.options);
        });
        scope.listeners = [];
        state.scopes.delete(scopeId);
    };

    const setNavigationState = (isNavigating) => {
        const main = document.querySelector("main.content");
        if (!main) {
            return;
        }

        main.classList.toggle("is-navigating", isNavigating);
        main.setAttribute("aria-busy", isNavigating ? "true" : "false");
    };

    const releaseViewportLocks = () => {
        if (document.body) {
            document.body.style.overflow = "";
        }
        document.documentElement.style.overflow = "";
    };

    const runInlineScript = (code) => {
        if (typeof code !== "string" || code.trim() === "") {
            return;
        }

        try {
            const executor = new Function(code);
            executor();
        } catch (error) {
            console.error("SentryLink dynamic script error:", error);
        }
    };

    const runScriptsInOrder = async (root) => {
        if (!root) {
            return;
        }

        const scripts = Array.from(root.querySelectorAll("script"));
        for (const script of scripts) {
            const src = (script.getAttribute("src") || "").trim();
            if (src !== "") {
                let absoluteSrc = "";
                try {
                    absoluteSrc = new URL(src, window.location.href).href;
                } catch (error) {
                    script.remove();
                    continue;
                }

                if (!state.loadedScripts.has(absoluteSrc)) {
                    await new Promise((resolve) => {
                        const externalScript = document.createElement("script");
                        for (const attributeName of script.getAttributeNames()) {
                            if (attributeName.toLowerCase() === "src") {
                                continue;
                            }
                            externalScript.setAttribute(attributeName, script.getAttribute(attributeName) || "");
                        }

                        externalScript.src = absoluteSrc;
                        externalScript.async = false;
                        externalScript.onload = () => resolve();
                        externalScript.onerror = () => resolve();
                        document.head.appendChild(externalScript);
                    });

                    state.loadedScripts.add(absoluteSrc);
                }

                script.remove();
                continue;
            }

            runInlineScript(script.textContent || "");
            script.remove();
        }
    };

    const isHtmlResponse = (response) => {
        const contentType = (response.headers.get("content-type") || "").toLowerCase();
        return contentType.includes("text/html") || contentType.includes("application/xhtml+xml");
    };

    const shouldBypassLink = (anchor, event) => {
        if (!anchor || anchor.dataset.noAjax === "1") {
            return true;
        }

        if (anchor.classList.contains("logout-link")) {
            return true;
        }

        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        const href = (anchor.getAttribute("href") || "").trim();
        if (href === "" || href.startsWith("#") || href.startsWith("javascript:") || href.startsWith("mailto:") || href.startsWith("tel:")) {
            return true;
        }

        const target = (anchor.getAttribute("target") || "").trim().toLowerCase();
        if (target !== "" && target !== "_self") {
            return true;
        }

        if (anchor.hasAttribute("download")) {
            return true;
        }

        let url;
        try {
            url = new URL(anchor.href, window.location.href);
        } catch (error) {
            return true;
        }

        if (url.origin !== window.location.origin) {
            return true;
        }

        if (url.searchParams.has("export")) {
            return true;
        }

        return false;
    };

    const shouldBypassForm = (form) => {
        if (!form || form.dataset.noAjax === "1") {
            return true;
        }

        const target = (form.getAttribute("target") || "").trim().toLowerCase();
        if (target !== "" && target !== "_self") {
            return true;
        }

        let actionUrl;
        try {
            actionUrl = new URL(form.getAttribute("action") || window.location.href, window.location.href);
        } catch (error) {
            return true;
        }

        if (actionUrl.origin !== window.location.origin) {
            return true;
        }

        if (actionUrl.searchParams.has("export")) {
            return true;
        }

        return false;
    };

    const pickDefaultSubmitter = (form) => {
        return form.querySelector("button[type='submit'], button:not([type]), input[type='submit']");
    };

    const buildFormRequest = (form, submitter) => {
        const method = ((form.getAttribute("method") || "GET").trim() || "GET").toUpperCase();
        const actionUrl = new URL(form.getAttribute("action") || window.location.href, window.location.href);
        const formData = new FormData(form);
        const effectiveSubmitter = submitter || pickDefaultSubmitter(form);

        if (effectiveSubmitter && effectiveSubmitter.name) {
            formData.append(effectiveSubmitter.name, effectiveSubmitter.value ?? "");
        }

        if (method === "GET") {
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value instanceof File) {
                    continue;
                }
                params.append(key, value);
            }
            actionUrl.search = params.toString();
            return {
                method,
                url: actionUrl.href,
                body: null,
                history: "push",
            };
        }

        return {
            method: method === "POST" ? "POST" : method,
            url: actionUrl.href,
            body: formData,
            history: "replace",
        };
    };

    const updateShellFromHtml = async (html) => {
        const parser = new DOMParser();
        const nextDocument = parser.parseFromString(html, "text/html");
        const nextMain = nextDocument.querySelector("main.content");
        const nextSidebar = nextDocument.querySelector(".sidebar");
        const currentMain = document.querySelector("main.content");
        const currentSidebar = document.querySelector(".sidebar");

        if (!nextMain || !currentMain) {
            return false;
        }

        const previousScope = state.currentScope;
        cleanupScope(previousScope);
        state.currentScope = previousScope + 1;
        ensureScope(state.currentScope);

        if (nextSidebar && currentSidebar) {
            const sidebarClone = nextSidebar.cloneNode(true);
            currentSidebar.replaceWith(sidebarClone);
        }

        const mainClone = nextMain.cloneNode(true);
        currentMain.replaceWith(mainClone);

        if (nextDocument.title && nextDocument.title.trim() !== "") {
            document.title = nextDocument.title;
        }

        const mountedMain = document.querySelector("main.content");
        await runScriptsInOrder(mountedMain);
        document.dispatchEvent(new CustomEvent("sentrylink:content-updated", {
            detail: { url: window.location.href },
        }));

        return true;
    };

    const requestPage = async (url, options = {}) => {
        if (!url || state.navigating) {
            return;
        }

        const {
            method = "GET",
            body = null,
            history = "push",
            scrollToTop = false,
        } = options;

        state.navigating = true;
        releaseViewportLocks();
        setNavigationState(true);

        try {
            const response = await fetch(url, {
                method,
                body,
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    Accept: "text/html,application/xhtml+xml",
                    "X-Requested-With": "XMLHttpRequest",
                },
                redirect: "follow",
            });

            const finalUrl = response.url || url;
            if (!isHtmlResponse(response)) {
                window.location.assign(finalUrl);
                return;
            }

            const html = await response.text();
            const swapped = await updateShellFromHtml(html);
            if (!swapped) {
                window.location.assign(finalUrl);
                return;
            }

            if (history === "push") {
                window.history.pushState({}, "", finalUrl);
            } else if (history === "replace") {
                window.history.replaceState({}, "", finalUrl);
            }

            if (scrollToTop) {
                window.scrollTo({ top: 0, left: 0, behavior: "auto" });
            }
        } catch (error) {
            if (error && error.name === "AbortError") {
                return;
            }
            console.error("SentryLink dynamic navigation error:", error);
        } finally {
            state.navigating = false;
            releaseViewportLocks();
            setNavigationState(false);
        }
    };

    const onDocumentClick = (event) => {
        const anchor = event.target instanceof Element ? event.target.closest("a") : null;
        if (!anchor || shouldBypassLink(anchor, event)) {
            return;
        }

        event.preventDefault();
        requestPage(anchor.href, {
            method: "GET",
            history: "push",
            scrollToTop: true,
        });
    };

    const onFormSubmit = (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || shouldBypassForm(form)) {
            return;
        }

        event.preventDefault();
        const submitter = event.submitter instanceof HTMLElement ? event.submitter : pickDefaultSubmitter(form);
        if (submitter instanceof HTMLElement) {
            submitter.setAttribute("disabled", "disabled");
        }

        const request = buildFormRequest(form, submitter);
        const shouldScrollToTop = request.method === "GET";

        requestPage(request.url, {
            method: request.method,
            body: request.body,
            history: request.history,
            scrollToTop: shouldScrollToTop,
        }).finally(() => {
            if (submitter instanceof HTMLElement && submitter.isConnected) {
                submitter.removeAttribute("disabled");
            }
        });
    };

    if (!state.bound) {
        state.bound = true;
        state.native.documentAdd("click", onDocumentClick);
        state.native.documentAdd("submit", onFormSubmit);
        state.native.windowAdd("popstate", () => {
            requestPage(window.location.href, {
                method: "GET",
                history: "none",
                scrollToTop: false,
            });
        });
    }

    window.SentryLinkShell = window.SentryLinkShell || {};
    window.SentryLinkShell.navigate = (url) => requestPage(url, { method: "GET", history: "push", scrollToTop: true });
    window.SentryLinkShell.refreshCurrentPage = () => requestPage(window.location.href, { method: "GET", history: "replace", scrollToTop: false });
})();
</script>
HTML;

        $validationScript = <<<'HTML'
<script>
(() => {
    const FIELD_SELECTOR = "input:not([type='hidden']):not([type='submit']):not([type='button']):not([type='reset']):not([type='file']), select, textarea";
    const formId = (() => {
        if (!window.__sentrylinkLiveValidationCounter) {
            window.__sentrylinkLiveValidationCounter = 0;
        }
        window.__sentrylinkLiveValidationCounter += 1;
        return window.__sentrylinkLiveValidationCounter;
    })();

    const cleanLabel = (value) => {
        if (typeof value !== "string") {
            return "";
        }
        return value.trim().replace(/\s+/g, " ");
    };

    const findFieldLabel = (field) => {
        const nearestLabel = field.closest("label");
        if (nearestLabel) {
            const text = cleanLabel(nearestLabel.textContent || "");
            if (text !== "") {
                return text;
            }
        }

        if (field.id) {
            const linkedLabel = document.querySelector(`label[for="${field.id}"]`);
            if (linkedLabel) {
                const text = cleanLabel(linkedLabel.textContent || "");
                if (text !== "") {
                    return text;
                }
            }
        }

        const ariaLabel = cleanLabel(field.getAttribute("aria-label") || "");
        if (ariaLabel !== "") {
            return ariaLabel;
        }

        return cleanLabel(field.getAttribute("placeholder") || "") || "This field";
    };

    const ensureMessageNode = (field) => {
        if (!field.dataset.validationMessageId) {
            const base = field.id || field.name || `field-${Math.random().toString(36).slice(2, 8)}`;
            field.dataset.validationMessageId = `live-valid-${formId}-${base.replace(/[^a-zA-Z0-9_-]/g, "_")}`;
        }

        const messageId = field.dataset.validationMessageId;
        let node = document.getElementById(messageId);
        if (node) {
            return node;
        }

        node = document.createElement("div");
        node.id = messageId;
        node.className = "field-validation-message";
        node.hidden = true;
        field.insertAdjacentElement("afterend", node);
        return node;
    };

    const validationMessage = (field) => {
        const validity = field.validity;
        if (!validity) {
            return "";
        }

        if (field.type === "number" && /^[-+]$/.test(field.value.trim())) {
            return "Enter a valid number.";
        }

        if (validity.valid) {
            return "";
        }

        if (validity.valueMissing) {
            return `${findFieldLabel(field)} is required.`;
        }
        if (validity.badInput) {
            return "Enter a valid value.";
        }
        if (validity.typeMismatch) {
            if (field.type === "email") {
                return "Enter a valid email address.";
            }
            return "Enter a valid value.";
        }
        if (validity.rangeUnderflow) {
            const minValue = field.getAttribute("min");
            return minValue !== null ? `Value must be at least ${minValue}.` : "Value is too low.";
        }
        if (validity.rangeOverflow) {
            const maxValue = field.getAttribute("max");
            return maxValue !== null ? `Value must be ${maxValue} or below.` : "Value is too high.";
        }
        if (validity.stepMismatch) {
            const stepValue = field.getAttribute("step");
            return stepValue && stepValue !== "any"
                ? `Use increments of ${stepValue}.`
                : "Enter a valid increment.";
        }
        if (validity.tooShort) {
            return `Enter at least ${field.minLength} characters.`;
        }
        if (validity.tooLong) {
            return `Use at most ${field.maxLength} characters.`;
        }
        if (validity.patternMismatch) {
            const title = cleanLabel(field.getAttribute("title") || "");
            return title !== "" ? title : "Invalid format.";
        }

        return "Invalid value.";
    };

    const applyFieldValidation = (field, force = false) => {
        if (!field || field.disabled || field.readOnly) {
            return;
        }

        const touched = force || field.dataset.validationTouched === "1";
        const message = validationMessage(field);
        const showError = touched && message !== "";
        const messageNode = ensureMessageNode(field);

        messageNode.textContent = showError ? message : "";
        messageNode.hidden = !showError;
        field.classList.toggle("is-invalid", showError);
        field.setAttribute("aria-invalid", showError ? "true" : "false");
        field.setAttribute("aria-describedby", showError ? messageNode.id : "");
    };

    const bindField = (field) => {
        if (!field || field.dataset.liveValidationBound === "1") {
            return;
        }

        field.dataset.liveValidationBound = "1";
        ensureMessageNode(field);

        const markTouchedAndValidate = () => {
            field.dataset.validationTouched = "1";
            applyFieldValidation(field, true);
        };

        field.addEventListener("input", markTouchedAndValidate);
        field.addEventListener("change", markTouchedAndValidate);
        field.addEventListener("blur", markTouchedAndValidate);
    };

    document.querySelectorAll("form").forEach((form) => {
        const fields = Array.from(form.querySelectorAll(FIELD_SELECTOR));
        fields.forEach(bindField);

        if (form.dataset.liveValidationSubmitBound === "1") {
            return;
        }

        form.dataset.liveValidationSubmitBound = "1";
        form.addEventListener("submit", () => {
            fields.forEach((field) => {
                field.dataset.validationTouched = "1";
                applyFieldValidation(field, true);
            });
        });
    });
})();
</script>
HTML;

        echo $loaderScript;
        echo $scrollScript;
        echo $validationScript;
        echo $dynamicShellScript;
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
