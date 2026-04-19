<?php shell_start('SentryLink | My QR', $user, 'student', 'qr', 'My Live QR', 'The QR refreshes every 10 seconds for gate validation.'); ?>
<style>
.qr-download-block {
    display: grid;
    gap: 0.55rem;
    justify-items: center;
    margin-top: 1rem;
}

.qr-download-btn {
    width: auto;
    min-width: 220px;
    max-width: 100%;
    justify-content: center;
}

.qr-download-copy {
    max-width: 34rem;
    font-size: 0.95rem;
}

.qr-hold-row {
    display: grid;
    gap: 0.55rem;
    justify-items: center;
    margin-top: 0.75rem;
}

.live-qr-shell {
    width: min(340px, calc(100vw - 48px));
}

.live-qr-frame {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #ffffff;
    padding: 18px;
    border-radius: 24px;
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.24);
}

.live-qr-canvas,
.live-qr-canvas canvas,
.live-qr-canvas img {
    width: 100% !important;
    height: 100% !important;
    display: block;
    image-rendering: pixelated;
}

.live-qr-help {
    max-width: 28rem;
    margin: 0.9rem auto 0;
    color: var(--muted);
    font-size: 0.94rem;
    line-height: 1.6;
}

@media (max-width: 640px) {
    .qr-download-btn {
        width: 100%;
        min-width: 0;
    }
}
</style>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="panel text-center">
            <?php if (! $ticket): ?>
                <h3 class="h5 mb-3">No active event QR is available</h3>
                <p class="text-secondary mb-0">You need a paid or free ticket for an ongoing event before the QR appears here.</p>
            <?php else: ?>
                <div class="mb-3">
                    <div class="text-secondary">Current Event</div>
                    <h3 class="h4 mb-1"><?= h($ticket['title']) ?></h3>
                    <div class="text-secondary"><?= h(date('M d, Y', strtotime($ticket['event_date']))) ?> | <?= h(substr($ticket['start_time'], 0, 5)) ?> - <?= h(substr($ticket['end_time'], 0, 5)) ?></div>
                </div>
                <div class="mx-auto mb-3 position-relative live-qr-shell">
                    <div class="live-qr-frame">
                        <div id="qrCode" class="live-qr-canvas"></div>
                    </div>
                    <div class="position-absolute top-0 end-0 mt-2 me-2 badge text-bg-primary" id="countdown">10s</div>
                </div>
                <p class="text-secondary mb-2" id="refreshText">Generating QR...</p>
                <div id="offlineBanner" class="alert alert-warning d-none mb-0">Offline mode detected. The displayed QR may expire until connection returns.</div>
                <div class="live-qr-help">For faster gate scanning, turn your screen brightness up and hold the QR steady until the officer phone confirms it.</div>
                <div class="qr-hold-row">
                    <button id="holdQrBtn" type="button" class="btn btn-outline-light">Hold Current QR (6s)</button>
                    <small id="holdQrText" class="text-secondary">Use hold only when an officer is actively scanning your screen.</small>
                </div>
                <div class="qr-download-block">
                    <button id="downloadQrBtn" type="button" class="btn btn-outline-light qr-download-btn" disabled>Download One-Time QR</button>
                    <div id="downloadQrText" class="text-secondary qr-download-copy">The downloaded QR stays valid until the first successful scan, then it is removed from the system.</div>
                </div>
                <div id="downloadQrBuffer" class="d-none"></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$script = '<script>
const studentTicketStateUrl = ' . json_encode(app_url('api/student/ticket-state')) . ';
let studentTicketStateHash = ' . json_encode($ticketStateHash ?? '') . ';
let studentTicketStateReloading = false;

async function syncStudentTicketState() {
    if (studentTicketStateReloading) {
        return;
    }

    try {
        const response = await fetch(studentTicketStateUrl, {
            credentials: "same-origin",
            cache: "no-store",
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (typeof data.state_hash !== "string" || data.state_hash === "") {
            return;
        }

        if (data.state_hash !== studentTicketStateHash) {
            studentTicketStateReloading = true;
            if (window.SentryLinkShell && typeof window.SentryLinkShell.refreshCurrentPage === "function") {
                window.SentryLinkShell.refreshCurrentPage();
            } else {
                window.location.reload();
            }
        }
    } catch (error) {
    }
}

setInterval(syncStudentTicketState, 5000);
document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
        syncStudentTicketState();
    }
});
window.addEventListener("focus", syncStudentTicketState);
</script>';

if ($ticket) {
    $script .= '
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const countdownEl = document.getElementById("countdown");
const refreshText = document.getElementById("refreshText");
const offlineBanner = document.getElementById("offlineBanner");
const qrCodeEl = document.getElementById("qrCode");
const downloadQrBtn = document.getElementById("downloadQrBtn");
const downloadQrText = document.getElementById("downloadQrText");
const downloadQrBuffer = document.getElementById("downloadQrBuffer");
const holdQrBtn = document.getElementById("holdQrBtn");
const holdQrText = document.getElementById("holdQrText");
let seconds = 10;
let qrCode = null;
let downloadToken = "";
let downloadFileName = "sentrylink-entry-qr.png";
let currentToken = "";
let currentTokenVersion = 0;
let heartbeatRttMs = 0;

function renderQrCode(token) {
    if (!window.QRCode) {
        throw new Error("QR library failed to load.");
    }

    if (!qrCode) {
        qrCode = new QRCode(qrCodeEl, {
            width: 320,
            height: 320,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M,
        });
    } else {
        qrCode.clear();
    }

    qrCode.makeCode(token);
}

function syncDownloadQrState(isAvailable) {
    downloadQrBtn.disabled = !isAvailable;
    downloadQrBtn.setAttribute("aria-disabled", String(!isAvailable));
}

function renderDownloadQrDataUrl(token) {
    if (!window.QRCode) {
        throw new Error("QR library failed to load.");
    }

    downloadQrBuffer.innerHTML = "";

    const exportQr = new QRCode(downloadQrBuffer, {
        width: 960,
        height: 960,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
    });
    exportQr.makeCode(token);

    return new Promise((resolve, reject) => {
        requestAnimationFrame(() => {
            const canvas = downloadQrBuffer.querySelector("canvas");
            const image = downloadQrBuffer.querySelector("img");

            if (canvas) {
                resolve(canvas.toDataURL("image/png"));
                return;
            }

            if (image && image.src) {
                resolve(image.src);
                return;
            }

            reject(new Error("Unable to render the downloadable QR."));
        });
    });
}

async function downloadQrImage() {
    if (!downloadToken) {
        return;
    }

    syncDownloadQrState(false);
    downloadQrText.textContent = "Preparing your downloadable QR...";

    try {
        const dataUrl = await renderDownloadQrDataUrl(downloadToken);
        const link = document.createElement("a");
        link.href = dataUrl;
        link.download = downloadFileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        downloadQrText.textContent = "Downloaded. This QR stays valid until the first successful scan.";
    } catch (error) {
        downloadQrText.textContent = error.message;
    } finally {
        syncDownloadQrState(downloadToken !== "");
    }
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

        currentToken = data.token || "";
        currentTokenVersion = Number(data.token_version || 0);
        renderQrCode(data.token);
        downloadToken = data.download_token || "";
        downloadFileName = data.download_file_name || "sentrylink-entry-qr.png";
        if (data.download_available) {
            downloadQrText.textContent = "The downloaded QR stays valid until the first successful scan, then it is removed from the system.";
        } else {
            downloadQrText.textContent = "A downloadable QR is no longer available for this ticket.";
        }
        syncDownloadQrState(Boolean(data.download_available && downloadToken));
        refreshText.textContent = "Last refreshed at " + new Date().toLocaleTimeString();
        offlineBanner.classList.add("d-none");
        seconds = 10;
        countdownEl.textContent = "10s";
    } catch (error) {
        refreshText.textContent = error.message;
        offlineBanner.classList.remove("d-none");
    }
}

async function holdQrToken() {
    if (!currentToken) {
        holdQrText.textContent = "Generate a live QR first.";
        return;
    }

    holdQrBtn.disabled = true;
    holdQrText.textContent = "Pinning current QR...";

    try {
        const response = await fetch("' . h(app_url('api/qr/hold')) . '", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token: currentToken, token_version: currentTokenVersion }),
        });
        const data = await response.json();

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || "Unable to hold QR.");
        }

        holdQrText.textContent = "Held until " + new Date(data.hold_expires_at).toLocaleTimeString() + ".";
    } catch (error) {
        holdQrText.textContent = error.message;
    } finally {
        setTimeout(() => {
            holdQrBtn.disabled = false;
        }, 1000);
    }
}

async function sendHeartbeat() {
    if (!currentToken) {
        return;
    }

    const started = performance.now();

    try {
        const response = await fetch("' . h(app_url('api/qr/heartbeat')) . '", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                token_version: currentTokenVersion,
                rtt_ms: heartbeatRttMs,
            }),
        });
        const data = await response.json();
        const elapsed = Math.round(performance.now() - started);
        heartbeatRttMs = elapsed;

        if (!response.ok || data.ok === false) {
            return;
        }

        if (typeof data.token_version === "number" && data.token_version > 0) {
            currentTokenVersion = data.token_version;
        }

        if (data.latency_state === "offline") {
            offlineBanner.classList.remove("d-none");
        } else if (elapsed <= 2500) {
            offlineBanner.classList.add("d-none");
        }
    } catch (error) {
    }
}

refreshQr();
setInterval(refreshQr, 9000);
setInterval(tickCountdown, 1000);
setInterval(sendHeartbeat, 4000);
downloadQrBtn.addEventListener("click", downloadQrImage);
if (holdQrBtn) {
    holdQrBtn.addEventListener("click", holdQrToken);
}
window.addEventListener("offline", () => offlineBanner.classList.remove("d-none"));
window.addEventListener("online", () => {
    refreshQr();
    sendHeartbeat();
    syncStudentTicketState();
});
</script>';
}
shell_end($script);
?>
