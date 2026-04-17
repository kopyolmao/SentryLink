<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: ../loginadmin.php");
    exit;
}

$message = "";
$error = "";

// Get all events for dropdown
$events = $conn->query("SELECT id, title, event_date FROM events WHERE status IN ('draft', 'open') AND deleted_at IS NULL ORDER BY event_date DESC");

// Handle CSV import
if(isset($_POST['import']) && isset($_FILES['csv_file'])){
    $event_id = $_POST['event_id'];
    
    if(!$event_id){
        $error = "Please select an event";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        $valid_rows = [];
        $invalid_rows = [];
        $row_num = 0;
        
        // Skip header row
        fgetcsv($handle);
        
        while(($row = fgetcsv($handle, 1000, ',')) !== FALSE){
            $row_num++;
            $receipt_id = trim($row[0] ?? '');
            $student_no = trim($row[1] ?? '');
            
            if(empty($receipt_id) || empty($student_no)){
                $invalid_rows[] = "Row $row_num: Missing receipt_id or student_no";
                continue;
            }
            
            // Check if student exists
            $student_check = $conn->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
            $student_check->bind_param("s", $student_no);
            $student_check->execute();
            $student = $student_check->get_result()->fetch_assoc();
            
            if(!$student){
                $invalid_rows[] = "Row $row_num: Student ID '$student_no' not found";
                continue;
            }
            
            // Check if receipt already exists
            $receipt_check = $conn->prepare("SELECT id FROM tickets WHERE receipt_id = ?");
            $receipt_check->bind_param("s", $receipt_id);
            $receipt_check->execute();
            
            if($receipt_check->get_result()->num_rows > 0){
                $invalid_rows[] = "Row $row_num: Receipt '$receipt_id' already used";
                continue;
            }
            
            // Check if student already has ticket for this event
            $ticket_check = $conn->prepare("SELECT id FROM tickets WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL");
            $ticket_check->bind_param("ii", $student['id'], $event_id);
            $ticket_check->execute();
            
            if($ticket_check->get_result()->num_rows > 0){
                $invalid_rows[] = "Row $row_num: Student '$student_no' already has ticket for this event";
                continue;
            }
            
            $valid_rows[] = [
                'receipt_id' => $receipt_id,
                'user_id' => $student['id'],
                'student_no' => $student_no
            ];
        }
        
        fclose($handle);
        
        // Store in session for confirmation
        $_SESSION['import_data'] = [
            'event_id' => $event_id,
            'valid_rows' => $valid_rows,
            'invalid_rows' => $invalid_rows
        ];
        
        if(count($valid_rows) > 0){
            header("Location: import_confirm.php");
            exit;
        } else {
            $error = "No valid rows found. All rows have issues.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Receipts - SyntreLink Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; font-family: 'Segoe UI', sans-serif; }
    .sidebar { background: #121212; min-height: 100vh; padding: 1.5rem; border-right: 1px solid #262626; position: fixed; width: 260px; }
    .content { margin-left: 260px; padding: 2rem; }
    .sidebar a { color: #9585c8; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; }
    .sidebar a:hover, .sidebar a.active { background: rgba(124,92,252,0.15); color: #fff; }
    .logo { font-size: 22px; font-weight: bold; color: #7c5cfc; margin-bottom: 2rem; }
    .import-card { background: #121212; border: 1px solid #262626; border-radius: 16px; padding: 2rem; max-width: 700px; }
    .form-label { color: #9585c8; font-size: 14px; margin-bottom: 8px; }
    .form-control, .form-select { background: #121212; border: 1px solid #262626; color: #fff; padding: 12px; border-radius: 8px; }
    .form-control:focus, .form-select:focus { background: #121212; color: #fff; border-color: #7c5cfc; }
    .btn-primary { background: #7c5cfc; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; }
    .btn-primary:hover { background: #6a4de8; }
    .alert-error { background: #2a0d0d; border: 1px solid #ff4d4d; color: #ff8080; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    .alert-info { background: #0d1a2a; border: 1px solid #5cb4ff; color: #5cb4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    .csv-format { background: #1a1525; padding: 1rem; border-radius: 8px; margin-top: 1rem; font-family: monospace; font-size: 13px; color: #9585c8; }
    .info-box { background: #121212; border: 1px solid #262626; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .info-title { color: #7c5cfc; font-weight: 500; margin-bottom: 0.5rem; }
    .info-text { color: #9585c8; font-size: 14px; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar">
        <div class="logo">SyntreLink</div>
        <a href="dashboard.php">Dashboard</a>
        <a href="events.php">Events</a>
        <a href="students.php">Students</a>
        <a href="tickets.php">Tickets</a>
        <a href="import_receipts.php" class="active">Import Receipts (CSV)</a>
        <a href="admissions.php">Admissions Log</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php" style="margin-top: 2rem;">Logout</a>
    </div>
    
    <div class="content">
        <h2 style="margin-bottom: 2rem;">Import Receipts (CSV)</h2>
        
        <?php if($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="import-card">
            <div class="info-box">
                <div class="info-title">How to Import</div>
                <div class="info-text">
                    1. Select the event you want to import receipts for<br>
                    2. Upload a CSV file with receipt IDs<br>
                    3. Review the validation report<br>
                    4. Confirm to create tickets
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select Event</label>
                    <select name="event_id" class="form-select" required>
                        <option value="">-- Select Event --</option>
                        <?php while($event = $events->fetch_assoc()): ?>
                        <option value="<?php echo $event['id']; ?>">
                            <?php echo htmlspecialchars($event['title']); ?> (<?php echo $event['event_date']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Upload CSV File</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                
                <button type="submit" name="import" class="btn btn-primary">Validate & Preview</button>
            </form>
            
            <div class="csv-format">
                <strong>CSV Format:</strong><br>
                Column 1: receipt_id | Column 2: student_no<br>
                <br>
                <em>Example:</em><br>
                RCP-001,STU001<br>
                RCP-002,STU002<br>
                RCP-003,STU003
            </div>
        </div>
    </div>
</div>

</body>
</html>