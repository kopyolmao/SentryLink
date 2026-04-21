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
}
</style>

<div class="student-dashboard">
    <section class="student-hero">
        <span class="hero-badge">Student Portal</span>
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
            <div class="stat-value"><?= h((string) ($stats['notifications'] ?? 0)) ?></div>
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
                <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('s/notifications')) ?>">Open Notifications</a>
            </div>
        </aside>
    </section>
</div>

<?php
$script = '<script>
const studentTicketStateUrl = ' . json_encode(app_url('api/student/ticket-state')) . ';
let studentTicketStateHash = ' . json_encode($ticketStateHash ?? '') . ';
let studentTicketStateReloading = false;

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

setInterval(syncStudentTicketState, 5000);
document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
        syncStudentTicketState();
    }
});
window.addEventListener("focus", syncStudentTicketState);
</script>';

shell_end($script);
?>
