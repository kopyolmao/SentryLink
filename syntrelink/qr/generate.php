<?php
require_once __DIR__ . '/qrcode.php';

$code = $_GET['code'] ?? '';

if ($code === '') {
    http_response_code(400);
    exit('Missing QR payload.');
}

header('Location: ' . generateQRCode($code));
exit;
