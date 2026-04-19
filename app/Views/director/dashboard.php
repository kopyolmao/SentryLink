<?php shell_start('SentryLink | Director Dashboard', $user, 'director', 'dashboard', 'Director Overview', 'Read-only visibility into event operations and attendance.'); ?>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Events</div><div class="value"><?= $metrics['events'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Ongoing</div><div class="value"><?= $metrics['ongoing'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Tickets</div><div class="value"><?= $metrics['tickets'] ?></div></div></div>
    <div class="col-md-3"><div class="metric"><div class="text-secondary">Check-Ins</div><div class="value"><?= $metrics['admissions'] ?></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="panel"><h3 class="h5 mb-3">Recent Events</h3><ul class="list-soft"><?php foreach ($events as $event): ?><li><strong><?= h($event['title']) ?></strong><div class="text-secondary"><?= h($event['event_date']) ?> | <?= h($event['venue']) ?> | <?= h(ucfirst($event['status'])) ?></div></li><?php endforeach; ?></ul></div></div>
    <div class="col-lg-6"><div class="panel"><h3 class="h5 mb-3">Latest Gate Activity</h3><ul class="list-soft"><?php foreach ($recentAdmissions as $row): ?><li><strong><?= h($row['first_name'] . ' ' . $row['last_name']) ?></strong><div class="text-secondary"><?= h($row['student_id']) ?> | <?= h($row['title']) ?> | <?= h($row['scanned_at']) ?> | <?= h(admission_status_label($row['status'])) ?></div></li><?php endforeach; ?></ul></div></div>
</div>
<?php
$script = '<script>
const directorDashboardGateActivityStateUrl = ' . json_encode(app_url('api/gate-activity-state')) . ';
let directorDashboardGateActivityStateHash = "";

async function syncDirectorDashboardGateActivity() {
    try {
        const response = await fetch(directorDashboardGateActivityStateUrl, {
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

        if (directorDashboardGateActivityStateHash !== "" && data.state_hash !== directorDashboardGateActivityStateHash) {
            if (window.SentryLinkShell && typeof window.SentryLinkShell.refreshCurrentPage === "function") {
                window.SentryLinkShell.refreshCurrentPage();
            } else {
                window.location.reload();
            }
            return;
        }

        directorDashboardGateActivityStateHash = data.state_hash;
    } catch (error) {
    }
}

syncDirectorDashboardGateActivity();
setInterval(syncDirectorDashboardGateActivity, 5000);
window.addEventListener("focus", syncDirectorDashboardGateActivity);
</script>';

shell_end($script);
?>
