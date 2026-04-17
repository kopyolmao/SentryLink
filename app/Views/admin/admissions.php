<?php shell_start('SentryLink | Admissions', $user, 'admin', 'admissions', 'Gate Logs', 'Event gate entries and exits across all officers.'); ?>
<?php
$admissionExportParams = [];
if ($eventFilter > 0) {
    $admissionExportParams['event_id'] = (string) $eventFilter;
}
if ($statusFilter !== '') {
    $admissionExportParams['status'] = $statusFilter;
}
$admissionExportParams['export'] = 'csv';
?>
<div class="panel">
    <form method="GET" class="filter-form">
        <div class="filter-field filter-span-4"><label class="form-label">Event</label><select class="form-select" name="event_id"><option value="">All events</option><?php foreach ($events as $event): ?><option value="<?= $event['id'] ?>" <?= $eventFilter === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?></option><?php endforeach; ?></select></div>
        <div class="filter-field filter-span-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['in', 'out', 'admitted', 'duplicate', 'rejected'] as $status): ?><option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(admission_status_label($status)) ?></option><?php endforeach; ?></select></div>
        <div class="filter-field filter-span-4 filter-actions d-flex gap-2">
            <button class="btn btn-primary">Apply Filters</button>
            <a class="btn btn-outline-light" href="<?= h(app_url('admin/admissions?' . http_build_query($admissionExportParams))) ?>">Export CSV</a>
        </div>
    </form>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table table-dark align-middle">
            <thead><tr><th>Student</th><th>Event</th><th>Officer</th><th>Gate</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['first_name'] . ' ' . $log['last_name']) ?><br><small class="text-secondary"><?= h($log['student_id']) ?></small></td>
                    <td><?= h($log['title']) ?></td>
                    <td><?= h(trim(($log['officer_first'] ?? '') . ' ' . ($log['officer_last'] ?? ''))) ?></td>
                    <td><?= h($log['gate_location'] ?: 'Main Gate') ?></td>
                    <td><?= h($log['scanned_at']) ?></td>
                    <td><span class="badge text-bg-<?= h(admission_status_badge($log['status'])) ?>"><?= h(admission_status_label($log['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$gateActivityParams = [];
if ($eventFilter > 0) {
    $gateActivityParams['event_id'] = (string) $eventFilter;
}
if ($statusFilter !== '') {
    $gateActivityParams['status'] = $statusFilter;
}
$script = '<script>
const adminGateActivityStateUrl = ' . json_encode(app_url('api/gate-activity-state') . ($gateActivityParams ? '?' . http_build_query($gateActivityParams) : '')) . ';
let adminGateActivityStateHash = "";

async function syncAdminGateActivity() {
    try {
        const response = await fetch(adminGateActivityStateUrl, {
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

        if (adminGateActivityStateHash !== "" && data.state_hash !== adminGateActivityStateHash) {
            window.location.reload();
            return;
        }

        adminGateActivityStateHash = data.state_hash;
    } catch (error) {
    }
}

syncAdminGateActivity();
setInterval(syncAdminGateActivity, 5000);
window.addEventListener("focus", syncAdminGateActivity);
</script>';

shell_end($script);
?>
