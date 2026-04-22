<?php shell_start('SentryLink | Student Dashboard', $user, 'student', 'dashboard', 'Student Dashboard', 'Ticket status, live QR access, and recent updates.'); ?>
<?php
$displayName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$displayName = $displayName !== '' ? $displayName : 'Student';
$firstName   = trim((string) ($user['first_name'] ?? '')) ?: $displayName;
$nextTicket  = $upcoming[0] ?? null;
$verified    = (int) ($user['email_verified'] ?? 0) === 1;
$course      = trim((string) ($user['course'] ?? ''));
$yearLevel   = trim((string) ($user['year_level'] ?? ''));
$profileMeta = trim($course . ($course !== '' && $yearLevel !== '' ? ' | ' : '') . $yearLevel);
$hasLiveQr   = false;

foreach ($upcoming as $ticket) {
    if (in_array((string) $ticket['payment_status'], ['paid', 'free'], true) && (string) $ticket['status'] === 'ongoing') {
        $hasLiveQr = true;
        break;
    }
}

$primaryAction = $hasLiveQr
    ? ['label' => 'Open My QR', 'url' => app_url('s/my-qr')]
    : ['label' => 'View My Transactions', 'url' => app_url('s/my-tickets')];

$secondaryAction = $stats['notifications'] > 0
    ? ['label' => 'Review Notifications', 'url' => app_url('s/notifications')]
    : ['label' => 'Open Account', 'url' => app_url('s/account')];

$nextStepTitle = $hasLiveQr ? 'Gate entry is available.' : 'Ticket review is your next step.';
$nextStepText  = $hasLiveQr
    ? 'You have at least one ongoing paid or free event ticket, so the live QR is the fastest next action.'
    : 'There is no active live QR right now. Review your assigned tickets and schedules first.';
?>
<style>
.student-dashboard {
    display: grid;
    gap: 1rem;
}

.student-hero {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(circle at top right, rgba(113, 89, 255, 0.32), transparent 36%),
        radial-gradient(circle at bottom left, rgba(34, 211, 238, 0.18), transparent 32%),
        linear-gradient(145deg, rgba(17, 23, 42, 0.98), rgba(10, 15, 30, 0.96));
    padding: 1.3rem;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.7rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #d4c8ff;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 0.42rem 0.72rem;
}

.hero-title {
    margin: 0.85rem 0 0.35rem;
    font-size: clamp(1.8rem, 3.3vw, 2.5rem);
    line-height: 1.08;
}

.hero-copy {
    color: #b4c1dc;
    max-width: 64ch;
    margin-bottom: 1.1rem;
}

.hero-headline {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
}

.notification-bell-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.42rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: rgba(255, 255, 255, 0.06);
    color: #eef4ff;
    padding: 0.42rem 0.75rem;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    transition: transform 140ms ease, border-color 140ms ease, background-color 140ms ease;
}

.notification-bell-btn:hover,
.notification-bell-btn:focus {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.11);
}

.notification-bell-btn .material-symbols-outlined {
    font-size: 1.04rem;
}

.notification-bell-count {
    min-width: 1.25rem;
    height: 1.25rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: #fff;
    font-size: 0.69rem;
    line-height: 1;
    padding: 0 0.28rem;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}

.notification-toast {
    position: fixed;
    top: 1.2rem;
    right: 1.2rem;
    z-index: 3000;
    width: min(92vw, 24rem);
    border-radius: 16px;
    border: 1px solid rgba(96, 165, 250, 0.42);
    background: linear-gradient(180deg, rgba(30, 58, 138, 0.95), rgba(16, 34, 86, 0.95));
    color: #eaf3ff;
    box-shadow: 0 18px 44px rgba(3, 9, 28, 0.5);
    padding: 0.86rem 0.92rem;
    transform: translateY(-10px) scale(0.98);
    opacity: 0;
    pointer-events: none;
}

.notification-toast.is-visible {
    animation: notificationToastIn 320ms cubic-bezier(0.2, 0.9, 0.2, 1) forwards;
    pointer-events: auto;
}

.notification-toast.is-hiding {
    animation: notificationToastOut 220ms ease forwards;
}

.notification-toast-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.notification-toast-title {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 700;
}

.notification-toast-copy {
    margin-top: 0.4rem;
    color: #cddfff;
    font-size: 0.9rem;
    line-height: 1.45;
}

.notification-modal[hidden] {
    display: none;
}

.notification-modal {
    position: fixed;
    inset: 0;
    z-index: 2900;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.notification-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(2, 6, 21, 0.74);
    backdrop-filter: blur(2px);
}

.notification-modal-dialog {
    position: relative;
    width: min(100%, 38rem);
    max-height: min(86vh, 42rem);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: linear-gradient(180deg, rgba(14, 23, 41, 0.98), rgba(9, 15, 27, 0.97));
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.45);
    animation: notificationModalIn 260ms cubic-bezier(0.2, 0.9, 0.2, 1);
}

.notification-modal-head,
.notification-modal-foot {
    padding: 0.92rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.notification-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
}

.notification-modal-foot {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.6rem;
}

.notification-modal-body {
    padding: 0.75rem 1rem 1rem;
    overflow: auto;
}

.notification-modal-list {
    display: grid;
    gap: 0.62rem;
}

.notification-item {
    width: 100%;
    text-align: left;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.03);
    color: inherit;
    padding: 0.72rem 0.78rem;
}

.notification-item.is-unread {
    border-color: rgba(96, 165, 250, 0.5);
    background: linear-gradient(180deg, rgba(30, 58, 138, 0.2), rgba(14, 24, 54, 0.15));
}

.notification-item-head {
    display: flex;
    justify-content: space-between;
    gap: 0.7rem;
    align-items: flex-start;
}

.notification-item-title {
    font-weight: 700;
}

.notification-item-meta {
    font-size: 0.76rem;
    color: #9fb0cf;
    white-space: nowrap;
}

.notification-item-copy {
    margin-top: 0.34rem;
    color: #c8d5ef;
    font-size: 0.9rem;
}

@keyframes notificationToastIn {
    0% {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes notificationToastOut {
    0% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }
}

@keyframes notificationModalIn {
    0% {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(270px, 0.9fr);
    gap: 0.9rem;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.7rem;
}

.hero-actions .btn {
    flex: 1 1 200px;
    justify-content: center;
}

.hero-spotlight {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
    padding: 0.95rem 1rem;
}

.hero-spotlight .eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #9fb0cf;
}

.hero-spotlight .spotlight-title {
    margin: 0.42rem 0 0.22rem;
    font-size: 1.04rem;
}

.hero-spotlight .spotlight-meta {
    color: #c8d5ef;
    font-size: 0.9rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.stat-card {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: linear-gradient(180deg, rgba(17, 24, 41, 0.98), rgba(9, 14, 26, 0.96));
    padding: 1rem;
    min-height: 148px;
    display: flex;
    flex-direction: column;
}

.stat-kicker {
    color: #9fb0cf;
    font-size: 0.82rem;
}

.stat-value {
    margin-top: 0.35rem;
    font-size: 2rem;
    font-weight: 800;
}

.stat-note {
    margin-top: auto;
    color: #c8d5ef;
    font-size: 0.84rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.85fr);
    gap: 0.9rem;
}

.glass-panel {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(12, 19, 34, 0.94);
    padding: 1rem;
}

.ticket-table thead th {
    color: #9fb0cf;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 0.74rem;
    border-bottom-color: rgba(255, 255, 255, 0.12);
}

.ticket-table tbody td {
    border-top-color: rgba(255, 255, 255, 0.08);
    vertical-align: middle;
}

.quick-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.72rem;
}

.quick-list li {
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    padding: 0.72rem 0.8rem;
}

@media (max-width: 1060px) {
    .hero-grid,
    .stats-grid,
    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .hero-headline {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="student-dashboard">
    <section class="student-hero">
        <div class="hero-headline">
            <span class="hero-badge">Student Portal</span>
            <button type="button" class="notification-bell-btn" id="notificationBellBtn" aria-haspopup="dialog" aria-controls="notificationModal" aria-label="Open notifications">
                <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
                <span>Notifications</span>
                <span id="notificationBellCount" class="notification-bell-count <?= ((int) ($stats['notifications'] ?? 0)) > 0 ? '' : 'd-none' ?>"><?= h((string) min(99, (int) ($stats['notifications'] ?? 0))) ?></span>
            </button>
        </div>
        <h2 class="hero-title">Welcome, <?= h($firstName) ?></h2>
        <p class="hero-copy"><?= h($nextStepText) ?></p>

        <div class="hero-grid">
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= h($primaryAction['url']) ?>"><?= h($primaryAction['label']) ?></a>
                <a class="btn btn-outline-light" href="<?= h($secondaryAction['url']) ?>"><?= h($secondaryAction['label']) ?></a>
            </div>
            <div class="hero-spotlight">
                <div class="eyebrow">Next Step</div>
                <h3 class="spotlight-title"><?= h($nextStepTitle) ?></h3>
                <div class="spotlight-meta">
                    <?php if ($nextTicket): ?>
                        <?= h((string) $nextTicket['title']) ?><br>
                        <?= h(date('M d, Y', strtotime((string) $nextTicket['event_date']))) ?>
                    <?php else: ?>
                        No upcoming tickets yet.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-kicker">Total Tickets</div>
            <div class="stat-value"><?= h((string) ($stats['tickets'] ?? 0)) ?></div>
            <div class="stat-note">All assigned records in your account.</div>
        </article>
        <article class="stat-card">
            <div class="stat-kicker">Gate-Ready Tickets</div>
            <div class="stat-value"><?= h((string) ($stats['active'] ?? 0)) ?></div>
            <div class="stat-note">Tickets marked paid or free.</div>
        </article>
        <article class="stat-card">
            <div class="stat-kicker">Unread Notifications</div>
            <div class="stat-value" id="dashboardNotificationCount"><?= h((string) ($stats['notifications'] ?? 0)) ?></div>
            <div class="stat-note">New announcements requiring review.</div>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="glass-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 mb-0">Recent Tickets</h3>
                <a class="btn btn-sm btn-outline-light" href="<?= h(app_url('s/my-tickets')) ?>">View All</a>
            </div>

            <?php if ($upcoming !== []): ?>
                <div class="table-wrap">
                    <table class="table table-dark align-middle ticket-table mb-0">
                        <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Gate State</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($upcoming as $ticket): ?>
                            <?php [$ticketLabel, $ticketBadge] = ticket_status_badge((string) $ticket['payment_status']); ?>
                            <?php [$gateLabel, $gateBadge] = [
                                (string) ($ticket['gate_state']['current_label'] ?? 'Ready for Entry'),
                                (string) ($ticket['gate_state']['badge'] ?? 'primary'),
                            ]; ?>
                            <tr>
                                <td>
                                    <?= h((string) $ticket['title']) ?><br>
                                    <small class="text-secondary"><?= h((string) $ticket['venue']) ?></small>
                                </td>
                                <td>
                                    <?= h(date('M d, Y', strtotime((string) $ticket['event_date']))) ?><br>
                                    <small class="text-secondary"><?= h(substr((string) $ticket['start_time'], 0, 5) . ' - ' . substr((string) $ticket['end_time'], 0, 5)) ?></small>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= h($ticketBadge) ?>"><?= h($ticketLabel) ?></span>
                                    <div class="mt-1"><span class="badge text-bg-<?= h(event_status_badge((string) $ticket['status'])) ?>"><?= h(event_status_label((string) $ticket['status'])) ?></span></div>
                                </td>
                                <td><span class="badge text-bg-<?= h($gateBadge) ?>"><?= h($gateLabel) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-secondary mb-0">No ticket records are available yet.</p>
            <?php endif; ?>
        </div>

        <aside class="glass-panel">
            <h3 class="h5 mb-3">Account Snapshot</h3>
            <ul class="quick-list">
                <li>
                    <strong><?= h($displayName) ?></strong><br>
                    <small class="text-secondary"><?= h((string) ($user['student_id'] ?? '')) ?></small>
                </li>
                <li>
                    <strong>Email Verification</strong><br>
                    <small class="text-secondary"><?= $verified ? 'Verified' : 'Pending verification' ?></small>
                </li>
                <li>
                    <strong>Course and Year</strong><br>
                    <small class="text-secondary"><?= h($profileMeta !== '' ? $profileMeta : 'Not set yet') ?></small>
                </li>
            </ul>
            <div class="d-grid gap-2 mt-3">
                <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('s/account')) ?>">Open Account</a>
                <button type="button" class="btn btn-outline-light btn-sm" id="openNotificationsPanelBtn">Open Notifications</button>
            </div>
        </aside>
    </section>
</div>

<div class="notification-toast" id="notificationToast" hidden role="status" aria-live="polite">
    <div class="notification-toast-head">
        <div class="notification-toast-title">
            <span class="material-symbols-outlined" aria-hidden="true">notifications_active</span>
            <span id="notificationToastTitle">New notifications</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="notificationToastOpenBtn">Open</button>
    </div>
    <div class="notification-toast-copy" id="notificationToastCopy"></div>
</div>

<div class="notification-modal" id="notificationModal" hidden>
    <div class="notification-modal-backdrop" data-close-notification-modal></div>
    <div class="notification-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="notificationModalTitle">
        <div class="notification-modal-head">
            <h3 class="h5 mb-0" id="notificationModalTitle">Notifications</h3>
            <button type="button" class="btn btn-outline-light btn-sm" id="notificationMarkAllBtn">Mark All Read</button>
        </div>
        <div class="notification-modal-body">
            <div class="text-secondary" id="notificationModalEmpty">No notifications yet.</div>
            <div class="notification-modal-list" id="notificationModalList"></div>
        </div>
        <div class="notification-modal-foot">
            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('s/notifications')) ?>">Open Full Page</a>
            <button type="button" class="btn btn-primary btn-sm" data-close-notification-modal>Close</button>
        </div>
    </div>
</div>

<?php
$ticketStateUrl = json_encode(app_url('api/student/ticket-state'));
$notificationFeedUrl = json_encode(app_url('api/notifications'));
$notificationReadUrl = json_encode(app_url('api/notifications/read'));
$initialUnreadCount = (int) ($stats['notifications'] ?? 0);
$scriptTemplate = <<<'HTML'
<script>
const studentTicketStateUrl = __TICKET_STATE_URL__;
const studentNotificationFeedUrl = __NOTIFICATION_FEED_URL__;
const studentNotificationReadUrl = __NOTIFICATION_READ_URL__;
const initialDashboardNotificationCount = __INITIAL_UNREAD_COUNT__;
let studentTicketStateHash = __TICKET_STATE_HASH__;
let studentTicketStateReloading = false;
let notificationItems = [];
let notificationUnreadCount = Number(initialDashboardNotificationCount || 0);
let notificationToastTimer = null;

const notificationBellBtn = document.getElementById("notificationBellBtn");
const openNotificationsPanelBtn = document.getElementById("openNotificationsPanelBtn");
const notificationBellCount = document.getElementById("notificationBellCount");
const dashboardNotificationCount = document.getElementById("dashboardNotificationCount");
const notificationModal = document.getElementById("notificationModal");
const notificationModalList = document.getElementById("notificationModalList");
const notificationModalEmpty = document.getElementById("notificationModalEmpty");
const notificationMarkAllBtn = document.getElementById("notificationMarkAllBtn");
const notificationToast = document.getElementById("notificationToast");
const notificationToastTitle = document.getElementById("notificationToastTitle");
const notificationToastCopy = document.getElementById("notificationToastCopy");
const notificationToastOpenBtn = document.getElementById("notificationToastOpenBtn");
const notificationCloseButtons = document.querySelectorAll("[data-close-notification-modal]");

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function formatNotificationTime(value) {
    const source = String(value || "").trim();
    if (source === "") {
        return "";
    }

    const parsed = new Date(source.replace(" ", "T"));
    if (Number.isNaN(parsed.getTime())) {
        return source;
    }

    return parsed.toLocaleString();
}

function hideNotificationToast() {
    if (!notificationToast || notificationToast.hidden) {
        return;
    }

    notificationToast.classList.remove("is-visible");
    notificationToast.classList.add("is-hiding");
    setTimeout(() => {
        notificationToast.hidden = true;
        notificationToast.classList.remove("is-hiding");
    }, 220);
}

function showNotificationToast(title, copy) {
    if (!notificationToast || !notificationToastTitle || !notificationToastCopy) {
        return;
    }

    notificationToastTitle.textContent = title;
    notificationToastCopy.textContent = copy;
    notificationToast.hidden = false;
    notificationToast.classList.remove("is-hiding");
    notificationToast.classList.remove("is-visible");
    void notificationToast.offsetWidth;
    notificationToast.classList.add("is-visible");

    if (notificationToastTimer) {
        clearTimeout(notificationToastTimer);
    }
    notificationToastTimer = setTimeout(() => {
        hideNotificationToast();
    }, 5600);
}

function updateNotificationCounters(unreadCount) {
    const value = Math.max(0, Number(unreadCount || 0));
    notificationUnreadCount = value;

    if (dashboardNotificationCount) {
        dashboardNotificationCount.textContent = String(value);
    }

    if (!notificationBellCount) {
        return;
    }

    if (value > 0) {
        notificationBellCount.textContent = String(Math.min(99, value));
        notificationBellCount.classList.remove("d-none");
    } else {
        notificationBellCount.classList.add("d-none");
        notificationBellCount.textContent = "0";
    }
}

function renderNotificationsModal() {
    if (!notificationModalList || !notificationModalEmpty) {
        return;
    }

    if (!Array.isArray(notificationItems) || notificationItems.length === 0) {
        notificationModalList.innerHTML = "";
        notificationModalEmpty.hidden = false;
        return;
    }

    notificationModalEmpty.hidden = true;
    notificationModalList.innerHTML = notificationItems.map((item) => {
        const id = Number(item && item.id ? item.id : 0);
        const isUnread = Number(item && item.is_read ? item.is_read : 0) === 0;
        const title = escapeHtml(item && item.title ? item.title : "Notification");
        const message = escapeHtml(item && item.message ? item.message : "");
        const createdAt = escapeHtml(formatNotificationTime(item && item.created_at ? item.created_at : ""));
        const statusLabel = isUnread ? "Unread" : "Read";
        const statusBadge = isUnread ? "primary" : "secondary";

        return `<button type="button" class="notification-item ${isUnread ? "is-unread" : ""}" data-notification-id="${id}" data-notification-unread="${isUnread ? "1" : "0"}">
            <div class="notification-item-head">
                <div>
                    <div class="notification-item-title">${title}</div>
                </div>
                <div class="text-end">
                    <span class="badge text-bg-${statusBadge}">${statusLabel}</span>
                    <div class="notification-item-meta">${createdAt}</div>
                </div>
            </div>
            <div class="notification-item-copy">${message}</div>
        </button>`;
    }).join("");
}

function openNotificationModal() {
    if (!notificationModal) {
        return;
    }

    notificationModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeNotificationModal() {
    if (!notificationModal || notificationModal.hidden) {
        return;
    }

    notificationModal.hidden = true;
    document.body.style.overflow = "";
}

async function refreshNotifications(options = {}) {
    const showAttention = Boolean(options.showAttention);
    const previousUnread = notificationUnreadCount;

    try {
        const response = await fetch(studentNotificationFeedUrl, {
            credentials: "same-origin",
            cache: "no-store",
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const unread = Math.max(0, Number(data && data.unread ? data.unread : 0));
        notificationItems = Array.isArray(data && data.notifications) ? data.notifications : [];
        updateNotificationCounters(unread);
        renderNotificationsModal();

        if (unread > 0 && (showAttention || unread > previousUnread)) {
            const firstUnread = notificationItems.find((item) => Number(item && item.is_read ? item.is_read : 0) === 0) || notificationItems[0];
            const toastTitle = unread === 1 ? "1 new notification" : `${unread} new notifications`;
            const toastCopy = firstUnread && firstUnread.title
                ? String(firstUnread.title)
                : "Open notifications to review updates.";
            showNotificationToast(toastTitle, toastCopy);
        }
    } catch (error) {
    }
}

async function markNotificationsRead(payload) {
    try {
        const response = await fetch(studentNotificationReadUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(payload),
        });
        if (!response.ok) {
            return false;
        }
        const data = await response.json();
        return Boolean(data && data.ok);
    } catch (error) {
        return false;
    }
}

async function syncStudentTicketState() {
    if (studentTicketStateReloading) {
        return;
    }

    try {
        const response = await fetch(studentTicketStateUrl, {
            credentials: "same-origin",
            cache: "no-store",
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (typeof data.state_hash !== "string" || data.state_hash === "") {
            return;
        }

        if (data.state_hash !== studentTicketStateHash) {
            studentTicketStateReloading = true;
            if (window.SentryLinkShell && typeof window.SentryLinkShell.refreshCurrentPage === "function") {
                window.SentryLinkShell.refreshCurrentPage();
            } else {
                window.location.reload();
            }
        }
    } catch (error) {
    }
}

if (notificationBellBtn) {
    notificationBellBtn.addEventListener("click", () => {
        openNotificationModal();
        refreshNotifications({ showAttention: false });
        hideNotificationToast();
    });
}

if (openNotificationsPanelBtn) {
    openNotificationsPanelBtn.addEventListener("click", () => {
        openNotificationModal();
        refreshNotifications({ showAttention: false });
        hideNotificationToast();
    });
}

if (notificationToastOpenBtn) {
    notificationToastOpenBtn.addEventListener("click", () => {
        openNotificationModal();
        refreshNotifications({ showAttention: false });
        hideNotificationToast();
    });
}

notificationCloseButtons.forEach((button) => {
    button.addEventListener("click", closeNotificationModal);
});

if (notificationModalList) {
    notificationModalList.addEventListener("click", async (event) => {
        const itemButton = event.target.closest("[data-notification-id]");
        if (!itemButton) {
            return;
        }

        const notificationId = Number(itemButton.getAttribute("data-notification-id") || "0");
        const isUnread = itemButton.getAttribute("data-notification-unread") === "1";

        if (!isUnread || notificationId <= 0) {
            return;
        }

        const ok = await markNotificationsRead({ id: notificationId });
        if (ok) {
            await refreshNotifications({ showAttention: false });
        }
    });
}

if (notificationMarkAllBtn) {
    notificationMarkAllBtn.addEventListener("click", async () => {
        const ok = await markNotificationsRead({ all: true });
        if (ok) {
            await refreshNotifications({ showAttention: false });
        }
    });
}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeNotificationModal();
        hideNotificationToast();
    }
});

updateNotificationCounters(notificationUnreadCount);
renderNotificationsModal();
refreshNotifications({ showAttention: notificationUnreadCount > 0 });
setInterval(refreshNotifications, 15000);
setInterval(syncStudentTicketState, 5000);
document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
        syncStudentTicketState();
        refreshNotifications({ showAttention: false });
    }
});
window.addEventListener("focus", () => {
    syncStudentTicketState();
    refreshNotifications({ showAttention: false });
});
</script>
HTML;
$script = strtr($scriptTemplate, [
    '__TICKET_STATE_URL__' => $ticketStateUrl,
    '__NOTIFICATION_FEED_URL__' => $notificationFeedUrl,
    '__NOTIFICATION_READ_URL__' => $notificationReadUrl,
    '__INITIAL_UNREAD_COUNT__' => (string) $initialUnreadCount,
    '__TICKET_STATE_HASH__' => json_encode((string) ($ticketStateHash ?? '')),
]);

shell_end($script);
?>
