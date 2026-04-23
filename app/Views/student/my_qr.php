<?php shell_start('SentryLink | My QR', $user, 'student', 'qr', 'My Live QR', 'The QR refreshes every 10 seconds for gate validation.'); ?>
<style>
.qr-download-block {
    display: grid;
    gap: 0.55rem;
    justify-items: center;
    margin-top: 1rem;
}

.qr-download-btn {
    width: auto;
    min-width: 220px;
    max-width: 100%;
    justify-content: center;
}

.qr-download-copy {
    max-width: 34rem;
    font-size: 0.95rem;
}

.qr-hold-row {
    display: grid;
    gap: 0.55rem;
    justify-items: center;
    margin-top: 0.75rem;
}

.live-qr-shell {
    width: min(340px, calc(100vw - 48px));
}

.live-qr-frame {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #ffffff;
    padding: 18px;
    border-radius: 24px;
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.24);
}

.live-qr-canvas,
.live-qr-canvas canvas,
.live-qr-canvas img {
    width: 100% !important;
    height: 100% !important;
    display: block;
    image-rendering: pixelated;
}

.live-qr-help {
    max-width: 28rem;
    margin: 0.9rem auto 0;
    color: var(--muted);
    font-size: 0.94rem;
    line-height: 1.6;
}

.qr-action-indicator {
    margin: 0.9rem auto 0;
    max-width: 32rem;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.03);
    display: grid;
    gap: 0.25rem;
    text-align: left;
}

.qr-action-indicator .qr-action-label {
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
}

.qr-action-indicator .qr-action-value {
    margin: 0;
    font-size: 1.04rem;
    font-weight: 700;
}

.qr-action-indicator .qr-action-copy {
    color: var(--muted);
    font-size: 0.9rem;
}

.qr-action-indicator.is-in {
    border-color: rgba(59, 130, 246, 0.55);
    background: linear-gradient(180deg, rgba(37, 99, 235, 0.2), rgba(37, 99, 235, 0.08));
}

.qr-action-indicator.is-in .qr-action-value {
    color: #bfdbfe;
}

.qr-action-indicator.is-out {
    border-color: rgba(245, 158, 11, 0.55);
    background: linear-gradient(180deg, rgba(217, 119, 6, 0.2), rgba(217, 119, 6, 0.08));
}

.qr-action-indicator.is-out .qr-action-value {
    color: #fcd34d;
}

.qr-notification-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 0.85rem;
}

.qr-notification-bell-btn {
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

.qr-notification-bell-btn:hover,
.qr-notification-bell-btn:focus {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.11);
}

.qr-notification-bell-btn .material-symbols-outlined {
    font-size: 1.04rem;
}

.qr-notification-bell-count {
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

.qr-notification-toast {
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

.qr-notification-toast.is-visible {
    animation: qrNotificationToastIn 320ms cubic-bezier(0.2, 0.9, 0.2, 1) forwards;
    pointer-events: auto;
}

.qr-notification-toast.is-hiding {
    animation: qrNotificationToastOut 220ms ease forwards;
}

.qr-notification-toast-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.qr-notification-toast-title {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 700;
}

.qr-notification-toast-copy {
    margin-top: 0.4rem;
    color: #cddfff;
    font-size: 0.9rem;
    line-height: 1.45;
}

.qr-notification-modal[hidden] {
    display: none;
}

.qr-notification-modal {
    position: fixed;
    inset: 0;
    z-index: 2900;
    display: grid;
    place-items: center;
    padding: 1rem;
}

.qr-notification-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(2, 6, 21, 0.74);
    backdrop-filter: blur(2px);
}

.qr-notification-modal-dialog {
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
    animation: qrNotificationModalIn 260ms cubic-bezier(0.2, 0.9, 0.2, 1);
}

.qr-notification-modal-head,
.qr-notification-modal-foot {
    padding: 0.92rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.qr-notification-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
}

.qr-notification-modal-foot {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.6rem;
}

.qr-notification-modal-body {
    padding: 0.75rem 1rem 1rem;
    overflow: auto;
}

.qr-notification-modal-list {
    display: grid;
    gap: 0.62rem;
}

.qr-notification-item {
    width: 100%;
    text-align: left;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.03);
    color: inherit;
    padding: 0.72rem 0.78rem;
}

.qr-notification-item.is-unread {
    border-color: rgba(96, 165, 250, 0.5);
    background: linear-gradient(180deg, rgba(30, 58, 138, 0.2), rgba(14, 24, 54, 0.15));
}

.qr-notification-item-head {
    display: flex;
    justify-content: space-between;
    gap: 0.7rem;
    align-items: flex-start;
}

.qr-notification-item-title {
    font-weight: 700;
}

.qr-notification-item-meta {
    font-size: 0.76rem;
    color: #9fb0cf;
    white-space: nowrap;
}

.qr-notification-item-copy {
    margin-top: 0.34rem;
    color: #c8d5ef;
    font-size: 0.9rem;
}

@keyframes qrNotificationToastIn {
    0% {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes qrNotificationToastOut {
    0% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }
}

@keyframes qrNotificationModalIn {
    0% {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 640px) {
    .qr-download-btn {
        width: 100%;
        min-width: 0;
    }
}
</style>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="panel text-center">
            <div class="qr-notification-toolbar">
                <button type="button" class="qr-notification-bell-btn" id="qrNotificationBellBtn" aria-haspopup="dialog" aria-controls="qrNotificationModal" aria-label="Open notifications">
                    <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
                    <span>Notifications</span>
                    <span id="qrNotificationBellCount" class="qr-notification-bell-count d-none">0</span>
                </button>
            </div>
            <?php if (! $ticket): ?>
                <h3 class="h5 mb-3">No active event QR is available</h3>
                <p class="text-secondary mb-0">You need a paid or free ticket for an ongoing event before the QR appears here.</p>
            <?php else: ?>
                <div class="mb-3">
                    <div class="text-secondary">Current Event</div>
                    <h3 class="h4 mb-1"><?= h($ticket['title']) ?></h3>
                    <div class="text-secondary"><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?> | <?= h(substr($ticket['start_time'], 0, 5)) ?> - <?= h(substr($ticket['end_time'], 0, 5)) ?></div>
                </div>
                <div class="mx-auto mb-3 position-relative live-qr-shell">
                    <div class="live-qr-frame">
                        <div id="qrCode" class="live-qr-canvas"></div>
                    </div>
                    <div class="position-absolute top-0 end-0 mt-2 me-2 badge text-bg-primary" id="countdown">10s</div>
                </div>
                <p class="text-secondary mb-2" id="refreshText">Generating QR...</p>
                <div
                    id="qrActionIndicator"
                    class="qr-action-indicator <?= (($gateState['next_action'] ?? 'in') === 'out') ? 'is-out' : 'is-in' ?>"
                    aria-live="polite"
                >
                    <span class="qr-action-label">Current QR Action</span>
                    <p id="qrActionValue" class="qr-action-value mb-0">
                        <?= (($gateState['next_action'] ?? 'in') === 'out') ? 'OUT Scan' : 'IN Scan' ?>
                    </p>
                    <span id="qrActionCopy" class="qr-action-copy"><?= h((string) ($gateState['next_action_copy'] ?? 'The next successful scan will log this student into the event.')) ?></span>
                </div>
                <div id="offlineBanner" class="alert alert-warning d-none mb-0">Offline mode detected. The displayed QR may expire until connection returns.</div>
                <div class="live-qr-help">For faster gate scanning, turn your screen brightness up and hold the QR steady until the officer phone confirms it.</div>
                <div class="qr-hold-row">
                    <button id="holdQrBtn" type="button" class="btn btn-outline-light">Hold Current QR (6s)</button>
                    <small id="holdQrText" class="text-secondary">Use hold only when an officer is actively scanning your screen.</small>
                </div>
                <div class="qr-download-block">
                    <button id="downloadQrBtn" type="button" class="btn btn-outline-light qr-download-btn" disabled>Download One-Time QR</button>
                    <div id="downloadQrText" class="text-secondary qr-download-copy">The downloaded QR stays valid until the first successful scan, then it is removed from the system.</div>
                </div>
                <div id="downloadQrBuffer" class="d-none"></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="qr-notification-toast" id="qrNotificationToast" hidden role="status" aria-live="polite">
    <div class="qr-notification-toast-head">
        <div class="qr-notification-toast-title">
            <span class="material-symbols-outlined" aria-hidden="true">notifications_active</span>
            <span id="qrNotificationToastTitle">New notifications</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="qrNotificationToastOpenBtn">Open</button>
    </div>
    <div class="qr-notification-toast-copy" id="qrNotificationToastCopy"></div>
</div>
<div class="qr-notification-modal" id="qrNotificationModal" hidden>
    <div class="qr-notification-modal-backdrop" data-close-qr-notification-modal></div>
    <div class="qr-notification-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="qrNotificationModalTitle">
        <div class="qr-notification-modal-head">
            <h3 class="h5 mb-0" id="qrNotificationModalTitle">Notifications</h3>
            <button type="button" class="btn btn-outline-light btn-sm" id="qrNotificationMarkAllBtn">Mark All Read</button>
        </div>
        <div class="qr-notification-modal-body">
            <div class="text-secondary" id="qrNotificationModalEmpty">No notifications yet.</div>
            <div class="qr-notification-modal-list" id="qrNotificationModalList"></div>
        </div>
        <div class="qr-notification-modal-foot">
            <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('s/notifications')) ?>">Open Full Page</a>
            <button type="button" class="btn btn-primary btn-sm" data-close-qr-notification-modal>Close</button>
        </div>
    </div>
</div>
<?php
$ticketStateUrl = json_encode(app_url('api/student/ticket-state'));
$notificationFeedUrl = json_encode(app_url('api/notifications'));
$notificationReadUrl = json_encode(app_url('api/notifications/read'));
$ticketStateHashJson = json_encode((string) ($ticketStateHash ?? ''));
$scriptTemplate = <<<'HTML'
<script>
const studentTicketStateUrl = __TICKET_STATE_URL__;
const studentNotificationFeedUrl = __NOTIFICATION_FEED_URL__;
const studentNotificationReadUrl = __NOTIFICATION_READ_URL__;
let studentTicketStateHash = __TICKET_STATE_HASH__;
let studentTicketStateReloading = false;
let qrNotificationItems = [];
let qrNotificationUnreadCount = 0;
let qrNotificationToastTimer = null;

const qrNotificationBellBtn = document.getElementById("qrNotificationBellBtn");
const qrNotificationBellCount = document.getElementById("qrNotificationBellCount");
const qrNotificationModal = document.getElementById("qrNotificationModal");
const qrNotificationModalList = document.getElementById("qrNotificationModalList");
const qrNotificationModalEmpty = document.getElementById("qrNotificationModalEmpty");
const qrNotificationMarkAllBtn = document.getElementById("qrNotificationMarkAllBtn");
const qrNotificationToast = document.getElementById("qrNotificationToast");
const qrNotificationToastTitle = document.getElementById("qrNotificationToastTitle");
const qrNotificationToastCopy = document.getElementById("qrNotificationToastCopy");
const qrNotificationToastOpenBtn = document.getElementById("qrNotificationToastOpenBtn");
const qrNotificationCloseButtons = document.querySelectorAll("[data-close-qr-notification-modal]");

function escapeQrNotificationHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function formatQrNotificationTime(value) {
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

function hideQrNotificationToast() {
    if (!qrNotificationToast || qrNotificationToast.hidden) {
        return;
    }

    qrNotificationToast.classList.remove("is-visible");
    qrNotificationToast.classList.add("is-hiding");
    setTimeout(() => {
        qrNotificationToast.hidden = true;
        qrNotificationToast.classList.remove("is-hiding");
    }, 220);
}

function showQrNotificationToast(title, copy) {
    if (!qrNotificationToast || !qrNotificationToastTitle || !qrNotificationToastCopy) {
        return;
    }

    qrNotificationToastTitle.textContent = title;
    qrNotificationToastCopy.textContent = copy;
    qrNotificationToast.hidden = false;
    qrNotificationToast.classList.remove("is-hiding");
    qrNotificationToast.classList.remove("is-visible");
    void qrNotificationToast.offsetWidth;
    qrNotificationToast.classList.add("is-visible");

    if (qrNotificationToastTimer) {
        clearTimeout(qrNotificationToastTimer);
    }
    qrNotificationToastTimer = setTimeout(() => {
        hideQrNotificationToast();
    }, 5600);
}

function updateQrNotificationCounters(unreadCount) {
    const value = Math.max(0, Number(unreadCount || 0));
    qrNotificationUnreadCount = value;

    if (!qrNotificationBellCount) {
        return;
    }

    if (value > 0) {
        qrNotificationBellCount.textContent = String(Math.min(99, value));
        qrNotificationBellCount.classList.remove("d-none");
    } else {
        qrNotificationBellCount.classList.add("d-none");
        qrNotificationBellCount.textContent = "0";
    }
}

function renderQrNotificationsModal() {
    if (!qrNotificationModalList || !qrNotificationModalEmpty) {
        return;
    }

    if (!Array.isArray(qrNotificationItems) || qrNotificationItems.length === 0) {
        qrNotificationModalList.innerHTML = "";
        qrNotificationModalEmpty.hidden = false;
        return;
    }

    qrNotificationModalEmpty.hidden = true;
    qrNotificationModalList.innerHTML = qrNotificationItems.map((item) => {
        const id = Number(item && item.id ? item.id : 0);
        const isUnread = Number(item && item.is_read ? item.is_read : 0) === 0;
        const title = escapeQrNotificationHtml(item && item.title ? item.title : "Notification");
        const message = escapeQrNotificationHtml(item && item.message ? item.message : "");
        const createdAt = escapeQrNotificationHtml(formatQrNotificationTime(item && item.created_at ? item.created_at : ""));
        const statusLabel = isUnread ? "Unread" : "Read";
        const statusBadge = isUnread ? "primary" : "secondary";

        return `<button type="button" class="qr-notification-item ${isUnread ? "is-unread" : ""}" data-qr-notification-id="${id}" data-qr-notification-unread="${isUnread ? "1" : "0"}">
            <div class="qr-notification-item-head">
                <div>
                    <div class="qr-notification-item-title">${title}</div>
                </div>
                <div class="text-end">
                    <span class="badge text-bg-${statusBadge}">${statusLabel}</span>
                    <div class="qr-notification-item-meta">${createdAt}</div>
                </div>
            </div>
            <div class="qr-notification-item-copy">${message}</div>
        </button>`;
    }).join("");
}

function openQrNotificationModal() {
    if (!qrNotificationModal) {
        return;
    }

    qrNotificationModal.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeQrNotificationModal() {
    if (!qrNotificationModal || qrNotificationModal.hidden) {
        return;
    }

    qrNotificationModal.hidden = true;
    document.body.style.overflow = "";
}

async function markQrNotificationsRead(payload) {
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

async function refreshQrNotifications(options = {}) {
    const showAttention = Boolean(options.showAttention);
    const previousUnread = qrNotificationUnreadCount;

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
        qrNotificationItems = Array.isArray(data && data.notifications) ? data.notifications : [];
        updateQrNotificationCounters(unread);
        renderQrNotificationsModal();

        if (unread > 0 && (showAttention || unread > previousUnread)) {
            const firstUnread = qrNotificationItems.find((item) => Number(item && item.is_read ? item.is_read : 0) === 0) || qrNotificationItems[0];
            const toastTitle = unread === 1 ? "1 new notification" : `${unread} new notifications`;
            const toastCopy = firstUnread && firstUnread.title
                ? String(firstUnread.title)
                : "Open notifications to review updates.";
            showQrNotificationToast(toastTitle, toastCopy);
        }
    } catch (error) {
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

if (qrNotificationBellBtn) {
    qrNotificationBellBtn.addEventListener("click", () => {
        openQrNotificationModal();
        refreshQrNotifications({ showAttention: false });
        hideQrNotificationToast();
    });
}

if (qrNotificationToastOpenBtn) {
    qrNotificationToastOpenBtn.addEventListener("click", () => {
        openQrNotificationModal();
        refreshQrNotifications({ showAttention: false });
        hideQrNotificationToast();
    });
}

qrNotificationCloseButtons.forEach((button) => {
    button.addEventListener("click", closeQrNotificationModal);
});

if (qrNotificationModalList) {
    qrNotificationModalList.addEventListener("click", async (event) => {
        const itemButton = event.target.closest("[data-qr-notification-id]");
        if (!itemButton) {
            return;
        }

        const notificationId = Number(itemButton.getAttribute("data-qr-notification-id") || "0");
        const isUnread = itemButton.getAttribute("data-qr-notification-unread") === "1";

        if (!isUnread || notificationId <= 0) {
            return;
        }

        const ok = await markQrNotificationsRead({ id: notificationId });
        if (ok) {
            await refreshQrNotifications({ showAttention: false });
        }
    });
}

if (qrNotificationMarkAllBtn) {
    qrNotificationMarkAllBtn.addEventListener("click", async () => {
        const ok = await markQrNotificationsRead({ all: true });
        if (ok) {
            await refreshQrNotifications({ showAttention: false });
        }
    });
}

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeQrNotificationModal();
        hideQrNotificationToast();
    }
});

updateQrNotificationCounters(qrNotificationUnreadCount);
renderQrNotificationsModal();
refreshQrNotifications({ showAttention: true });
setInterval(refreshQrNotifications, 15000);
setInterval(syncStudentTicketState, 5000);
document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
        syncStudentTicketState();
        refreshQrNotifications({ showAttention: false });
    }
});
window.addEventListener("focus", () => {
    syncStudentTicketState();
    refreshQrNotifications({ showAttention: false });
});
</script>
HTML;
$script = strtr($scriptTemplate, [
    '__TICKET_STATE_URL__' => $ticketStateUrl,
    '__NOTIFICATION_FEED_URL__' => $notificationFeedUrl,
    '__NOTIFICATION_READ_URL__' => $notificationReadUrl,
    '__TICKET_STATE_HASH__' => $ticketStateHashJson,
]);

if ($ticket) {
    $script .= '
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const countdownEl = document.getElementById("countdown");
const refreshText = document.getElementById("refreshText");
const offlineBanner = document.getElementById("offlineBanner");
const qrCodeEl = document.getElementById("qrCode");
const downloadQrBtn = document.getElementById("downloadQrBtn");
const downloadQrText = document.getElementById("downloadQrText");
const downloadQrBuffer = document.getElementById("downloadQrBuffer");
const holdQrBtn = document.getElementById("holdQrBtn");
const holdQrText = document.getElementById("holdQrText");
const qrActionIndicator = document.getElementById("qrActionIndicator");
const qrActionValue = document.getElementById("qrActionValue");
const qrActionCopy = document.getElementById("qrActionCopy");
let seconds = 10;
let qrCode = null;
let downloadToken = "";
let downloadFileName = "sentrylink-entry-qr.png";
let currentToken = "";
let currentTokenVersion = 0;
let heartbeatRttMs = 0;

function renderQrActionIndicator(nextAction, nextActionCopy) {
    if (!qrActionIndicator || !qrActionValue || !qrActionCopy) {
        return;
    }

    const action = String(nextAction || "in").toLowerCase() === "out" ? "out" : "in";
    qrActionIndicator.classList.toggle("is-out", action === "out");
    qrActionIndicator.classList.toggle("is-in", action !== "out");
    qrActionValue.textContent = action === "out" ? "OUT Scan" : "IN Scan";

    if (typeof nextActionCopy === "string" && nextActionCopy.trim() !== "") {
        qrActionCopy.textContent = nextActionCopy;
    }
}

function renderQrCode(token) {
    if (!window.QRCode) {
        throw new Error("QR library failed to load.");
    }

    if (!qrCode) {
        qrCode = new QRCode(qrCodeEl, {
            width: 320,
            height: 320,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M,
        });
    } else {
        qrCode.clear();
    }

    qrCode.makeCode(token);
}

function syncDownloadQrState(isAvailable) {
    downloadQrBtn.disabled = !isAvailable;
    downloadQrBtn.setAttribute("aria-disabled", String(!isAvailable));
}

function renderDownloadQrDataUrl(token) {
    if (!window.QRCode) {
        throw new Error("QR library failed to load.");
    }

    downloadQrBuffer.innerHTML = "";

    const exportQr = new QRCode(downloadQrBuffer, {
        width: 960,
        height: 960,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
    });
    exportQr.makeCode(token);

    return new Promise((resolve, reject) => {
        requestAnimationFrame(() => {
            const canvas = downloadQrBuffer.querySelector("canvas");
            const image = downloadQrBuffer.querySelector("img");

            if (canvas) {
                resolve(canvas.toDataURL("image/png"));
                return;
            }

            if (image && image.src) {
                resolve(image.src);
                return;
            }

            reject(new Error("Unable to render the downloadable QR."));
        });
    });
}

async function downloadQrImage() {
    if (!downloadToken) {
        return;
    }

    syncDownloadQrState(false);
    downloadQrText.textContent = "Preparing your downloadable QR...";

    try {
        const dataUrl = await renderDownloadQrDataUrl(downloadToken);
        const link = document.createElement("a");
        link.href = dataUrl;
        link.download = downloadFileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        downloadQrText.textContent = "Downloaded. This QR stays valid until the first successful scan.";
    } catch (error) {
        downloadQrText.textContent = error.message;
    } finally {
        syncDownloadQrState(downloadToken !== "");
    }
}

function tickCountdown() {
    seconds = seconds <= 1 ? 10 : seconds - 1;
    countdownEl.textContent = seconds + "s";
}

async function refreshQr() {
    try {
        const response = await fetch("' . h(app_url('api/qr/generate')) . '", { credentials: "same-origin" });
        const data = await response.json();

        if (!response.ok || data.error) {
            throw new Error(data.error || "QR refresh failed.");
        }

        currentToken = data.token || "";
        currentTokenVersion = Number(data.token_version || 0);
        renderQrCode(data.token);
        downloadToken = data.download_token || "";
        downloadFileName = data.download_file_name || "sentrylink-entry-qr.png";
        if (data.download_available) {
            downloadQrText.textContent = "The downloaded QR stays valid until the first successful scan, then it is removed from the system.";
        } else {
            downloadQrText.textContent = "A downloadable QR is no longer available for this ticket.";
        }
        syncDownloadQrState(Boolean(data.download_available && downloadToken));
        refreshText.textContent = "Last refreshed at " + new Date().toLocaleTimeString();
        renderQrActionIndicator(data.next_action, data.next_action_copy);
        offlineBanner.classList.add("d-none");
        seconds = 10;
        countdownEl.textContent = "10s";
    } catch (error) {
        refreshText.textContent = error.message;
        offlineBanner.classList.remove("d-none");
    }
}

async function holdQrToken() {
    if (!currentToken) {
        holdQrText.textContent = "Generate a live QR first.";
        return;
    }

    holdQrBtn.disabled = true;
    holdQrText.textContent = "Pinning current QR...";

    try {
        const response = await fetch("' . h(app_url('api/qr/hold')) . '", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token: currentToken, token_version: currentTokenVersion }),
        });
        const data = await response.json();

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || "Unable to hold QR.");
        }

        holdQrText.textContent = "Held until " + new Date(data.hold_expires_at).toLocaleTimeString() + ".";
    } catch (error) {
        holdQrText.textContent = error.message;
    } finally {
        setTimeout(() => {
            holdQrBtn.disabled = false;
        }, 1000);
    }
}

async function sendHeartbeat() {
    if (!currentToken) {
        return;
    }

    const started = performance.now();

    try {
        const response = await fetch("' . h(app_url('api/qr/heartbeat')) . '", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                token_version: currentTokenVersion,
                rtt_ms: heartbeatRttMs,
            }),
        });
        const data = await response.json();
        const elapsed = Math.round(performance.now() - started);
        heartbeatRttMs = elapsed;

        if (!response.ok || data.ok === false) {
            return;
        }

        if (typeof data.token_version === "number" && data.token_version > 0) {
            currentTokenVersion = data.token_version;
        }

        if (data.latency_state === "offline") {
            offlineBanner.classList.remove("d-none");
        } else if (elapsed <= 2500) {
            offlineBanner.classList.add("d-none");
        }
    } catch (error) {
    }
}

refreshQr();
setInterval(refreshQr, 9000);
setInterval(tickCountdown, 1000);
setInterval(sendHeartbeat, 4000);
downloadQrBtn.addEventListener("click", downloadQrImage);
if (holdQrBtn) {
    holdQrBtn.addEventListener("click", holdQrToken);
}
window.addEventListener("offline", () => offlineBanner.classList.remove("d-none"));
window.addEventListener("online", () => {
    refreshQr();
    sendHeartbeat();
    syncStudentTicketState();
});
</script>';
}
shell_end($script);
?>
