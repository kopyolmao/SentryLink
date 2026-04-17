<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$markAll = (bool) ($input['all'] ?? false);
$notificationId = (int) ($input['id'] ?? 0);
$userId = current_user_id();

if ($markAll) {
    db_execute($conn, 'UPDATE notifications SET is_read = 1 WHERE user_id = ?', 'i', [$userId]);
} elseif ($notificationId > 0) {
    db_execute($conn, 'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', 'ii', [$notificationId, $userId]);
}

echo json_encode(['ok' => true]);
?>
