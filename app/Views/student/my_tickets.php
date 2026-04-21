<?php shell_start('SentryLink | My Transactions', $user, 'student', 'tickets', 'My Transactions', 'All ticket records tied to your account.'); ?>
<div class="panel">
    <?php if ($tickets): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Ticket Code</th><th>Receipt ID</th><th>Ticket</th><th>Event</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <?php [$label, $badge] = ticket_status_badge($ticket['payment_status']); ?>
                    <?php $eventStatus = (string) ($ticket['status'] ?? 'draft'); ?>
                    <tr>
                        <td><?= h($ticket['title']) ?></td>
                        <td><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?><br><small class="text-secondary"><?= h(substr($ticket['start_time'], 0, 5) . ' - ' . substr($ticket['end_time'], 0, 5)) ?></small></td>
                        <td><?= h($ticket['venue']) ?></td>
                        <td><code><?= h($ticket['ticket_code']) ?></code></td>
                        <td><?= h($ticket['receipt_id'] ?: 'Free Event') ?></td>
                        <td><span class="badge text-bg-<?= h($badge) ?>"><?= h($label) ?></span></td>
                        <td>
                            <span class="badge text-bg-<?= h(event_status_badge($eventStatus)) ?>"><?= h(ucfirst($eventStatus)) ?></span>
                            <?php if (in_array((string) $ticket['payment_status'], ['paid', 'free'], true) && $eventStatus === 'ongoing'): ?>
                                <div><small class="text-secondary">QR entry is live now.</small></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No transactions found yet.</p>
    <?php endif; ?>
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
