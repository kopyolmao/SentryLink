<?php shell_start('SentryLink | Admin Dashboard', $user, 'admin', 'dashboard', 'Admin Dashboard', 'Operational overview for events, tickets, and gate activity.'); ?>
<style>
.admin-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.admin-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.admin-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(280px, 1fr);
    gap: 1rem;
    align-items: start;
}

.admin-stack {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
}

.admin-head,
.admin-list-item,
.admin-actions {
    min-width: 0;
}

.admin-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.9rem;
    margin-bottom: 1rem;
}

.admin-head h3 {
    margin: 0;
}

.admin-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-list li + li {
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.admin-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.85rem;
}

.admin-list-copy {
    min-width: 0;
}

.admin-list-copy strong,
.admin-audit strong {
    overflow-wrap: anywhere;
}

.admin-actions {
    display: grid;
    gap: 0.75rem;
}

.admin-actions .btn {
    width: 100%;
    justify-content: center;
}

@media (max-width: 1200px) {
    .admin-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-metrics {
        grid-template-columns: 1fr;
    }

    .admin-head,
    .admin-list-item {
        flex-direction: column;
        align-items: stretch;
    }

    .admin-head .btn,
    .admin-list-item .badge {
        width: fit-content;
    }
}
</style>

<div class="admin-dashboard">
    <section class="admin-metrics">
        <div class="metric">
            <div class="text-secondary">Students</div>
            <div class="value"><?= h((string) $metrics['students']) ?></div>
        </div>
        <div class="metric">
            <div class="text-secondary">Events</div>
            <div class="value"><?= h((string) $metrics['events']) ?></div>
        </div>
        <div class="metric">
            <div class="text-secondary">Tickets</div>
            <div class="value"><?= h((string) $metrics['tickets']) ?></div>
        </div>
        <div class="metric">
            <div class="text-secondary">Admissions Today</div>
            <div class="value"><?= h((string) $metrics['admissions']) ?></div>
        </div>
    </section>

    <section class="admin-grid">
        <div class="panel">
            <div class="admin-head">
                <div>
                    <h3 class="h5">Recent Events</h3>
                    <div class="text-secondary">Most recent event records and their current status.</div>
                </div>
                <a class="btn btn-primary btn-sm" href="<?= h(app_url('admin/events')) ?>">Manage Events</a>
            </div>

            <?php if ($recentEvents): ?>
                <ul class="admin-list">
                    <?php foreach ($recentEvents as $event): ?>
                        <li>
                            <div class="admin-list-item">
                                <div class="admin-list-copy">
                                    <strong><?= h($event['title']) ?></strong>
                                    <div class="text-secondary"><?= h($event['event_date']) ?></div>
                                </div>
                                <span class="badge text-bg-<?= h(event_status_badge($event['status'])) ?>"><?= h(ucfirst($event['status'])) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-secondary mb-0">No events found.</p>
            <?php endif; ?>
        </div>

        <div class="admin-stack">
            <div class="panel">
                <div class="admin-head">
                    <div>
                        <h3 class="h5">Quick Actions</h3>
                        <div class="text-secondary">Shortcuts for common admin tasks.</div>
                    </div>
                </div>
                <div class="admin-actions">
                    <a class="btn btn-primary" href="<?= h(app_url('admin/tickets/import-receipts')) ?>">Import Receipts</a>
                    <a class="btn btn-outline-light" href="<?= h(app_url('admin/students')) ?>">Manage Students</a>
                    <a class="btn btn-outline-light" href="<?= h(app_url('admin/notifications/broadcast')) ?>">Send Broadcast</a>
                </div>
            </div>

            <div class="panel">
                <div class="admin-head">
                    <div>
                        <h3 class="h5">Latest Audit Entries</h3>
                        <div class="text-secondary">Most recent system actions recorded in the audit log.</div>
                    </div>
                </div>

                <?php if ($recentAudit): ?>
                    <ul class="admin-list">
                        <?php foreach ($recentAudit as $log): ?>
                            <li class="admin-audit">
                                <strong><?= h($log['action']) ?></strong>
                                <div class="text-secondary">
                                    <?= h(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? 'System'))) ?>
                                    |
                                    <?= h($log['created_at']) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-secondary mb-0">No audit entries yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php shell_end(); ?>
