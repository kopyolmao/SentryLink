<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if (current_user_role() !== 'ssg' || !current_user_id()) {
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

$rows = db_fetch_all(
    $conn,
    'SELECT student_id, full_name, course, year_level, payment_status, generated_at FROM event_attendee_cache WHERE event_id = ? ORDER BY full_name ASC',
    'i',
    [$eventId]
);

echo json_encode(['attendees' => $rows]);
?>
