<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'guard'){
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>QR Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #000;
    color: #fff;
    text-align: center;
}

#reader {
    width: 300px;
    margin: auto;
}

.result-box {
    margin-top: 20px;
    padding: 15px;
    border-radius: 10px;
}

.valid { background: #0f5132; }
.invalid { background: #842029; }
.used { background: #664d03; }
</style>
</head>

<body>

<h2 class="mt-4">🎫 QR Ticket Scanner</h2>

<div id="reader"></div>

<div id="result" class="result-box"></div>

<a href="../logout.php" class="btn btn-danger mt-3">Logout</a>

<!-- QR LIBRARY -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
function onScanSuccess(decodedText) {

    fetch("validate.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "qr=" + decodedText
    })
    .then(res => res.text())
    .then(data => {
        let box = document.getElementById("result");

        if(data.includes("VALID")){
            box.className = "result-box valid";
        } else if(data.includes("USED")){
            box.className = "result-box used";
        } else {
            box.className = "result-box invalid";
        }

        box.innerHTML = data;
    });

}

let scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
scanner.render(onScanSuccess);
</script>

</body>
</html>