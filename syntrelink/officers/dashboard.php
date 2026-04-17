<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['ssg']);
$userId = (int) $user['id'];

$stats = [
    'today' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM admissions WHERE scanned_by = ? AND DATE(scanned_at) = CURDATE()', 'i', [$userId]),
    'total' => (int) db_scalar($conn, 'SELECT COUNT(*) FROM admissions WHERE scanned_by = ?', 'i', [$userId]),
    'ongoing' => (int) db_scalar($conn, "SELECT COUNT(*) FROM events WHERE status = 'ongoing' AND deleted_at IS NULL"),
];

$recentLogs = db_fetch_all(
    $conn,
    "SELECT a.scanned_at, a.status, a.gate_location, u.student_id, u.first_name, u.last_name, e.title
     FROM admissions a
     INNER JOIN users u ON u.id = a.user_id
     INNER JOIN events e ON e.id = a.event_id
     WHERE a.scanned_by = ?
     ORDER BY a.scanned_at DESC
     LIMIT 10",
    'i',
    [$userId]
);

$activeEvents = db_fetch_all(
    $conn,
    "SELECT id, title, event_date FROM events WHERE status = 'ongoing' AND deleted_at IS NULL ORDER BY event_date ASC"
);

shell_start('SentryLink | Officer Dashboard', $user, 'ssg', 'dashboard', 'Officer Dashboard', 'Gate scanning status and recent validations.');
?>
<style>
.officer-dashboard {
    display: flex;
    flex-direction: column;
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
    grid-template-columns: minmax(0, 0.98fr) minmax(0, 1.22fr);
    gap: 1rem;
    align-items: stretch;
}

.officer-panel {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
}

.officer-panel-header {
    display: grid;
    gap: 0.35rem;
}

.officer-panel-header h3 {
    margin: 0;
}

.officer-panel-header p {
    margin: 0;
    color: var(--muted);
}

.gate-hero,
.gate-item,
.log-card,
.officer-empty {
    border-radius: 22px;
    border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.035);
}

.gate-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
    padding: 1.15rem;
}

.gate-kicker {
    display: inline-flex;
    width: fit-content;
    margin-bottom: 0.6rem;
    padding: 0.42rem 0.78rem;
    border-radius: 999px;
    background: rgba(15, 139, 141, 0.18);
    color: #9cf7f1;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.gate-hero h4 {
    margin: 0;
    font-size: 1.18rem;
}

.gate-hero p {
    margin: 0.5rem 0 0;
    color: var(--muted);
    max-width: 40ch;
}

.officer-action-btn {
    min-width: 11rem;
    padding: 0.95rem 1.2rem;
    justify-content: center;
    white-space: nowrap;
}

.gate-list,
.log-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.85rem;
}

.gate-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.9rem;
    align-items: center;
    padding: 1rem 1.1rem;
}

.gate-copy,
.log-copy {
    min-width: 0;
}

.gate-title,
.log-title {
    margin: 0;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.gate-meta,
.log-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.8rem;
    margin-top: 0.45rem;
    color: var(--muted);
    font-size: 0.92rem;
}

.event-scan-btn {
    min-width: 9.5rem;
    justify-content: center;
}

.log-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.85rem;
    align-items: start;
    padding: 1rem 1.1rem;
}

.log-subtitle {
    margin-top: 0.28rem;
    color: var(--muted);
    font-size: 0.92rem;
}

.log-side {
    display: flex;
    flex-direction: column;
    align-items: end;
    gap: 0.65rem;
    text-align: right;
}

.log-time {
    color: var(--muted);
    font-size: 0.88rem;
}

.officer-empty {
    display: grid;
    gap: 0.5rem;
    align-content: center;
    min-height: 190px;
    padding: 1.15rem;
    color: var(--muted);
}

.officer-empty strong {
    color: var(--text);
    font-size: 1rem;
}

@media (max-width: 1200px) {
    .officer-metrics,
    .officer-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .gate-hero,
    .gate-item,
    .log-card {
        grid-template-columns: 1fr;
    }

    .officer-action-btn,
    .event-scan-btn {
        width: 100%;
    }

    .log-side {
        align-items: start;
        text-align: left;
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
            <div class="text-secondary">Active Events</div>
            <div class="value"><?= h((string) $stats['ongoing']) ?></div>
        </div>
    </section>

    <section class="officer-grid">
        <div class="panel officer-panel">
            <div class="officer-panel-header">
                <h3 class="h5">Today's Active Gates</h3>
                <p>Ongoing events available for immediate scanning.</p>
            </div>

