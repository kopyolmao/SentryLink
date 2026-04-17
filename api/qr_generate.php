<?php
session_start();
header('Content-Type: application/json');

include "../config/db.php";

// Check authentication
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'student'){
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if(!$user){
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if user has valid ticket
$ticket = $conn->query("
    SELECT t.*, e.title as event_title, e.id as event_id 
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    WHERE t.user_id = $user_id 
    AND t.payment_status IN ('paid', 'free') 
    AND t.deleted_at IS NULL 
    AND e.status = 'ongoing' 
    ORDER BY e.event_date DESC 
    LIMIT 1
")->fetch_assoc();

if(!$ticket){
    echo json_encode(['error' => 'No active ticket found']);
    exit;
}

// Generate dynamic QR token (stateless HMAC)
$QR_SECRET_KEY = 'syntrelink_qr_secret_key_2026'; // In production, move to .env

$jti = bin2hex(random_bytes(8)); // 16-char hex
$iat = time();
$exp = $iat + 10; // 10 seconds

$payload = [
    'uid' => $user_id,
    'sid' => $user['student_id'],
    'jti' => $jti,
    'iat' => $iat,
    'exp' => $exp
];

$payload_json = json_encode($payload);
$payload_b64 = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');

$signature = hash_hmac('sha256', $payload_b64, $QR_SECRET_KEY);
$sig_b64 = rtrim(strtr(base64_encode(hex2bin($signature)), '+/', '-_'), '=');

$token = $payload_b64 . '.' . $sig_b64;

// Generate QR image using inline data URI
require_once "../qr/qrcode.php";

$qr_image = generateQRCode($token);

echo json_encode([
    'token' => $token,
    'qr_image' => $qr_image,
    'expires_in' => 10,
    'event_title' => $ticket['event_title']
]);

// Store token for validation (optional - can also validate stateless)
$_SESSION['current_qr_jti'] = $jti;
$_SESSION['current_qr_event'] = $ticket['event_id'];