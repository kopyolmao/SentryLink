<?php
require_once __DIR__ . '/../includes/ui.php';

$user = require_role(['student']);
$userId = (int) $user['id'];

$ticket = db_fetch_one(
    $conn,
    "SELECT t.id, t.payment_status, e.id AS event_id, e.title, e.event_date, e.start_time, e.end_time, e.status
     FROM tickets t
     INNER JOIN events e ON e.id = t.event_id
     WHERE t.user_id = ?
       AND t.payment_status IN ('paid', 'free')
       AND t.deleted_at IS NULL
       AND e.status = 'ongoing'
     ORDER BY e.event_date DESC, e.start_time DESC
     LIMIT 1",
    'i',
    [$userId]
);

shell_start('SentryLink | My QR', $user, 'student', 'qr', 'My Live QR', 'The QR refreshes every 10 seconds for gate validation.');
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="panel text-center">
            <?php if (!$ticket): ?>
                <h3 class="h5 mb-3">No active event QR is available</h3>
                <p class="text-secondary mb-0">You need a paid or free ticket for an ongoing event before the QR appears here.</p>
            <?php else: ?>
                <div class="mb-3">
                    <div class="text-secondary">Current Event</div>
                    <h3 class="h4 mb-1"><?= h($ticket['title']) ?></h3>
                    <div class="text-secondary"><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?> | <?= h(substr($ticket['start_time'], 0, 5)) ?> - <?= h(substr($ticket['end_time'], 0, 5)) ?></div>
                </div>

                <div class="mx-auto mb-3 position-relative" style="width: 300px;">
                    <div id="qrCode" class="d-flex align-items-center justify-content-center rounded-4 bg-white p-3" style="width: 300px; height: 300px;"></div>
                    <div class="position-absolute top-0 end-0 mt-2 me-2 badge text-bg-primary" id="countdown">10s</div>
                </div>
                <p class="text-secondary mb-2" id="refreshText">Generating QR...</p>
                <div id="offlineBanner" class="alert alert-warning d-none mb-0">Offline mode detected. The displayed QR may expire until connection returns.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$script = '';
if ($ticket) {
    $script = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const countdownEl = document.getElementById("countdown");
const refreshText = document.getElementById("refreshText");
const offlineBanner = document.getElementById("offlineBanner");
const qrCodeEl = document.getElementById("qrCode");
let seconds = 10;
let qrCode = null;

function renderQrCode(token) {
    if (!window.QRCode) {
        throw new Error("QR library failed to load.");
    }

    if (!qrCode) {
        qrCode = new QRCode(qrCodeEl, {
            width: 264,
            height: 264,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H,
        });
    } else {
        qrCode.clear();
    }

    qrCode.makeCode(token);
}

function tickCountdown() {
    seconds = seconds <= 1 ? 10 : seconds - 1;
    countdownEl.textContent = seconds + "s";
}

async function refreshQr() {
    try {
        const response = await fetch("' . h(app_url('api/qr/generate')) . '", { credentials: "same-origin" });
        const data = await response.json();

        if (!response.ok || data.error) {
            throw new Error(data.error || "QR refresh failed.");
        }

        renderQrCode(data.token);
        refreshText.textContent = "Last refreshed at " + new Date().toLocaleTimeString();
        offlineBanner.classList.add("d-none");
        seconds = 10;
        countdownEl.textContent = "10s";
    } catch (error) {
        refreshText.textContent = error.message;
        offlineBanner.classList.remove("d-none");
    }
}

refreshQr();
setInterval(refreshQr, 9000);
setInterval(tickCountdown, 1000);
window.addEventListener("offline", () => offlineBanner.classList.remove("d-none"));
window.addEventListener("online", refreshQr);
</script>';
}

shell_end($script);
?>
