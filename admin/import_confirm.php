<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: ../loginadmin.php");
    exit;
}

if(!isset($_SESSION['import_data'])){
    header("Location: import_receipts.php");
    exit;
}

$import_data = $_SESSION['import_data'];
$event_id = $import_data['event_id'];
$valid_rows = $import_data['valid_rows'];
$invalid_rows = $import_data['invalid_rows'];

// Get event info
$event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();

// Handle confirmation
if(isset($_POST['confirm'])){
    $inserted = 0;
    $user_ids = [];
    
    foreach($valid_rows as $row){
        $ticket_code = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, event_id, ticket_code, receipt_id, payment_status, issued_at) VALUES (?, ?, ?, ?, 'paid', NOW())");
        $stmt->bind_param("iiss", $row['user_id'], $event_id, $ticket_code, $row['receipt_id']);
        
        if($stmt->execute()){
            $inserted++;
            $user_ids[] = $row['user_id'];
        }
    }
    
    // Log the import
    $admin_id = $_SESSION['user'];
    $conn->query("INSERT INTO audit_logs (user_id, action, target_type, target_id, created_at) VALUES ($admin_id, 'CSV_RECEIPT_IMPORT', 'event', $event_id, NOW())");
    
    // Store success message
    $_SESSION['import_success'] = [
        'inserted' => $inserted,
        'event_title' => $event['title']
    ];
    
    unset($_SESSION['import_data']);
    header("Location: import_success.php");
    exit;
}

if(isset($_POST['cancel'])){
    unset($_SESSION['import_data']);
    header("Location: import_receipts.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Import - SyntreLink Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #121212; min-height: 100vh; padding: 1.5rem; border-right: 1px solid #262626; position: fixed; width: 260px; }
    .content { margin-left: 260px; padding: 2rem; }
    .sidebar a { color: #9585c8; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
    .sidebar a:hover, .sidebar a.active { background: rgba(124,92,252,0.15); color: #fff; }
    .logo { font-size: 22px; font-weight: bold; color: #7c5cfc; margin-bottom: 2rem; }
    .confirm-card { background: #121212; border: 1px solid #262626; border-radius: 16px; padding: 2rem; }
    .summary-box { display: flex; gap: 2rem; margin-bottom: 2rem; }
    .summary-item { flex: 1; background: #1a1525; padding: 1.5rem; border-radius: 12px; text-align: center; }
    .summary-val { font-size: 32px; font-weight: 600; color: #7c5cfc; }
    .summary-val.success { color: #3de0a0; }
    .summary-val.error { color: #ff4d4d; }
    .summary-label { color: #9585c8; font-size: 14px; }
    .btn-primary { background: #3de0a0; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; color: #000; }
    .btn-primary:hover { background: #2fc48f; }
    .btn-secondary { background: transparent; border: 1px solid #262626; color: #9585c8; padding: 12px 24px; border-radius: 8px; }
    .table-dark { background: #121212; border-color: #262626; }
    .badge-valid { background: rgba(61,224,160,0.2); color: #3de0a0; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
    .badge-invalid { background: rgba(255,77,77,0.2); color: #ff4d4d; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar">
        <div class="logo">SyntreLink</div>
        <a href="dashboard.php">Dashboard</a>
        <a href="import_receipts.php" class="active">Import Receipts (CSV)</a>
    </div>
    
    <div class="content">
        <h2 style="margin-bottom: 2rem;">Confirm Import</h2>
        
        <div class="confirm-card">
            <h4 style="margin-bottom: 1rem;">Event: <?php echo htmlspecialchars($event['title']); ?></h4>
            <p style="color: #9585c8; margin-bottom: 2rem;">Date: <?php echo $event['event_date']; ?></p>
            
            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-val success"><?php echo count($valid_rows); ?></div>
                    <div class="summary-label">Valid Rows (Will be imported)</div>
                </div>
                <div class="summary-item">
                    <div class="summary-val error"><?php echo count($invalid_rows); ?></div>
                    <div class="summary-label">Invalid Rows (Skipped)</div>
                </div>
            </div>
            
            <?php if(count($valid_rows) > 0): ?>
            <h5 style="color: #3de0a0; margin-bottom: 1rem;">Preview - Valid Rows</h5>
            <table class="table table-dark table-sm">
                <thead>
                    <tr>
                        <th>Receipt ID</th>
                        <th>Student No</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($valid_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['receipt_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                        <td><span class="badge-valid">Valid</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if(count($invalid_rows) > 0): ?>
            <h5 style="color: #ff4d4d; margin: 1.5rem 0 1rem;">Invalid Rows</h5>
            <div style="background: #1a1525; padding: 1rem; border-radius: 8px; font-size: 13px; color: #9585c8; max-height: 200px; overflow-y: auto;">
                <?php foreach($invalid_rows as $row): ?>
                <div><?php echo htmlspecialchars($row); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <form method="POST">
                    <button type="submit" name="confirm" class="btn btn-primary">Confirm Import (<?php echo count($valid_rows); ?> tickets)</button>
                </form>
                <form method="POST">
                    <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>