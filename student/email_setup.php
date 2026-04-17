<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['pending_user_id'])){
    header("Location: ../login.php");
    exit;
}

$error = "";
$success = "";

if(isset($_POST['send_code'])){
    $email = trim($_POST['email']);
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $_SESSION['pending_user_id']);
        $check->execute();
        if($check->get_result()->num_rows > 0){
            $error = "Email already in use";
        } else {
            // Generate 6-digit code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store in session temporarily (in production, store in database with expiry)
            $_SESSION['verification_email'] = $email;
            $_SESSION['verification_code'] = $code;
            $_SESSION['code_expiry'] = time() + 600; // 10 minutes
            
            // For demo/development: show the code
            $success = "Verification code sent! (Demo: your code is <strong>" . $code . "</strong>)";
            
            // TODO: Send email via SMTP in production
            // mail($email, "Your Verification Code", "Your code: $code");
        }
    }
}

if(isset($_POST['verify_code'])){
    $entered_code = $_POST['code'];
    
    if(!isset($_SESSION['verification_code']) || !isset($_SESSION['code_expiry'])){
        $error = "Please request a new verification code";
    } elseif(time() > $_SESSION['code_expiry']){
        $error = "Verification code expired. Please request a new one.";
        unset($_SESSION['verification_code']);
    } elseif($entered_code === $_SESSION['verification_code']){
        // Update user email and mark as verified
        $email = $_SESSION['verification_email'];
        $user_id = $_SESSION['pending_user_id'];
        
        $update = $conn->prepare("UPDATE users SET email = ?, email_verified = 1 WHERE id = ?");
        $update->bind_param("si", $email, $user_id);
        
        if($update->execute()){
            // Get user data and log them in
            $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
            $_SESSION['user'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_data'] = $user;
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['email_setup_required']);
            unset($_SESSION['verification_code']);
            unset($_SESSION['verification_email']);
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Failed to update. Please try again.";
        }
    } else {
        $error = "Invalid verification code";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Setup - SyntreLink</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #07060f; color: #e8e6f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
    .setup-card { background: #121212; border: 1px solid #262626; border-radius: 16px; padding: 2.5rem; width: 400px; max-width: 90%; }
    .logo { font-size: 28px; font-weight: bold; text-align: center; margin-bottom: 10px; color: #7c5cfc; }
    .subtitle { text-align: center; color: #9585c8; margin-bottom: 2rem; font-size: 14px; }
    .form-control { background: #121212; border: 1px solid #262626; color: #fff; padding: 12px; border-radius: 8px; }
    .form-control:focus { background: #121212; color: #fff; border-color: #7c5cfc; box-shadow: none; }
    .btn-primary { background: #7c5cfc; border: none; padding: 12px; border-radius: 8px; font-weight: 600; width: 100%; }
    .btn-primary:hover { background: #6a4de8; }
    .alert-error { background: #2a0d0d; border: 1px solid #ff4d4d; color: #ff8080; padding: 12px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 1rem; }
    .alert-success { background: #0d2a1a; border: 1px solid #3de0a0; color: #3de0a0; padding: 12px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 1rem; }
    .step { font-size: 12px; color: #5c5080; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
    .divider { height: 1px; background: #262626; margin: 1.5rem 0; }
    .code-input { font-size: 24px; letter-spacing: 8px; text-align: center; font-family: monospace; }
</style>
</head>
<body>

<div class="setup-card">
    <div class="logo">SyntreLink</div>
    <div class="subtitle">Email Setup - First Login</div>
    
    <?php if(isset($_SESSION['verification_code'])): ?>
        <!-- Step 2: Verify Code -->
        <div class="step">Step 2: Enter Verification Code</div>
        <?php if($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label" style="color: #9585c8; font-size: 13px;">Verification Code</label>
                <input type="text" name="code" class="form-control code-input" maxlength="6" placeholder="000000" required>
            </div>
            <button type="submit" name="verify_code" class="btn btn-primary">Verify & Continue</button>
        </form>
        <div class="divider"></div>
        <div style="text-align: center;">
            <a href="?" style="color: #7c5cfc; font-size: 13px;">Request new code</a>
        </div>
    <?php else: ?>
        <!-- Step 1: Enter Email -->
        <div class="step">Step 1: Enter Your Email</div>
        <?php if($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label" style="color: #9585c8; font-size: 13px;">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required>
            </div>
            <button type="submit" name="send_code" class="btn btn-primary">Send Verification Code</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>