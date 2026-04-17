<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Security Check
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

// 2. Handle CRUD Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_receipt'])) {
        $r_no = mysqli_real_escape_string($conn, $_POST['receipt']);
        $e_id = mysqli_real_escape_string($conn, $_POST['event']);
        $conn->query("INSERT INTO receipts (receipt_no, event_id, used) VALUES ('$r_no', '$e_id', 'No')");
        header("Location: dashboard.php?page=dashboard");
    }
    if (isset($_POST['add_event'])) {
        $name = mysqli_real_escape_string($conn, $_POST['event_name']);
        $date = $_POST['event_date'];
        $price = $_POST['price'];
        $conn->query("INSERT INTO events (event_name, event_date, ticket_price) VALUES ('$name', '$date', '$price')");
        header("Location: dashboard.php?page=events");
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SentryLink | Admin Terminal</title>
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
            --sidebar-w: 240px;
        }

        :root[data-theme="light"] {
            --bg: #f8f9fd;
            --bg2: #ffffff;
            --bg3: #ffffff;
            --border: rgba(124, 92, 252, 0.1);
            --border2: rgba(124, 92, 252, 0.2);
            --purple: #7c5cfc;
            --purple-light: #6a4de8;
            --text: #1a1535;
            --text2: #4a4468;
            --text3: #8e89ab;
            --card-shadow: 0 4px 20px rgba(124, 92, 252, 0.08);
            --sidebar-w: 240px;
        }

        body { 
            font-family: 'DM Sans', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            min-height: 100vh; 
            transition: background 0.3s, color 0.3s;
        }

        .sidebar { 
            width: var(--sidebar-w); 
            background: var(--bg2); 
            border-right: 0.5px solid var(--border); 
            height: 100vh; 
            position: fixed; 
            padding: 1.5rem 0; 
        }

        .main-content { margin-left: var(--sidebar-w); padding: 2.25rem; }

        .brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 18px; font-weight: 600;
            color: var(--purple-light); letter-spacing: -0.3px;
            padding: 0 1.25rem 2rem;
        }
        .brand span { color: var(--purple); }

        .nav-section {
            font-size: 10px; font-weight: 500; color: var(--text3);
            letter-spacing: 1px; text-transform: uppercase;
            padding: 1.25rem 1.25rem 0.4rem;
        }

        .nav-link { 
            display: flex; align-items: center; gap: 10px;
            padding: 9px 1.25rem; font-size: 13.5px; color: var(--text2);
            text-decoration: none; border-left: 2px solid transparent;
            transition: all 0.15s;
        }
        .nav-link:hover { background: rgba(124,92,252,0.05); color: var(--text); }
        .nav-link.active { 
            background: rgba(124,92,252,0.1); 
            color: var(--text); 
            border-left-color: var(--purple); 
        }

        .card { 
            background: var(--bg3); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            box-shadow: var(--card-shadow); 
        }

        .theme-toggle-btn {
            background: rgba(124, 92, 252, 0.1);
            border: 1px solid var(--border);
            color: var(--purple);
            border-radius: 8px; padding: 8px; width: 100%;
            font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.2s;
        }
        .theme-toggle-btn:hover { background: rgba(124, 92, 252, 0.2); }

        .table { color: inherit; border-color: var(--border); }
        .table thead { background: rgba(124, 92, 252, 0.05); }
        .table-light { --bs-table-bg: rgba(124, 92, 252, 0.05); --bs-table-color: var(--text); }

        .badge-admin { background: rgba(13, 202, 240, 0.15); color: #0dcaf0; }
        .badge-student { background: rgba(124, 92, 252, 0.15); color: var(--purple-light); }
        .badge-staff { background: rgba(255, 193, 7, 0.15); color: #ffc107; }

        .form-control, .form-select {
            background: var(--bg2); border: 1px solid var(--border); color: var(--text);
        }
        .form-control:focus { background: var(--bg2); color: var(--text); border-color: var(--purple); box-shadow: none; }
        
        .stat-val { font-family: 'Space Grotesk', sans-serif; font-size: 28px; }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <div class="brand"><span>Sentry</span>Link</div>

    <nav class="flex-grow-1">
        <div class="nav-section">Main Menu</div>
        <a href="dashboard.php?page=dashboard" class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="dashboard.php?page=events" class="nav-link <?= $page == 'events' ? 'active' : '' ?>">Events</a>
        <a href="dashboard.php?page=users" class="nav-link <?= $page == 'users' ? 'active' : '' ?>">Users</a>
        <a href="dashboard.php?page=settings" class="nav-link <?= $page == 'settings' ? 'active' : '' ?>">Settings</a>
    </nav>

    <div class="mt-auto px-3 pb-3">
        <button class="theme-toggle-btn mb-2" id="themeBtn">Toggle Theme</button>
        <a href="../logout.php" class="btn btn-outline-danger w-100 btn-sm fw-bold" style="border-radius:8px;">Logout</a>
    </div>
</div>

<main class="main-content">

    <?php switch($page): case 'events': ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0">Event Management</h2>
        </div>
        
        <div class="card p-4 mb-4">
            <h6 class="fw-bold mb-3" style="color: var(--purple-light);">Create New Event</h6>
            <form method="POST" class="row g-3">
                <div class="col-md-5"><input type="text" name="event_name" class="form-control" placeholder="Event Name" required></div>
                <div class="col-md-3"><input type="date" name="event_date" class="form-control" required></div>
                <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="Price" required></div>
                <div class="col-md-2"><button name="add_event" class="btn btn-primary w-100 fw-bold" style="background:var(--purple); border:none;">Add Event</button></div>
            </form>
        </div>

        <div class="card overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-3">Event Name</th><th>Date</th><th class="text-end pe-3">Ticket Price</th></tr></thead>
                <tbody>
                    <?php 
                    $ev_res = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
                    while($e = $ev_res->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold ps-3"><?= $e['event_name'] ?></td>
                        <td class="text-muted"><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
                        <td class="text-end pe-3 fw-bold">₱<?= number_format($e['ticket_price'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php break; case 'users': ?>
        <h2 class="fw-bold mb-4">System Users</h2>
        <div class="card overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-3">ID</th><th>Username / Name</th><th class="text-end pe-3">Role</th></tr></thead>
                <tbody>
                    <?php 
                    $user_res = $conn->query("SELECT * FROM users");
                    while($u = $user_res->fetch_assoc()): 
                        $display_name = $u['username'] ?? $u['user_name'] ?? $u['name'] ?? "Unknown User";
                    ?>
                    <tr>
                        <td class="ps-3 text-muted">#<?= $u['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($display_name) ?></td>
                        <td class="text-end pe-3">
                            <span class="badge badge-<?= strtolower($u['role']) ?> px-3 py-2 text-uppercase" style="font-size: 10px; border-radius: 6px;">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php break; case 'settings': ?>
        <h2 class="fw-bold mb-4">Account Settings</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h6 class="fw-bold mb-3" style="color:var(--purple-light);">Security</h6>
                    <label class="small text-muted mb-1">New Admin Password</label>
                    <input type="password" class="form-control mb-3" placeholder="••••••••">
                    <button class="btn btn-primary btn-sm fw-bold px-4" style="background:var(--purple); border:none; width: fit-content;">Update Password</button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h6 class="fw-bold mb-3" style="color:var(--purple-light);">System Status</h6>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" checked><label class="ms-2">Public Registration</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox"><label class="ms-2">Maintenance Mode</label></div>
                </div>
            </div>
        </div>

    <?php break; default: ?>
        <h2 class="fw-bold mb-4">Administrative Overview</h2>
        <div class="row g-4 mb-4">
            <?php 
            $t_ev = $conn->query("SELECT COUNT(*) as t FROM events")->fetch_assoc()['t'];
            $t_tk = $conn->query("SELECT COUNT(*) as t FROM tickets")->fetch_assoc()['t'];
            $u_tk = $conn->query("SELECT COUNT(*) as t FROM tickets WHERE status='used'")->fetch_assoc()['t'];
            ?>
            <div class="col-md-4"><div class="card p-4"><div class="text-muted small fw-bold mb-2">LIVE EVENTS</div><div class="stat-val"><?= $t_ev ?></div></div></div>
            <div class="col-md-4"><div class="card p-4"><div class="text-muted small fw-bold mb-2">TOTAL ISSUED</div><div class="stat-val"><?= $t_tk ?></div></div></div>
            <div class="col-md-4"><div class="card p-4"><div class="text-muted small fw-bold mb-2">VALIDATED</div><div class="stat-val" style="color: var(--purple-light);"><?= $u_tk ?></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card overflow-hidden h-100">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Recent Receipts</h6>
                        <a href="dashboard.php?page=events" class="small text-decoration-none" style="color:var(--purple-light);">View All</a>
                    </div>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Receipt #</th><th>Event</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php 
                            $rec_res = $conn->query("SELECT r.*, e.event_name FROM receipts r JOIN events e ON r.event_id = e.id ORDER BY r.receipt_no DESC LIMIT 5");
                            while($r = $rec_res->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= $r['receipt_no'] ?></td>
                                <td><?= $r['event_name'] ?></td>
                                <td><span class="badge bg-<?= strtolower($r['used'])=='no'?'success':'danger' ?>-subtle text-<?= strtolower($r['used'])=='no'?'success':'danger' ?>" style="font-size: 10px; border-radius: 5px;"><?= $r['used'] == 'No' ? 'Unused' : 'Redeemed' ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <h6 class="fw-bold mb-3" style="color:var(--purple-light);">Issue New Receipt</h6>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="small text-muted mb-1">Receipt Number</label>
                            <input type="text" name="receipt" class="form-control" placeholder="OR-0000" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Select Event</label>
                            <select name="event" class="form-select">
                                <?php 
                                $ev_list = $conn->query("SELECT * FROM events");
                                while($ev = $ev_list->fetch_assoc()): ?>
                                    <option value="<?= $ev['id'] ?>"><?= $ev['event_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button name="add_receipt" class="btn btn-primary w-100 fw-bold" style="background:var(--purple); border:none;">Authorize Receipt</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endswitch; ?>
</main>

<script>
    const themeBtn = document.getElementById('themeBtn');
    const html = document.documentElement;

    // Persist and Sync Theme logic
    const applyTheme = (theme) => {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    };

    themeBtn.addEventListener('click', () => {
