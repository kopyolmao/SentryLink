<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if (!current_user_id() || !in_array(current_user_role(), ['ssg', 'admin', 'director'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$eventId = (int) ($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing event ID']);
    exit;
}

$logs = db_fetch_all(
    $conn,
    "SELECT a.scanned_at, a.status, a.gate_location,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.student_id,
            e.title AS event_title
     FROM admissions a
     INNER JOIN users u ON u.id = a.user_id
     INNER JOIN events e ON e.id = a.event_id
     WHERE a.event_id = ?
     ORDER BY a.scanned_at DESC
     LIMIT 100",
    'i',
    [$eventId]
);

foreach ($logs as &$log) {
    $log['badge'] = admission_status_badge($log['status']);
    $log['status'] = ucfirst($log['status']);
}

echo json_encode(['logs' => $logs]);
?>
