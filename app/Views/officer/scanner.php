<?php shell_start('SentryLink | Scanner', $user, 'ssg', 'scanner', 'QR Scanner', 'Select an ongoing event, then validate student QR codes.'); ?>
<style>
.scanner-shell {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.scanner-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.92fr);
    gap: 1.2rem;
    align-items: stretch;
}

.scanner-preview-card,
.scanner-result-card {
    min-width: 0;
    height: 100%;
}

.scanner-preview-card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.scanner-preview-shell {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    border-radius: 28px;
    background:
        radial-gradient(circle at top, rgba(15, 139, 141, 0.18), transparent 42%),
        linear-gradient(180deg, rgba(4, 7, 18, 0.98), rgba(1, 3, 11, 1));
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03);
}

.scanner-preview-shell video {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.scanner-controls {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.scanner-controls .btn {
    width: 100%;
    justify-content: center;
}

.scanner-controls .btn:disabled {
    opacity: 0.58;
    pointer-events: none;
    filter: saturate(0.55);
}

.scanner-result-card {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    min-height: 100%;
}

.scanner-result-surface {
    position: relative;
    overflow: hidden;
    height: 100%;
    min-height: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding: 1.4rem;
    border-radius: 28px;
    background: linear-gradient(180deg, rgba(49, 19, 85, 0.5), rgba(27, 14, 55, 0.72));
    border: 1px solid rgba(255,255,255,0.08);
    transition: border-color 220ms ease, box-shadow 220ms ease, transform 220ms ease;
}

.scanner-result-surface::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    opacity: 0;
    background:
        radial-gradient(circle at 24% 22%, rgba(74, 222, 128, 0.26), transparent 44%),
        linear-gradient(180deg, rgba(34, 197, 94, 0.18), rgba(34, 197, 94, 0.03));
}

.scanner-result-surface::after {
    content: "";
    position: absolute;
    top: 16px;
    right: 16px;
    width: 12px;
    height: 12px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.52);
    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
}

.scanner-result-surface-success {
    border-color: rgba(74, 222, 128, 0.62);
    box-shadow: 0 0 0 1px rgba(74, 222, 128, 0.25), 0 0 28px rgba(34, 197, 94, 0.25);
}

.scanner-result-surface-success::before {
    animation: scanner-success-blink 1000ms ease-out forwards;
}

.scanner-result-surface-success::after {
    background: #4ade80;
    animation: scanner-success-ping 1000ms ease-out;
}

@keyframes scanner-success-blink {
    0% {
        opacity: 0;
    }
    24% {
        opacity: 0.92;
    }
    55% {
        opacity: 0.35;
    }
    84% {
        opacity: 0.66;
    }
    100% {
        opacity: 0;
    }
}

@keyframes scanner-success-ping {
    0% {
        box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.58);
    }
    100% {
        box-shadow: 0 0 0 16px rgba(74, 222, 128, 0);
    }
}

.scanner-result-head h3 {
    margin: 0;
}

.scanner-result-head p {
    margin: 0.55rem 0 0;
    color: var(--muted);
}

.scan-status {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 0.45rem 0.78rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.scan-status-admitted {
    background: rgba(34, 197, 94, 0.16);
    color: #b7ffd7;
}

.scan-status-duplicate {
    background: rgba(245, 158, 11, 0.16);
    color: #ffe0a3;
}

.scan-status-error {
    background: rgba(239, 68, 68, 0.16);
    color: #ffc3cb;
}

.scanner-result-copy {
    display: grid;
    gap: 0.55rem;
    color: var(--text);
}

.scanner-result-copy > div {
    overflow-wrap: anywhere;
}

.scanner-empty {
    color: var(--muted);
}

@media (max-width: 1080px) {
    .scanner-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .scanner-controls {
        grid-template-columns: 1fr;
    }

    .scanner-preview-shell,
    .scanner-result-surface {
        border-radius: 22px;
    }
}
</style>
<div class="scanner-shell">
<div class="panel">
    <form class="filter-form" method="GET">
        <div class="filter-field filter-span-8">
            <label class="form-label">Event Gate</label>
            <select class="form-select" name="event_id" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                <option value="">Select ongoing event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['id'] ?>" <?= $selectedEvent === (int) $event['id'] ? 'selected' : '' ?>><?= h($event['title']) ?> (<?= h($event['event_date']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field filter-span-4">
            <?php if ($selectedEventInfo): ?>
                <div class="metric py-2"><div class="text-secondary">Admissions for Event</div><div class="value" style="font-size: 26px;"><?= $scanCount ?></div></div>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php if ($selectedEventInfo): ?>
<div class="panel">
    <div class="scanner-layout">
        <div class="scanner-preview-card">
            <div class="scanner-preview-shell">
                <video id="video" playsinline></video>
            </div>
            <div class="scanner-controls">
                <button id="startScannerBtn" class="btn btn-primary" type="button" onclick="startScanner()">Start Camera</button>
                <button id="stopScannerBtn" class="btn btn-outline-light" type="button" onclick="stopScanner()" disabled>Stop Camera</button>
            </div>
        </div>
        <div class="scanner-result-card">
            <div id="resultCard" class="scanner-result-surface">
                <div class="scanner-result-head">
                    <h3 class="h5">Scan Result</h3>
                    <p>Waiting for a QR scan.</p>
                </div>
                <div id="resultBody" class="scanner-empty">Result details will appear here.</div>
            </div>
        </div>
    </div>
    <canvas id="canvas" class="d-none"></canvas>
</div>
<?php else: ?>
<div class="panel"><p class="text-secondary mb-0">Choose an event first to start scanning.</p></div>
<?php endif; ?>
</div>
<?php
$script = '';
if ($selectedEventInfo) {
    $script = '
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const context = canvas.getContext("2d");
const resultBody = document.getElementById("resultBody");
const resultCard = document.getElementById("resultCard");
const startScannerBtn = document.getElementById("startScannerBtn");
const stopScannerBtn = document.getElementById("stopScannerBtn");
let stream = null;
let busy = false;
let successFeedbackTimer = null;
let pingAudioContext = null;

function setScannerControls(isActive, isPending = false) {
    startScannerBtn.disabled = isActive || isPending;
    stopScannerBtn.disabled = !isActive || isPending;
    startScannerBtn.setAttribute("aria-disabled", String(startScannerBtn.disabled));
    stopScannerBtn.setAttribute("aria-disabled", String(stopScannerBtn.disabled));
}

function clearScannerStream() {
    if (!stream) {
        video.pause();
        video.srcObject = null;
        setScannerControls(false);
        return;
    }

    const activeStream = stream;
    stream = null;
    activeStream.getTracks().forEach(track => track.stop());
    video.pause();
    video.srcObject = null;
    setScannerControls(false);
}

function attachScannerLifecycle(activeStream) {
    activeStream.getTracks().forEach(track => {
        track.addEventListener("ended", () => {
            if (stream === activeStream) {
                stream = null;
                video.pause();
                video.srcObject = null;
                setScannerControls(false);
            }
        }, { once: true });
    });
}

function statusClass(status) {
    if (status === "admitted" || status === "in") return "scan-status scan-status-admitted";
    if (status === "duplicate" || status === "out") return "scan-status scan-status-duplicate";
    return "scan-status scan-status-error";
}

function isSuccessStatus(status) {
    return status === "admitted" || status === "in" || status === "out";
}

function playSuccessPing() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        if (!pingAudioContext) pingAudioContext = new AudioCtx();
        if (pingAudioContext.state === "suspended") {
            pingAudioContext.resume().catch(() => {});
        }

        const now = pingAudioContext.currentTime;
        const oscillator = pingAudioContext.createOscillator();
        const gain = pingAudioContext.createGain();

        oscillator.type = "sine";
        oscillator.frequency.setValueAtTime(1046.5, now);
        oscillator.frequency.exponentialRampToValueAtTime(1318.5, now + 0.08);

        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.12, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.22);

        oscillator.connect(gain);
        gain.connect(pingAudioContext.destination);

        oscillator.start(now);
        oscillator.stop(now + 0.24);
    } catch (error) {
        // Ignore audio failures to avoid interrupting scan flow.
    }
}

function triggerSuccessFeedback() {
    if (!resultCard) return;

    resultCard.classList.remove("scanner-result-surface-success");
    void resultCard.offsetWidth;
    resultCard.classList.add("scanner-result-surface-success");

    if (successFeedbackTimer) clearTimeout(successFeedbackTimer);
    successFeedbackTimer = setTimeout(() => {
        resultCard.classList.remove("scanner-result-surface-success");
    }, 1100);

    playSuccessPing();
}

function renderResult(data) {
    const student = data.student || {};
    const lines = [];
    const status = String(data.status || "error").toLowerCase();
    lines.push("<span class=\"" + statusClass(status) + "\">" + status + "</span>");
    if (data.message) lines.push("<div>" + data.message + "</div>");
    if (student.name) lines.push("<div><strong>Name:</strong> " + student.name + "</div>");
    if (student.student_id) lines.push("<div><strong>ID:</strong> " + student.student_id + "</div>");
    if (student.course) lines.push("<div><strong>Course:</strong> " + student.course + "</div>");
    if (student.year) lines.push("<div><strong>Year:</strong> " + student.year + "</div>");
    resultBody.className = "scanner-result-copy";
    resultBody.innerHTML = lines.join("");

    if (isSuccessStatus(status)) {
        triggerSuccessFeedback();
    } else {
        resultCard.classList.remove("scanner-result-surface-success");
    }
}
async function validateQr(token) {
    if (busy) return;
    busy = true;
    try {
        const response = await fetch("' . h(app_url('api/qr/validate')) . '", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token, event_id: ' . $selectedEvent . ' })
        });
        const data = await response.json();
        renderResult(data);
    } catch (error) {
        renderResult({ status: "error", message: error.message });
    } finally {
        setTimeout(() => { busy = false; }, 1500);
    }
}
function tick() {
    if (!stream || busy) {
        if (stream) requestAnimationFrame(tick);
        return;
    }
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code) validateQr(code.data);
    }
    if (stream) requestAnimationFrame(tick);
}
async function startScanner() {
    if (stream) {
        setScannerControls(true);
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        renderResult({ status: "error", message: "Camera access is not supported on this device." });
        setScannerControls(false);
        return;
    }

    setScannerControls(false, true);

    try {
        const activeStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        stream = activeStream;
        attachScannerLifecycle(activeStream);
        video.srcObject = activeStream;
        await video.play();
        setScannerControls(true);
        requestAnimationFrame(tick);
    } catch (error) {
        stream = null;
        video.pause();
        video.srcObject = null;
        setScannerControls(false);
        renderResult({ status: "error", message: "Camera access failed: " + error.message });
    }
}
function stopScanner() {
    clearScannerStream();
}

setScannerControls(false);
</script>';
}
shell_end($script);
?>
