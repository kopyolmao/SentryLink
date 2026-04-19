<?php shell_start('SentryLink | Gate Log', $user, 'ssg', 'gate-log', 'Gate Log', 'Recent admissions for the selected event.'); ?>
<div class="panel">
    <form method="GET" class="filter-form">
        <div class="filter-field filter-span-8">
            <label class="form-label">Event</label>
            <select class="form-select" name="event_id" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                <option value="">Select event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $eventId === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?> (<?= h($event['event_date']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>
<div class="panel">
    <?php if ($logs): ?>
        <div class="table-wrap">
            <table class="table table-dark align-middle">
                <thead><tr><th>Student</th><th>Event</th><th>Gate</th><th>Time</th><th>Status</th></tr></thead>
                <tbody id="gateLogBody">
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= h($log['first_name'] . ' ' . $log['last_name']) ?><br><small class="text-secondary"><?= h($log['student_id']) ?></small></td>
                        <td><?= h($log['title']) ?></td>
                        <td><?= h($log['gate_location'] ?: 'Main Gate') ?></td>
                        <td><?= h($log['scanned_at']) ?></td>
                        <td><span class="badge text-bg-<?= h(admission_status_badge($log['status'])) ?>"><?= h(ucfirst($log['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-secondary mb-0">No gate logs yet for the selected event.</p>
    <?php endif; ?>
</div>
<?php
$script = '';
if ($eventId > 0) {
    $script = '
<script>
async function refreshGateLog() {
    try {
        const response = await fetch("' . h(app_url('api/gate-log/' . $eventId)) . '");
        const data = await response.json();
        if (!data.logs) return;
        const tbody = document.getElementById("gateLogBody");
        if (!tbody) return;
        tbody.innerHTML = data.logs.map(log => `
            <tr>
                <td>${log.name}<br><small class="text-secondary">${log.student_id}</small></td>
                <td>${log.event_title}</td>
                <td>${log.gate_location || "Main Gate"}</td>
                <td>${log.scanned_at}</td>
                <td><span class="badge text-bg-${log.badge}">${log.status}</span></td>
            </tr>
        `).join("");
    } catch (error) {}
}
setInterval(refreshGateLog, 10000);
</script>';
}
shell_end($script);
?>
