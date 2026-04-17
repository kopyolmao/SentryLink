<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = current_user_id();
$notifications = db_fetch_all(
    $conn,
    'SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
    'i',
    [$userId]
);
$unread = (int) db_scalar($conn, 'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', 'i', [$userId]);

echo json_encode([
    'unread' => $unread,
    'notifications' => $notifications,
]);
?>
