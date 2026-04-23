<?php shell_start('SentryLink | Admin Dashboard', $user, 'admin', 'dashboard', 'Admin Dashboard', 'Operational overview for events, tickets, and gate activity.'); ?>
<?php
$analytics          = is_array($analytics ?? null) ? $analytics : [];
$trendLabels        = array_values($analytics['trend_labels'] ?? []);
$trendAdmissions    = array_map('intval', $analytics['trend_admissions'] ?? []);
$trendTickets       = array_map('intval', $analytics['trend_tickets'] ?? []);
$eventStatusLabels  = array_values($analytics['event_status_labels'] ?? []);
$eventStatusValues  = array_map('intval', $analytics['event_status_values'] ?? []);
$ticketMixLabels    = array_values($analytics['ticket_mix_labels'] ?? []);
$ticketMixValues    = array_map('intval', $analytics['ticket_mix_values'] ?? []);
$topCourses         = is_array($analytics['top_courses'] ?? null) ? $analytics['top_courses'] : [];
$weeklyAdmissionSum = array_sum($trendAdmissions);
$weeklyTicketSum    = array_sum($trendTickets);
$scanPerTicket      = $weeklyTicketSum > 0 ? round($weeklyAdmissionSum / $weeklyTicketSum, 2) : 0.0;
$topCourseMax       = 0;

foreach ($topCourses as $course) {
    $topCourseMax = max($topCourseMax, (int) ($course['total'] ?? 0));
}
?>
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

.admin-analytics-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.42fr) minmax(330px, 1fr);
    gap: 1rem;
    align-items: stretch;
}

.admin-analytics-stack {
    display: grid;
    gap: 1rem;
    grid-template-rows: repeat(2, minmax(0, 1fr));
}

.admin-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(320px, 1fr);
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

.admin-head-tight {
    margin-bottom: 0.75rem;
}

.admin-head h3 {
    margin: 0;
}

.analytics-panel {
    display: flex;
    flex-direction: column;
}

.analytics-panel-wide {
    min-height: 364px;
}

.analytics-canvas-wrap {
    position: relative;
    min-height: 220px;
    flex: 1 1 auto;
}

.analytics-canvas-wrap canvas {
    width: 100% !important;
    height: 100% !important;
}

.analytics-kpis {
    margin-top: 0.85rem;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
}

.analytics-kpi {
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    padding: 0.65rem 0.72rem;
}

.analytics-kpi-label {
    color: #9fb0cf;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.analytics-kpi-value {
    margin-top: 0.22rem;
    font-size: 1.08rem;
    font-weight: 700;
}

.analytics-legend {
    margin-top: 0.65rem;
    display: grid;
    gap: 0.34rem;
}

.analytics-legend-item {
    display: flex;
    justify-content: space-between;
    gap: 0.7rem;
    color: #c8d5ef;
    font-size: 0.87rem;
}

.analytics-legend-item strong {
    color: #edf3ff;
}

.top-courses-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.75rem;
}

.top-courses-item-head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: baseline;
    margin-bottom: 0.35rem;
}

.top-courses-item-head strong {
    overflow-wrap: anywhere;
}

.top-courses-item-head span {
    color: #9fb0cf;
    font-size: 0.82rem;
}

.top-courses-meter {
    width: 100%;
    height: 8px;
    border-radius: 999px;
    background: rgba(255,255,255,0.07);
    overflow: hidden;
}

.top-courses-meter-fill {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #5ea2ff, #7a7cff, #d88bff);
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

@media (max-width: 1280px) {
    .admin-analytics-grid {
        grid-template-columns: 1fr;
    }

    .admin-analytics-stack {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: none;
    }
}

@media (max-width: 1200px) {
    .admin-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .admin-analytics-stack {
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

    .analytics-kpis {
        grid-template-columns: 1fr;
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

    <section class="admin-analytics-grid">
        <div class="panel analytics-panel analytics-panel-wide">
            <div class="admin-head admin-head-tight">
                <div>
                    <h3 class="h5">7-Day Activity Trend</h3>
                    <div class="text-secondary">Ticket issuance and gate scans over the last seven days.</div>
                </div>
            </div>
            <div class="analytics-canvas-wrap">
                <canvas id="adminTrendChart" aria-label="Weekly ticket and admissions trend" role="img"></canvas>
            </div>
            <div class="analytics-kpis">
                <div class="analytics-kpi">
                    <div class="analytics-kpi-label">7-Day Tickets</div>
                    <div class="analytics-kpi-value"><?= h((string) $weeklyTicketSum) ?></div>
                </div>
                <div class="analytics-kpi">
                    <div class="analytics-kpi-label">7-Day Gate Scans</div>
                    <div class="analytics-kpi-value"><?= h((string) $weeklyAdmissionSum) ?></div>
                </div>
                <div class="analytics-kpi">
                    <div class="analytics-kpi-label">Scan / Ticket</div>
                    <div class="analytics-kpi-value"><?= h(number_format($scanPerTicket, 2)) ?></div>
                </div>
            </div>
        </div>

        <div class="admin-analytics-stack">
            <div class="panel analytics-panel">
                <div class="admin-head admin-head-tight">
                    <div>
                        <h3 class="h5">Event Status Mix</h3>
                        <div class="text-secondary">Current distribution of event lifecycle states.</div>
                    </div>
                </div>
                <div class="analytics-canvas-wrap">
                    <canvas id="adminEventStatusChart" aria-label="Event status distribution" role="img"></canvas>
                </div>
                <div class="analytics-legend">
                    <?php foreach ($eventStatusLabels as $index => $label): ?>
                        <div class="analytics-legend-item">
                            <span><?= h((string) $label) ?></span>
                            <strong><?= h((string) ($eventStatusValues[$index] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel analytics-panel">
                <div class="admin-head admin-head-tight">
                    <div>
                        <h3 class="h5">Ticket Payment Mix</h3>
                        <div class="text-secondary">Current ticket statuses across all active records.</div>
                    </div>
                </div>
                <div class="analytics-canvas-wrap">
                    <canvas id="adminTicketMixChart" aria-label="Ticket payment status distribution" role="img"></canvas>
                </div>
                <div class="analytics-legend">
                    <?php foreach ($ticketMixLabels as $index => $label): ?>
                        <div class="analytics-legend-item">
                            <span><?= h((string) $label) ?></span>
                            <strong><?= h((string) ($ticketMixValues[$index] ?? 0)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
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
                <div class="admin-head admin-head-tight">
                    <div>
                        <h3 class="h5">Top Student Courses</h3>
                        <div class="text-secondary">Enrollment spread for currently active student accounts.</div>
                    </div>
                </div>
                <?php if ($topCourses): ?>
                    <ul class="top-courses-list">
                        <?php foreach ($topCourses as $course): ?>
                            <?php
                            $courseTotal = (int) ($course['total'] ?? 0);
                            $courseRatio = $topCourseMax > 0 ? (int) round(($courseTotal / $topCourseMax) * 100) : 0;
                            ?>
                            <li>
                                <div class="top-courses-item-head">
                                    <strong><?= h((string) ($course['course'] ?? 'Unassigned')) ?></strong>
                                    <span><?= h((string) $courseTotal) ?> students</span>
                                </div>
                                <div class="top-courses-meter">
                                    <div class="top-courses-meter-fill" style="width: <?= h((string) $courseRatio) ?>%;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-secondary mb-0">No student records available for course analytics.</p>
                <?php endif; ?>
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

<?php
$trendJson = json_encode(
    [
        'labels'     => $trendLabels,
        'admissions' => $trendAdmissions,
        'tickets'    => $trendTickets,
    ],
    JSON_UNESCAPED_SLASHES
);
$eventStatusJson = json_encode(
    [
        'labels' => $eventStatusLabels,
        'values' => $eventStatusValues,
    ],
    JSON_UNESCAPED_SLASHES
);
$ticketMixJson = json_encode(
    [
        'labels' => $ticketMixLabels,
        'values' => $ticketMixValues,
    ],
    JSON_UNESCAPED_SLASHES
);

$scriptTemplate = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const adminTrendData = __TREND_DATA__;
const adminEventStatusData = __EVENT_STATUS_DATA__;
const adminTicketMixData = __TICKET_MIX_DATA__;

(() => {
    if (typeof window.Chart !== "function") {
        return;
    }

    const palette = [
        "#7a7cff",
        "#4ea5ff",
        "#22d3ee",
        "#34d399",
        "#f59e0b",
        "#f97316",
        "#ef4444",
        "#a855f7",
    ];

    const noDataConfig = {
        labels: ["No Data"],
        values: [1],
        colors: ["#2d3b55"],
    };

    const contextColor = "#c8d5ef";
    const gridColor = "rgba(255,255,255,0.08)";

    const buildDoughnutData = (source) => {
        const labels = Array.isArray(source.labels) ? source.labels : [];
        const values = Array.isArray(source.values) ? source.values : [];
        const filtered = [];

        labels.forEach((label, index) => {
            const value = Number(values[index] || 0);
            if (value > 0) {
                filtered.push({
                    label: String(label),
                    value,
                });
            }
        });

        if (filtered.length === 0) {
            return noDataConfig;
        }

        return {
            labels: filtered.map((item) => item.label),
            values: filtered.map((item) => item.value),
            colors: filtered.map((item, index) => palette[index % palette.length]),
        };
    };

    const trendCanvas = document.getElementById("adminTrendChart");
    if (trendCanvas) {
        new Chart(trendCanvas.getContext("2d"), {
            type: "line",
            data: {
                labels: Array.isArray(adminTrendData.labels) ? adminTrendData.labels : [],
                datasets: [
                    {
                        label: "Gate Scans",
                        data: Array.isArray(adminTrendData.admissions) ? adminTrendData.admissions : [],
                        borderColor: "#22d3ee",
                        backgroundColor: "rgba(34,211,238,0.18)",
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.36,
                    },
                    {
                        label: "Tickets Issued",
                        data: Array.isArray(adminTrendData.tickets) ? adminTrendData.tickets : [],
                        borderColor: "#8b5cf6",
                        backgroundColor: "rgba(139,92,246,0.18)",
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.36,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: contextColor,
                            usePointStyle: true,
                            pointStyle: "circle",
                            boxWidth: 8,
                            boxHeight: 8,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            color: contextColor,
                        },
                        grid: {
                            color: gridColor,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: contextColor,
                            precision: 0,
                        },
                        grid: {
                            color: gridColor,
                        },
                    },
                },
            },
        });
    }

    const eventStatusCanvas = document.getElementById("adminEventStatusChart");
    if (eventStatusCanvas) {
        const chartData = buildDoughnutData(adminEventStatusData);
        new Chart(eventStatusCanvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: chartData.colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "62%",
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
    }

    const ticketMixCanvas = document.getElementById("adminTicketMixChart");
    if (ticketMixCanvas) {
        const chartData = buildDoughnutData(adminTicketMixData);
        new Chart(ticketMixCanvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: chartData.colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "62%",
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
    }
})();
</script>
HTML;

$script = strtr($scriptTemplate, [
    '__TREND_DATA__'        => $trendJson !== false ? $trendJson : '{"labels":[],"admissions":[],"tickets":[]}',
    '__EVENT_STATUS_DATA__' => $eventStatusJson !== false ? $eventStatusJson : '{"labels":[],"values":[]}',
    '__TICKET_MIX_DATA__'   => $ticketMixJson !== false ? $ticketMixJson : '{"labels":[],"values":[]}',
]);

shell_end($script);
?>
