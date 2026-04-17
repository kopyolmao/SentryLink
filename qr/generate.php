<?php
include "../config/db.php";
include "phpqrcode/qrlib.php";

$code=$_GET['code'];

QRcode::png($code);
?>