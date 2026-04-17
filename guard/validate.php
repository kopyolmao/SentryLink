<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'guard'){
    exit("Unauthorized");
}

$qr = $_POST['qr'];

// Check ticket
$stmt = $conn->prepare("SELECT * FROM tickets WHERE qr_code=?");
$stmt->bind_param("s",$qr);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){

    $ticket = $result->fetch_assoc();

    if(isset($ticket['status']) && $ticket['status'] == 'used'){
        echo "⚠️ USED TICKET";
    } else {

        // Mark as used
        $conn->query("UPDATE tickets SET status='used' WHERE qr_code='$qr'");

        echo "✅ VALID TICKET";
    }

} else {
    echo "❌ INVALID QR";
}
?>