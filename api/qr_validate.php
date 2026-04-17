<?php
session_start();
header('Content-Type: application/json');

include "../config/db.php";

// Check authentication
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'ssg'){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$officer_id = $_SESSION['user'];
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$event_id = $input['event_id'] ?? 0;

if(!$token || !$event_id){
    echo json_encode(['status' => 'error', 'message' => 'Missing token or event']);
    exit;
}

$QR_SECRET_KEY = 'syntrelink_qr_secret_key_2026'; // In production, move to .env

// Step 1: Parse token
$parts = explode('.', $token);
if(count($parts) != 2){
    echo json_encode(['status' => 'error', 'message' => 'Invalid QR format']);
    exit;
}

$payload_b64 = $parts[0];
$sig_b64 = $parts[1];

// Step 2: Verify HMAC signature
$expected_sig = bin2hex(hash_hmac('sha256', $payload_b64, $QR_SECRET_KEY, true));
$actual_sig = bin2hex(base64_decode(strtr($sig_b64, '-_', '+/')));

if(!hash_equals($expected_sig, $actual_sig)){
    echo json_encode(['status' => 'error', 'message' => 'Invalid QR Code']);
    exit;
}

// Step 3: Decode payload
$payload = json_decode(base64_decode(strtr($payload_b64, '-_', '+/')), true);
if(!$payload){
    echo json_encode(['status' => 'error', 'message' => 'Invalid QR payload']);
    exit;
}

// Step 4: Check expiration with 5-second grace window
if($payload['exp'] + 5 < time()){
    echo json_encode(['status' => 'error', 'message' => 'QR Expired - Ask student to refresh']);
    exit;
}

// Step 5: Check blacklist (already used)
$jti = $payload['jti'];
$user_id = $payload['uid'];

$blacklist_check = $conn->prepare("SELECT id FROM qr_blacklist WHERE token_jti = ?");
$blacklist_check->bind_param("s", $jti);
$blacklist_check->execute();

if($blacklist_check->get_result()->num_rows > 0){
    echo json_encode(['status' => 'error', 'message' => 'QR Already Used']);
    exit;
}

// Step 6: Check valid ticket
$ticket_check = $conn->prepare("
    SELECT t.*, u.first_name, u.last_name, u.student_id, u.course, u.year_level, u.profile_photo
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ? AND t.event_id = ? AND t.payment_status IN ('paid', 'free') AND t.deleted_at IS NULL
");
$ticket_check->bind_param("ii", $user_id, $event_id);
$ticket_check->execute();
$ticket = $ticket_check->get_result()->fetch_assoc();

if(!$ticket){
    echo json_encode(['status' => 'error', 'message' => 'No Valid Ticket']);
    exit;
}

// Step 7: Check if already admitted
$admission_check = $conn->prepare("
    SELECT id FROM admissions WHERE user_id = ? AND event_id = ? AND status = 'admitted'
");
$admission_check->bind_param("ii", $user_id, $event_id);
$admission_check->execute();

if($admission_check->get_result()->num_rows > 0){
    echo json_encode(['status' => 'duplicate', 'message' => 'Already Admitted', 'student' => [
        'name' => $ticket['first_name'] . ' ' . $ticket['last_name'],
        'student_id' => $ticket['student_id'],
        'course' => $ticket['course'],
        'year' => $ticket['year_level']
    ]]);
    exit;
}

// Step 8: Record admission (atomic)
$conn->begin_transaction();

try {
    // Insert into blacklist
    $blacklist_insert = $conn->prepare("INSERT INTO qr_blacklist (token_jti, user_id, event_id, used_at) VALUES (?, ?, ?, NOW())");
    $blacklist_insert->bind_param("sii", $jti, $user_id, $event_id);
    $blacklist_insert->execute();
    
    // Insert admission
    $gate_location = 'Gate A'; // Could be configurable
    $admission_insert = $conn->prepare("INSERT INTO admissions (ticket_id, user_id, event_id, scanned_by, scanned_at, gate_location, status) VALUES (?, ?, ?, ?, NOW(), ?, 'admitted')");
    $admission_insert->bind_param("iiiis", $ticket['id'], $user_id, $event_id, $officer_id, $gate_location);
    $admission_insert->execute();
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'admitted', 
        'student' => [
            'name' => $ticket['first_name'] . ' ' . $ticket['last_name'],
            'student_id' => $ticket['student_id'],
            'course' => $ticket['course'],
            'year' => $ticket['year_level'],
            'photo' => $ticket['profile_photo']
        ]
    ]);
    
} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Admission failed: ' . $e->getMessage()]);
}