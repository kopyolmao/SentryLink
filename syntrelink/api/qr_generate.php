<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if (current_user_role() !== 'student' || !current_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = current_user_id();
$user = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$userId]);

$ticket = db_fetch_one(
    $conn,
    "SELECT t.id, e.id AS event_id, e.title
     FROM tickets t
     INNER JOIN events e ON e.id = t.event_id
     WHERE t.user_id = ?
       AND t.payment_status IN ('paid', 'free')
       AND t.deleted_at IS NULL
       AND e.status = 'ongoing'
     ORDER BY e.event_date DESC, e.start_time DESC
     LIMIT 1",
    'i',
    [$userId]
);

if (!$ticket) {
    http_response_code(404);
    echo json_encode(['error' => 'No active event ticket found.']);
    exit;
}

$secret = 'syntrelink_qr_secret_key_2026';
$payload = [
    'uid' => $userId,
    'sid' => $user['student_id'],
    'eid' => (int) $ticket['event_id'],
    'jti' => bin2hex(random_bytes(8)),
    'iat' => time(),
    'exp' => time() + 10,
];

$payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
$payloadB64 = rtrim(strtr(base64_encode($payloadJson), '+/', '-_'), '=');
$signature = hash_hmac('sha256', $payloadB64, $secret, true);
$signatureB64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
$token = $payloadB64 . '.' . $signatureB64;

require_once __DIR__ . '/../qr/qrcode.php';

echo json_encode([
    'token' => $token,
    'qr_image' => generateQRCode($token),
    'event_title' => $ticket['title'],
    'expires_in' => 10,
]);
?>
