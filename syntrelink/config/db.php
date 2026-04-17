<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "syntrelink_db";

$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Optional session_start if needed elsewhere
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>