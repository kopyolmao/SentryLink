<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'student'){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get user tickets
$tickets = $conn->query("
    SELECT t.*, e.title as event_title, e.event_date, e.start_time, e.end_time, e.venue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.user_id = $user_id AND t.deleted_at IS NULL
    ORDER BY e.event_date DESC
");

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$name = $user['first_name'] . ' ' . $user['last_name'];
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SyntreLink — My Tickets</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root[data-theme="dark"] {
    --bg: #07060f; --bg2: #0d0b1a; --bg3: #110f21;
    --border: rgba(124, 92, 252, 0.14); --purple: #7c5cfc; --purple-light: #a48bff;
    --text: #e8e6f0; --text2: #9585c8; --text3: #5c5080;
    --card-shadow: 0 4px 20px rgba(0,0,0,0.4); --radius: 12px;
}
body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
.topbar { background: var(--bg2); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
.brand { font-size: 20px; font-weight: bold; color: var(--purple); }
.btn-back { color: var(--text2); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
.btn-activate { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; text-decoration: none; }
.container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
.page-title { font-size: 24px; margin-bottom: 2rem; }
.ticket-card { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1rem; }
.ticket-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
.ticket-title { font-size: 18px; font-weight: 600; }
.badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; }
.badge-active { background: rgba(61,224,160,0.2); color: #3de0a0; }
.badge-pending { background: rgba(252,185,92,0.2); color: #fcb95c; }
.badge-used { background: rgba(124,92,252,0.2); color: var(--purple-light); }
.ticket-details { color: var(--text2); font-size: 14px; }
.ticket-details div { margin-bottom: 5px; }
.empty-state { text-align: center; padding: 3rem; color: var(--text3); }
</style>
</head>
<body>

<div class="topbar">
    <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    <div class="brand">SyntreLink</div>
</div>

<div class="container">
    <h1 class="page-title">My Tickets</h1>
    
    <?php if($tickets->num_rows > 0): ?>
        <?php while($ticket = $tickets->fetch_assoc()): ?>
        <div class="ticket-card">
            <div class="ticket-header">
                <div class="ticket-title"><?php echo htmlspecialchars($ticket['event_title']); ?></div>
                <?php 
                $badge_class = ['paid' => 'badge-active', 'free' => 'badge-active', 'pending' => 'badge-pending', 'cancelled' => 'badge-used'];
                $badge_text = ['paid' => 'Paid', 'free' => 'Free', 'pending' => 'Pending', 'cancelled' => 'Cancelled'];
                ?>
                <span class="badge <?php echo $badge_class[$ticket['payment_status']]; ?>"><?php echo $badge_text[$ticket['payment_status']]; ?></span>
            </div>
            <div class="ticket-details">
                <div>📅 <?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></div>
                <div>⏰ <?php echo substr($ticket['start_time'], 0, 5); ?> - <?php echo substr($ticket['end_time'], 0, 5); ?></div>
                <div>📍 <?php echo htmlspecialchars($ticket['venue']); ?></div>
                <div>🎫 Ticket Code: <?php echo htmlspecialchars($ticket['ticket_code']); ?></div>
            </div>
            <?php if($ticket['payment_status'] == 'paid' || $ticket['payment_status'] == 'free'): ?>
            <div style="margin-top: 1rem;">
                <a href="my_qr.php" class="btn-activate">View QR Code</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state">
        <h4>No tickets found</h4>
        <p>Your tickets will appear here once the admin imports receipts.</p>
    </div>
    <?php endif; ?>
</div>

</body>
</html>