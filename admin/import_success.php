<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: ../loginadmin.php");
    exit;
}

if(!isset($_SESSION['import_success'])){
    header("Location: import_receipts.php");
    exit;
}

$success = $_SESSION['import_success'];
unset($_SESSION['import_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Successful - SyntreLink Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #121212; min-height: 100vh; padding: 1.5rem; border-right: 1px solid #262626; position: fixed; width: 260px; }
    .content { margin-left: 260px; padding: 2rem; }
    .logo { font-size: 22px; font-weight: bold; color: #7c5cfc; margin-bottom: 2rem; }
    .success-card { background: #121212; border: 1px solid #3de0a0; border-radius: 16px; padding: 3rem; text-align: center; max-width: 500px; margin: 0 auto; }
    .success-icon { font-size: 64px; margin-bottom: 1rem; }
    .success-title { font-size: 28px; font-weight: 600; color: #3de0a0; margin-bottom: 1rem; }
    .success-text { color: #9585c8; margin-bottom: 2rem; }
    .btn-primary { background: #7c5cfc; border: none; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; }
    .btn-primary:hover { background: #6a4de8; color: #fff; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar">
        <div class="logo">SyntreLink</div>
    </div>
    
    <div class="content">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <div class="success-title">Import Successful!</div>
            <div class="success-text">
                Successfully created <strong><?php echo $success['inserted']; ?></strong> tickets for<br>
                <strong><?php echo htmlspecialchars($success['event_title']); ?></strong>
            </div>
            <a href="import_receipts.php" class="btn btn-primary">Import More</a>
            <br><br>
            <a href="dashboard.php" style="color: #9585c8;">Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>