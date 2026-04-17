<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['user']) || $_SESSION['role'] != 'student'){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get user's tickets
$tickets = $conn->query("
    SELECT t.*, e.title as event_title, e.event_date, e.start_time, e.end_time, e.venue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.user_id = $user_id AND t.deleted_at IS NULL
    ORDER BY e.event_date DESC
");

$total_tickets = 0;
$active_tickets = 0;

$ticket_rows = [];
while($row = $tickets->fetch_assoc()){
    $ticket_rows[] = $row;
    $total_tickets++;
    if(in_array($row['payment_status'], ['paid', 'free'])){
        $active_tickets++;
    }
}

// Get upcoming events (that have tickets)
$events = $conn->query("
    SELECT e.* FROM events e
    JOIN tickets t ON e.id = t.event_id
    WHERE t.user_id = $user_id AND e.status IN ('open', 'ongoing') AND e.deleted_at IS NULL
    GROUP BY e.id
    ORDER BY e.event_date ASC
    LIMIT 5
");

$name = $user['first_name'] . ' ' . $user['last_name'];
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SyntreLink — Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Grotesk:wght@500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root[data-theme="dark"] {
    --bg: #07060f;
    --bg2: #0d0b1a;
    --bg3: #110f21;
    --border: rgba(124, 92, 252, 0.14);
    --border2: rgba(124, 92, 252, 0.25);
    --purple: #7c5cfc;
    --purple-light: #a48bff;
    --text: #e8e6f0;
    --text2: #9585c8;
    --text3: #5c5080;
    --card-shadow: 0 4px 20px rgba(0,0,0,0.4);
}

:root {
    --cyan: #5ccffc;
    --green: #3de0a0;
    --amber: #fcb95c;
    --pink: #fc5c96;
    --radius: 12px;
    --radius-sm: 8px;
}

html, body { height: 100%; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

.topbar {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 2rem; height: 60px;
    background: var(--bg2);
    border-bottom: 0.5px solid var(--border);
}

.brand { font-family: 'Space Grotesk', sans-serif; font-size: 18px; font-weight: 600; color: var(--purple-light); }
.brand span { color: var(--purple); }

.topbar-right { display: flex; align-items: center; gap: 12px; }

.btn-activate { background: var(--purple); color: #fff; border: none; border-radius: var(--radius-sm); padding: 8px 18px; font-size: 13px; font-weight: 500; text-decoration: none; }
.btn-logout { background: transparent; color: var(--text3); border: 0.5px solid var(--border); border-radius: var(--radius-sm); padding: 8px 14px; font-size: 13px; text-decoration: none; }

.avatar-circle {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, var(--purple), var(--cyan));
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600; color: #fff;
}

.layout { display: grid; grid-template-columns: 220px 1fr; min-height: calc(100vh - 60px); }

.sidebar { background: var(--bg2); border-right: 0.5px solid var(--border); padding: 1.75rem 0; position: sticky; top: 60px; height: calc(100vh - 60px); }
.sidebar-user { padding: 0 1.25rem 1.5rem; border-bottom: 0.5px solid var(--border); }
.s-avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--cyan)); display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 600; color: #fff; margin-bottom: 12px; }
.s-name { font-size: 14px; font-weight: 500; color: var(--text); }
.s-id { font-size: 12px; color: var(--text3); margin-top: 3px; }
.s-badge { display: inline-block; margin-top: 8px; background: rgba(124,92,252,0.12); color: var(--purple-light); border-radius: 6px; padding: 3px 9px; font-size: 11px; font-weight: 500; }

.nav-section { font-size: 10px; font-weight: 500; color: var(--text3); letter-spacing: 1px; text-transform: uppercase; padding: 1.25rem 1.25rem 0.4rem; }
.nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 1.25rem; font-size: 13.5px; color: var(--text2); text-decoration: none; border-left: 2px solid transparent; }
.nav-item:hover { color: var(--text); }
.nav-item.active { background: rgba(124,92,252,0.1); color: var(--text); border-left-color: var(--purple); }

.content { padding: 2rem 2.25rem; }
.page-header h1 { font-size: 22px; font-weight: 500; color: var(--text); }

.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 2rem; }
.stat-card { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.1rem 1.25rem; box-shadow: var(--card-shadow); }
.stat-label { font-size: 11px; color: var(--text3); text-transform: uppercase; margin-bottom: 8px; }
.stat-val { font-size: 28px; font-weight: 500; color: var(--text); font-family: 'Space Grotesk', sans-serif; }
.stat-icon { float: right; font-size: 22px; margin-top: -4px; }

.section-title { font-size: 14px; font-weight: 500; color: var(--purple-light); display: flex; align-items: center; gap: 8px; margin-bottom: 1.25rem; }
.section-title-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--purple); }

.tickets-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.ticket-card { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; box-shadow: var(--card-shadow); }
.ticket-badge { font-size: 11px; padding: 3px 9px; border-radius: 6px; font-weight: 500; }
.badge-active { background: rgba(61,224,160,0.15); color: var(--green); }
.badge-used { background: rgba(124,92,252,0.12); color: var(--purple-light); }
.badge-pending { background: rgba(252,185,92,0.15); color: var(--amber); }
.ticket-name { font-size: 14px; font-weight: 500; color: var(--text); margin-bottom: 4px; }
.ticket-meta { font-size: 12px; color: var(--text3); line-height: 1.6; }

.empty-state { text-align: center; padding: 3rem; color: var(--text3); }
.empty-state h4 { color: var(--text2); margin-bottom: 0.5rem; }
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">Syntre<span>Link</span></div>
    <div class="topbar-right">
        <a href="my_qr.php" class="btn-activate">View My QR</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
        <div class="avatar-circle"><?php echo $initials; ?></div>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <div class="sidebar-user">
            <div class="s-avatar"><?php echo $initials; ?></div>
            <div class="s-name"><?php echo htmlspecialchars($name); ?></div>
            <div class="s-id"><?php echo $user['student_id']; ?></div>
            <span class="s-badge">Student</span>
        </div>
        
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item active">Dashboard</a>
        <a href="my_ticket.php" class="nav-item">My Tickets</a>
        <a href="my_qr.php" class="nav-item">My QR Code</a>
        <a href="profile.php" class="nav-item">Profile</a>
        <a href="settings.php" class="nav-item">Settings</a>
    </div>
    
    <div class="content">
        <div class="page-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Tickets</div>
                <div class="stat-val"><?php echo $total_tickets; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Tickets</div>
                <div class="stat-val" style="color: var(--green);"><?php echo $active_tickets; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Upcoming Events</div>
                <div class="stat-val" style="color: var(--cyan);"><?php echo $events->num_rows; ?></div>
            </div>
        </div>
        
        <div class="section-title">
            <div class="section-title-dot"></div>
            My Tickets
        </div>
        
        <?php if(count($ticket_rows) > 0): ?>
        <div class="tickets-grid">
            <?php foreach($ticket_rows as $ticket): ?>
            <div class="ticket-card">
                <?php 
                $status_class = [
                    'paid' => 'badge-active', 
                    'free' => 'badge-active', 
                    'pending' => 'badge-pending', 
                    'cancelled' => 'badge-used'
                ];
                $status_label = [
                    'paid' => 'Paid', 
                    'free' => 'Free', 
                    'pending' => 'Pending', 
                    'cancelled' => 'Cancelled'
                ];
                ?>
                <span class="ticket-badge <?php echo $status_class[$ticket['payment_status']]; ?>">
                    <?php echo $status_label[$ticket['payment_status']]; ?>
                </span>
                <div class="ticket-name" style="margin-top: 10px;"><?php echo htmlspecialchars($ticket['event_title']); ?></div>
                <div class="ticket-meta">
                    <div>📅 <?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></div>
                    <div>⏰ <?php echo substr($ticket['start_time'], 0, 5); ?> - <?php echo substr($ticket['end_time'], 0, 5); ?></div>
                    <div>📍 <?php echo htmlspecialchars($ticket['venue']); ?></div>
                </div>
                <?php if($ticket['payment_status'] == 'paid' || $ticket['payment_status'] == 'free'): ?>
                <a href="my_qr.php" class="btn-activate" style="display: block; text-align: center; margin-top: 1rem; font-size: 12px;">View QR Code</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <h4>No tickets yet</h4>
            <p>Your tickets will appear here after admin imports receipts.</p>
        </div>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>