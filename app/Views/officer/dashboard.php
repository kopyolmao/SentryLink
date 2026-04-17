<?php shell_start('SentryLink | Officer Dashboard', $user, 'ssg', 'dashboard', 'Officer Dashboard', 'Gate scanning status and recent validations.'); ?>
<style>
.officer-dashboard {
    display: grid;
    gap: 1rem;
}

.officer-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.officer-metrics .metric {
    height: 100%;
}

.officer-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 1rem;
}

.officer-panel {
    display: grid;
    gap: 0.9rem;
}

.officer-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.9rem;
}

.officer-panel-head p {
    margin: 0;
    color: var(--muted);
}

.gate-list,
.log-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.72rem;
}

.gate-item,
.log-item {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    padding: 0.9rem 1rem;
}

.gate-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.8rem;
    align-items: center;
}

.gate-title,
.log-title {
    margin: 0;
    font-size: 1rem;
}

.gate-meta,
.log-meta {
    margin-top: 0.35rem;
    color: var(--muted);
    font-size: 0.9rem;
}

.log-item {
    display: grid;
    gap: 0.55rem;
}

.log-foot {
    display: flex;
    justify-content: space-between;
    gap: 0.6rem;
    align-items: center;
    flex-wrap: wrap;
}

.officer-empty {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    color: var(--muted);
    padding: 1rem;
}

@media (max-width: 1080px) {
    .officer-metrics,
    .officer-grid {
        grid-template-columns: 1fr;
    }

    .gate-item {
        grid-template-columns: 1fr;
    }

    .gate-item .btn {
        width: 100%;
    }
}
</style>

<div class="officer-dashboard">
    <section class="officer-metrics">
        <div class="metric">
            <div class="text-secondary">Scans Today</div>
            <div class="value"><?= h((string) $stats['today']) ?></div>
        </div>
        <div class="metric">
            <div class="text-secondary">Total Scans</div>
            <div class="value"><?= h((string) $stats['total']) ?></div>
        </div>
        <div class="metric">
            <div class="text-secondary">Ongoing Events</div>
            <div class="value"><?= h((string) $stats['ongoing']) ?></div>
        </div>
    </section>

    <section class="officer-grid">
        <div class="panel officer-panel">
            <div class="officer-panel-head">
                <div>
                    <h3 class="h5 mb-1">Active Gates</h3>
                    <p>Choose an event and jump directly to scanner mode.</p>
                </div>
                <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('o/scanner')) ?>">Open Scanner</a>
            </div>

            <?php if ($activeEvents === []): ?>
                <div class="officer-empty">No ongoing events are available right now.</div>
            <?php else: ?>
                <ul class="gate-list">
                    <?php foreach ($activeEvents as $event): ?>
                        <li class="gate-item">
                            <div>
                                <h4 class="gate-title"><?= h((string) $event['title']) ?></h4>
                                <div class="gate-meta">Date: <?= h(date('M d, Y', strtotime((string) $event['event_date']))) ?></div>
                            </div>
                            <a class="btn btn-primary btn-sm" href="<?= h(app_url('o/scanner?event_id=' . (int) $event['id'])) ?>">Start Scan</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel officer-panel">
            <div class="officer-panel-head">
                <div>
                    <h3 class="h5 mb-1">Recent Logs</h3>
                    <p>Your latest gate validation activity.</p>
                </div>
                <a class="btn btn-outline-light btn-sm" href="<?= h(app_url('o/gate-log')) ?>">View Gate Log</a>
            </div>

            <?php if ($recentLogs === []): ?>
                <div class="officer-empty">No scan records yet for this account.</div>
            <?php else: ?>
                <ul class="log-list">
                    <?php foreach ($recentLogs as $log): ?>
                        <li class="log-item">
                            <h4 class="log-title"><?= h((string) $log['first_name'] . ' ' . (string) $log['last_name']) ?></h4>
                            <div class="log-meta">
                                <?= h((string) $log['student_id']) ?> | <?= h((string) $log['title']) ?>
                            </div>
                            <div class="log-foot">
                                <span class="badge text-bg-<?= h(admission_status_badge((string) $log['status'])) ?>"><?= h(admission_status_label((string) $log['status'])) ?></span>
                                <small class="text-secondary"><?= h((string) ($log['gate_location'] ?: 'Main Gate')) ?> | <?= h((string) $log['scanned_at']) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php shell_end(); ?>
